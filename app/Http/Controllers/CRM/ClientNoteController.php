<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Resources\CRM\ClientNoteResource;
use App\Models\CRM\Client;
use App\Models\CRM\ClientNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientNoteController extends Controller
{
    public function index(Client $client)
    {
        return ClientNoteResource::collection(
            $client->notes()->with('author')->latest()->paginate(20)
        );
    }

    public function store(Request $request, Client $client)
    {
        $data = $request->validate([
            'body' => 'required|string',
            'pinned' => 'boolean',
        ]);

        $note = $client->notes()->create($data + [
            'user_id' => Auth::id(),
        ]);

        return ClientNoteResource::make($note->load('author'));
    }

    public function destroy(ClientNote $note)
    {
        $note->delete();

        return response()->json(['status' => 'deleted']);
    }
}
