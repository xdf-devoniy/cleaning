<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class IntegrationsController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'IntegrationsController placeholder']);
    }
}
