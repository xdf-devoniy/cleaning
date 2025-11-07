<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class SettingsController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'SettingsController placeholder']);
    }
}
