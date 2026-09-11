<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Http;
use App\Core\Mailer;
use App\Core\RateLimit;
use App\Core\Session;
use App\Core\Str;
use App\Core\Validator;
use App\Models\Category;
use App\Models\Submission;

/**
 * Envío de contenido por parte de visitantes sin cuenta.
 * Nada de lo que llega por aquí se publica solo: siempre queda pendiente.
 */
final class SubmissionController extends Controller
{
    public function form(): void
    {
        $this->view('public/submit', [
            'categories' => Category::active(),
            'compat'     => Config::compatibilityOptions(),
            'errors'     => $this->takeErrors(),
            'maxBytes'   => Config::maxUploadBytes(),
        ], 'Enviar una skill');
    }

    public function store(): void
    {
        Csrf::verify();

        // Trampa para robots: un campo invisible que un humano nunca rellena.
        if (Http::input('website') !== '') {
            Session::flash('ok', 'Recibimos tu envío.');
            Http::redirect('/submit/received');
        }

        if (RateLimit::tooMany('submission', 5, 3600)) {
            $this->backWithErrors(
                ['general' => 'Demasiados envíos desde esta conexión.'],
                'Has enviado varias skills en la última hora. Inténtalo más tarde.'
            );
            return;
        }

        $kind        = Http::input('kind', 'skill') === 'agent' ? 'agent' : 'skill';
        $name        = Http::input('author_name');
        $email       = mb_strtolower(Http::input('email'));
        $skillName   = Http::input('skill_name');
        $description = Http::inputRaw('description');
        $categoryId  = Http::inputInt('category_id');
        $compat      = Http::inputArray('compatibility');
        $comments    = Http::inputRaw('comments');
        $consent     = Http::inputBool('consent');

        $v = new Validator();
        $v->required('skill_name', $skillName, 'El nombre')->max('skill_name', $skillName, 140, 'El nombre');
        $v->required('email', $email, 'El correo')->email('email', $email);
        $v->required('description', $description, 'La descripción')
          ->min('description', $description, 30, 'La descripción')
          ->max('description', $description, 20000, 'La descripción');
        $v->max('author_name', $name, 120, 'Tu nombre');
        $v->max('comments', $comments, 2000, 'Los comentarios');
        $v->condition('consent', $consent, 'Necesitamos tu autorización para tratar el correo y avisarte del resultado.');

        $allowedCompat = Config::compatibilityOptions();
        $compat = array_values(array_intersect($compat, $allowedCompat));
        $v->condition('compatibility', $compat !== [], 'Indica al menos una compatibilidad.');

        if ($categoryId > 0 && Category::find($categoryId) === null) {
            $categoryId = 0;
        }

        // ------------------------------------------------------------ Archivo
        $file = $this->handleUpload($v);

        if ($v->fails()) {
            $this->backWithErrors($v->errors());
            return;
        }

        RateLimit::hit('submission');

        $reference = Str::reference($kind === 'agent' ? 'AG' : 'SK');

        $submissionId = Database::insert('skill_submissions', [
            'reference'     => $reference,
            'kind'          => $kind,
            'name'          => $name !== '' ? $name : null,
            'email'         => $email,
            'skill_name'    => $skillName,
            'description'   => $description,
            'category_id'   => $categoryId > 0 ? $categoryId : null,
            'compatibility' => Str::csvFromList($compat),
            'comments'      => $comments !== '' ? $comments : null,
            'file_path'     => $file['path'],
            'file_name'     => $file['name'],
            'file_size'     => $file['size'],
            'content'       => $file['content'],
            'status'        => 'pending',
            'ip_hash'       => Str::ipHash(),
            'consent'       => 1,
        ]);

        Audit::log('submission_received', 'submission', $submissionId, [
            'reference' => $reference,
            'kind'      => $kind,
            'name'      => $skillName,
        ], $email);

        $this->notifyAdmin($submissionId, $reference, $kind, $skillName, $description, $name, $email, $categoryId, $compat);
        $this->confirmSender($email, $reference, $skillName, $name);

        Session::set('__last_submission', $reference);
        Http::redirect('/submit/received');
    }

    public function received(): void
    {
        $reference = (string) Session::get('__last_submission', '');
        Session::forget('__last_submission');

        $submission = $reference !== '' ? Submission::findByReference($reference) : null;

        $this->view('public/submit-received', [
            'reference'  => $reference,
            'submission' => $submission,
        ], 'Envío recibido');
    }

    // ---------------------------------------------------------------------

    /**
     * Valida y guarda el archivo adjunto fuera de la raíz web.
     * @return array{path:?string,name:?string,size:int,content:?string}
     */
    private function handleUpload(Validator $v): array
    {
        $empty = ['path' => null, 'name' => null, 'size' => 0, 'content' => null];

        if (!isset($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $empty;
        }

        $f = $_FILES['file'];

        if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
            $v->add('file', 'El archivo supera el tamaño máximo permitido por el servidor.');
            return $empty;
        }
        if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            $v->add('file', 'No pudimos leer el archivo. Vuelve a adjuntarlo.');
            return $empty;
        }
        if ((int) $f['size'] > Config::maxUploadBytes()) {
            $v->add('file', 'El archivo pesa más de ' . Str::humanBytes(Config::maxUploadBytes()) . '.');
            return $empty;
        }

        $ext = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, Config::allowedUploadExtensions(), true)) {
            $v->add('file', 'Sólo aceptamos archivos .md, .txt, .json o .zip.');
            return $empty;
        }

        $raw = (string) file_get_contents($f['tmp_name']);

        // Un .json que no es JSON válido se rechaza antes de guardarse.
        if ($ext === 'json' && json_decode($raw, true) === null && trim($raw) !== 'null') {
            $v->add('file', 'El archivo .json no tiene un formato válido.');
            return $empty;
        }
        // Un archivo de texto debe ser texto: nada de binarios con extensión cambiada.
        if (in_array($ext, ['md', 'txt', 'json'], true) && strpos($raw, "\0") !== false) {
            $v->add('file', 'El archivo no parece ser de texto.');
            return $empty;
        }

        $dir = Config::storagePath('uploads/' . date('Y/m'));
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            $v->add('file', 'No pudimos guardar el archivo. Inténtalo de nuevo.');
            return $empty;
        }

        // Nombre aleatorio: el nombre original nunca toca el sistema de archivos.
        $stored = bin2hex(random_bytes(16)) . '.' . $ext;
        if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $stored)) {
            $v->add('file', 'No pudimos guardar el archivo. Inténtalo de nuevo.');
            return $empty;
        }
        @chmod($dir . '/' . $stored, 0640);

        return [
            'path'    => 'uploads/' . date('Y/m') . '/' . $stored,
            'name'    => mb_substr((string) $f['name'], 0, 190),
            'size'    => (int) $f['size'],
            'content' => in_array($ext, ['md', 'txt', 'json'], true) ? mb_substr($raw, 0, 60000) : null,
        ];
    }

    private function notifyAdmin(
        int $id, string $reference, string $kind, string $skillName,
        string $description, string $name, string $email, int $categoryId, array $compat
    ): void {
        $category = $categoryId > 0 ? (Category::find($categoryId)['name'] ?? 'Sin categoría') : 'Sin categoría';

        $html = Mailer::layout(
            ($kind === 'agent' ? 'Nuevo agente' : 'Nueva skill') . ' pendiente de revisión',
            'Alguien envió contenido a la biblioteca. Nada se publica hasta que lo apruebes.',
            [
                'Referencia'    => $reference,
                'Nombre'        => $skillName,
                'Tipo'          => $kind === 'agent' ? 'Agente (reglas .md)' : 'Habilidad (.json)',
                'Descripción'   => Str::excerpt($description, 400),
                'Autor'         => $name !== '' ? $name : 'No indicado',
                'Correo'        => $email,
                'Categoría'     => $category,
                'Compatibilidad'=> implode(', ', $compat),
                'Fecha'         => date('d/m/Y H:i'),
            ],
            ['url' => Config::absUrl('/admin/submissions/' . $id), 'label' => 'Revisar el envío'],
            'Recibes este aviso porque administras la plataforma.'
        );

        Mailer::send(Mailer::adminAddress(), '[Pendiente] ' . $skillName, $html, 'submission_admin');
    }

    private function confirmSender(string $email, string $reference, string $skillName, string $name): void
    {
        $greeting = $name !== '' ? 'Hola ' . e($name) . ',' : 'Hola,';

        $html = Mailer::layout(
            'Hemos recibido tu envío',
            $greeting . ' gracias por compartir tu trabajo. Lo revisaremos antes de publicarlo en la biblioteca '
            . 'y te escribiremos a este mismo correo con el resultado.',
            [
                'Identificador' => $reference,
                'Nombre'        => $skillName,
                'Estado'        => 'Pendiente de revisión',
                'Fecha'         => date('d/m/Y H:i'),
            ],
            ['url' => Config::absUrl('/skills'), 'label' => 'Explorar la biblioteca'],
            'Guarda el identificador: lo necesitas si quieres consultar o dar de baja tu envío. '
            . 'Usamos tu correo únicamente para informarte del estado de este envío.'
        );

        Mailer::send($email, 'Recibimos tu envío · ' . $reference, $html, 'submission_sender');
    }
}
