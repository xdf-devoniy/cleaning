<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class PaymentsController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'PaymentsController placeholder']);
    }
}
