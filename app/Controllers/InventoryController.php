<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class InventoryController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'InventoryController placeholder']);
    }
}
