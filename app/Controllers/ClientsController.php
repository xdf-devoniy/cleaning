<?php
namespace App\Controllers;

use App\Core\{Controller, Response, Validator};
use App\Models\{Address, Client};

class ClientsController extends Controller
{
    public function index(): Response
    {
        if ($response = $this->requireAuth('clients.manage')) {
            return $response;
        }

        $clients = Client::withStats();
        $addresses = Address::allGroupedByClient();

        return $this->view('clients/index', [
            'clients' => $clients,
            'addresses' => $addresses,
        ]);
    }

    public function store(): Response
    {
        if ($response = $this->requireAuth('clients.manage')) {
            return $response;
        }

        $validator = new Validator();
        $rules = [
            'name' => 'required',
            'phone' => 'required',
            'email' => 'email',
        ];

        if (!$validator->validate($_POST, $rules)) {
            return $this->redirectWith('/clients', ['error' => 'Ma\'lumotlarni tekshiring']);
        }

        $clientId = Client::create([
            'name' => trim($_POST['name']),
            'status' => $_POST['status'] ?? 'active',
            'phone' => trim($_POST['phone']),
            'email' => trim($_POST['email'] ?? ''),
            'preferred_days' => trim($_POST['preferred_days'] ?? ''),
            'preferred_time' => trim($_POST['preferred_time'] ?? ''),
            'tags' => trim($_POST['tags'] ?? ''),
        ]);

        if (!empty($_POST['address_line'])) {
            Address::create([
                'client_id' => $clientId,
                'label' => $_POST['address_label'] ?? 'Primary',
                'address_line' => $_POST['address_line'],
                'latitude' => $_POST['latitude'] ?? null,
                'longitude' => $_POST['longitude'] ?? null,
            ]);
        }

        return $this->redirectWith('/clients', ['success' => 'Mijoz qo\'shildi']);
    }
}
