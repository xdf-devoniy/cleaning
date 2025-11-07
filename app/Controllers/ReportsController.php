<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class ReportsController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'ReportsController placeholder']);
    }
}
