<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Generación de los formatos descargables.
 *
 * Regla del producto: las REGLAS de un agente viajan en Markdown (.md) y las
 * HABILIDADES en JSON (.json). Todo lo demás (txt, zip) se deriva de esas dos
 * piezas sin alterar una sola instrucción del contenido original.
 */
final class Packager
{
    public const MIME = [
        'md'   => 'text/markdown; charset=utf-8',
        'txt'  => 'text/plain; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'zip'  => 'application/zip',
    ];

    // -----------------------------------------------------------------
    //  HABILIDADES (skills)
    // -----------------------------------------------------------------

    /** SKILL.md canónico: front-matter + cuerpo tal cual lo escribió el autor. */
    public static function skillMarkdown(array $skill): string
    {
        $compat = Str::listFromCsv($skill['compatibility'] ?? '');
        $tags   = Str::listFromCsv($skill['tags'] ?? '');

        $fm  = "---\n";
        $fm .= 'name: ' . $skill['slug'] . "\n";
        $fm .= 'title: ' . self::yamlValue((string) $skill['name']) . "\n";
        $fm .= 'description: ' . self::yamlValue((string) $skill['short_description']) . "\n";
        $fm .= 'version: ' . ($skill['version'] ?? '1.0.0') . "\n";
        $fm .= 'author: ' . self::yamlValue(self::authorName($skill)) . "\n";
        if (!empty($skill['category_name'])) {
            $fm .= 'category: ' . self::yamlValue((string) $skill['category_name']) . "\n";
        }
        $fm .= 'updated: ' . date('Y-m-d', strtotime((string) ($skill['updated_at'] ?? 'now'))) . "\n";
        if ($tags) {
            $fm .= "tags:\n";
            foreach ($tags as $t) { $fm .= '  - ' . self::yamlValue($t) . "\n"; }
        }
        $fm .= "compatibility:\n";
        foreach ($compat ?: ['OpenClaw'] as $c) { $fm .= '  - ' . self::yamlValue($c) . "\n"; }
        $fm .= 'source: ' . Config::absUrl('/skills/' . $skill['slug']) . "\n";
        $fm .= "---\n\n";

        $body = trim((string) ($skill['description'] ?? ''));
        // Si el cuerpo ya trae su propio front-matter, se respeta el del autor.
        [$existing, $clean] = Markdown::splitFrontMatter($body);
        if ($existing !== []) {
            $body = $clean;
        }

        if ($body === '') {
            $body = '# ' . $skill['name'] . "\n\n" . $skill['short_description'] . "\n";
        } elseif (!preg_match('/^#\s/m', $body)) {
            $body = '# ' . $skill['name'] . "\n\n" . $body;
        }

        return $fm . $body . "\n";
    }

    /** Versión en texto plano: mismo contenido, sin sintaxis Markdown. */
    public static function skillText(array $skill): string
    {
        $md   = self::skillMarkdown($skill);
        [, $body] = Markdown::splitFrontMatter($md);

        $head  = str_repeat('=', 70) . "\n";
        $head .= mb_strtoupper((string) $skill['name']) . "\n";
        $head .= str_repeat('=', 70) . "\n";
        $head .= 'Version:       ' . ($skill['version'] ?? '1.0.0') . "\n";
        $head .= 'Autor:         ' . self::authorName($skill) . "\n";
        $head .= 'Categoria:     ' . ($skill['category_name'] ?? 'Sin categoria') . "\n";
        $head .= 'Compatible:    ' . ($skill['compatibility'] ?: 'OpenClaw') . "\n";
        $head .= 'Actualizado:   ' . date('d/m/Y', strtotime((string) ($skill['updated_at'] ?? 'now'))) . "\n";
        $head .= 'Fuente:        ' . Config::absUrl('/skills/' . $skill['slug']) . "\n";
        $head .= str_repeat('=', 70) . "\n\n";

        return $head . self::plainify($body) . "\n";
    }

    /**
     * Definición JSON de la habilidad — este es el formato que consume el agente.
     * Si el autor cargó su propio JSON, ese manda; si no, se deriva del Markdown.
     */
    public static function skillJson(array $skill, bool $pretty = true): string
    {
        $data = self::skillArray($skill);
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | ($pretty ? JSON_PRETTY_PRINT : 0);
        return (string) json_encode($data, $flags);
    }

    /** @return array<string,mixed> */
    public static function skillArray(array $skill): array
    {
        $body = trim((string) ($skill['description'] ?? ''));
        [, $cleanBody] = Markdown::splitFrontMatter($body);
        $sections = Markdown::sections($cleanBody);

        $pick = static function (array $sections, array $names): ?string {
            foreach ($sections as $title => $content) {
                foreach ($names as $n) {
                    if (mb_strtolower(trim($title)) === mb_strtolower($n)) {
                        return trim($content);
                    }
                }
            }
            return null;
        };
        $asList = static function (?string $text): array {
            if ($text === null || trim($text) === '') {
                return [];
            }
            $items = [];
            foreach (explode("\n", $text) as $line) {
                if (preg_match('/^\s*(?:[-*+]|\d+[.)])\s+(.+)$/', $line, $m)) {
                    $items[] = trim($m[1]);
                }
            }
            return $items;
        };

        $objective    = $pick($sections, ['Objetivo', 'Objective', 'Proposito', 'Propósito']);
        $instructions = $pick($sections, ['Instrucciones', 'Instructions']);
        $workflow     = $pick($sections, ['Workflow', 'Flujo', 'Flujo de trabajo', 'Proceso']);
        $rules        = $pick($sections, ['Reglas', 'Rules', 'Restricciones']);
        $inputs       = $pick($sections, ['Inputs', 'Entradas']);
        $outputs      = $pick($sections, ['Outputs', 'Salidas']);
        $examples     = $pick($sections, ['Ejemplos', 'Examples']);

        $definition = [
            'schema'        => 'forja/skill@1',
            'name'          => $skill['slug'],
            'title'         => $skill['name'],
            'description'   => $skill['short_description'],
            'version'       => $skill['version'] ?? '1.0.0',
            'author'        => self::authorName($skill),
            'category'      => $skill['category_name'] ?? null,
            'tags'          => Str::listFromCsv($skill['tags'] ?? ''),
            'compatibility' => Str::listFromCsv($skill['compatibility'] ?? '') ?: ['OpenClaw'],
            'updated_at'    => date('c', strtotime((string) ($skill['updated_at'] ?? 'now'))),
            'source'        => Config::absUrl('/skills/' . $skill['slug']),
            'objective'     => $objective,
            'instructions'  => $instructions,
            'workflow'      => $asList($workflow) ?: ($workflow !== null ? [$workflow] : []),
            'rules'         => $asList($rules) ?: ($rules !== null ? [$rules] : []),
            'inputs'        => $asList($inputs) ?: ($inputs !== null ? [$inputs] : []),
            'outputs'       => $asList($outputs) ?: ($outputs !== null ? [$outputs] : []),
            'examples'      => $examples !== null ? [$examples] : [],
            'prompt'        => trim($cleanBody),
        ];

        // Override manual del autor: se fusiona encima de lo derivado.
        $custom = trim((string) ($skill['definition_json'] ?? ''));
        if ($custom !== '') {
            $parsed = json_decode($custom, true);
            if (is_array($parsed)) {
                $definition = array_merge($definition, $parsed);
            }
        }

        return $definition;
    }

    /** Paquete ZIP de una sola habilidad. */
    public static function skillZip(array $skill, array $formats): string
    {
        $dir = $skill['slug'];
        $zip = new Zip();
        $ts  = strtotime((string) ($skill['updated_at'] ?? 'now'));

        $zip->add($dir . '/SKILL.md', self::skillMarkdown($skill), $ts);
        $zip->add($dir . '/skill.json', self::skillJson($skill), $ts);
        if (in_array('txt', $formats, true)) {
            $zip->add($dir . '/prompt.txt', self::skillText($skill), $ts);
        }
        $zip->add($dir . '/README.md', self::skillReadme($skill), $ts);
        $zip->add($dir . '/metadata.json', (string) json_encode([
            'package'      => $skill['slug'],
            'type'         => 'skill',
            'version'      => $skill['version'] ?? '1.0.0',
            'generated_at' => date('c'),
            'generated_by' => Config::appName(),
            'source'       => Config::absUrl('/skills/' . $skill['slug']),
            'files'        => ['SKILL.md', 'skill.json', 'prompt.txt', 'README.md'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $ts);

        return $zip->build();
    }

    private static function skillReadme(array $skill): string
    {
        $url = Config::absUrl('/skills/' . $skill['slug']);
        return "# {$skill['name']}\n\n"
            . $skill['short_description'] . "\n\n"
            . "| | |\n|---|---|\n"
            . "| Versión | `" . ($skill['version'] ?? '1.0.0') . "` |\n"
            . "| Autor | " . self::authorName($skill) . " |\n"
            . "| Compatibilidad | " . ($skill['compatibility'] ?: 'OpenClaw') . " |\n"
            . "| Ficha | {$url} |\n\n"
            . "## Contenido del paquete\n\n"
            . "- `SKILL.md` — la habilidad en Markdown, lista para agentes que leen reglas en texto.\n"
            . "- `skill.json` — la misma habilidad como definición estructurada.\n"
            . "- `prompt.txt` — versión en texto plano para pegar en cualquier chat.\n\n"
            . "## Instalación en OpenClaw\n\n"
            . "1. Copia la carpeta dentro de `skills/` en tu proyecto.\n"
            . "2. Reinicia el agente para que registre la habilidad.\n"
            . "3. Verifica que aparece en el listado de habilidades disponibles.\n\n"
            . "Guía completa: " . Config::absUrl('/docs/openclaw') . "\n";
    }

    // -----------------------------------------------------------------
    //  AGENTES
    // -----------------------------------------------------------------

    /** Archivo de reglas del agente (AGENT.md) — el entregable en Markdown. */
    public static function agentMarkdown(array $agent, array $skills = []): string
    {
        $compat = Str::listFromCsv($agent['compatibility'] ?? '') ?: ['OpenClaw'];
        $tags   = Str::listFromCsv($agent['tags'] ?? '');

        $fm  = "---\n";
        $fm .= 'name: ' . $agent['slug'] . "\n";
        $fm .= 'title: ' . self::yamlValue((string) $agent['name']) . "\n";
        if (!empty($agent['role_title'])) {
            $fm .= 'role: ' . self::yamlValue((string) $agent['role_title']) . "\n";
        }
        $fm .= 'description: ' . self::yamlValue((string) $agent['short_description']) . "\n";
        $fm .= 'version: ' . ($agent['version'] ?? '1.0.0') . "\n";
        $fm .= 'author: ' . self::yamlValue(self::authorName($agent)) . "\n";
        $fm .= 'updated: ' . date('Y-m-d', strtotime((string) ($agent['updated_at'] ?? 'now'))) . "\n";
        if ($tags) {
            $fm .= "tags:\n";
            foreach ($tags as $t) { $fm .= '  - ' . self::yamlValue($t) . "\n"; }
        }
        $fm .= "compatibility:\n";
        foreach ($compat as $c) { $fm .= '  - ' . self::yamlValue($c) . "\n"; }
        if ($skills) {
            $fm .= "skills:\n";
            foreach ($skills as $s) { $fm .= '  - ' . $s['slug'] . "\n"; }
        }
        $fm .= 'source: ' . Config::absUrl('/agents/' . $agent['slug']) . "\n";
        $fm .= "---\n\n";

        $body = trim((string) ($agent['rules_md'] ?? ''));
        [$existing, $clean] = Markdown::splitFrontMatter($body);
        if ($existing !== []) {
            $body = $clean;
        }
        if (!preg_match('/^#\s/m', $body)) {
            $body = '# ' . $agent['name'] . "\n\n" . $body;
        }

        if ($skills) {
            $body .= "\n\n## Habilidades incluidas\n\n";
            foreach ($skills as $s) {
                $body .= '- **' . $s['name'] . "** (`skills/" . $s['slug'] . ".json`) — " . $s['short_description'] . "\n";
            }
        }

        return $fm . $body . "\n";
    }

    /** @return array<string,mixed> */
    public static function agentArray(array $agent, array $skills = []): array
    {
        return [
            'schema'        => 'forja/agent@1',
            'name'          => $agent['slug'],
            'title'         => $agent['name'],
            'role'          => $agent['role_title'] ?? null,
            'description'   => $agent['short_description'],
            'version'       => $agent['version'] ?? '1.0.0',
            'author'        => self::authorName($agent),
            'category'      => $agent['category_name'] ?? null,
            'tags'          => Str::listFromCsv($agent['tags'] ?? ''),
            'compatibility' => Str::listFromCsv($agent['compatibility'] ?? '') ?: ['OpenClaw'],
            'updated_at'    => date('c', strtotime((string) ($agent['updated_at'] ?? 'now'))),
            'source'        => Config::absUrl('/agents/' . $agent['slug']),
            'rules_file'    => 'AGENT.md',
            'system_prompt' => $agent['system_prompt'] ?? null,
            'skills'        => array_map(static fn (array $s) => [
                'name'    => $s['slug'],
                'title'   => $s['name'],
                'version' => $s['version'] ?? '1.0.0',
                'file'    => 'skills/' . $s['slug'] . '.json',
                'source'  => Config::absUrl('/skills/' . $s['slug']),
            ], array_values($skills)),
        ];
    }

    public static function agentJson(array $agent, array $skills = []): string
    {
        return (string) json_encode(
            self::agentArray($agent, $skills),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }

    /**
     * Paquete completo del agente: reglas en .md + una habilidad .json por archivo.
     * @param array<int,array<string,mixed>> $skills
     */
    public static function agentZip(array $agent, array $skills): string
    {
        $dir = $agent['slug'];
        $ts  = strtotime((string) ($agent['updated_at'] ?? 'now'));
        $zip = new Zip();

        $zip->add($dir . '/AGENT.md', self::agentMarkdown($agent, $skills), $ts);
        $zip->add($dir . '/agent.json', self::agentJson($agent, $skills), $ts);

        foreach ($skills as $s) {
            $zip->add($dir . '/skills/' . $s['slug'] . '.json', self::skillJson($s), $ts);
            $zip->add($dir . '/skills/' . $s['slug'] . '.md', self::skillMarkdown($s), $ts);
        }

        $zip->add($dir . '/README.md', self::agentReadme($agent, $skills), $ts);
        $zip->add($dir . '/manifest.json', (string) json_encode([
            'package'      => $agent['slug'],
            'type'         => 'agent',
            'version'      => $agent['version'] ?? '1.0.0',
            'rules'        => 'AGENT.md',
            'skills'       => array_map(static fn ($s) => 'skills/' . $s['slug'] . '.json', $skills),
            'generated_at' => date('c'),
            'generated_by' => Config::appName(),
            'source'       => Config::absUrl('/agents/' . $agent['slug']),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $ts);

        return $zip->build();
    }

    private static function agentReadme(array $agent, array $skills): string
    {
        $out  = '# ' . $agent['name'] . "\n\n";
        if (!empty($agent['role_title'])) {
            $out .= '**' . $agent['role_title'] . "**\n\n";
        }
        $out .= $agent['short_description'] . "\n\n";
        $out .= "## Qué trae este paquete\n\n";
        $out .= "| Archivo | Qué es |\n|---|---|\n";
        $out .= "| `AGENT.md` | Las reglas del agente en Markdown. Es el archivo que lee el modelo. |\n";
        $out .= "| `agent.json` | Manifiesto: metadatos y lista de habilidades. |\n";
        $out .= "| `skills/*.json` | Una habilidad por archivo, en formato estructurado. |\n";
        $out .= "| `skills/*.md` | La misma habilidad en Markdown, por si tu agente lee texto. |\n\n";

        if ($skills) {
            $out .= "## Habilidades (" . count($skills) . ")\n\n";
            foreach ($skills as $s) {
                $out .= '- **' . $s['name'] . '** `v' . ($s['version'] ?? '1.0.0') . '` — ' . $s['short_description'] . "\n";
            }
            $out .= "\n";
        }

        $out .= "## Puesta en marcha\n\n";
        $out .= "1. Descomprime la carpeta en tu proyecto.\n";
        $out .= "2. Carga `AGENT.md` como reglas del agente.\n";
        $out .= "3. Registra los archivos de `skills/` como habilidades disponibles.\n";
        $out .= "4. Quita del manifiesto las habilidades que no vayas a usar.\n\n";
        $out .= 'Documentación: ' . Config::absUrl('/docs/openclaw') . "\n";
        return $out;
    }

    // -----------------------------------------------------------------
    //  SELECCIÓN A MEDIDA (el carrito de la tienda)
    // -----------------------------------------------------------------

    /**
     * Paquete armado por el usuario: agentes elegidos + habilidades sueltas.
     * @param array<int,array<string,mixed>> $agents  cada uno con clave 'skills'
     * @param array<int,array<string,mixed>> $skills  habilidades sueltas
     */
    public static function customPack(array $agents, array $skills): string
    {
        $zip  = new Zip();
        $ts   = time();
        $root = 'forja-pack';

        $index = [
            'schema'       => 'forja/pack@1',
            'generated_at' => date('c'),
            'generated_by' => Config::appName(),
            'source'       => Config::absUrl('/constructor'),
            'agents'       => [],
            'skills'       => [],
        ];

        foreach ($agents as $agent) {
            $agentSkills = $agent['skills'] ?? [];
            $base = $root . '/agents/' . $agent['slug'];
            $zip->add($base . '/AGENT.md', self::agentMarkdown($agent, $agentSkills), $ts);
            $zip->add($base . '/agent.json', self::agentJson($agent, $agentSkills), $ts);
            foreach ($agentSkills as $s) {
                $zip->add($base . '/skills/' . $s['slug'] . '.json', self::skillJson($s), $ts);
                $zip->add($base . '/skills/' . $s['slug'] . '.md', self::skillMarkdown($s), $ts);
            }
            $index['agents'][] = [
                'name'    => $agent['slug'],
                'title'   => $agent['name'],
                'version' => $agent['version'] ?? '1.0.0',
                'rules'   => 'agents/' . $agent['slug'] . '/AGENT.md',
                'skills'  => array_map(static fn ($s) => 'agents/' . $agent['slug'] . '/skills/' . $s['slug'] . '.json', $agentSkills),
            ];
        }

        foreach ($skills as $s) {
            $zip->add($root . '/skills/' . $s['slug'] . '.json', self::skillJson($s), $ts);
            $zip->add($root . '/skills/' . $s['slug'] . '.md', self::skillMarkdown($s), $ts);
            $index['skills'][] = [
                'name'    => $s['slug'],
                'title'   => $s['name'],
                'version' => $s['version'] ?? '1.0.0',
                'json'    => 'skills/' . $s['slug'] . '.json',
                'md'      => 'skills/' . $s['slug'] . '.md',
            ];
        }

        $zip->add($root . '/pack.json', (string) json_encode($index, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $ts);
        $zip->add($root . '/README.md', self::packReadme($agents, $skills), $ts);

        return $zip->build();
    }

    private static function packReadme(array $agents, array $skills): string
    {
        $out  = "# Paquete a medida\n\n";
        $out .= 'Generado el ' . date('d/m/Y H:i') . " desde " . Config::appName() . ".\n\n";

        if ($agents) {
            $out .= "## Agentes (" . count($agents) . ")\n\n";
            foreach ($agents as $a) {
                $n = count($a['skills'] ?? []);
                $out .= '- **' . $a['name'] . "** — reglas en `agents/" . $a['slug'] . "/AGENT.md`, {$n} habilidad(es) en `skills/`.\n";
            }
            $out .= "\n";
        }
        if ($skills) {
            $out .= "## Habilidades sueltas (" . count($skills) . ")\n\n";
            foreach ($skills as $s) {
                $out .= '- **' . $s['name'] . "** — `skills/" . $s['slug'] . ".json`\n";
            }
            $out .= "\n";
        }

        $out .= "## Cómo se usa\n\n";
        $out .= "Las reglas van en Markdown y las habilidades en JSON. Carga el `AGENT.md`\n";
        $out .= "del agente que elegiste y registra los `.json` de `skills/` como habilidades.\n\n";
        $out .= 'Guía: ' . Config::absUrl('/docs/openclaw') . "\n";
        return $out;
    }

    // -----------------------------------------------------------------

    public static function authorName(array $row): string
    {
        if (!empty($row['author_display'])) {
            return (string) $row['author_display'];
        }
        if (!empty($row['author_name'])) {
            return (string) $row['author_name'];
        }
        return (string) Config::appName();
    }

    private static function yamlValue(string $v): string
    {
        $v = str_replace(["\n", "\r"], ' ', trim($v));
        if ($v === '' || preg_match('/[:#\-\[\]{}&*!|>%@`"\']/', $v) || is_numeric($v)) {
            return '"' . str_replace('"', '\"', $v) . '"';
        }
        return $v;
    }

    /** Convierte Markdown a texto legible sin marcas. */
    private static function plainify(string $md): string
    {
        $lines = explode("\n", $md);
        $out   = [];
        $fence = false;

        foreach ($lines as $line) {
            if (preg_match('/^\s*```/', $line)) {
                $fence = !$fence;
                $out[] = $fence ? '    ---' : '    ---';
                continue;
            }
            if ($fence) {
                $out[] = '    ' . $line;
                continue;
            }
            if (preg_match('/^(#{1,6})\s+(.+)$/', trim($line), $m)) {
                $title = strtoupper(trim($m[2]));
                $out[] = '';
                $out[] = $title;
                $out[] = str_repeat(strlen($m[1]) <= 2 ? '-' : '.', min(70, mb_strlen($title)));
                continue;
            }
            $t = $line;
            $t = preg_replace('/\*\*\*(.+?)\*\*\*/', '$1', $t) ?? $t;
            $t = preg_replace('/\*\*(.+?)\*\*/', '$1', $t) ?? $t;
            $t = preg_replace('/(?<!\w)\*(.+?)\*(?!\w)/', '$1', $t) ?? $t;
            $t = preg_replace('/`([^`]+)`/', '$1', $t) ?? $t;
            $t = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '$1 ($2)', $t) ?? $t;
            $t = preg_replace('/^\s*[-*+]\s+/', '  - ', $t) ?? $t;
            $t = preg_replace('/^\s*>\s?/', '  | ', $t) ?? $t;
            $out[] = rtrim($t);
        }

        return trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $out)) ?? '');
    }
}
