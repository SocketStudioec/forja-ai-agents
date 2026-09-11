<?php
declare(strict_types=1);

/**
 * Controlador frontal. Todo el tráfico entra por aquí.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Controllers\AdminController;
use App\Controllers\AgentController;
use App\Controllers\AuthController;
use App\Controllers\BuilderController;
use App\Controllers\DashboardController;
use App\Controllers\DownloadController;
use App\Controllers\PageController;
use App\Controllers\SkillController;
use App\Controllers\SubmissionController;
use App\Core\Config;
use App\Core\Router;
use App\Core\Session;

// ------------------------------------------------- Ruta solicitada, normalizada
$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$base = Config::basePath();
if ($base !== '' && strncmp($uri, $base, strlen($base)) === 0) {
    $uri = substr($uri, strlen($base));
}
$path = '/' . trim(rawurldecode($uri), '/');
if ($path !== '/') {
    $path = rtrim($path, '/');
}
Session::set('__path', $path);

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$r      = new Router();

// ------------------------------------------------------------------ Públicas
$r->get('/',                      [PageController::class, 'home']);
$r->get('/categories',            [PageController::class, 'categories']);
$r->get('/docs/openclaw',         [PageController::class, 'docs']);
$r->get('/privacy',               [PageController::class, 'privacy']);

// Tienda de agentes
$r->get('/agents',                [AgentController::class, 'index']);
$r->get('/agents/{slug}',         [AgentController::class, 'show']);

// Biblioteca de habilidades
$r->get('/skills',                [SkillController::class, 'index']);
$r->get('/skills/{slug}',         [SkillController::class, 'show']);

// Descargas (sin sesión para contenido público)
$r->get('/skills/{slug}/download',  [DownloadController::class, 'skill']);
$r->get('/agents/{slug}/download',  [DownloadController::class, 'agent']);

// Constructor: el usuario arma su propio paquete
$r->get('/builder',               [BuilderController::class, 'index']);
$r->post('/builder/download',     [BuilderController::class, 'download']);

// Envío de contenido sin cuenta
$r->get('/submit',                [SubmissionController::class, 'form']);
$r->post('/submit',               [SubmissionController::class, 'store']);
$r->get('/submit/received',       [SubmissionController::class, 'received']);

// ------------------------------------------------------------------- Sesión
$r->get('/login',                 [AuthController::class, 'loginForm']);
$r->post('/login',                [AuthController::class, 'login']);
$r->get('/register',              [AuthController::class, 'registerForm']);
$r->post('/register',             [AuthController::class, 'register']);
$r->get('/forgot',                [AuthController::class, 'forgotForm']);
$r->post('/forgot',               [AuthController::class, 'forgot']);
$r->get('/reset/{token}',         [AuthController::class, 'resetForm']);
$r->post('/reset/{token}',        [AuthController::class, 'reset']);
$r->post('/logout',               [AuthController::class, 'logout']);

// -------------------------------------------------------- Panel del usuario
$r->get('/dashboard',                       [DashboardController::class, 'index']);
$r->get('/dashboard/skills',                [DashboardController::class, 'skills']);
$r->get('/dashboard/skills/new',            [DashboardController::class, 'skillForm']);
$r->post('/dashboard/skills/new',           [DashboardController::class, 'skillStore']);
$r->get('/dashboard/skills/{id}/edit',      [DashboardController::class, 'skillForm']);
$r->post('/dashboard/skills/{id}/edit',     [DashboardController::class, 'skillStore']);
$r->post('/dashboard/skills/{id}/delete',   [DashboardController::class, 'skillDelete']);
$r->post('/dashboard/skills/{id}/status',   [DashboardController::class, 'skillStatus']);

$r->get('/dashboard/agents',                [DashboardController::class, 'agents']);
$r->get('/dashboard/agents/new',            [DashboardController::class, 'agentForm']);
$r->post('/dashboard/agents/new',           [DashboardController::class, 'agentStore']);
$r->get('/dashboard/agents/{id}/edit',      [DashboardController::class, 'agentForm']);
$r->post('/dashboard/agents/{id}/edit',     [DashboardController::class, 'agentStore']);
$r->post('/dashboard/agents/{id}/delete',   [DashboardController::class, 'agentDelete']);
$r->post('/dashboard/agents/{id}/status',   [DashboardController::class, 'agentStatus']);

$r->get('/dashboard/favorites',             [DashboardController::class, 'favorites']);
$r->post('/dashboard/favorites/toggle',     [DashboardController::class, 'favoriteToggle']);
$r->get('/dashboard/account',               [DashboardController::class, 'account']);
$r->post('/dashboard/account',              [DashboardController::class, 'accountUpdate']);
$r->post('/dashboard/account/delete',       [DashboardController::class, 'accountDelete']);

// --------------------------------------------------------------- Administración
$r->get('/admin',                           [AdminController::class, 'dashboard']);

$r->get('/admin/users',                     [AdminController::class, 'users']);
$r->get('/admin/users/new',                 [AdminController::class, 'userForm']);
$r->post('/admin/users/new',                [AdminController::class, 'userStore']);
$r->get('/admin/users/{id}/edit',           [AdminController::class, 'userForm']);
$r->post('/admin/users/{id}/edit',          [AdminController::class, 'userStore']);
$r->post('/admin/users/{id}/status',        [AdminController::class, 'userStatus']);
$r->post('/admin/users/{id}/delete',        [AdminController::class, 'userDelete']);

$r->get('/admin/skills',                    [AdminController::class, 'skills']);
$r->post('/admin/skills/{id}/status',       [AdminController::class, 'skillStatus']);
$r->post('/admin/skills/{id}/delete',       [AdminController::class, 'skillDelete']);
$r->post('/admin/skills/{id}/feature',      [AdminController::class, 'skillFeature']);

$r->get('/admin/agents',                    [AdminController::class, 'agents']);
$r->post('/admin/agents/{id}/status',       [AdminController::class, 'agentStatus']);
$r->post('/admin/agents/{id}/delete',       [AdminController::class, 'agentDelete']);

$r->get('/admin/submissions',               [AdminController::class, 'submissions']);
$r->get('/admin/submissions/{id}',          [AdminController::class, 'submissionShow']);
$r->post('/admin/submissions/{id}/review',  [AdminController::class, 'submissionReview']);
$r->post('/admin/submissions/{id}/delete',  [AdminController::class, 'submissionDelete']);
$r->get('/admin/submissions/{id}/file',     [AdminController::class, 'submissionFile']);

$r->get('/admin/categories',                [AdminController::class, 'categories']);
$r->post('/admin/categories',               [AdminController::class, 'categoryStore']);
$r->post('/admin/categories/{id}/delete',   [AdminController::class, 'categoryDelete']);

$r->get('/admin/audit',                     [AdminController::class, 'audit']);
$r->get('/admin/downloads',                 [AdminController::class, 'downloads']);

$r->dispatch($method, $path);
