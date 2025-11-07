<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class QuotesController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'QuotesController placeholder']);
    }
}
