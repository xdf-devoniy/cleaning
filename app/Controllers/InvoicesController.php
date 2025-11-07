<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class InvoicesController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'InvoicesController placeholder']);
    }
}
