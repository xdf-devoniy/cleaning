<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryUsage;
use Illuminate\Http\Request;

class InventoryItemController extends Controller
{
    public function index()
    {
        return response()->json(
            InventoryItem::with('supplier')->paginate(25)
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'sku' => 'nullable|string',
            'category' => 'nullable|string',
            'unit' => 'required|string',
            'on_hand' => 'nullable|numeric',
            'cost_price' => 'nullable|numeric',
            'min_stock_level' => 'nullable|numeric',
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
        ]);

        $item = InventoryItem::create($data);

        return response()->json($item);
    }

    public function show(InventoryItem $item)
    {
        return response()->json($item->load(['supplier', 'usages' => fn ($q) => $q->latest()->limit(20)]));
    }

    public function update(Request $request, InventoryItem $item)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'sku' => 'nullable|string',
            'category' => 'nullable|string',
            'unit' => 'sometimes|string',
            'on_hand' => 'nullable|numeric',
            'cost_price' => 'nullable|numeric',
            'min_stock_level' => 'nullable|numeric',
        ]);

        $item->update($data);

        return response()->json($item);
    }

    public function destroy(InventoryItem $item)
    {
        $item->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function assignToJob(Request $request, InventoryItem $item)
    {
        $data = $request->validate([
            'job_id' => 'required|integer|exists:jobs,id',
            'quantity' => 'required|numeric|min:0',
        ]);

        $usage = InventoryUsage::create([
            'inventory_item_id' => $item->id,
            'job_id' => $data['job_id'],
            'quantity' => $data['quantity'],
            'used_at' => now(),
        ]);

        $item->decrement('on_hand', $data['quantity']);

        return response()->json($usage);
    }
}
