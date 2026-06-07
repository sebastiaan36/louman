<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bestelling geannuleerd</title>
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
            background-color: #fdecea;
            border-left: 4px solid #e53935;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert h2 {
            margin: 0 0 10px 0;
            font-size: 18px;
            color: #b71c1c;
        }
        .info-grid { display: table; width: 100%; margin-bottom: 20px; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; padding: 8px 0; font-weight: 600; width: 40%; color: #555; }
        .info-value { display: table-cell; padding: 8px 0; }
        .section { margin: 25px 0; padding: 20px; background: #f9f9f9; border-radius: 6px; }
        .section h3 { margin: 0 0 15px 0; font-size: 16px; color: #2c3e50; border-bottom: 2px solid #e0e0e0; padding-bottom: 8px; }
        .items-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .items-table thead { background-color: #2c3e50; color: white; }
        .items-table th { padding: 12px; text-align: left; font-weight: 600; font-size: 14px; }
        .items-table td { padding: 12px; border-bottom: 1px solid #e0e0e0; }
        .items-table tbody tr:last-child td { border-bottom: none; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-section { margin-top: 20px; text-align: right; padding: 15px; background: #fff; border-radius: 6px; }
        .footer { background: #f4f4f4; padding: 20px; text-align: center; color: #888; font-size: 13px; }
    </style>
</head>
@php
    $logoPath = storage_path('app/public/img/Logo.png');
    $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($logoPath)) : null;
@endphp
<body>
    <div class="container">
        @if($logoBase64)
            <div style="background:#ffffff; text-align:center; padding:20px 30px;">
                <img src="{{ $logoBase64 }}" alt="Slagerij Louman" style="max-height:80px; max-width:240px;">
            </div>
        @endif
        <div class="header">
            <h1>Bestelling geannuleerd</h1>
        </div>

        <div class="content">
            <div class="alert">
                <h2>Bestelling #{{ $order->id }}</h2>
                <p>Deze bestelling is geannuleerd.</p>
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
                </div>
            </div>

            <div class="section">
                <h3>Bestelde producten</h3>
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
                    Totaalbedrag: <strong>€ {{ number_format($order->total, 2, ',', '.') }}</strong>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>
                Deze email is automatisch gegenereerd vanuit het B2B klantportaal.<br>
                <small>Slagerij Louman • info@louman-jordaan.nl</small>
            </p>
        </div>
    </div>
</body>
</html>
