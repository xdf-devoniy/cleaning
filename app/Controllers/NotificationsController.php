<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class NotificationsController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'NotificationsController placeholder']);
    }
}
