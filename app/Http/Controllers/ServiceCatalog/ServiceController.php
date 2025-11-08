<?php

namespace App\Http\Controllers\ServiceCatalog;

use App\Http\Controllers\Controller;
use App\Models\ServiceCatalog\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    public function index()
    {
        return response()->json(Service::with('priceTiers')->paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'code' => 'nullable|string',
            'base_price' => 'required|numeric',
            'duration_minutes' => 'required|integer',
            'pricing_type' => 'required|string',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'is_active' => 'boolean',
            'price_tiers' => 'array',
        ]);

        $service = DB::transaction(function () use ($data) {
            $service = Service::create($data);

            foreach ($data['price_tiers'] ?? [] as $tier) {
                $service->priceTiers()->create($tier);
            }

            return $service->load('priceTiers');
        });

        return response()->json($service);
    }

    public function show(Service $service)
    {
        return response()->json($service->load('priceTiers'));
    }

    public function update(Request $request, Service $service)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'code' => 'nullable|string',
            'base_price' => 'nullable|numeric',
            'duration_minutes' => 'nullable|integer',
            'pricing_type' => 'nullable|string',
            'description' => 'nullable|string',
            'category' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $service->update($data);

        return response()->json($service->fresh('priceTiers'));
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function duplicate(Service $service)
    {
        $clone = $service->replicate()->fill([
            'name' => $service->name.' (Copy)',
            'code' => $service->code ? $service->code.'-COPY' : null,
        ]);
        $clone->save();
        foreach ($service->priceTiers as $tier) {
            $clone->priceTiers()->create($tier->only(['location', 'client_type', 'price', 'currency', 'unit', 'valid_from', 'valid_to']));
        }

        return response()->json($clone->load('priceTiers'));
    }
}
