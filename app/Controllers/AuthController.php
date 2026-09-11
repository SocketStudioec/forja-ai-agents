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
use App\Core\RateLimit;
use App\Core\Session;
use App\Core\Str;
use App\Core\Validator;
use App\Models\User;

final class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Auth::check()) {
            Http::redirect('/dashboard');
        }
        $this->view('auth/login', ['errors' => $this->takeErrors()], 'Iniciar sesión', 'layouts/slim');
    }

    public function login(): void
    {
        Csrf::verify();

        if (RateLimit::tooMany('login', 8, 900)) {
            $this->backWithErrors(
                ['login' => 'Demasiados intentos.'],
                'Demasiados intentos fallidos. Espera unos minutos antes de volver a probar.'
            );
            return;
        }

        $login    = Http::input('login');
        $password = (string) ($_POST['password'] ?? '');

        RateLimit::hit('login');

        $user = User::findByLogin($login);

        // Se compara siempre un hash para que la respuesta tarde lo mismo
        // exista o no la cuenta: así no se puede sondear qué correos existen.
        $hash = $user['password_hash'] ?? '$2y$12$usuarioinexistenteusuarioinexistenteusuarioinexiste12345678';

        if (!password_verify($password, $hash) || $user === null) {
            Audit::log('login_failed', 'user', null, ['login' => $login], $login);
            $this->backWithErrors(['login' => 'Credenciales incorrectas.'], 'Usuario o contraseña incorrectos.');
            return;
        }

        if ($user['status'] !== 'active') {
            Audit::log('login_failed', 'user', (int) $user['id'], ['reason' => $user['status']], $user['email']);
            $this->backWithErrors(
                ['login' => 'Cuenta no disponible.'],
                'Tu cuenta está suspendida. Escríbenos si crees que es un error.'
            );
            return;
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            Database::run('UPDATE users SET password_hash = :h WHERE id = :id', [
                'h'  => password_hash($password, PASSWORD_DEFAULT),
                'id' => (int) $user['id'],
            ]);
        }

        Auth::login((int) $user['id']);
        RateLimit::clear('login');
        Audit::log('login', 'user', (int) $user['id']);

        $intended = (string) Session::get('__intended', '');
        Session::forget('__intended');
        Session::flash('ok', 'Bienvenido de vuelta, ' . Auth::displayName($user) . '.');

        if ($intended !== '' && strpos($intended, '://') === false) {
            Http::redirect($intended);
        }
        Http::redirect($user['role'] === 'admin' ? '/admin' : '/dashboard');
    }

    public function registerForm(): void
    {
        if (Auth::check()) {
            Http::redirect('/dashboard');
        }
        $this->view('auth/register', ['errors' => $this->takeErrors()], 'Crear cuenta', 'layouts/slim');
    }

    public function register(): void
    {
        Csrf::verify();

        if (Http::input('website') !== '') {
            Http::redirect('/login');
        }
        if (RateLimit::tooMany('register', 5, 3600)) {
            $this->backWithErrors(['general' => 'Demasiados registros.'], 'Demasiados registros desde esta conexión.');
            return;
        }

        $name     = Http::input('name');
        $lastname = Http::input('lastname');
        $email    = mb_strtolower(Http::input('email'));
        $username = mb_strtolower(Http::input('username'));
        $password = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['password_confirm'] ?? '');
        $consent  = Http::inputBool('consent');

        $v = new Validator();
        $v->required('name', $name, 'El nombre')->max('name', $name, 80, 'El nombre');
        $v->max('lastname', $lastname, 80, 'El apellido');
        $v->required('email', $email, 'El correo')->email('email', $email);
        $v->required('username', $username, 'El usuario')
          ->regex('username', $username, '/^[a-z0-9_.-]{3,40}$/', 'El usuario admite 3 a 40 caracteres: letras minúsculas, números, punto, guion y guion bajo.');
        $v->password('password', $password);
        $v->match('password_confirm', $password, $confirm, 'Las contraseñas no coinciden.');
        $v->condition('consent', $consent, 'Debes aceptar el tratamiento de datos para crear la cuenta.');

        if (!$v->fails()) {
            if (User::emailTaken($email)) {
                $v->add('email', 'Ya existe una cuenta con ese correo.');
            }
            if (User::usernameTaken($username)) {
                $v->add('username', 'Ese nombre de usuario ya está ocupado.');
            }
        }

        if ($v->fails()) {
            $this->backWithErrors($v->errors());
            return;
        }

        RateLimit::hit('register');

        $userId = Database::insert('users', [
            'name'          => $name,
            'lastname'      => $lastname !== '' ? $lastname : null,
            'username'      => $username,
            'email'         => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role'          => 'user',
            'status'        => 'active',
        ]);

        Auth::login($userId);
        Audit::log('user_registered', 'user', $userId, ['username' => $username]);

        Mailer::send(
            $email,
            'Tu cuenta en ' . Config::appName(),
            Mailer::layout(
                'Cuenta creada',
                'Hola ' . e($name) . ', tu cuenta ya está activa. Desde tu panel puedes crear habilidades en JSON, '
                . 'definir agentes con reglas en Markdown y publicarlos para que cualquiera los descargue.',
                ['Usuario' => $username, 'Correo' => $email],
                ['url' => Config::absUrl('/dashboard'), 'label' => 'Entrar al panel'],
                'Si no creaste esta cuenta, responde a este correo y la damos de baja.'
            ),
            'welcome'
        );

        Session::flash('ok', 'Cuenta creada. Ya puedes publicar tu primer agente.');
        Http::redirect('/dashboard');
    }

    public function forgotForm(): void
    {
        $this->view('auth/forgot', ['errors' => $this->takeErrors()], 'Recuperar contraseña', 'layouts/slim');
    }

    public function forgot(): void
    {
        Csrf::verify();

        $email = mb_strtolower(Http::input('email'));

        // La respuesta es idéntica exista o no la cuenta.
        $genericMessage = 'Si el correo corresponde a una cuenta, te enviamos un enlace para restablecer la contraseña.';

        if (RateLimit::tooMany('forgot', 5, 3600)) {
            Session::flash('info', $genericMessage);
            Http::redirect('/login');
        }
        RateLimit::hit('forgot');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->backWithErrors(['email' => 'Escribe un correo válido.'], 'Escribe un correo válido.');
            return;
        }

        $user = User::findByEmail($email);
        if ($user !== null && $user['status'] === 'active') {
            $token = bin2hex(random_bytes(32));
            Database::insert('password_resets', [
                'email'      => $email,
                'token_hash' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', time() + 3600),
            ]);

            Mailer::send(
                $email,
                'Restablecer tu contraseña',
                Mailer::layout(
                    'Restablecer contraseña',
                    'Recibimos una solicitud para cambiar la contraseña de tu cuenta. El enlace caduca en una hora '
                    . 'y sólo puede usarse una vez.',
                    ['Cuenta' => $email, 'Solicitado' => date('d/m/Y H:i')],
                    ['url' => Config::absUrl('/reset/' . $token), 'label' => 'Crear una contraseña nueva'],
                    'Si no fuiste tú, ignora este mensaje: la contraseña actual sigue siendo válida.'
                ),
                'password_reset'
            );
        }

        Session::flash('info', $genericMessage);
        Http::redirect('/login');
    }

    public function resetForm(array $args): void
    {
        $token = (string) ($args['token'] ?? '');
        if ($this->findReset($token) === null) {
            Session::flash('error', 'Ese enlace caducó o ya se usó. Pide uno nuevo.');
            Http::redirect('/forgot');
        }
        $this->view('auth/reset', [
            'token'  => $token,
            'errors' => $this->takeErrors(),
        ], 'Nueva contraseña', 'layouts/slim');
    }

    public function reset(array $args): void
    {
        Csrf::verify();

        $token = (string) ($args['token'] ?? '');
        $row   = $this->findReset($token);

        if ($row === null) {
            Session::flash('error', 'Ese enlace caducó o ya se usó. Pide uno nuevo.');
            Http::redirect('/forgot');
        }

        $password = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['password_confirm'] ?? '');

        $v = new Validator();
        $v->password('password', $password);
        $v->match('password_confirm', $password, $confirm, 'Las contraseñas no coinciden.');

        if ($v->fails()) {
            $this->backWithErrors($v->errors());
            return;
        }

        $user = User::findByEmail((string) $row['email']);
        if ($user === null) {
            Session::flash('error', 'No encontramos la cuenta asociada.');
            Http::redirect('/forgot');
        }

        Database::run('UPDATE users SET password_hash = :h WHERE id = :id', [
            'h'  => password_hash($password, PASSWORD_DEFAULT),
            'id' => (int) $user['id'],
        ]);
        Database::run('UPDATE password_resets SET used_at = NOW() WHERE id = :id', ['id' => (int) $row['id']]);

        Audit::log('password_reset', 'user', (int) $user['id'], [], (string) $user['email']);
        Session::flash('ok', 'Contraseña actualizada. Ya puedes entrar.');
        Http::redirect('/login');
    }

    public function logout(): void
    {
        Csrf::verify();
        Audit::log('logout', 'user', Auth::id());
        Auth::logout();
        Session::start();
        Session::flash('info', 'Sesión cerrada.');
        Http::redirect('/');
    }

    /** @return array<string,mixed>|null */
    private function findReset(string $token): ?array
    {
        if (strlen($token) !== 64) {
            return null;
        }
        return Database::first(
            'SELECT * FROM password_resets
             WHERE token_hash = :h AND used_at IS NULL AND expires_at > NOW()
             ORDER BY id DESC LIMIT 1',
            ['h' => hash('sha256', $token)]
        );
    }
}
