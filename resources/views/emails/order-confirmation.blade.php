<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bestelbevestiging #{{ $order->id }}</title>
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
        .order-summary {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .order-summary h2 {
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
        .total-box {
            text-align: right;
            margin: 10px 0 25px 0;
        }
        .total-amount {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
        }
        .address-section {
            margin-bottom: 25px;
        }
        .address-section h3 {
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0 0 8px 0;
        }
        .address-box {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            font-size: 14px;
            line-height: 1.7;
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
@endphp
<body>
    <div class="container">
        @if($logoBase64)
            <div style="background:#ffffff; text-align:center; padding:20px 30px;">
                <img src="{{ $logoBase64 }}" alt="Slagerij Louman" style="max-height:80px; max-width:240px;">
            </div>
        @endif
        <div class="header">
            <h1>Bedankt voor uw bestelling!</h1>
            <p>We hebben uw bestelling goed ontvangen en gaan er mee aan de slag.</p>
        </div>

        <div class="content">
            <p class="greeting">
                Beste {{ $order->customer->contact_person }},
            </p>
            <p>
                Uw bestelling bij <strong>Ambachtelijke Slagerij T.F.M. Louman</strong> is succesvol geplaatst.
                Hieronder vindt u een overzicht van uw bestelling.
            </p>

            <div class="order-summary">
                <h2>Bestelgegevens</h2>
                <div class="meta-row">
                    <div class="meta-label">Bestelnummer:</div>
                    <div class="meta-value"><strong>#{{ $order->id }}</strong></div>
                </div>
                <div class="meta-row">
                    <div class="meta-label">Besteldatum:</div>
                    <div class="meta-value">{{ $order->created_at->format('d-m-Y \o\m H:i') }}</div>
                </div>
                <div class="meta-row">
                    <div class="meta-label">Status:</div>
                    <div class="meta-value">In behandeling</div>
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

            <div class="total-box">
                <span class="total-amount">Totaal: € {{ number_format($order->total, 2, ',', '.') }}</span>
            </div>

            <div class="address-section">
                <h3>Afleveradres</h3>
                <div class="address-box">
                    @if($order->deliveryAddress)
                        <strong>{{ $order->deliveryAddress->name }}</strong><br>
                        {{ $order->deliveryAddress->street_name }} {{ $order->deliveryAddress->house_number }}<br>
                        {{ $order->deliveryAddress->postal_code }} {{ $order->deliveryAddress->city }}
                    @else
                        <strong>Hoofdadres</strong><br>
                        {{ $order->customer->street_name }} {{ $order->customer->house_number }}<br>
                        {{ $order->customer->postal_code }} {{ $order->customer->city }}
                    @endif
                </div>
            </div>

            @if($order->notes)
                <div class="notes-box">
                    <h4>Uw opmerkingen</h4>
                    <p>{{ $order->notes }}</p>
                </div>
            @endif

            <div class="contact-section">
                <p>
                    Vragen over uw bestelling? Neem contact met ons op via<br>
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
