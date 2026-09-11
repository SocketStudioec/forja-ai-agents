<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Http;
use App\Core\Packager;
use App\Core\Session;
use App\Core\Str;
use App\Models\Agent;
use App\Models\Skill;

/**
 * El constructor es la caja de la tienda: el visitante elige agentes y
 * habilidades sueltas y se lleva un único ZIP con las reglas en .md y las
 * habilidades en .json.
 */
final class BuilderController extends Controller
{
    public function index(): void
    {
        $agents = Agent::browse(['sort' => 'skills', 'per_page' => 36])['items'];
        foreach ($agents as &$a) {
            $a['skill_list'] = Agent::skills((int) $a['id']);
        }
        unset($a);

        $this->view('public/builder', [
            'agents' => $agents,
            'skills' => Skill::browse(['sort' => 'downloads', 'per_page' => 48])['items'],
        ], 'Arma tu paquete');
    }

    public function download(): void
    {
        Csrf::verify();

        $agentSlugs = Str::listFromCsv(Http::input('agents'));
        $skillSlugs = Str::listFromCsv(Http::input('skills'));

        if (!$agentSlugs && !$skillSlugs) {
            Session::flash('error', 'Elige al menos un agente o una habilidad antes de descargar.');
            Http::redirect('/builder');
        }

        // Tope defensivo: nadie arma un paquete de 500 piezas por accidente.
        $agentSlugs = array_slice($agentSlugs, 0, 20);
        $skillSlugs = array_slice($skillSlugs, 0, 60);

        $agents = $this->loadAgents($agentSlugs);
        $skills = $this->loadSkills($skillSlugs);

        if (!$agents && !$skills) {
            Session::flash('error', 'No pudimos encontrar el contenido seleccionado. Vuelve a intentarlo.');
            Http::redirect('/builder');
        }

        $zip = Packager::customPack($agents, $skills);

        foreach ($agents as $a) {
            Database::insert('downloads', [
                'skill_id' => null, 'agent_id' => (int) $a['id'], 'user_id' => Auth::id(),
                'kind' => 'pack', 'format' => 'zip', 'ip_hash' => Str::ipHash(),
            ]);
            Database::run('UPDATE agents SET downloads = downloads + 1 WHERE id = :id', ['id' => (int) $a['id']]);
        }
        foreach ($skills as $s) {
            Database::insert('downloads', [
                'skill_id' => (int) $s['id'], 'agent_id' => null, 'user_id' => Auth::id(),
                'kind' => 'pack', 'format' => 'zip', 'ip_hash' => Str::ipHash(),
            ]);
            Database::run('UPDATE skills SET downloads = downloads + 1 WHERE id = :id', ['id' => (int) $s['id']]);
        }

        Audit::log('download', 'pack', null, [
            'agents' => count($agents),
            'skills' => count($skills),
        ]);

        Http::download(
            'paquete-' . date('Ymd-His') . '.zip',
            $zip,
            Packager::MIME['zip']
        );
    }

    /** @return array<int,array<string,mixed>> */
    private function loadAgents(array $slugs): array
    {
        $out = [];
        foreach ($slugs as $slug) {
            $agent = Agent::findBySlug($slug);
            if ($agent === null || $agent['status'] !== 'published' || !in_array($agent['visibility'], ['public', 'unlisted'], true)) {
                continue;
            }
            $agent['skills'] = Agent::skills((int) $agent['id']);
            $out[] = $agent;
        }
        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    private function loadSkills(array $slugs): array
    {
        $out = [];
        foreach ($slugs as $slug) {
            $skill = Skill::findBySlug($slug);
            if ($skill === null || $skill['status'] !== 'published' || !in_array($skill['visibility'], ['public', 'unlisted'], true)) {
                continue;
            }
            $out[] = $skill;
        }
        return $out;
    }
}
