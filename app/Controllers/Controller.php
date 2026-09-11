<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Core\View;

abstract class Controller
{
    /** @param array<string,mixed> $data */
    protected function view(string $view, array $data = [], string $title = '', string $layout = 'layouts/base'): void
    {
        $GLOBALS['__old_input'] = Session::oldInput();

        $data['flashes']  = Session::takeFlashes();
        $data['authUser'] = Auth::user();
        $data['isAdmin']  = Auth::isAdmin();
        $data['errors']   = $data['errors'] ?? [];

        View::render($view, $data, $title, $layout);
    }

    /** Vuelve al formulario conservando lo escrito y el motivo del fallo. */
    protected function backWithErrors(array $errors, string $message = 'Revisa los campos marcados.'): void
    {
        Session::flashInput($_POST);
        Session::set('__errors', $errors);
        Session::flash('error', $message);
        \App\Core\Http::back();
    }

    /** @return array<string,string> */
    protected function takeErrors(): array
    {
        $e = Session::get('__errors', []);
        Session::forget('__errors');
        return is_array($e) ? $e : [];
    }
}
