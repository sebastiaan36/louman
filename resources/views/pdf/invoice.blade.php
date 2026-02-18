<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factuur #{{ $order->id }}</title>
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

        .invoice-details {
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 3px;
        }

        .invoice-details table {
            width: 100%;
        }

        .invoice-details td {
            padding: 3px;
            font-size: 8pt;
        }

        .invoice-details td:first-child {
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

        .payment-info {
            margin-top: 15px;
            padding: 10px;
            background-color: #e8f4fd;
            border-left: 3px solid #2196f3;
        }

        .payment-info h4 {
            font-size: 9pt;
            margin-bottom: 5px;
        }

        .payment-info p {
            font-size: 8pt;
            color: #555;
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
                @php $logoPath = storage_path('app/public/img/Logo.png'); @endphp
                @if(file_exists($logoPath))
                    <img src="{{ $logoPath }}" alt="Slagerij Louman" style="max-height:60px; max-width:180px; margin-bottom:6px; display:block;">
                @endif
                <div class="company-info">
                    <strong>Ambachtelijke Slagerij T.F.M. Louman</strong>
                    Goudsbloemstraat 76<br>
                    1015 JR Amsterdam<br>
                    Tel: 020 6220771<br>
                    E-mail: info@louman-jordaan.nl
                </div>
            </div>
            <div class="header-right">
                <div class="document-title">FACTUUR</div>
                <div class="document-info">
                    Factuurnummer: #{{ $order->id }}<br>
                    Factuurdatum: {{ $invoiceDate->format('d-m-Y') }}<br>
                    Vervaldatum: {{ $invoiceDate->copy()->addDays(14)->format('d-m-Y') }}
                </div>
            </div>
        </div>

        <!-- Addresses -->
        <div class="addresses">
            <div class="address-block">
                <h3>Factuuradres</h3>
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

        <!-- Invoice Details -->
        <div class="invoice-details">
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
                    <td>Factuurdatum:</td>
                    <td>{{ $invoiceDate->format('d-m-Y') }}</td>
                </tr>
                <tr>
                    <td>Betalingstermijn:</td>
                    <td>14 dagen (voor {{ $invoiceDate->copy()->addDays(14)->format('d-m-Y') }})</td>
                </tr>
                @if($order->customer->kvk_number)
                <tr>
                    <td>KVK nummer:</td>
                    <td>{{ $order->customer->kvk_number }}</td>
                </tr>
                @endif
                @if($order->customer->vat_number)
                <tr>
                    <td>BTW nummer:</td>
                    <td>{{ $order->customer->vat_number }}</td>
                </tr>
                @endif
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
                    <td colspan="4" class="text-right"><strong>Totaal te betalen (incl. BTW):</strong></td>
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

        <!-- Payment Info -->
        <div class="payment-info">
            <h4>Betalingsinformatie</h4>
            <p>Gelieve het totaalbedrag van <strong>€ {{ number_format($totalInclVat, 2, ',', '.') }}</strong> binnen 14 dagen over te maken onder vermelding van factuurnummer <strong>#{{ $order->id }}</strong>.</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Ambachtelijke Slagerij T.F.M. Louman • Goudsbloemstraat 76 • 1015 JR Amsterdam • Tel: 020 6220771 • info@louman-jordaan.nl</p>
        </div>
    </div>
</body>
</html>
