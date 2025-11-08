<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Invoice;
use App\Models\Finance\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $quotes = Quote::with('client')->latest()->paginate(25);

        return response()->json($quotes);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'nullable|integer|exists:services,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric',
            'items.*.discount' => 'nullable|numeric',
            'currency' => 'nullable|string|max:3',
            'notes' => 'nullable|string',
            'valid_until' => 'nullable|date',
        ]);

        $quote = DB::transaction(function () use ($data) {
            $quote = Quote::create([
                'client_id' => $data['client_id'],
                'number' => 'Q-'.Str::upper(Str::random(6)),
                'status' => 'draft',
                'currency' => $data['currency'] ?? 'UZS',
                'notes' => $data['notes'] ?? null,
                'valid_until' => $data['valid_until'] ?? now()->addDays(30),
            ]);

            $subtotal = 0;
            $taxTotal = 0;
            $discountTotal = 0;

            foreach ($data['items'] as $itemData) {
                $lineSubtotal = $itemData['quantity'] * $itemData['unit_price'];
                $lineDiscount = $itemData['discount'] ?? 0;
                $taxRate = $itemData['tax_rate'] ?? 0;
                $lineTax = ($lineSubtotal - $lineDiscount) * ($taxRate / 100);
                $lineTotal = $lineSubtotal - $lineDiscount + $lineTax;

                $quote->items()->create($itemData + [
                    'total' => $lineTotal,
                ]);

                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;
                $discountTotal += $lineDiscount;
            }

            $quote->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $discountTotal,
                'total' => $subtotal - $discountTotal + $taxTotal,
            ]);

            return $quote->load('items');
        });

        return response()->json($quote);
    }

    public function show(Quote $quote)
    {
        return response()->json($quote->load('items'));
    }

    public function update(Request $request, Quote $quote)
    {
        $data = $request->validate([
            'status' => 'sometimes|string',
            'notes' => 'nullable|string',
            'valid_until' => 'nullable|date',
        ]);

        $quote->update($data);

        return response()->json($quote->fresh('items'));
    }

    public function destroy(Quote $quote)
    {
        $quote->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function convert(Quote $quote)
    {
        $invoice = DB::transaction(function () use ($quote) {
            $invoice = Invoice::create([
                'client_id' => $quote->client_id,
                'quote_id' => $quote->id,
                'number' => str_replace('Q-', 'INV-', $quote->number),
                'status' => 'sent',
                'subtotal' => $quote->subtotal,
                'tax_total' => $quote->tax_total,
                'discount_total' => $quote->discount_total,
                'total' => $quote->total,
                'balance_due' => $quote->total,
                'currency' => $quote->currency,
                'issued_at' => now(),
                'due_at' => now()->addDays(7),
                'notes' => $quote->notes,
            ]);

            foreach ($quote->items as $item) {
                $invoice->items()->create($item->only([
                    'service_id',
                    'description',
                    'quantity',
                    'unit_price',
                    'discount',
                    'tax_rate',
                    'total',
                ]));
            }

            $quote->update(['status' => 'converted']);

            return $invoice->load('items');
        });

        return response()->json($invoice);
    }
}
