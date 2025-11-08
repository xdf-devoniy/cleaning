<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Finance\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::with('client')->latest()->paginate(25);

        return response()->json($invoices);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric',
            'items.*.discount' => 'nullable|numeric',
            'currency' => 'nullable|string|max:3',
            'notes' => 'nullable|string',
            'issued_at' => 'nullable|date',
            'due_at' => 'nullable|date',
        ]);

        $invoice = DB::transaction(function () use ($data) {
            $invoice = Invoice::create([
                'client_id' => $data['client_id'],
                'number' => 'INV-'.now()->format('ymdHis'),
                'status' => 'sent',
                'currency' => $data['currency'] ?? 'UZS',
                'issued_at' => $data['issued_at'] ?? now(),
                'due_at' => $data['due_at'] ?? now()->addDays(7),
                'notes' => $data['notes'] ?? null,
            ]);

            $subtotal = 0;
            $taxTotal = 0;
            $discountTotal = 0;

            foreach ($data['items'] as $item) {
                $lineSubtotal = $item['quantity'] * $item['unit_price'];
                $lineDiscount = $item['discount'] ?? 0;
                $taxRate = $item['tax_rate'] ?? 0;
                $lineTax = ($lineSubtotal - $lineDiscount) * ($taxRate / 100);
                $lineTotal = $lineSubtotal - $lineDiscount + $lineTax;

                $invoice->items()->create($item + ['total' => $lineTotal]);

                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;
                $discountTotal += $lineDiscount;
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $discountTotal,
                'total' => $subtotal - $discountTotal + $taxTotal,
                'balance_due' => $subtotal - $discountTotal + $taxTotal,
            ]);

            return $invoice->load('items');
        });

        return response()->json($invoice);
    }

    public function show(Invoice $invoice)
    {
        return response()->json($invoice->load(['items', 'payments']));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'status' => 'sometimes|string',
            'notes' => 'nullable|string',
            'due_at' => 'nullable|date',
        ]);

        $invoice->update($data);

        return response()->json($invoice->fresh(['items', 'payments']));
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function recordPayment(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
            'method' => 'required|string',
            'reference' => 'nullable|string',
        ]);

        $payment = $invoice->payments()->create($data + [
            'paid_at' => now(),
        ]);

        $newBalance = max(0, $invoice->balance_due - $data['amount']);

        $invoice->update([
            'balance_due' => $newBalance,
            'status' => $newBalance <= 0 ? 'paid' : 'partial',
        ]);

        return response()->json($invoice->fresh(['items', 'payments']));
    }

    public function sendReminder(Invoice $invoice)
    {
        // Hook for Telegram/email reminder dispatch
        return response()->json(['status' => 'queued']);
    }

    public function downloadPdf(Invoice $invoice)
    {
        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice->load(['client', 'items', 'payments'])]);

        return $pdf->download("invoice-{$invoice->number}.pdf");
    }
}
