<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Resources\CRM\ClientResource;
use App\Models\CRM\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::query()->with(['contacts', 'addresses', 'loyaltyAccount']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($tag = $request->string('tag')->toString()) {
            $query->whereJsonContains('tags', $tag);
        }

        $clients = $query->paginate(25);

        return ClientResource::collection($clients);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'required|string|max:50',
            'tags' => 'array',
            'preferred_language' => 'nullable|string|max:5',
            'notes' => 'nullable|string',
            'lead_status' => 'required|string',
        ]);

        $client = Client::create($data + [
            'source' => $request->string('source')->toString(),
            'tags' => $data['tags'] ?? [],
        ]);

        return ClientResource::make($client->load(['contacts', 'addresses', 'loyaltyAccount']));
    }

    public function show(Client $client)
    {
        $client->load([
            'contacts',
            'addresses',
            'notes.author',
            'reminders',
            'communicationLogs',
            'loyaltyAccount.transactions',
            'loyaltyAccount.referrals',
            'invoices',
            'jobs',
        ]);

        return ClientResource::make($client);
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'sometimes|string|max:50',
            'tags' => 'array',
            'preferred_language' => 'nullable|string|max:5',
            'notes' => 'nullable|string',
            'lead_status' => 'sometimes|string',
        ]);

        $client->update($data);

        return ClientResource::make($client->fresh(['contacts', 'addresses', 'loyaltyAccount']));
    }

    public function destroy(Client $client)
    {
        $client->delete();

        return response()->json(['status' => 'deleted']);
    }
}
