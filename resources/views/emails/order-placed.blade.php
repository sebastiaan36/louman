<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nieuwe Bestelling</title>
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
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px 20px;
        }
        .alert {
            background-color: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert h2 {
            margin: 0 0 10px 0;
            font-size: 18px;
            color: #2e7d32;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            padding: 8px 0;
            font-weight: 600;
            width: 40%;
            color: #555;
        }
        .info-value {
            display: table-cell;
            padding: 8px 0;
        }
        .section {
            margin: 25px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 6px;
        }
        .section h3 {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #2c3e50;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 8px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .items-table thead {
            background-color: #2c3e50;
            color: white;
        }
        .items-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        .items-table tbody tr:last-child td {
            border-bottom: none;
        }
        .items-table tbody tr:hover {
            background-color: #f5f5f5;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-section {
            margin-top: 20px;
            text-align: right;
            padding: 15px;
            background: #fff;
            border-radius: 6px;
        }
        .total-row {
            padding: 8px 0;
            font-size: 15px;
        }
        .total-row.final {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            padding-top: 12px;
            margin-top: 12px;
            border-top: 2px solid #2c3e50;
        }
        .notes-box {
            background: #fff9e6;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .notes-box h4 {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: #f57f17;
        }
        .notes-box p {
            margin: 0;
            color: #666;
            white-space: pre-wrap;
        }
        .footer {
            background: #f9f9f9;
            padding: 20px;
            text-align: center;
            font-size: 13px;
            color: #777;
            border-top: 1px solid #e0e0e0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #4caf50;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            margin: 15px 0;
        }
        .button:hover {
            background: #45a049;
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
            <h1>📦 Nieuwe Bestelling Ontvangen</h1>
        </div>

        <div class="content">
            <div class="alert">
                <h2>Bestelling #{{ $order->id }}</h2>
                <p>Er is een nieuwe bestelling geplaatst via het B2B klantportaal. De pakbon is als bijlage toegevoegd.</p>
            </div>

            <div class="section">
                <h3>Bestelgegevens</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Bestelnummer:</div>
                        <div class="info-value"><strong>#{{ $order->id }}</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Besteldatum:</div>
                        <div class="info-value">{{ $order->created_at->format('d-m-Y H:i') }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Status:</div>
                        <div class="info-value">
                            <span style="background: #ff9800; color: white; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                                {{ match($order->status) {
                                    'pending' => 'IN BEHANDELING',
                                    'confirmed' => 'BEVESTIGD',
                                    'completed' => 'VOLTOOID',
                                    'cancelled' => 'GEANNULEERD',
                                    default => strtoupper($order->status)
                                } }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h3>Klantinformatie</h3>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Bedrijfsnaam:</div>
                        <div class="info-value"><strong>{{ $order->customer->company_name }}</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Contactpersoon:</div>
                        <div class="info-value">{{ $order->customer->contact_person }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Telefoonnummer:</div>
                        <div class="info-value">{{ $order->customer->phone_number }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email:</div>
                        <div class="info-value">{{ $order->customer->user->email }}</div>
                    </div>
                    @if($order->customer->discount_percentage > 0)
                        <div class="info-row">
                            <div class="info-label">Korting:</div>
                            <div class="info-value">{{ $order->customer->discount_percentage }}%</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="section">
                <h3>Afleveradres</h3>
                @if($order->deliveryAddress)
                    <p style="margin: 0;">
                        <strong>{{ $order->deliveryAddress->name }}</strong><br>
                        {{ $order->deliveryAddress->street_name }} {{ $order->deliveryAddress->house_number }}<br>
                        {{ $order->deliveryAddress->postal_code }} {{ $order->deliveryAddress->city }}
                    </p>
                @else
                    <p style="margin: 0;">
                        <strong>Hoofdadres (Factuuradres)</strong><br>
                        {{ $order->customer->street_name }} {{ $order->customer->house_number }}<br>
                        {{ $order->customer->postal_code }} {{ $order->customer->city }}
                    </p>
                @endif
            </div>

            <div class="section">
                <h3>Bestelde Producten</h3>
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
                                <td>
                                    <strong>{{ $item->product->title }}</strong><br>
                                    <small style="color: #999;">Art.nr: {{ $item->product->article_number }}</small>
                                </td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-right">€ {{ number_format($item->price, 2, ',', '.') }}</td>
                                <td class="text-right">€ {{ number_format($item->price * $item->quantity, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="total-section">
                    <div class="total-row final">
                        Totaalbedrag: <strong>€ {{ number_format($order->total, 2, ',', '.') }}</strong>
                    </div>
                </div>
            </div>

            @if($order->notes)
                <div class="notes-box">
                    <h4>⚠️ Opmerkingen van de klant</h4>
                    <p>{{ $order->notes }}</p>
                </div>
            @endif
        </div>

        <div class="footer">
            <p>
                Deze email is automatisch gegenereerd vanuit het B2B klantportaal.<br>
                <small>Slagerij Louman • info@louman-joraan.nl</small>
            </p>
        </div>
    </div>
</body>
</html>
