<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class ContractsController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'ContractsController placeholder']);
    }
}
