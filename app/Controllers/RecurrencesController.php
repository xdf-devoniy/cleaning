<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class RecurrencesController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'RecurrencesController placeholder']);
    }
}
