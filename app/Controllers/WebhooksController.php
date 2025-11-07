<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class WebhooksController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'WebhooksController placeholder']);
    }
}
