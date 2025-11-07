<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class OrdersController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'OrdersController placeholder']);
    }
}
