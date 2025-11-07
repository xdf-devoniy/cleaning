<?php
namespace App\Core;

use App\Core\View;
use App\Core\{Auth, I18n};

abstract class Controller
{
    protected Auth $auth;
    protected I18n $i18n;

    public function __construct()
    {
        $this->auth = Auth::instance();
        $this->i18n = I18n::instance();
    }

    protected function view(string $template, array $data = []): Response
    {
        $view = new View($template, $data);
        return Response::html($view->render());
    }

    protected function json(array $data, int $status = 200): Response
    {
        return Response::json($data, $status);
    }

    protected function redirect(string $path): Response
    {
        return new Response('', 302, ['Location' => $path]);
    }
}
