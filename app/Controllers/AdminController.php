<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Http;
use App\Core\Mailer;
use App\Core\Markdown;
use App\Core\Session;
use App\Core\Str;
use App\Core\Validator;
use App\Models\Agent;
use App\Models\Category;
use App\Models\Skill;
use App\Models\Submission;
use App\Models\User;

final class AdminController extends Controller
{
    public function __construct()
    {
        Auth::requireAdmin();
    }

    // -------------------------------------------------------- Panel general
    public function dashboard(): void
    {
        $stats = [
            'users'      => (int) Database::scalar('SELECT COUNT(*) FROM users'),
            'users_new'  => (int) Database::scalar('SELECT COUNT(*) FROM users WHERE created_at > (NOW() - INTERVAL 30 DAY)'),
            'skills'     => (int) Database::scalar('SELECT COUNT(*) FROM skills'),
            'agents'     => (int) Database::scalar('SELECT COUNT(*) FROM agents'),
            'published'  => (int) Database::scalar("SELECT COUNT(*) FROM skills WHERE status = 'published'")
                          + (int) Database::scalar("SELECT COUNT(*) FROM agents WHERE status = 'published'"),
            'pending'    => Submission::pendingCount(),
            'downloads'  => (int) Database::scalar('SELECT COUNT(*) FROM downloads'),
            'downloads30'=> (int) Database::scalar('SELECT COUNT(*) FROM downloads WHERE created_at > (NOW() - INTERVAL 30 DAY)'),
        ];

        $topSkills = Database::all(
            "SELECT s.name, s.slug, s.downloads, s.status,
                COALESCE(NULLIF(TRIM(CONCAT(COALESCE(u.name,''),' ',COALESCE(u.lastname,''))),''), u.username, s.author_name, '—') AS author
             FROM skills s LEFT JOIN users u ON u.id = s.user_id
             ORDER BY s.downloads DESC LIMIT 6"
        );

        $topAgents = Database::all(
            'SELECT a.name, a.slug, a.downloads, a.status,
                (SELECT COUNT(*) FROM agent_skills x WHERE x.agent_id = a.id) AS skills_count
             FROM agents a ORDER BY a.downloads DESC LIMIT 6'
        );

        $newUsers = Database::all(
            'SELECT id, name, lastname, username, email, role, status, created_at
             FROM users ORDER BY created_at DESC LIMIT 6'
        );

        $activity = Database::all(
            'SELECT l.*, u.username FROM audit_logs l LEFT JOIN users u ON u.id = l.user_id
             ORDER BY l.id DESC LIMIT 12'
        );

        $pendingList = Database::all(
            "SELECT * FROM skill_submissions WHERE status IN ('pending','under_review')
             ORDER BY created_at ASC LIMIT 6"
        );

        $this->view('admin/dashboard', [
            'stats'       => $stats,
            'topSkills'   => $topSkills,
            'topAgents'   => $topAgents,
            'newUsers'    => $newUsers,
            'activity'    => $activity,
            'pendingList' => $pendingList,
        ], 'Administración', 'layouts/panel');
    }

    // -------------------------------------------------------------- Usuarios
    public function users(): void
    {
        $filters = [
            'q'      => Http::input('q'),
            'role'   => Http::input('role'),
            'status' => Http::input('status'),
            'page'   => Http::inputInt('page', 1),
        ];

        $this->view('admin/users', [
            'result'  => User::adminList($filters),
            'filters' => $filters,
        ], 'Usuarios', 'layouts/panel');
    }

    public function userForm(array $args = []): void
    {
        $user = null;
        if (!empty($args['id'])) {
            $user = User::find((int) $args['id']);
            if ($user === null) {
                $this->missing('Ese usuario no existe.');
                return;
            }
        }

        $this->view('admin/user-form', [
            'user'   => $user,
            'errors' => $this->takeErrors(),
        ], $user ? 'Editar usuario' : 'Nuevo usuario', 'layouts/panel');
    }

    public function userStore(array $args = []): void
    {
        Csrf::verify();

        $existing = null;
        if (!empty($args['id'])) {
            $existing = User::find((int) $args['id']);
            if ($existing === null) {
                $this->missing('Ese usuario no existe.');
                return;
            }
        }

        $name     = Http::input('name');
        $lastname = Http::input('lastname');
        $email    = mb_strtolower(Http::input('email'));
        $username = mb_strtolower(Http::input('username'));
        $role     = Http::input('role', 'user');
        $status   = Http::input('status', 'active');
        $password = (string) ($_POST['password'] ?? '');

        $v = new Validator();
        $v->required('name', $name, 'El nombre')->max('name', $name, 80, 'El nombre');
        $v->required('email', $email, 'El correo')->email('email', $email);
        $v->required('username', $username, 'El usuario')
          ->regex('username', $username, '/^[a-z0-9_.-]{3,40}$/', 'El usuario admite 3 a 40 caracteres en minúscula.');
        $v->in('role', $role, ['admin', 'user'], 'El rol');
        $v->in('status', $status, ['active', 'suspended'], 'El estado');

        if (!$v->fails()) {
            if (User::emailTaken($email, $existing ? (int) $existing['id'] : null)) {
                $v->add('email', 'Ese correo ya está registrado.');
            }
            if (User::usernameTaken($username, $existing ? (int) $existing['id'] : null)) {
                $v->add('username', 'Ese usuario ya existe.');
            }
        }

        if ($existing === null) {
            $v->password('password', $password);
        } elseif ($password !== '') {
            $v->password('password', $password);
        }

        // Nadie puede dejar la plataforma sin administradores activos.
        if ($existing !== null && $existing['role'] === 'admin' && ($role !== 'admin' || $status !== 'active')) {
            $admins = (int) Database::scalar("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active' AND id <> :id", ['id' => (int) $existing['id']]);
            if ($admins < 1) {
                $v->add('role', 'Es el último administrador activo: asigna otro antes de cambiarlo.');
            }
        }

        if ($v->fails()) {
            $this->backWithErrors($v->errors());
            return;
        }

        $data = [
            'name'     => $name,
            'lastname' => $lastname !== '' ? $lastname : null,
            'email'    => $email,
            'username' => $username,
            'role'     => $role,
            'status'   => $status,
        ];

        if ($existing === null) {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            $id = Database::insert('users', $data);
            Audit::log('user_created', 'user', $id, ['username' => $username, 'role' => $role]);

            Mailer::send($email, 'Tu cuenta en ' . Config::appName(), Mailer::layout(
                'Cuenta creada',
                'Un administrador creó una cuenta para ti. Entra con tu correo y la contraseña que te compartieron, '
                . 'y cámbiala desde tu panel en cuanto puedas.',
                ['Usuario' => $username, 'Correo' => $email, 'Rol' => User::roleLabel($role)],
                ['url' => Config::absUrl('/login'), 'label' => 'Iniciar sesión']
            ), 'user_created');

            Session::flash('ok', 'Usuario creado.');
            Http::redirect('/admin/users');
        }

        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        Database::update('users', $data, 'id = :id', ['id' => (int) $existing['id']]);

        if ($existing['role'] !== $role) {
            Audit::log('user_role_changed', 'user', (int) $existing['id'], ['from' => $existing['role'], 'to' => $role]);
        }
        Audit::log('user_updated', 'user', (int) $existing['id'], ['username' => $username]);

        Session::flash('ok', 'Usuario actualizado.');
        Http::redirect('/admin/users');
    }

    public function userStatus(array $args): void
    {
        Csrf::verify();

        $user = User::find((int) ($args['id'] ?? 0));
        if ($user === null) {
            $this->missing('Ese usuario no existe.');
            return;
        }

        $to = Http::input('status');
        if (!in_array($to, ['active', 'suspended'], true)) {
            Session::flash('error', 'Estado no válido.');
            Http::back('/admin/users');
        }

        if ((int) $user['id'] === Auth::id() && $to === 'suspended') {
            Session::flash('error', 'No puedes suspender tu propia cuenta.');
            Http::back('/admin/users');
        }

        if ($user['role'] === 'admin' && $to === 'suspended') {
            $admins = (int) Database::scalar("SELECT COUNT(*) FROM users WHERE role='admin' AND status='active' AND id <> :id", ['id' => (int) $user['id']]);
            if ($admins < 1) {
                Session::flash('error', 'Es el último administrador activo.');
                Http::back('/admin/users');
            }
        }

        Database::update('users', ['status' => $to], 'id = :id', ['id' => (int) $user['id']]);
        Audit::log($to === 'active' ? 'user_activated' : 'user_suspended', 'user', (int) $user['id'], ['email' => $user['email']]);

        Session::flash('ok', $to === 'active' ? 'Usuario activado.' : 'Usuario suspendido.');
        Http::back('/admin/users');
    }

    public function userDelete(array $args): void
    {
        Csrf::verify();

        $user = User::find((int) ($args['id'] ?? 0));
        if ($user === null) {
            $this->missing('Ese usuario no existe.');
            return;
        }
        if ((int) $user['id'] === Auth::id()) {
            Session::flash('error', 'Usa "Mi cuenta" para darte de baja tú mismo.');
            Http::back('/admin/users');
        }
        if ($user['role'] === 'admin') {
            $admins = (int) Database::scalar("SELECT COUNT(*) FROM users WHERE role='admin' AND status='active' AND id <> :id", ['id' => (int) $user['id']]);
            if ($admins < 1) {
                Session::flash('error', 'Es el último administrador activo.');
                Http::back('/admin/users');
            }
        }

        Database::run('DELETE FROM users WHERE id = :id', ['id' => (int) $user['id']]);
        Audit::log('user_deleted', 'user', (int) $user['id'], ['email' => $user['email']]);

        Session::flash('ok', 'Usuario eliminado. Su contenido publicado queda sin autor asociado.');
        Http::back('/admin/users');
    }

    // ---------------------------------------------------- Skills (global)
    public function skills(): void
    {
        $filters = [
            'q'          => Http::input('q'),
            'status'     => Http::input('status'),
            'visibility' => Http::input('visibility'),
            'category'   => Http::input('category'),
            'compat'     => Http::input('compat'),
            'user'       => Http::input('user'),
            'page'       => Http::inputInt('page', 1),
        ];

        $this->view('admin/skills', [
            'result'     => Skill::adminList($filters),
            'filters'    => $filters,
            'categories' => Category::active(),
            'compat'     => Config::compatibilityOptions(),
        ], 'Todas las skills', 'layouts/panel');
    }

    public function skillStatus(array $args): void
    {
        Csrf::verify();

        $skill = Skill::find((int) ($args['id'] ?? 0));
        if ($skill === null) {
            $this->missing('Esa habilidad no existe.');
            return;
        }

        $to    = Http::input('status');
        $notes = Http::inputRaw('review_notes');
        $valid = ['draft', 'pending', 'under_review', 'approved', 'rejected', 'published', 'archived'];

        if (!in_array($to, $valid, true)) {
            Session::flash('error', 'Estado no válido.');
            Http::back('/admin/skills');
        }

        $update = ['status' => $to, 'review_notes' => $notes !== '' ? $notes : null];
        if ($to === 'published' && empty($skill['published_at'])) {
            $update['published_at'] = date('Y-m-d H:i:s');
        }
        Database::update('skills', $update, 'id = :id', ['id' => (int) $skill['id']]);

        $action = ['published' => 'skill_published', 'rejected' => 'skill_rejected', 'approved' => 'skill_approved'][$to] ?? 'skill_updated';
        Audit::log($action, 'skill', (int) $skill['id'], ['to' => $to, 'name' => $skill['name']]);

        $this->notifyAuthor($skill, $to, $notes, 'skill');

        Session::flash('ok', 'Estado actualizado a ' . Skill::statusLabel($to) . '.');
        Http::back('/admin/skills');
    }

    public function skillDelete(array $args): void
    {
        Csrf::verify();
        $skill = Skill::find((int) ($args['id'] ?? 0));
        if ($skill === null) {
            $this->missing('Esa habilidad no existe.');
            return;
        }
        Database::run('DELETE FROM skills WHERE id = :id', ['id' => (int) $skill['id']]);
        Audit::log('skill_deleted', 'skill', (int) $skill['id'], ['name' => $skill['name']]);
        Session::flash('ok', 'Habilidad eliminada.');
        Http::back('/admin/skills');
    }

    public function skillFeature(array $args): void
    {
        Csrf::verify();
        $skill = Skill::find((int) ($args['id'] ?? 0));
        if ($skill === null) {
            $this->missing('Esa habilidad no existe.');
            return;
        }
        $to = (int) $skill['featured'] === 1 ? 0 : 1;
        Database::update('skills', ['featured' => $to], 'id = :id', ['id' => (int) $skill['id']]);
        Audit::log('skill_updated', 'skill', (int) $skill['id'], ['featured' => $to]);
        Session::flash('ok', $to ? 'Marcada como destacada.' : 'Ya no está destacada.');
        Http::back('/admin/skills');
    }

    // ---------------------------------------------------- Agentes (global)
    public function agents(): void
    {
        $filters = [
            'q'          => Http::input('q'),
            'status'     => Http::input('status'),
            'visibility' => Http::input('visibility'),
            'category'   => Http::input('category'),
            'page'       => Http::inputInt('page', 1),
        ];

        $this->view('admin/agents', [
            'result'     => Agent::adminList($filters),
            'filters'    => $filters,
            'categories' => Category::active(),
        ], 'Todos los agentes', 'layouts/panel');
    }

    public function agentStatus(array $args): void
    {
        Csrf::verify();
        $agent = Agent::find((int) ($args['id'] ?? 0));
        if ($agent === null) {
            $this->missing('Ese agente no existe.');
            return;
        }

        $to    = Http::input('status');
        $notes = Http::inputRaw('review_notes');
        $valid = ['draft', 'pending', 'under_review', 'approved', 'rejected', 'published', 'archived'];
        if (!in_array($to, $valid, true)) {
            Session::flash('error', 'Estado no válido.');
            Http::back('/admin/agents');
        }

        $update = ['status' => $to, 'review_notes' => $notes !== '' ? $notes : null];
        if ($to === 'published' && empty($agent['published_at'])) {
            $update['published_at'] = date('Y-m-d H:i:s');
        }
        Database::update('agents', $update, 'id = :id', ['id' => (int) $agent['id']]);
        Audit::log($to === 'published' ? 'agent_published' : 'agent_updated', 'agent', (int) $agent['id'], ['to' => $to]);

        $this->notifyAuthor($agent, $to, $notes, 'agent');

        Session::flash('ok', 'Estado actualizado.');
        Http::back('/admin/agents');
    }

    public function agentDelete(array $args): void
    {
        Csrf::verify();
        $agent = Agent::find((int) ($args['id'] ?? 0));
        if ($agent === null) {
            $this->missing('Ese agente no existe.');
            return;
        }
        Database::run('DELETE FROM agents WHERE id = :id', ['id' => (int) $agent['id']]);
        Audit::log('agent_deleted', 'agent', (int) $agent['id'], ['name' => $agent['name']]);
        Session::flash('ok', 'Agente eliminado.');
        Http::back('/admin/agents');
    }

    // ------------------------------------------------------------- Envíos
    public function submissions(): void
    {
        $filters = [
            'q'      => Http::input('q'),
            'status' => Http::input('status'),
            'kind'   => Http::input('kind'),
            'page'   => Http::inputInt('page', 1),
        ];

        $this->view('admin/submissions', [
            'result'  => Submission::adminList($filters),
            'filters' => $filters,
        ], 'Bandeja de envíos', 'layouts/panel');
    }

    public function submissionShow(array $args): void
    {
        $submission = Submission::find((int) ($args['id'] ?? 0));
        if ($submission === null) {
            $this->missing('Ese envío no existe.');
            return;
        }

        $preview = '';
        $content = (string) ($submission['content'] ?? '');
        if ($content !== '') {
            $ext = strtolower(pathinfo((string) $submission['file_name'], PATHINFO_EXTENSION));
            if ($ext === 'json') {
                $decoded = json_decode($content, true);
                $preview = $decoded !== null
                    ? (string) json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : $content;
            } else {
                [, $body] = Markdown::splitFrontMatter($content);
                $preview  = $body;
            }
        }

        $this->view('admin/submission-show', [
            'submission' => $submission,
            'preview'    => $preview,
            'html'       => $content !== '' ? Markdown::toHtml((string) $submission['description']) : '',
            'categories' => Category::active(),
        ], 'Envío ' . $submission['reference'], 'layouts/panel');
    }

    public function submissionReview(array $args): void
    {
        Csrf::verify();

        $submission = Submission::find((int) ($args['id'] ?? 0));
        if ($submission === null) {
            $this->missing('Ese envío no existe.');
            return;
        }

        $decision = Http::input('decision');
        $notes    = Http::inputRaw('review_notes');
        $id       = (int) $submission['id'];

        if ($decision === 'under_review') {
            Database::update('skill_submissions', [
                'status'      => 'under_review',
                'review_notes'=> $notes !== '' ? $notes : null,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => date('Y-m-d H:i:s'),
            ], 'id = :id', ['id' => $id]);
            Audit::log('submission_received', 'submission', $id, ['status' => 'under_review']);
            Session::flash('ok', 'Marcado como en revisión.');
            Http::redirect('/admin/submissions/' . $id);
        }

        if ($decision === 'reject') {
            if (trim($notes) === '') {
                Session::flash('error', 'Escribe el motivo del rechazo: se le envía al remitente.');
                Http::redirect('/admin/submissions/' . $id);
            }

            Database::update('skill_submissions', [
                'status'       => 'rejected',
                'review_notes' => $notes,
                'reviewed_by'  => Auth::id(),
                'reviewed_at'  => date('Y-m-d H:i:s'),
            ], 'id = :id', ['id' => $id]);

            Audit::log('submission_rejected', 'submission', $id, ['reference' => $submission['reference']]);

            Mailer::send((string) $submission['email'], 'Tu envío requiere cambios · ' . $submission['reference'],
                Mailer::layout(
                    'Tu envío requiere modificaciones',
                    'Revisamos lo que nos enviaste y por ahora no podemos publicarlo. Te dejamos el motivo para que '
                    . 'puedas corregirlo y volver a enviarlo cuando quieras.',
                    [
                        'Identificador' => $submission['reference'],
                        'Nombre'        => $submission['skill_name'],
                        'Motivo'        => $notes,
                    ],
                    ['url' => Config::absUrl('/submit'), 'label' => 'Enviar una versión corregida'],
                    'Si crees que se trata de un error, responde a este correo.'
                ), 'submission_rejected');

            Session::flash('ok', 'Envío rechazado y motivo enviado al remitente.');
            Http::redirect('/admin/submissions/' . $id);
        }

        if ($decision !== 'approve') {
            Session::flash('error', 'Decisión no reconocida.');
            Http::redirect('/admin/submissions/' . $id);
        }

        // ----------------------------------------------------- Aprobación
        $publish    = Http::inputBool('publish');
        $categoryId = Http::inputInt('category_id') ?: (int) ($submission['category_id'] ?? 0);
        $kind       = (string) $submission['kind'];

        $body = trim((string) ($submission['content'] ?? ''));
        if ($body === '') {
            $body = (string) $submission['description'];
        }

        if ($kind === 'agent') {
            $slug = Agent::uniqueSlug((string) $submission['skill_name']);
            $newId = Database::insert('agents', [
                'user_id'           => null,
                'name'              => $submission['skill_name'],
                'slug'              => $slug,
                'short_description' => Str::excerpt((string) $submission['description'], 200),
                'rules_md'          => $body,
                'category_id'       => $categoryId > 0 ? $categoryId : null,
                'compatibility'     => $submission['compatibility'],
                'version'           => '1.0.0',
                'status'            => $publish ? 'published' : 'approved',
                'visibility'        => 'public',
                'author_name'       => $submission['name'],
                'author_email'      => $submission['email'],
                'published_at'      => $publish ? date('Y-m-d H:i:s') : null,
            ]);
            $publicUrl = Config::absUrl('/agents/' . $slug);
            Database::update('skill_submissions', ['agent_id' => $newId], 'id = :id', ['id' => $id]);
            Audit::log('agent_created', 'agent', $newId, ['from_submission' => $submission['reference']]);
        } else {
            $slug = Skill::uniqueSlug((string) $submission['skill_name']);
            $newId = Database::insert('skills', [
                'user_id'           => null,
                'name'              => $submission['skill_name'],
                'slug'              => $slug,
                'short_description' => Str::excerpt((string) $submission['description'], 200),
                'description'       => $body,
                'category_id'       => $categoryId > 0 ? $categoryId : null,
                'compatibility'     => $submission['compatibility'],
                'version'           => '1.0.0',
                'status'            => $publish ? 'published' : 'approved',
                'visibility'        => 'public',
                'formats'           => 'md,txt,json,zip',
                'author_name'       => $submission['name'],
                'author_email'      => $submission['email'],
                'submission_id'     => $id,
                'published_at'      => $publish ? date('Y-m-d H:i:s') : null,
            ]);
            $publicUrl = Config::absUrl('/skills/' . $slug);
            Skill::recordVersion($newId, '1.0.0', $body, 'Alta desde envío ' . $submission['reference'], (int) Auth::id());
            Database::update('skill_submissions', ['skill_id' => $newId], 'id = :id', ['id' => $id]);
            Audit::log('skill_created', 'skill', $newId, ['from_submission' => $submission['reference']]);
        }

        Database::update('skill_submissions', [
            'status'       => $publish ? 'published' : 'approved',
            'review_notes' => $notes !== '' ? $notes : null,
            'reviewed_by'  => Auth::id(),
            'reviewed_at'  => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $id]);

        Audit::log('submission_approved', 'submission', $id, [
            'reference' => $submission['reference'],
            'published' => $publish ? 1 : 0,
        ]);

        Mailer::send((string) $submission['email'],
            ($publish ? 'Tu envío ya está publicado · ' : 'Tu envío fue aprobado · ') . $submission['reference'],
            Mailer::layout(
                $publish ? 'Tu envío ya está publicado' : 'Tu envío fue aprobado',
                $publish
                    ? 'Revisamos tu envío y ya está disponible en la biblioteca. Cualquiera puede verlo y descargarlo desde su enlace público.'
                    : 'Revisamos tu envío y quedó aprobado. Lo publicaremos en la biblioteca en breve.',
                array_filter([
                    'Identificador' => $submission['reference'],
                    'Nombre'        => $submission['skill_name'],
                    'Enlace'        => $publish ? $publicUrl : null,
                    'Notas'         => $notes !== '' ? $notes : null,
                ]),
                $publish ? ['url' => $publicUrl, 'label' => 'Ver la publicación'] : null,
                'Gracias por aportar a la biblioteca.'
            ), 'submission_approved');

        Session::flash('ok', $publish ? 'Aprobado y publicado.' : 'Aprobado. Publícalo cuando quieras.');
        Http::redirect('/admin/submissions/' . $id);
    }

    public function submissionDelete(array $args): void
    {
        Csrf::verify();
        $submission = Submission::find((int) ($args['id'] ?? 0));
        if ($submission === null) {
            $this->missing('Ese envío no existe.');
            return;
        }

        // Se borra también el archivo del disco: es un dato personal más.
        if (!empty($submission['file_path'])) {
            $file = Config::storagePath((string) $submission['file_path']);
            if (is_file($file)) {
                @unlink($file);
            }
        }

        Database::run('DELETE FROM skill_submissions WHERE id = :id', ['id' => (int) $submission['id']]);
        Audit::log('submission_rejected', 'submission', (int) $submission['id'], ['deleted' => 1, 'reference' => $submission['reference']]);

        Session::flash('ok', 'Envío eliminado junto con su archivo.');
        Http::redirect('/admin/submissions');
    }

    /** Descarga del archivo adjunto de un envío. Sólo administradores. */
    public function submissionFile(array $args): void
    {
        $submission = Submission::find((int) ($args['id'] ?? 0));
        if ($submission === null || empty($submission['file_path'])) {
            $this->missing('Ese envío no tiene archivo adjunto.');
            return;
        }

        // La ruta guardada se normaliza y se comprueba que siga dentro de storage.
        $base = realpath(Config::storagePath());
        $file = realpath(Config::storagePath((string) $submission['file_path']));

        if ($base === false || $file === false || strncmp($file, $base, strlen($base)) !== 0 || !is_file($file)) {
            $this->missing('El archivo ya no está disponible.');
            return;
        }

        Audit::log('download', 'submission', (int) $submission['id'], ['reference' => $submission['reference']]);

        Http::download(
            (string) ($submission['file_name'] ?? 'adjunto'),
            (string) file_get_contents($file),
            'application/octet-stream'
        );
    }

    // --------------------------------------------------------- Categorías
    public function categories(): void
    {
        $this->view('admin/categories', [
            'categories' => Category::all(),
            'errors'     => $this->takeErrors(),
        ], 'Categorías', 'layouts/panel');
    }

    public function categoryStore(): void
    {
        Csrf::verify();

        $id          = Http::inputInt('id');
        $name        = Http::input('name');
        $description = Http::input('description');
        $status      = Http::input('status', 'active');
        $position    = Http::inputInt('position');

        $v = new Validator();
        $v->required('name', $name, 'El nombre')->max('name', $name, 90, 'El nombre');
        $v->max('description', $description, 255, 'La descripción');
        $v->in('status', $status, ['active', 'hidden'], 'El estado');

        if ($v->fails()) {
            $this->backWithErrors($v->errors());
            return;
        }

        $data = [
            'name'        => $name,
            'description' => $description !== '' ? $description : null,
            'status'      => $status,
            'position'    => $position,
        ];

        if ($id > 0 && Category::find($id) !== null) {
            $data['slug'] = Category::uniqueSlug($name, $id);
            Database::update('categories', $data, 'id = :id', ['id' => $id]);
            Audit::log('category_updated', 'category', $id, ['name' => $name]);
            Session::flash('ok', 'Categoría actualizada.');
        } else {
            $data['slug'] = Category::uniqueSlug($name);
            $newId = Database::insert('categories', $data);
            Audit::log('category_created', 'category', $newId, ['name' => $name]);
            Session::flash('ok', 'Categoría creada.');
        }

        Http::redirect('/admin/categories');
    }

    public function categoryDelete(array $args): void
    {
        Csrf::verify();
        $category = Category::find((int) ($args['id'] ?? 0));
        if ($category === null) {
            $this->missing('Esa categoría no existe.');
            return;
        }

        Database::run('DELETE FROM categories WHERE id = :id', ['id' => (int) $category['id']]);
        Audit::log('category_deleted', 'category', (int) $category['id'], ['name' => $category['name']]);

        Session::flash('ok', 'Categoría eliminada. El contenido que la usaba queda sin categoría.');
        Http::redirect('/admin/categories');
    }

    // ----------------------------------------------------------- Auditoría
    public function audit(): void
    {
        $action = Http::input('action');
        $page   = max(1, Http::inputInt('page', 1));
        $per    = 40;

        $where  = '1=1';
        $params = [];
        if ($action !== '') {
            $where = 'l.action = :a';
            $params['a'] = $action;
        }

        $total = (int) Database::scalar('SELECT COUNT(*) FROM audit_logs l WHERE ' . $where, $params);
        $items = Database::all(
            'SELECT l.*, u.username FROM audit_logs l LEFT JOIN users u ON u.id = l.user_id
             WHERE ' . $where . ' ORDER BY l.id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per),
            $params
        );

        $actions = Database::all('SELECT DISTINCT action FROM audit_logs ORDER BY action ASC');

        $this->view('admin/audit', [
            'items'   => $items,
            'actions' => $actions,
            'action'  => $action,
            'page'    => $page,
            'pages'   => (int) max(1, ceil($total / $per)),
            'total'   => $total,
        ], 'Auditoría', 'layouts/panel');
    }

    public function downloads(): void
    {
        $byFormat = Database::all(
            'SELECT format, COUNT(*) AS n FROM downloads GROUP BY format ORDER BY n DESC'
        );
        $byDay = Database::all(
            'SELECT DATE(created_at) AS d, COUNT(*) AS n FROM downloads
             WHERE created_at > (NOW() - INTERVAL 30 DAY) GROUP BY DATE(created_at) ORDER BY d ASC'
        );
        $recent = Database::all(
            "SELECT d.*, s.name AS skill_name, s.slug AS skill_slug, a.name AS agent_name, a.slug AS agent_slug, u.username
             FROM downloads d
             LEFT JOIN skills s ON s.id = d.skill_id
             LEFT JOIN agents a ON a.id = d.agent_id
             LEFT JOIN users u ON u.id = d.user_id
             ORDER BY d.id DESC LIMIT 50"
        );

        $this->view('admin/downloads', [
            'byFormat' => $byFormat,
            'byDay'    => $byDay,
            'recent'   => $recent,
            'total'    => (int) Database::scalar('SELECT COUNT(*) FROM downloads'),
        ], 'Descargas', 'layouts/panel');
    }

    // ---------------------------------------------------------------------

    /** Avisa al autor cuando su contenido cambia de estado. */
    private function notifyAuthor(array $row, string $status, string $notes, string $type): void
    {
        $email = (string) ($row['author_email'] ?? '');
        if ($email === '' && !empty($row['user_id'])) {
            $author = User::find((int) $row['user_id']);
            $email  = (string) ($author['email'] ?? '');
        }
        if ($email === '' || !in_array($status, ['published', 'rejected', 'approved'], true)) {
            return;
        }

        $url = Config::absUrl(($type === 'agent' ? '/agents/' : '/skills/') . $row['slug']);

        $titles = [
            'published' => 'Tu publicación ya está disponible',
            'approved'  => 'Tu contenido fue aprobado',
            'rejected'  => 'Tu contenido requiere cambios',
        ];

        Mailer::send($email, $titles[$status] . ' · ' . $row['name'], Mailer::layout(
            $titles[$status],
            $status === 'rejected'
                ? 'Un administrador revisó tu contenido y por ahora no puede publicarse.'
                : 'Un administrador revisó tu contenido y ya cambió de estado.',
            array_filter([
                'Nombre' => $row['name'],
                'Estado' => Skill::statusLabel($status),
                'Enlace' => $status === 'published' ? $url : null,
                'Notas'  => $notes !== '' ? $notes : null,
            ]),
            $status === 'published' ? ['url' => $url, 'label' => 'Ver publicación'] : null
        ), 'status_' . $status);
    }

    private function missing(string $message): void
    {
        http_response_code(404);
        $this->view('errors/error', [
            'code' => 404, 'title' => 'No encontrado', 'message' => $message,
        ], 'No encontrado');
    }
}
