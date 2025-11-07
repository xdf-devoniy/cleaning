<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class LeadsController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'LeadsController placeholder']);
    }
}
