<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class ServicesController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'ServicesController placeholder']);
    }
}
