<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class FinanceController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'FinanceController placeholder']);
    }
}
