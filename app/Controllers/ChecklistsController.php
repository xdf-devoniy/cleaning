<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class ChecklistsController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'ChecklistsController placeholder']);
    }
}
