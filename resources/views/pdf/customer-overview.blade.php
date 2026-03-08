<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Bestellingenoverzicht</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            color: #222;
            line-height: 1.3;
        }

        .page {
            padding: 14px 16px;
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: avoid;
        }

        /* Page header */
        .page-header {
            border-bottom: 2px solid #222;
            padding-bottom: 5px;
            margin-bottom: 10px;
            display: table;
            width: 100%;
        }

        .page-header-left {
            display: table-cell;
            vertical-align: bottom;
        }

        .page-header-right {
            display: table-cell;
            vertical-align: bottom;
            text-align: right;
        }

        .day-title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .meta {
            font-size: 7pt;
            color: #555;
            margin-top: 2px;
        }

        /* 2-column grid */
        .grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px;
        }

        .grid td {
            width: 50%;
            vertical-align: top;
        }

        /* Customer card */
        .card {
            border: 1.5px solid #bbb;
            padding: 5px 7px;
            min-height: 36px;
        }

        .card-header {
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
            margin-bottom: 4px;
            display: table;
            width: 100%;
        }

        .card-header-left {
            display: table-cell;
            vertical-align: top;
        }

        .card-name {
            font-size: 8pt;
            font-weight: bold;
        }

        .card-phone {
            font-size: 6.5pt;
            color: #555;
            margin-top: 1px;
        }

        .card-header-right {
            display: table-cell;
            vertical-align: top;
            text-align: right;
        }

        .card-empty-label {
            font-size: 6.5pt;
            color: #aaa;
            font-style: italic;
            margin-top: 2px;
        }

        /* Products table inside card */
        .products {
            width: 100%;
            border-collapse: collapse;
        }

        .products td {
            font-size: 7pt;
            padding: 1px 0;
            vertical-align: top;
        }

        .products .art {
            color: #888;
            width: 52px;
            padding-right: 5px;
        }

        .products .qty {
            text-align: right;
            font-weight: bold;
            width: 26px;
            padding-left: 4px;
        }

        .products tr:not(:last-child) td {
            border-bottom: 1px solid #f0f0f0;
        }
    </style>
</head>
<body>
    @if(count($dayGroups) > 0)
        @foreach($dayGroups as $day => $customers)
            <div class="page">
                <div class="page-header">
                    <div class="page-header-left">
                        <div class="day-title">{{ ucfirst($day) }}</div>
                        <div class="meta">
                            {{ count($customers) }} {{ count($customers) === 1 ? 'klant' : 'klanten' }}
                            &nbsp;|&nbsp;
                            Bestellingenoverzicht &mdash; {{ $generatedAt }}
                        </div>
                    </div>
                    <div class="page-header-right">
                        <div class="meta">
                            Bestellingen in behandeling: <strong>{{ $orderCount }}</strong>
                            &nbsp;|&nbsp;
                            Totaal klanten: <strong>{{ $customerCount }}</strong>
                        </div>
                    </div>
                </div>

                <table class="grid">
                    @foreach(array_chunk($customers, 2) as $row)
                        <tr>
                            @foreach($row as $customer)
                                <td>
                                    <div class="card">
                                        <div class="card-header">
                                            <div class="card-header-left">
                                                <div class="card-name">{{ $customer['company_name'] }}</div>
                                                @if($customer['phone_number'])
                                                    <div class="card-phone">{{ $customer['phone_number'] }}</div>
                                                @endif
                                            </div>
                                            @if(empty($customer['products']))
                                                <div class="card-header-right">
                                                    <div class="card-empty-label">geen bestelling</div>
                                                </div>
                                            @endif
                                        </div>
                                        @if(!empty($customer['products']))
                                            <table class="products">
                                                @foreach($customer['products'] as $product)
                                                    <tr>
                                                        <td class="art">{{ $product['article_number'] }}</td>
                                                        <td class="name">{{ $product['title'] }}</td>
                                                        <td class="qty">{{ $product['quantity'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                            @if(count($row) === 1)
                                <td></td>
                            @endif
                        </tr>
                    @endforeach
                </table>
            </div>
        @endforeach
    @else
        <div style="padding: 40px; text-align: center; color: #777; font-size: 9pt;">
            Geen goedgekeurde klanten gevonden.
        </div>
    @endif
</body>
</html>
