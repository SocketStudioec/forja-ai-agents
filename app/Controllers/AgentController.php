<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;
use App\Core\Http;
use App\Core\Markdown;
use App\Core\Packager;
use App\Models\Agent;
use App\Models\Category;

final class AgentController extends Controller
{
    public function index(): void
    {
        $filters = [
            'q'        => Http::input('q'),
            'category' => Http::input('category'),
            'compat'   => Http::input('compat'),
            'sort'     => Http::input('sort', 'recent'),
            'page'     => Http::inputInt('page', 1),
            'per_page' => 12,
        ];

        $result = Agent::browse($filters);

        // Cada tarjeta muestra las primeras habilidades del agente.
        foreach ($result['items'] as &$item) {
            $item['skill_preview'] = array_slice(Agent::skills((int) $item['id']), 0, 4);
        }
        unset($item);

        $this->view('public/agents', [
            'result'     => $result,
            'filters'    => $filters,
            'categories' => Category::active(),
            'compat'     => Config::compatibilityOptions(),
        ], 'Tienda de agentes');
    }

    public function show(array $args): void
    {
        $agent = Agent::findBySlug($args['slug'] ?? '');

        if ($agent === null || !$this->canSee($agent)) {
            http_response_code(404);
            $this->view('errors/error', [
                'code'    => 404,
                'title'   => 'Agente no encontrado',
                'message' => 'Puede que se haya despublicado o que la dirección esté mal escrita.',
            ], 'No encontrado');
            return;
        }

        Database::run('UPDATE agents SET views = views + 1 WHERE id = :id', ['id' => (int) $agent['id']]);

        $skills = Agent::skills((int) $agent['id']);
        [, $rules] = Markdown::splitFrontMatter((string) $agent['rules_md']);

        $isFavorite = false;
        if (Auth::check()) {
            $isFavorite = Database::first(
                'SELECT id FROM favorites WHERE user_id = :u AND agent_id = :a LIMIT 1',
                ['u' => Auth::id(), 'a' => (int) $agent['id']]
            ) !== null;
        }

        $this->view('public/agent-show', [
            'agent'       => $agent,
            'skills'      => $skills,
            'rulesHtml'   => Markdown::toHtml($rules),
            'rulesRaw'    => Packager::agentMarkdown($agent, $skills),
            'jsonPreview' => Packager::agentJson($agent, $skills),
            'isFavorite'  => $isFavorite,
            'canEdit'     => Auth::ownsOrAdmin($agent['user_id'] !== null ? (int) $agent['user_id'] : null),
        ], (string) $agent['name']);
    }

    private function canSee(array $agent): bool
    {
        if ($agent['status'] === 'published' && in_array($agent['visibility'], ['public', 'unlisted'], true)) {
            return true;
        }
        return Auth::ownsOrAdmin($agent['user_id'] !== null ? (int) $agent['user_id'] : null);
    }
}
