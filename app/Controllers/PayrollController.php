<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class PayrollController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'PayrollController placeholder']);
    }
}
