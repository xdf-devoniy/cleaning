<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Resources\CRM\ClientResource;
use App\Models\CRM\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientMergeController extends Controller
{
    public function __invoke(Request $request, Client $client)
    {
        $data = $request->validate([
            'duplicate_id' => 'required|integer|exists:clients,id',
        ]);

        $duplicate = Client::findOrFail($data['duplicate_id']);

        DB::transaction(function () use ($client, $duplicate) {
            foreach (['contacts', 'addresses', 'notes', 'reminders', 'communicationLogs'] as $relation) {
                $duplicate->{$relation}()->update(['client_id' => $client->id]);
            }

            if (! $client->loyaltyAccount && $duplicate->loyaltyAccount) {
                $duplicate->loyaltyAccount->update(['client_id' => $client->id]);
            }

            $client->update([
                'notes' => trim((string) $client->notes.'\n'.(string) $duplicate->notes),
                'tags' => collect($client->tags ?? [])->merge($duplicate->tags ?? [])->unique()->values()->all(),
            ]);

            $duplicate->delete();
        });

        return ClientResource::make($client->fresh(['contacts', 'addresses', 'loyaltyAccount']));
    }
}
