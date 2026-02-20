<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Productielijst</title>
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        thead tr {
            background-color: #222;
            color: #fff;
        }

        thead th {
            padding: 5px 8px;
            text-align: left;
            font-size: 8pt;
            font-weight: bold;
        }

        thead th.qty {
            text-align: right;
            width: 70px;
        }

        tbody tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        tbody td {
            padding: 4px 8px;
            font-size: 8.5pt;
            border-bottom: 1px solid #e0e0e0;
        }

        tbody td.qty {
            text-align: right;
            font-weight: bold;
        }

        tbody td.article {
            width: 90px;
            color: #555;
            font-size: 8pt;
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
                <div class="title">PRODUCTIELIJST</div>
                <div class="meta">Gegenereerd op {{ $generatedAt }}</div>
            </div>
            <div class="header-right">
                <div class="meta">Bestellingen in behandeling: <strong>{{ $orderCount }}</strong></div>
            </div>
        </div>

        @if(count($products) > 0)
            <table>
                <thead>
                    <tr>
                        <th>Artikelnummer</th>
                        <th>Naam</th>
                        <th class="qty">Aantal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                        <tr>
                            <td class="article">{{ $product['article_number'] }}</td>
                            <td>{{ $product['title'] }}</td>
                            <td class="qty">{{ $product['quantity'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty">Geen bestellingen in behandeling.</div>
        @endif
    </div>
</body>
</html>
