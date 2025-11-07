<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class StaffController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'StaffController placeholder']);
    }
}
