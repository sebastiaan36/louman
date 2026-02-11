<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pakbon #{{ $order->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.4;
        }

        .container {
            padding: 15px;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: right;
        }

        .logo {
            max-width: 150px;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 8pt;
            line-height: 1.3;
        }

        .company-info strong {
            display: block;
            font-size: 9pt;
            margin-bottom: 3px;
        }

        .document-title {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .document-info {
            font-size: 8pt;
            color: #666;
        }

        .addresses {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .address-block {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }

        .address-block h3 {
            font-size: 10pt;
            margin-bottom: 5px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
        }

        .address-content {
            font-size: 8pt;
            line-height: 1.3;
        }

        .order-details {
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 3px;
        }

        .order-details table {
            width: 100%;
        }

        .order-details td {
            padding: 3px;
            font-size: 8pt;
        }

        .order-details td:first-child {
            font-weight: bold;
            width: 120px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .items-table thead {
            background-color: #333;
            color: white;
        }

        .items-table th {
            padding: 6px;
            text-align: left;
            font-size: 8pt;
            font-weight: bold;
        }

        .items-table td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
            font-size: 8pt;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 2px solid #333;
        }

        .items-table tfoot td {
            padding: 6px;
            border-top: 1px solid #ddd;
            font-size: 8pt;
        }

        .items-table tfoot tr:first-child td {
            border-top: 2px solid #333;
            padding-top: 8px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 7pt;
            color: #666;
            text-align: center;
        }

        .notes {
            margin-top: 10px;
            padding: 10px;
            background-color: #fff9e6;
            border-left: 3px solid #ffc107;
        }

        .notes h4 {
            font-size: 9pt;
            margin-bottom: 5px;
        }

        .notes p {
            font-size: 8pt;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                @if(file_exists($logoPath))
                    <img src="{{ $logoPath }}" alt="Logo" class="logo">
                @endif
                <div class="company-info">
                    <strong>{{ $companyInfo['name'] }}</strong>
                    {{ $companyInfo['address'] }}<br>
                    {{ $companyInfo['postal_code'] }} {{ $companyInfo['city'] }}<br>
                    Tel: {{ $companyInfo['phone'] }}<br>
                    Fax: {{ $companyInfo['fax'] }}<br>
                    E-mail: {{ $companyInfo['email'] }}
                </div>
            </div>
            <div class="header-right">
                <div class="document-title">PAKBON</div>
                <div class="document-info">
                    Nummer: #{{ $order->id }}<br>
                    Datum: {{ $order->created_at->format('d-m-Y') }}
                </div>
            </div>
        </div>

        <!-- Addresses -->
        <div class="addresses">
            <div class="address-block">
                <h3>Klantgegevens</h3>
                <div class="address-content">
                    <strong>{{ $order->customer->company_name }}</strong><br>
                    {{ $order->customer->contact_person }}<br>
                    {{ $order->customer->street_name }} {{ $order->customer->house_number }}<br>
                    {{ $order->customer->postal_code }} {{ $order->customer->city }}<br>
                    Tel: {{ $order->customer->phone_number }}<br>
                    E-mail: {{ $order->customer->user->email }}
                </div>
            </div>

            <div class="address-block">
                <h3>Afleveradres</h3>
                <div class="address-content">
                    @if($order->deliveryAddress)
                        <strong>{{ $order->deliveryAddress->name }}</strong><br>
                        {{ $order->deliveryAddress->street_name }} {{ $order->deliveryAddress->house_number }}<br>
                        {{ $order->deliveryAddress->postal_code }} {{ $order->deliveryAddress->city }}
                    @else
                        <strong>Factuuradres</strong><br>
                        {{ $order->customer->street_name }} {{ $order->customer->house_number }}<br>
                        {{ $order->customer->postal_code }} {{ $order->customer->city }}
                    @endif
                </div>
            </div>
        </div>

        <!-- Order Details -->
        <div class="order-details">
            <table>
                <tr>
                    <td>Bestelnummer:</td>
                    <td>#{{ $order->id }}</td>
                </tr>
                <tr>
                    <td>Besteldatum:</td>
                    <td>{{ $order->created_at->format('d-m-Y H:i') }}</td>
                </tr>
                <tr>
                    <td>Status:</td>
                    <td>
                        @switch($order->status)
                            @case('pending')
                                In behandeling
                                @break
                            @case('confirmed')
                                Bevestigd
                                @break
                            @case('completed')
                                Voltooid
                                @break
                            @case('cancelled')
                                Geannuleerd
                                @break
                            @default
                                {{ $order->status }}
                        @endswitch
                    </td>
                </tr>
            </table>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Artikelnummer</th>
                    <th>Omschrijving</th>
                    <th class="text-center">Aantal</th>
                    <th class="text-right">Prijs</th>
                    <th class="text-right">Totaal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product->article_number }}</td>
                        <td>{{ $item->product->title }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">€ {{ number_format($item->price, 2, ',', '.') }}</td>
                        <td class="text-right">€ {{ number_format($item->price * $item->quantity, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                @php
                    $subtotal = (float) $order->total;
                    $vatAmount = $subtotal * 0.09;
                    $totalInclVat = $subtotal + $vatAmount;
                @endphp
                <tr>
                    <td colspan="4" class="text-right">Subtotaal (ex. BTW):</td>
                    <td class="text-right">€ {{ number_format($subtotal, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td colspan="4" class="text-right">BTW (9%):</td>
                    <td class="text-right">€ {{ number_format($vatAmount, 2, ',', '.') }}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="4" class="text-right"><strong>Totaal (incl. BTW):</strong></td>
                    <td class="text-right"><strong>€ {{ number_format($totalInclVat, 2, ',', '.') }}</strong></td>
                </tr>
            </tfoot>
        </table>

        <!-- Notes (if any) -->
        @if($order->notes)
            <div class="notes">
                <h4>Opmerkingen</h4>
                <p>{{ $order->notes }}</p>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Bedankt voor uw bestelling bij {{ $companyInfo['name'] }}</p>
            <p>{{ $companyInfo['address'] }} • {{ $companyInfo['postal_code'] }} {{ $companyInfo['city'] }} • Tel: {{ $companyInfo['phone'] }}</p>
        </div>
    </div>
</body>
</html>
