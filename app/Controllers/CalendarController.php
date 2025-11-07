<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class CalendarController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'CalendarController placeholder']);
    }
}
