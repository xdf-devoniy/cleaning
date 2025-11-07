<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class ExpensesController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'ExpensesController placeholder']);
    }
}
