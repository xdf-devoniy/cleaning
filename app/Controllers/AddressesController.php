<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class AddressesController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'AddressesController placeholder']);
    }
}
