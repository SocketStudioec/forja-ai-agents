<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Database;
use App\Models\Agent;
use App\Models\Category;
use App\Models\Skill;

final class PageController extends Controller
{
    public function home(): void
    {
        $agents = Agent::featured(3);
        foreach ($agents as &$agent) {
            $agent['skill_preview'] = array_slice(Agent::skills((int) $agent['id']), 0, 4);
        }
        unset($agent);

        $skills = Skill::featured(6);

        $stats = [
            'agents'    => (int) Database::scalar("SELECT COUNT(*) FROM agents WHERE status='published' AND visibility IN ('public','unlisted')"),
            'skills'    => (int) Database::scalar("SELECT COUNT(*) FROM skills WHERE status='published' AND visibility IN ('public','unlisted')"),
            'downloads' => (int) Database::scalar('SELECT COALESCE(SUM(downloads),0) FROM skills')
                         + (int) Database::scalar('SELECT COALESCE(SUM(downloads),0) FROM agents'),
            'authors'   => (int) Database::scalar("SELECT COUNT(DISTINCT user_id) FROM skills WHERE user_id IS NOT NULL AND status='published'"),
        ];

        $this->view('public/home', [
            'agents'     => $agents,
            'skills'     => $skills,
            'categories' => Category::withCounts(),
            'stats'      => $stats,
        ], 'Reglas en Markdown, habilidades en JSON');
    }

    public function categories(): void
    {
        $this->view('public/categories', [
            'categories' => Category::withCounts(),
        ], 'Categorías');
    }

    public function docs(): void
    {
        $this->view('public/docs', [
            'compat' => Config::compatibilityOptions(),
        ], 'Documentación para OpenClaw');
    }

    public function privacy(): void
    {
        $this->view('public/privacy', [], 'Privacidad y tratamiento de datos');
    }
}
