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
            font-size: 9pt;
            color: #222;
            line-height: 1.4;
        }

        .container {
            padding: 20px 25px;
        }

        .header {
            border-bottom: 2px solid #222;
            padding-bottom: 8px;
            margin-bottom: 14px;
            display: table;
            width: 100%;
        }

        .header-left {
            display: table-cell;
            vertical-align: bottom;
        }

        .header-right {
            display: table-cell;
            vertical-align: bottom;
            text-align: right;
        }

        .title {
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .meta {
            font-size: 7.5pt;
            color: #555;
            margin-top: 2px;
        }

        /* 2-column grid using a table */
        .grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
        }

        .grid td {
            width: 50%;
            vertical-align: top;
        }

        /* Customer card */
        .card {
            border: 1px solid #ccc;
            padding: 7px 9px;
        }

        .card-title {
            font-size: 8.5pt;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
            margin-bottom: 5px;
            display: table;
            width: 100%;
        }

        .card-title-name {
            display: table-cell;
        }

        .card-title-day {
            display: table-cell;
            text-align: right;
            font-weight: normal;
            color: #666;
        }

        /* Products table inside card */
        .products {
            width: 100%;
            border-collapse: collapse;
        }

        .products td {
            font-size: 7.5pt;
            padding: 2px 0;
            vertical-align: top;
        }

        .products .art {
            color: #777;
            width: 60px;
            padding-right: 6px;
        }

        .products .name {
            /* fills remaining space */
        }

        .products .qty {
            text-align: right;
            font-weight: bold;
            width: 30px;
            padding-left: 4px;
        }

        .products tr:not(:last-child) td {
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 2px;
        }

        .empty {
            text-align: center;
            padding: 30px;
            color: #777;
            font-size: 9pt;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <div class="title">BESTELLINGENOVERZICHT</div>
                <div class="meta">Gegenereerd op {{ $generatedAt }}</div>
            </div>
            <div class="header-right">
                <div class="meta">
                    Klanten: <strong>{{ $customerCount }}</strong> &nbsp;|&nbsp;
                    Bestellingen in behandeling: <strong>{{ $orderCount }}</strong>
                </div>
            </div>
        </div>

        @if(count($rows) > 0)
            <table class="grid">
                @foreach($rows as $row)
                    <tr>
                        @foreach($row as $customer)
                            <td>
                                <div class="card">
                                    <div class="card-title">
                                        <span class="card-title-name">{{ $customer['company_name'] }}</span>
                                        @if($customer['delivery_day'])
                                            <span class="card-title-day">{{ ucfirst($customer['delivery_day']) }}</span>
                                        @endif
                                    </div>
                                    <table class="products">
                                        @foreach($customer['products'] as $product)
                                            <tr>
                                                <td class="art">{{ $product['article_number'] }}</td>
                                                <td class="name">{{ $product['title'] }}</td>
                                                <td class="qty">{{ $product['quantity'] }}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </div>
                            </td>
                        @endforeach
                        @if(count($row) === 1)
                            <td></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        @else
            <div class="empty">Geen bestellingen in behandeling.</div>
        @endif
    </div>
</body>
</html>
