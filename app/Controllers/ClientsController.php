<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class ClientsController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'ClientsController placeholder']);
    }
}
