<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Resources\CRM\ClientReminderResource;
use App\Models\CRM\Client;
use App\Models\CRM\ClientReminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientReminderController extends Controller
{
    public function index(Client $client)
    {
        return ClientReminderResource::collection(
            $client->reminders()->latest('remind_at')->paginate(20)
        );
    }

    public function store(Request $request, Client $client)
    {
        $data = $request->validate([
            'channel' => 'required|string',
            'message' => 'required|string',
            'remind_at' => 'required|date',
        ]);

        $reminder = $client->reminders()->create($data + [
            'user_id' => Auth::id(),
        ]);

        return ClientReminderResource::make($reminder);
    }

    public function update(Request $request, ClientReminder $reminder)
    {
        $data = $request->validate([
            'channel' => 'sometimes|string',
            'message' => 'sometimes|string',
            'remind_at' => 'sometimes|date',
            'completed_at' => 'nullable|date',
        ]);

        $reminder->update($data);

        return ClientReminderResource::make($reminder);
    }

    public function destroy(ClientReminder $reminder)
    {
        $reminder->delete();

        return response()->json(['status' => 'deleted']);
    }
}
