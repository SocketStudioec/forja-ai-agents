<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\Http;
use App\Core\Markdown;
use App\Core\Packager;
use App\Core\Str;
use App\Core\Tier;
use App\Core\View;
use App\Models\Category;
use App\Models\Skill;

final class SkillController extends Controller
{
    public function index(): void
    {
        $filters = [
            'q'        => Http::input('q'),
            'category' => Http::input('category'),
            'compat'   => Http::input('compat'),
            'tag'      => Http::input('tag'),
            'tier'     => Http::input('tier'),
            'sort'     => Http::input('sort', 'recent'),
            'page'     => Http::inputInt('page', 1),
            'per_page' => 12,
        ];

        $result = Skill::browse($filters);

        $this->view('public/skills', [
            'result'     => $result,
            'filters'    => $filters,
            'categories' => Category::active(),
            'compat'     => Config::compatibilityOptions(),
        ], 'Habilidades');
    }

    public function show(array $args): void
    {
        $skill = Skill::findBySlug($args['slug'] ?? '');

        if ($skill === null || !$this->canSee($skill)) {
            http_response_code(404);
            $this->view('errors/error', [
                'code'    => 404,
                'title'   => 'Habilidad no encontrada',
                'message' => 'Puede que se haya despublicado o que la dirección esté mal escrita.',
            ], 'No encontrada');
            return;
        }

        Database::run('UPDATE skills SET views = views + 1 WHERE id = :id', ['id' => (int) $skill['id']]);

        $body = (string) ($skill['description'] ?? '');
        [, $clean] = Markdown::splitFrontMatter($body);

        $isFavorite = false;
        if (Auth::check()) {
            $isFavorite = Database::first(
                'SELECT id FROM favorites WHERE user_id = :u AND skill_id = :s LIMIT 1',
                ['u' => Auth::id(), 's' => (int) $skill['id']]
            ) !== null;
        }

        $esDePago     = Tier::isPaid($skill);
        $puedeEditar  = Auth::ownsOrAdmin($skill['user_id'] !== null ? (int) $skill['user_id'] : null);
        $verContenido = !$esDePago || $puedeEditar;

        $this->view('public/skill-show', [
            'skill'      => $skill,
            'esDePago'     => $esDePago,
            'verContenido' => $verContenido,
            'html'       => $verContenido ? Markdown::toHtml($clean) : '',
            'jsonPreview'=> $verContenido ? Packager::skillJson($skill) : '',
            'formats'    => Str::listFromCsv($skill['formats']),
            'related'    => Skill::related($skill, 3),
            'agents'     => Skill::usedByAgents((int) $skill['id']),
            'versions'   => Skill::versions((int) $skill['id'], 6),
            'isFavorite' => $isFavorite,
            'canEdit'    => $puedeEditar,
        ], (string) $skill['name']);
    }

    /** Una skill privada sólo la ve su autor o un administrador. */
    private function canSee(array $skill): bool
    {
        if ($skill['status'] === 'published' && in_array($skill['visibility'], ['public', 'unlisted'], true)) {
            return true;
        }
        return Auth::ownsOrAdmin($skill['user_id'] !== null ? (int) $skill['user_id'] : null);
    }
}
