<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class AssignmentsController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'AssignmentsController placeholder']);
    }
}
