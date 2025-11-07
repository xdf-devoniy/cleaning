<?php
namespace App\Core;

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
        $data = array_merge([
            'authUser' => $this->auth->user(),
            'flash' => $data['flash'] ?? $this->consumeFlash(),
        ], $data);

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

    protected function redirectWith(string $path, array $flash = []): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['_flash'] = $flash;
        return $this->redirect($path);
    }

    protected function requireAuth(?string $ability = null): ?Response
    {
        if (!$this->auth->check()) {
            return $this->redirect('/login');
        }

        if ($ability !== null && !$this->auth->authorize($ability)) {
            return Response::html('<h1>403 Forbidden</h1>', 403);
        }

        return null;
    }

    protected function consumeFlash(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flash;
    }
}
