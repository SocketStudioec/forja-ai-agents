<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Http;
use App\Core\Session;
use App\Core\Str;
use App\Core\Validator;
use App\Core\Visibility;
use App\Models\Agent;
use App\Models\Category;
use App\Models\Library;
use App\Models\Skill;
use App\Models\User;

final class DashboardController extends Controller
{
    /** Categorías elegidas en el formulario, resueltas al validar. @var array<int,int> */
    private array $categoriasAgente = [];

    public function __construct()
    {
        Auth::requireLogin();
    }

    // ----------------------------------------------------------- Resumen
    public function index(): void
    {
        $uid = (int) Auth::id();

        $stats = [
            'skills'     => (int) Database::scalar('SELECT COUNT(*) FROM skills WHERE user_id = :u', ['u' => $uid]),
            'agents'     => (int) Database::scalar('SELECT COUNT(*) FROM agents WHERE user_id = :u', ['u' => $uid]),
            'published'  => (int) Database::scalar("SELECT COUNT(*) FROM skills WHERE user_id = :u AND status = 'published'", ['u' => $uid])
                          + (int) Database::scalar("SELECT COUNT(*) FROM agents WHERE user_id = :u AND status = 'published'", ['u' => $uid]),
            'pending'    => (int) Database::scalar("SELECT COUNT(*) FROM skills WHERE user_id = :u AND status IN ('pending','under_review')", ['u' => $uid])
                          + (int) Database::scalar("SELECT COUNT(*) FROM agents WHERE user_id = :u AND status IN ('pending','under_review')", ['u' => $uid]),
            'downloads'  => (int) Database::scalar('SELECT COALESCE(SUM(downloads),0) FROM skills WHERE user_id = :u', ['u' => $uid])
                          + (int) Database::scalar('SELECT COALESCE(SUM(downloads),0) FROM agents WHERE user_id = :u', ['u' => $uid]),
            'favorites'  => (int) Database::scalar('SELECT COUNT(*) FROM favorites WHERE user_id = :u', ['u' => $uid]),
        ];

        $topSkills = Database::all(
            'SELECT name, slug, downloads, status FROM skills WHERE user_id = :u ORDER BY downloads DESC LIMIT 5',
            ['u' => $uid]
        );

        $activity = Database::all(
            'SELECT * FROM audit_logs WHERE user_id = :u ORDER BY id DESC LIMIT 8',
            ['u' => $uid]
        );

        // Qué hay disponible para descargar. Para quien acaba de entrar, esto
        // es lo único que le interesa de esta pantalla.
        $catalogo = [
            'agents' => (int) Database::scalar("SELECT COUNT(*) FROM agents WHERE status='published' AND visibility='public'"),
            'skills' => (int) Database::scalar("SELECT COUNT(*) FROM skills WHERE status='published' AND visibility='public'"),
        ];

        $misAgentes = Agent::forUser($uid);
        $sinContenido = $stats['skills'] === 0 && $stats['agents'] === 0;

        $destacados = $sinContenido ? Agent::featured(3) : [];
        foreach ($destacados as &$d) {
            $d['skill_preview'] = array_slice(Agent::skills((int) $d['id']), 0, 4);
        }
        unset($d);

        $this->view('dashboard/index', [
            'stats'        => $stats,
            'topSkills'    => $topSkills,
            'activity'     => $activity,
            'agents'       => $misAgentes,
            'catalogo'     => $catalogo,
            'sinContenido' => $sinContenido,
            'destacados'   => $destacados,
        ], 'Mi panel', 'layouts/panel');
    }

    // ---------------------------------------------------------- Skills
    public function skills(): void
    {
        $this->view('dashboard/skills', [
            'skills' => Skill::forUser((int) Auth::id(), Http::input('status') ?: null),
            'status' => Http::input('status'),
        ], 'Mis habilidades', 'layouts/panel');
    }

    public function skillForm(array $args = []): void
    {
        $skill = null;
        if (!empty($args['id'])) {
            $skill = Skill::find((int) $args['id']);
            if ($skill === null) {
                $this->missing('Esa habilidad no existe.');
                return;
            }
            Auth::requireOwnership($skill['user_id'] !== null ? (int) $skill['user_id'] : null);
        }

        $this->view('dashboard/skill-form', [
            'skill'      => $skill,
            'categories' => Category::active(),
            'compat'     => Config::compatibilityOptions(),
            'errors'     => $this->takeErrors(),
            'versions'   => $skill ? Skill::versions((int) $skill['id'], 8) : [],
        ], $skill ? 'Editar habilidad' : 'Nueva habilidad', 'layouts/panel');
    }

    public function skillStore(array $args = []): void
    {
        Csrf::verify();

        $existing = null;
        if (!empty($args['id'])) {
            $existing = Skill::find((int) $args['id']);
            if ($existing === null) {
                $this->missing('Esa habilidad no existe.');
                return;
            }
            Auth::requireOwnership($existing['user_id'] !== null ? (int) $existing['user_id'] : null);
        }

        $data = $this->validateSkill($existing);
        if ($data === null) {
            return;
        }

        $action = Http::input('action', 'draft');

        if ($existing === null) {
            $data['user_id']     = (int) Auth::id();
            $data['slug']        = Skill::uniqueSlug($data['name']);
            $data['status']      = $action === 'publish' ? 'published' : 'draft';
            $data['published_at']= $action === 'publish' ? date('Y-m-d H:i:s') : null;

            $id = Database::insert('skills', $data);
            Skill::recordVersion($id, $data['version'], $data['description'], 'Versión inicial', (int) Auth::id());
            Audit::log('skill_created', 'skill', $id, ['name' => $data['name'], 'status' => $data['status']]);

            Session::flash('ok', $action === 'publish'
                ? 'Habilidad publicada. Ya se puede descargar en JSON y Markdown.'
                : 'Borrador guardado.');
            Http::redirect('/dashboard/skills/' . $id . '/edit');
        }

        $id = (int) $existing['id'];

        // El slug sólo cambia si la habilidad todavía no se ha publicado:
        // un enlace público compartido no debe romperse nunca.
        if ($existing['status'] !== 'published' && $data['name'] !== $existing['name']) {
            $data['slug'] = Skill::uniqueSlug($data['name'], $id);
        }

        if ($action === 'publish') {
            $data['status'] = 'published';
            if (empty($existing['published_at'])) {
                $data['published_at'] = date('Y-m-d H:i:s');
            }
        } elseif ($action === 'unpublish') {
            $data['status'] = 'draft';
        }

        $contentChanged = $data['description'] !== $existing['description'];
        Database::update('skills', $data, 'id = :id', ['id' => $id]);

        if ($contentChanged || $data['version'] !== $existing['version']) {
            Skill::recordVersion($id, $data['version'], $data['description'], Http::input('changelog') ?: null, (int) Auth::id());
        }

        Audit::log('skill_updated', 'skill', $id, [
            'name'    => $data['name'],
            'version' => $data['version'],
            'status'  => $data['status'] ?? $existing['status'],
        ]);

        Session::flash('ok', 'Cambios guardados.');
        Http::redirect('/dashboard/skills/' . $id . '/edit');
    }

    public function skillDelete(array $args): void
    {
        Csrf::verify();
        $skill = Skill::find((int) ($args['id'] ?? 0));
        if ($skill === null) {
            $this->missing('Esa habilidad no existe.');
            return;
        }
        Auth::requireOwnership($skill['user_id'] !== null ? (int) $skill['user_id'] : null);

        Database::run('DELETE FROM skills WHERE id = :id', ['id' => (int) $skill['id']]);
        Audit::log('skill_deleted', 'skill', (int) $skill['id'], ['name' => $skill['name']]);

        Session::flash('ok', 'Habilidad eliminada.');
        Http::redirect('/dashboard/skills');
    }

    public function skillStatus(array $args): void
    {
        Csrf::verify();
        $skill = Skill::find((int) ($args['id'] ?? 0));
        if ($skill === null) {
            $this->missing('Esa habilidad no existe.');
            return;
        }
        Auth::requireOwnership($skill['user_id'] !== null ? (int) $skill['user_id'] : null);

        // Estado y visibilidad se envían por separado o juntos: lo que no venga
        // en el formulario se queda como está.
        $to  = Http::input('status');
        $vis = Http::input('visibility');

        $cambiaEstado      = $to !== '';
        $cambiaVisibilidad = $vis !== '';

        if (!$cambiaEstado && !$cambiaVisibilidad) {
            Session::flash('error', 'No indicaste ningún cambio.');
            Http::back('/dashboard/skills');
        }
        if ($cambiaEstado && !in_array($to, ['draft', 'published', 'archived'], true)) {
            Session::flash('error', 'Estado no válido.');
            Http::back('/dashboard/skills');
        }
        if ($cambiaVisibilidad && !Visibility::isValid($vis)) {
            Session::flash('error', 'Visibilidad no válida.');
            Http::back('/dashboard/skills');
        }

        $update = [];
        $partes = [];

        if ($cambiaEstado) {
            $update['status'] = $to;
            if ($to === 'published' && empty($skill['published_at'])) {
                $update['published_at'] = date('Y-m-d H:i:s');
            }
            $partes[] = $to === 'published' ? 'publicada' : 'estado ' . Skill::statusLabel($to);
        }
        if ($cambiaVisibilidad) {
            $update['visibility'] = $vis;
            $partes[] = 'visibilidad ' . mb_strtolower(Visibility::label($vis));
        }

        Database::update('skills', $update, 'id = :id', ['id' => (int) $skill['id']]);

        Audit::log(
            $cambiaEstado && $to === 'published' ? 'skill_published' : 'skill_updated',
            'skill',
            (int) $skill['id'],
            array_filter(['estado' => $cambiaEstado ? $to : null, 'visibilidad' => $cambiaVisibilidad ? $vis : null])
        );
        Session::flash('ok', 'Habilidad actualizada: ' . implode(' y ', $partes) . '.');
        Http::back('/dashboard/skills');
    }

    // ---------------------------------------------------------- Agentes
    public function agents(): void
    {
        $this->view('dashboard/agents', [
            'agents' => Agent::forUser((int) Auth::id()),
        ], 'Mis agentes', 'layouts/panel');
    }

    public function agentForm(array $args = []): void
    {
        $agent  = null;
        $linked = [];
        $cats   = [];
        if (!empty($args['id'])) {
            $agent = Agent::find((int) $args['id']);
            if ($agent === null) {
                $this->missing('Ese agente no existe.');
                return;
            }
            Auth::requireOwnership($agent['user_id'] !== null ? (int) $agent['user_id'] : null);
            $linked = Agent::skillIds((int) $agent['id']);
            $cats   = Agent::categoryIds((int) $agent['id']);
        }

        // Puede enlazar sus propias habilidades y cualquiera publicada.
        $available = Database::all(
            "SELECT s.id, s.name, s.slug, s.short_description, s.version, s.status, s.user_id
             FROM skills s
             WHERE s.user_id = :mine OR (s.status = 'published' AND s.visibility = 'public')
             ORDER BY (s.user_id = :mine_order) DESC, s.name ASC LIMIT 300",
            ['mine' => (int) Auth::id(), 'mine_order' => (int) Auth::id()]
        );

        $this->view('dashboard/agent-form', [
            'agent'      => $agent,
            'linked'     => $linked,
            'cats'       => $cats,
            'available'  => $available,
            'categories' => Category::active(),
            'compat'     => Config::compatibilityOptions(),
            'errors'     => $this->takeErrors(),
        ], $agent ? 'Editar agente' : 'Nuevo agente', 'layouts/panel');
    }

    public function agentStore(array $args = []): void
    {
        Csrf::verify();

        $existing = null;
        if (!empty($args['id'])) {
            $existing = Agent::find((int) $args['id']);
            if ($existing === null) {
                $this->missing('Ese agente no existe.');
                return;
            }
            Auth::requireOwnership($existing['user_id'] !== null ? (int) $existing['user_id'] : null);
        }

        $data = $this->validateAgent($existing);
        if ($data === null) {
            return;
        }

        $action   = Http::input('action', 'draft');
        $skillIds = array_map('intval', Http::inputArray('skills'));

        if ($existing === null) {
            $data['user_id']      = (int) Auth::id();
            $data['slug']         = Agent::uniqueSlug($data['name']);
            $data['status']       = $action === 'publish' ? 'published' : 'draft';
            $data['published_at'] = $action === 'publish' ? date('Y-m-d H:i:s') : null;

            $id = Database::insert('agents', $data);
            Agent::syncSkills($id, $skillIds, array_map('intval', Http::inputArray('required')));
            Agent::syncCategories($id, $this->categoriasAgente);
            Audit::log('agent_created', 'agent', $id, ['name' => $data['name'], 'skills' => count($skillIds)]);

            Session::flash('ok', $action === 'publish'
                ? 'Agente publicado. Sus reglas ya se descargan en .md y sus habilidades en .json.'
                : 'Borrador del agente guardado.');
            Http::redirect('/dashboard/agents/' . $id . '/edit');
        }

        $id = (int) $existing['id'];
        if ($existing['status'] !== 'published' && $data['name'] !== $existing['name']) {
            $data['slug'] = Agent::uniqueSlug($data['name'], $id);
        }
        if ($action === 'publish') {
            $data['status'] = 'published';
            if (empty($existing['published_at'])) {
                $data['published_at'] = date('Y-m-d H:i:s');
            }
        } elseif ($action === 'unpublish') {
            $data['status'] = 'draft';
        }

        Database::update('agents', $data, 'id = :id', ['id' => $id]);
        Agent::syncSkills($id, $skillIds, array_map('intval', Http::inputArray('required')));
        Agent::syncCategories($id, $this->categoriasAgente);
        Audit::log('agent_updated', 'agent', $id, ['name' => $data['name'], 'skills' => count($skillIds)]);

        Session::flash('ok', 'Agente actualizado.');
        Http::redirect('/dashboard/agents/' . $id . '/edit');
    }

    public function agentDelete(array $args): void
    {
        Csrf::verify();
        $agent = Agent::find((int) ($args['id'] ?? 0));
        if ($agent === null) {
            $this->missing('Ese agente no existe.');
            return;
        }
        Auth::requireOwnership($agent['user_id'] !== null ? (int) $agent['user_id'] : null);

        Database::run('DELETE FROM agents WHERE id = :id', ['id' => (int) $agent['id']]);
        Audit::log('agent_deleted', 'agent', (int) $agent['id'], ['name' => $agent['name']]);

        Session::flash('ok', 'Agente eliminado.');
        Http::redirect('/dashboard/agents');
    }

    public function agentStatus(array $args): void
    {
        Csrf::verify();
        $agent = Agent::find((int) ($args['id'] ?? 0));
        if ($agent === null) {
            $this->missing('Ese agente no existe.');
            return;
        }
        Auth::requireOwnership($agent['user_id'] !== null ? (int) $agent['user_id'] : null);

        $to  = Http::input('status');
        $vis = Http::input('visibility');

        $cambiaEstado      = $to !== '';
        $cambiaVisibilidad = $vis !== '';

        if (!$cambiaEstado && !$cambiaVisibilidad) {
            Session::flash('error', 'No indicaste ningún cambio.');
            Http::back('/dashboard/agents');
        }
        if ($cambiaEstado && !in_array($to, ['draft', 'published', 'archived'], true)) {
            Session::flash('error', 'Estado no válido.');
            Http::back('/dashboard/agents');
        }
        if ($cambiaVisibilidad && !Visibility::isValid($vis)) {
            Session::flash('error', 'Visibilidad no válida.');
            Http::back('/dashboard/agents');
        }

        $update = [];
        $partes = [];

        if ($cambiaEstado) {
            $update['status'] = $to;
            if ($to === 'published' && empty($agent['published_at'])) {
                $update['published_at'] = date('Y-m-d H:i:s');
            }
            $partes[] = $to === 'published' ? 'publicado' : 'estado ' . Skill::statusLabel($to);
        }
        if ($cambiaVisibilidad) {
            $update['visibility'] = $vis;
            $partes[] = 'visibilidad ' . mb_strtolower(Visibility::label($vis));
        }

        Database::update('agents', $update, 'id = :id', ['id' => (int) $agent['id']]);

        Audit::log(
            $cambiaEstado && $to === 'published' ? 'agent_published' : 'agent_updated',
            'agent',
            (int) $agent['id'],
            array_filter(['estado' => $cambiaEstado ? $to : null, 'visibilidad' => $cambiaVisibilidad ? $vis : null])
        );
        Session::flash('ok', 'Agente actualizado: ' . implode(' y ', $partes) . '.');
        Http::back('/dashboard/agents');
    }

    // -------------------------------------------------------- Favoritos
    public function favorites(): void
    {
        $uid = (int) Auth::id();

        $skills = Database::all(
            'SELECT ' . Skill::SELECT . '
             FROM favorites f
             JOIN skills s ON s.id = f.skill_id
             LEFT JOIN categories c ON c.id = s.category_id
             LEFT JOIN users u ON u.id = s.user_id
             WHERE f.user_id = :u AND f.skill_id IS NOT NULL
             ORDER BY f.created_at DESC',
            ['u' => $uid]
        );

        $agents = Database::all(
            'SELECT ' . Agent::SELECT . '
             FROM favorites f
             JOIN agents a ON a.id = f.agent_id
             LEFT JOIN categories c ON c.id = a.category_id
             LEFT JOIN users u ON u.id = a.user_id
             WHERE f.user_id = :u AND f.agent_id IS NOT NULL
             ORDER BY f.created_at DESC',
            ['u' => $uid]
        );

        $this->view('dashboard/favorites', [
            'skills' => $skills,
            'agents' => $agents,
        ], 'Mi biblioteca', 'layouts/panel');
    }

    public function favoriteToggle(): void
    {
        Csrf::verify();

        $type = Http::input('type');
        $id   = Http::inputInt('id');
        $uid  = (int) Auth::id();

        if (!in_array($type, ['skill', 'agent'], true) || $id <= 0) {
            Http::json(['ok' => false], 422);
        }

        $column = $type === 'skill' ? 'skill_id' : 'agent_id';
        $exists = Database::first(
            "SELECT id FROM favorites WHERE user_id = :u AND {$column} = :i LIMIT 1",
            ['u' => $uid, 'i' => $id]
        );

        if ($exists !== null) {
            Database::run('DELETE FROM favorites WHERE id = :id', ['id' => (int) $exists['id']]);
            $active = false;
        } else {
            Database::insert('favorites', [
                'user_id'  => $uid,
                'skill_id' => $type === 'skill' ? $id : null,
                'agent_id' => $type === 'agent' ? $id : null,
            ]);
            $active = true;
        }

        if (Http::wantsJson()) {
            Http::json(['ok' => true, 'active' => $active]);
        }

        Library::forget();
        Session::flash('ok', $active ? 'Agregado a tu cuenta.' : 'Quitado de tu cuenta.');
        Http::back('/dashboard/favorites');
    }

    // ----------------------------------------------------------- Cuenta
    public function account(): void
    {
        $this->view('dashboard/account', [
            'errors' => $this->takeErrors(),
        ], 'Mi cuenta', 'layouts/panel');
    }

    public function accountUpdate(): void
    {
        Csrf::verify();

        $user = Auth::user();
        $uid  = (int) $user['id'];

        $name     = Http::input('name');
        $lastname = Http::input('lastname');
        $email    = mb_strtolower(Http::input('email'));
        $bio      = Http::input('bio');
        $current  = (string) ($_POST['current_password'] ?? '');
        $new      = (string) ($_POST['password'] ?? '');
        $confirm  = (string) ($_POST['password_confirm'] ?? '');

        $v = new Validator();
        $v->required('name', $name, 'El nombre')->max('name', $name, 80, 'El nombre');
        $v->required('email', $email, 'El correo')->email('email', $email);
        $v->max('bio', $bio, 280, 'La descripción');

        if (!$v->fails() && User::emailTaken($email, $uid)) {
            $v->add('email', 'Ese correo ya está en uso.');
        }

        $update = [
            'name'     => $name,
            'lastname' => $lastname !== '' ? $lastname : null,
            'email'    => $email,
            'bio'      => $bio !== '' ? $bio : null,
        ];

        // El cambio de contraseña exige confirmar la actual.
        if ($new !== '' || $confirm !== '') {
            $stored = Database::first('SELECT password_hash FROM users WHERE id = :id', ['id' => $uid]);
            if (!password_verify($current, (string) ($stored['password_hash'] ?? ''))) {
                $v->add('current_password', 'La contraseña actual no es correcta.');
            }
            $v->password('password', $new);
            $v->match('password_confirm', $new, $confirm, 'Las contraseñas nuevas no coinciden.');
            if (!$v->fails()) {
                $update['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
            }
        }

        if ($v->fails()) {
            $this->backWithErrors($v->errors());
            return;
        }

        Database::update('users', $update, 'id = :id', ['id' => $uid]);
        Audit::log('user_updated', 'user', $uid, ['self' => true]);

        Session::flash('ok', 'Datos actualizados.');
        Http::redirect('/dashboard/account');
    }

    public function accountDelete(): void
    {
        Csrf::verify();

        $user = Auth::user();
        $uid  = (int) $user['id'];

        $password = (string) ($_POST['password'] ?? '');
        $stored   = Database::first('SELECT password_hash FROM users WHERE id = :id', ['id' => $uid]);

        if (!password_verify($password, (string) ($stored['password_hash'] ?? ''))) {
            $this->backWithErrors(['password' => 'Contraseña incorrecta.'], 'Confirma tu contraseña para dar de baja la cuenta.');
            return;
        }

        // Un administrador no puede quedarse sin relevo.
        if ($user['role'] === 'admin') {
            $admins = (int) Database::scalar("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'active'");
            if ($admins <= 1) {
                Session::flash('error', 'Eres el único administrador activo. Asigna otro antes de darte de baja.');
                Http::redirect('/dashboard/account');
            }
        }

        Audit::log('account_deletion', 'user', $uid, ['requested_by' => 'self'], (string) $user['email']);

        // Se elimina la cuenta; el contenido publicado queda con autor anónimo
        // gracias a ON DELETE SET NULL, que es lo que permite conservar la
        // biblioteca sin conservar datos personales.
        Database::run('DELETE FROM users WHERE id = :id', ['id' => $uid]);

        Auth::logout();
        Session::start();
        Session::flash('ok', 'Tu cuenta y tus datos personales fueron eliminados.');
        Http::redirect('/');
    }

    // ---------------------------------------------------------------------

    /** @return array<string,mixed>|null */
    private function validateSkill(?array $existing): ?array
    {
        $name        = Http::input('name');
        $short       = Http::input('short_description');
        $description = Http::inputRaw('description');
        $categoryId  = Http::inputInt('category_id');
        $tags        = Str::csvFromList(Str::listFromCsv(Http::input('tags')));
        $compat      = array_values(array_intersect(Http::inputArray('compatibility'), Config::compatibilityOptions()));
        $formats     = array_values(array_intersect(Http::inputArray('formats'), Config::formatOptions()));
        $visibility  = Http::input('visibility', 'public');
        $definition  = trim(Http::inputRaw('definition_json'));

        $version = Str::version(Http::input('version'), $existing['version'] ?? '1.0.0');
        $bump    = Http::input('bump');
        if ($existing !== null && in_array($bump, ['major', 'minor', 'patch'], true)) {
            $version = Str::bumpVersion((string) $existing['version'], $bump);
        }

        $v = new Validator();
        $v->required('name', $name, 'El nombre')->max('name', $name, 140, 'El nombre');
        $v->required('short_description', $short, 'La descripción corta')
          ->max('short_description', $short, 255, 'La descripción corta');
        $v->required('description', $description, 'El contenido')
          ->min('description', $description, 40, 'El contenido');
        $v->in('visibility', $visibility, ['public', 'private', 'unlisted'], 'La visibilidad');
        $v->condition('compatibility', $compat !== [], 'Elige al menos una compatibilidad.');

        if ($categoryId > 0 && Category::find($categoryId) === null) {
            $categoryId = 0;
        }
        if ($definition !== '' && json_decode($definition, true) === null) {
            $v->add('definition_json', 'El JSON personalizado no es válido.');
        }

        if ($v->fails()) {
            $this->backWithErrors($v->errors());
            return null;
        }

        return [
            'name'              => $name,
            'short_description' => $short,
            'description'       => $description,
            'category_id'       => $categoryId > 0 ? $categoryId : null,
            'tags'              => $tags !== '' ? $tags : null,
            'compatibility'     => Str::csvFromList($compat),
            'formats'           => Str::csvFromList($formats ?: ['md', 'json', 'txt', 'zip']),
            'version'           => $version,
            'visibility'        => $visibility,
            'definition_json'   => $definition !== '' ? $definition : null,
        ];
    }

    /** @return array<string,mixed>|null */
    private function validateAgent(?array $existing): ?array
    {
        $name       = Http::input('name');
        $role       = Http::input('role_title');
        $short      = Http::input('short_description');
        $rules      = Http::inputRaw('rules_md');
        $prompt     = Http::inputRaw('system_prompt');
        // Un agente puede pertenecer a varias categorías: cuál es la principal
        // lo resuelve Agent::syncCategories por orden de posición.
        $categorias = array_values(array_filter(array_map('intval', Http::inputArray('categories'))));
        $categoryId = $categorias[0] ?? Http::inputInt('category_id');
        $tags       = Str::csvFromList(Str::listFromCsv(Http::input('tags')));
        $compat     = array_values(array_intersect(Http::inputArray('compatibility'), Config::compatibilityOptions()));
        $visibility = Http::input('visibility', 'public');

        $version = Str::version(Http::input('version'), $existing['version'] ?? '1.0.0');
        $bump    = Http::input('bump');
        if ($existing !== null && in_array($bump, ['major', 'minor', 'patch'], true)) {
            $version = Str::bumpVersion((string) $existing['version'], $bump);
        }

        $v = new Validator();
        $v->required('name', $name, 'El nombre')->max('name', $name, 140, 'El nombre');
        $v->max('role_title', $role, 140, 'El rol');
        $v->required('short_description', $short, 'La descripción')
          ->max('short_description', $short, 255, 'La descripción');
        $v->required('rules_md', $rules, 'Las reglas')
          ->min('rules_md', $rules, 60, 'Las reglas');
        $v->max('system_prompt', $prompt, 2000, 'La instrucción de sistema');
        $v->in('visibility', $visibility, ['public', 'private', 'unlisted'], 'La visibilidad');
        $v->condition('compatibility', $compat !== [], 'Elige al menos una compatibilidad.');

        $categorias = array_values(array_filter($categorias, static fn ($c) => Category::find($c) !== null));
        if ($categoryId > 0 && Category::find($categoryId) === null) {
            $categoryId = 0;
        }
        $v->condition('categories', $categorias !== [] || $categoryId > 0, 'Elige al menos una categoría.');

        if ($v->fails()) {
            $this->backWithErrors($v->errors());
            return null;
        }

        $this->categoriasAgente = $categorias ?: ($categoryId > 0 ? [$categoryId] : []);

        return [
            'name'              => $name,
            'role_title'        => $role !== '' ? $role : null,
            'short_description' => $short,
            'rules_md'          => $rules,
            'system_prompt'     => $prompt !== '' ? $prompt : null,
            'category_id'       => $categoryId > 0 ? $categoryId : null,
            'tags'              => $tags !== '' ? $tags : null,
            'compatibility'     => Str::csvFromList($compat),
            'version'           => $version,
            'visibility'        => $visibility,
        ];
    }

    private function missing(string $message): void
    {
        http_response_code(404);
        $this->view('errors/error', [
            'code' => 404, 'title' => 'No encontrado', 'message' => $message,
        ], 'No encontrado');
    }
}
