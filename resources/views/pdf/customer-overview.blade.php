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

        @page {
            margin: 18px 16px;
        }

        .day-break {
            page-break-before: always;
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

        /* 2-column grid — DomPDF paginates the rows and repeats the header */
        .grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px;
        }

        .grid td.cell {
            width: 50%;
            vertical-align: top;
        }

        .grid thead td {
            padding: 0;
        }

        .grid tbody tr {
            page-break-inside: avoid;
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

        .pickup-badge {
            display: inline-block;
            background-color: #222;
            color: #fff;
            font-size: 6pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1px 5px;
            border-radius: 3px;
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

        .products .weight {
            color: #999;
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

        .notes {
            margin-top: 3px;
            padding-top: 3px;
            border-top: 1px solid #ddd;
            font-size: 6.5pt;
            color: #444;
            line-height: 1.25;
        }

        .notes strong {
            color: #222;
        }
    </style>
</head>
<body>
    @if(count($dayGroups) > 0)
        @foreach($dayGroups as $day => $customers)
            <table class="grid @if(! $loop->first) day-break @endif">
                <thead>
                    <tr>
                        <td colspan="2">
                            <div class="page-header">
                                <div class="page-header-left">
                                    <div class="day-title">{{ $day === 'onbekend' ? 'Geen leverdag ingesteld' : ucfirst($day) }}</div>
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
                        </td>
                    </tr>
                </thead>
                <tbody>
                    @foreach(array_chunk($customers, 2) as $pair)
                        <tr>
                            <td class="cell">
                                @include('pdf.partials.customer-card', ['customer' => $pair[0]])
                            </td>
                            <td class="cell">
                                @isset($pair[1])
                                    @include('pdf.partials.customer-card', ['customer' => $pair[1]])
                                @endisset
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @else
        <div style="padding: 40px; text-align: center; color: #777; font-size: 9pt;">
            Geen goedgekeurde klanten gevonden.
        </div>
    @endif
</body>
</html>
