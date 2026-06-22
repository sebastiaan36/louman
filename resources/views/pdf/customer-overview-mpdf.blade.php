<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Bestellingenoverzicht</title>
    <style>
        body {
            font-family: 'dejavusans', sans-serif;
            font-size: 8pt;
            color: #222;
            line-height: 1.3;
        }

        /* Repeating page header (mPDF running header — replaces the old <thead>) */
        .page-header {
            border-bottom: 2px solid #222;
            padding-bottom: 5px;
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

        /*
         * Column-major flow is produced by mPDF's native <columns> tag (see
         * the body) combined with the keepColumns config flag — NOT by CSS
         * column-count, which mPDF 8.x silently ignores. mPDF measures and
         * breaks itself; PHP only supplies the flat list of cards.
         */

        /* Customer card — must never split across columns or pages.
           An explicit background is required: in mPDF column mode a bordered
           block without a background hits an undefined-key error when it is
           relocated to the next column. */
        .card {
            border: 1.5px solid #bbb;
            background-color: #ffffff;
            padding: 5px 7px;
            margin-bottom: 5px;
            min-height: 36px;
            page-break-inside: avoid;
            break-inside: avoid;
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
            @php $headerName = 'day'.$loop->index; @endphp

            {{-- Define the running header for this day, then activate it. mPDF
                 keeps it on every overflow page until the next day switches it. --}}
            <htmlpageheader name="{{ $headerName }}">
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
            </htmlpageheader>

            @if($loop->first)
                <sethtmlpageheader name="{{ $headerName }}" value="on" show-this-page="1" />
            @else
                <pagebreak header="{{ $headerName }}" />
            @endif

            {{-- mPDF native columns: with keepColumns=on this fills column 1
                 top→bottom completely, then column 2, then the next page. --}}
            <columns column-count="2" column-gap="10" />
            @foreach($customers as $customer)
                @include('pdf.partials.customer-card', ['customer' => $customer])
            @endforeach
            <columns column-count="1" />
        @endforeach
    @else
        <div style="padding: 40px; text-align: center; color: #777; font-size: 9pt;">
            Geen goedgekeurde klanten gevonden.
        </div>
    @endif
</body>
</html>
