<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factuur #{{ $order->id }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: #2c3e50;
            color: #ffffff;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 8px 0;
            font-size: 26px;
        }
        .header p {
            margin: 0;
            opacity: 0.85;
            font-size: 15px;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .invoice-summary {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .invoice-summary h2 {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #2c3e50;
        }
        .meta-row {
            display: table;
            width: 100%;
            padding: 6px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .meta-row:last-child {
            border-bottom: none;
        }
        .meta-label {
            display: table-cell;
            font-weight: 600;
            color: #555;
            width: 40%;
        }
        .meta-value {
            display: table-cell;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .items-table thead {
            background-color: #2c3e50;
            color: white;
        }
        .items-table th {
            padding: 12px;
            text-align: left;
            font-size: 13px;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }
        .items-table tbody tr:last-child td {
            border-bottom: 2px solid #2c3e50;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals {
            margin: 10px 0 25px 0;
            text-align: right;
        }
        .totals table {
            margin-left: auto;
        }
        .totals td {
            padding: 4px 8px;
            font-size: 14px;
        }
        .totals td:first-child {
            color: #666;
        }
        .totals td:last-child {
            font-weight: 600;
            min-width: 100px;
            text-align: right;
        }
        .total-final td {
            font-size: 17px;
            font-weight: bold;
            color: #2c3e50;
            border-top: 2px solid #2c3e50;
            padding-top: 8px;
        }
        .payment-box {
            background: #e8f4fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        .payment-box h4 {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: #1565c0;
        }
        .payment-box p {
            margin: 0;
            font-size: 14px;
            color: #333;
        }
        .notes-box {
            background: #fff9e6;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        .notes-box h4 {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: #f57f17;
        }
        .notes-box p {
            margin: 0;
            font-size: 14px;
            color: #666;
            white-space: pre-wrap;
        }
        .contact-section {
            background: #e8f5e9;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .contact-section p {
            margin: 0;
            font-size: 14px;
            color: #2e7d32;
        }
        .contact-section a {
            color: #2e7d32;
            font-weight: 600;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #888;
            border-top: 1px solid #e9ecef;
        }
        .footer strong {
            color: #555;
        }
    </style>
</head>
@php
    $logoPath = storage_path('app/public/img/Logo.png');
    $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;

    $subtotal = (float) $order->total;
    $vatAmount = $subtotal * 0.09;
    $totalInclVat = $subtotal + $vatAmount;
@endphp
<body>
    <div class="container">
        @if($logoBase64)
            <div style="background:#ffffff; text-align:center; padding:20px 30px;">
                <img src="{{ $logoBase64 }}" alt="Slagerij Louman" style="max-height:80px; max-width:240px;">
            </div>
        @endif
        <div class="header">
            <h1>Factuur #{{ $order->id }}</h1>
            <p>Uw factuur van Ambachtelijke Slagerij T.F.M. Louman</p>
        </div>

        <div class="content">
            <p class="greeting">
                Beste {{ $order->customer->contact_person }},
            </p>
            <p>
                Uw bestelling bij <strong>Ambachtelijke Slagerij T.F.M. Louman</strong> is verzonden.
                In de bijlage vindt u de bijbehorende factuur. Hieronder een overzicht van de factuurgegevens.
            </p>

            <div class="invoice-summary">
                <h2>Factuurgegevens</h2>
                <div class="meta-row">
                    <div class="meta-label">Factuurnummer:</div>
                    <div class="meta-value"><strong>#{{ $order->id }}</strong></div>
                </div>
                <div class="meta-row">
                    <div class="meta-label">Factuurdatum:</div>
                    <div class="meta-value">{{ $invoiceDate->format('d-m-Y') }}</div>
                </div>
                <div class="meta-row">
                    <div class="meta-label">Besteldatum:</div>
                    <div class="meta-value">{{ $order->created_at->format('d-m-Y') }}</div>
                </div>
                <div class="meta-row">
                    <div class="meta-label">Betalingstermijn:</div>
                    <div class="meta-value">14 dagen (voor {{ $invoiceDate->copy()->addDays(14)->format('d-m-Y') }})</div>
                </div>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-center">Aantal</th>
                        <th class="text-right">Prijs</th>
                        <th class="text-right">Subtotaal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr>
                            <td>{{ $item->product->title }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-right">€ {{ number_format($item->price, 2, ',', '.') }}</td>
                            <td class="text-right">€ {{ number_format($item->price * $item->quantity, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totals">
                <table>
                    <tr>
                        <td>Subtotaal (ex. BTW):</td>
                        <td>€ {{ number_format($subtotal, 2, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>BTW (9%):</td>
                        <td>€ {{ number_format($vatAmount, 2, ',', '.') }}</td>
                    </tr>
                    <tr class="total-final">
                        <td>Totaal te betalen:</td>
                        <td>€ {{ number_format($totalInclVat, 2, ',', '.') }}</td>
                    </tr>
                </table>
            </div>

            <div class="payment-box">
                <h4>Betalingsinformatie</h4>
                <p>Gelieve het bedrag van <strong>€ {{ number_format($totalInclVat, 2, ',', '.') }}</strong> binnen 14 dagen te voldoen onder vermelding van factuurnummer <strong>#{{ $order->id }}</strong>.</p>
            </div>

            @if($order->notes)
                <div class="notes-box">
                    <h4>Opmerkingen bij bestelling</h4>
                    <p>{{ $order->notes }}</p>
                </div>
            @endif

            <div class="contact-section">
                <p>
                    Vragen over deze factuur? Neem contact met ons op via<br>
                    <a href="mailto:info@louman-jordaan.nl">info@louman-jordaan.nl</a> of bel <strong>020 6220771</strong>
                </p>
            </div>
        </div>

        <div class="footer">
            <strong>Ambachtelijke Slagerij T.F.M. Louman</strong><br>
            Goudsbloemstraat 76 • 1015 JR Amsterdam<br>
            Tel: 020 6220771 • info@louman-jordaan.nl
        </div>
    </div>
</body>
</html>
