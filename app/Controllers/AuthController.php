<?php
namespace App\Controllers;

use App\Core\{Auth, Controller, Response, Validator};

class AuthController extends Controller
{
    public function showLogin(): Response
    {
        if ($this->auth->check()) {
            return $this->redirect('/');
        }

        return $this->view('auth/login', ['errors' => []]);
    }

    public function login(): Response
    {
        if ($this->auth->check()) {
            return $this->redirect('/');
        }

        $validator = new Validator();
        if (!$validator->validate($_POST, ['email' => 'required|email', 'password' => 'required|min:6'])) {
            return $this->view('auth/login', ['errors' => $validator->errors()]);
        }

        if (Auth::instance()->attempt($_POST['email'], $_POST['password'])) {
            return $this->redirect('/');
        }

        return $this->view('auth/login', ['errors' => ['auth' => ['invalid']]]);
    }

    public function logout(): Response
    {
        Auth::instance()->logout();
        return $this->redirect('/login');
    }
}
