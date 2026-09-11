<?php
declare(strict_types=1);

namespace App\Core;

final class View
{
    /** @param array<string,mixed> $data */
    public static function render(string $view, array $data = [], string $title = '', string $layout = 'layouts/base'): void
    {
        $file = Config::viewPath($view);
        if (!is_file($file)) {
            throw new \RuntimeException('Vista no encontrada: ' . $view);
        }

        $data['__title']  = $title;
        $data['__view']   = $view;

        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        $content = (string) ob_get_clean();

        $layoutFile = Config::viewPath($layout);
        if (is_file($layoutFile)) {
            require $layoutFile;
            return;
        }
        echo $content;
    }

    /** Renderiza un fragmento y lo devuelve como cadena. */
    public static function partial(string $view, array $data = []): string
    {
        $file = Config::viewPath($view);
        if (!is_file($file)) {
            return '';
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return (string) ob_get_clean();
    }
}
