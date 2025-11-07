<?php
namespace App\Controllers;

use App\Core\{Controller, Response};

class PhotosController extends Controller
{
    public function index(): Response
    {
        return $this->json(['message' => 'PhotosController placeholder']);
    }
}
