<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class AttendanceController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'AttendanceController placeholder']);
    }
}
