<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class RatingsController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'RatingsController placeholder']);
    }
}
