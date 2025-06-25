<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_no }}</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        h2 { margin-top: 0; }
        .no-border td { border: none !important; }
    </style>
</head>
<body>

<h2>Invoice: {{ $invoice->invoice_no }}</h2>

<table class="no-border">
    <tr>
        <td><strong>Customer:</strong> {{ $invoice->customer->name }}</td>
        <td><strong>Date:</strong> {{ $invoice->date_of_supply }}</td>
        <td><strong>Time:</strong> {{ $invoice->time_of_supply }}</td>
    </tr>
    <tr>
        <td><strong>Address:</strong> {{ $invoice->customer->address }}</td>
        <td><strong>Phone:</strong> {{ $invoice->customer->phone }}</td>
        <td><strong>NTN/CNIC:</strong> {{ $invoice->customer->ntn_cnic }}</td>
    </tr>
</table>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Item</th>
            <th>Qty</th>
            <th>Unit Price</th>
            <th>Value</th>
            <th>ST %</th>
            <th>ST Amount</th>
            <th>Further Tax</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items as $key => $item)
            <tr>
                <td>{{ $key + 1 }}</td>
                <td>{{ $item->item->name }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 2) }}</td>
                <td>{{ number_format($item->value_of_goods, 2) }}</td>
                <td>{{ $item->sale_tax_rate }}%</td>
                <td>{{ number_format($item->amount_of_saleTax, 2) }}</td>
                <td>{{ number_format($item->further_tax, 2) }}</td>
                <td>{{ number_format($item->total, 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h4>Total Amount: {{ number_format($invoice->items->sum('total'), 2) }}</h4>

</body>
</html>
