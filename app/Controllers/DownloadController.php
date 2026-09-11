<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Http;
use App\Core\Packager;
use App\Core\Str;
use App\Models\Agent;
use App\Models\Skill;

final class DownloadController extends Controller
{
    /** Descarga de una habilidad en el formato pedido. */
    public function skill(array $args): void
    {
        $skill = Skill::findBySlug($args['slug'] ?? '');
        if ($skill === null || !$this->visible($skill)) {
            $this->notFound('Esa habilidad ya no está disponible para descarga.');
            return;
        }

        $format  = strtolower(Http::input('format', 'json'));
        $allowed = Str::listFromCsv($skill['formats']) ?: ['md', 'txt', 'json', 'zip'];

        // El JSON y el Markdown son el par base del producto: siempre disponibles.
        $allowed = array_values(array_unique(array_merge($allowed, ['json', 'md'])));

        if (!in_array($format, $allowed, true)) {
            $this->notFound('Ese formato no está habilitado para esta habilidad.');
            return;
        }

        $base = $skill['slug'];
        switch ($format) {
            case 'md':
                $content  = Packager::skillMarkdown($skill);
                $filename = $base . '.md';
                break;
            case 'txt':
                $content  = Packager::skillText($skill);
                $filename = $base . '.txt';
                break;
            case 'zip':
                $content  = Packager::skillZip($skill, $allowed);
                $filename = $base . '.zip';
                break;
            case 'json':
            default:
                $format   = 'json';
                $content  = Packager::skillJson($skill);
                $filename = $base . '.json';
                break;
        }

        Skill::registerDownload((int) $skill['id'], Auth::id(), $format);
        Audit::log('download', 'skill', (int) $skill['id'], ['format' => $format, 'slug' => $skill['slug']]);

        Http::download($filename, $content, Packager::MIME[$format]);
    }

    /**
     * Descarga de un agente.
     *  - md   → AGENT.md, las reglas
     *  - json → agent.json, el manifiesto
     *  - zip  → paquete completo con las habilidades elegidas
     * El parámetro `skills` limita qué habilidades entran en el ZIP.
     */
    public function agent(array $args): void
    {
        $agent = Agent::findBySlug($args['slug'] ?? '');
        if ($agent === null || !$this->visible($agent)) {
            $this->notFound('Ese agente ya no está disponible para descarga.');
            return;
        }

        $all      = Agent::skills((int) $agent['id']);
        $selected = $this->selectSkills($all, Http::input('skills'));

        $format = strtolower(Http::input('format', 'zip'));
        $base   = $agent['slug'];

        switch ($format) {
            case 'md':
                $content  = Packager::agentMarkdown($agent, $selected);
                $filename = $base . '.md';
                break;
            case 'json':
                $content  = Packager::agentJson($agent, $selected);
                $filename = $base . '.json';
                break;
            case 'zip':
            default:
                $format   = 'zip';
                $content  = Packager::agentZip($agent, $selected);
                $filename = $base . '.zip';
                break;
        }

        Agent::registerDownload((int) $agent['id'], Auth::id(), $format);
        Audit::log('download', 'agent', (int) $agent['id'], [
            'format' => $format,
            'slug'   => $agent['slug'],
            'skills' => count($selected),
        ]);

        Http::download($filename, $content, Packager::MIME[$format]);
    }

    /**
     * Filtra las habilidades del agente por los slugs pedidos.
     * Las marcadas como obligatorias entran siempre.
     * @param array<int,array<string,mixed>> $all
     * @return array<int,array<string,mixed>>
     */
    private function selectSkills(array $all, string $csv): array
    {
        $wanted = Str::listFromCsv($csv);
        if (!$wanted) {
            return $all;
        }
        $picked = array_values(array_filter(
            $all,
            static fn (array $s) => in_array($s['slug'], $wanted, true) || (int) ($s['required'] ?? 0) === 1
        ));
        return $picked ?: $all;
    }

    private function visible(array $row): bool
    {
        if ($row['status'] === 'published' && in_array($row['visibility'], ['public', 'unlisted'], true)) {
            return true;
        }
        return Auth::ownsOrAdmin($row['user_id'] !== null ? (int) $row['user_id'] : null);
    }

    private function notFound(string $message): void
    {
        http_response_code(404);
        $this->view('errors/error', [
            'code'    => 404,
            'title'   => 'Descarga no disponible',
            'message' => $message,
        ], 'Descarga no disponible');
    }
}
