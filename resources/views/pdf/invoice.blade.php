<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; }
        header { display: flex; justify-content: space-between; margin-bottom: 24px; }
        h1 { font-size: 24px; margin: 0; text-transform: uppercase; letter-spacing: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #cbd5f5; padding: 8px; text-align: left; }
        th { background: #e0f2fe; }
        .summary td { border: none; }
    </style>
</head>
<body>
    <header>
        <div>
            <h1>Invoice</h1>
            <p><strong>Number:</strong> {{ $invoice->number }}</p>
            <p><strong>Date:</strong> {{ optional($invoice->issued_at)->format('Y-m-d') }}</p>
            <p><strong>Due:</strong> {{ optional($invoice->due_at)->format('Y-m-d') }}</p>
        </div>
        <div>
            <p><strong>Bill To:</strong></p>
            <p>{{ $invoice->client->name }}</p>
            <p>{{ $invoice->client->phone }}</p>
            <p>{{ $invoice->client->email }}</p>
        </div>
    </header>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Discount</th>
                <th>Tax %</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ number_format($item->quantity, 2) }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ number_format($item->discount ?? 0, 2) }}</td>
                    <td>{{ number_format($item->tax_rate ?? 0, 2) }}</td>
                    <td>{{ number_format($item->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td style="width: 70%;"></td>
            <td>
                <p><strong>Subtotal:</strong> {{ number_format($invoice->subtotal, 2) }}</p>
                <p><strong>Discounts:</strong> {{ number_format($invoice->discount_total, 2) }}</p>
                <p><strong>Tax:</strong> {{ number_format($invoice->tax_total, 2) }}</p>
                <p><strong>Total:</strong> {{ number_format($invoice->total, 2) }} {{ $invoice->currency }}</p>
                <p><strong>Balance Due:</strong> {{ number_format($invoice->balance_due, 2) }} {{ $invoice->currency }}</p>
            </td>
        </tr>
    </table>

    <p style="margin-top: 32px;">Thank you for trusting CleanSuite. Payments can be made via Payme, Click, or Uzum QR.</p>
</body>
</html>
