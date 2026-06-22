<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Bestellingenoverzicht</title>
    <style>
        @include('pdf.partials.overview-css')
    </style>
</head>
<body>
@php
    $paginator = app(\App\Services\OverviewColumnPaginator::class);
    $days = $paginator->paginate($dayGroups);
    $colMm = $paginator->columnWidthMm();
    $gapMm = $paginator->gapMm();
    $firstPageRendered = false;
@endphp

@if(count($days) > 0)
    @foreach($days as $day => $pages)
        @php $headerName = 'day'.$loop->index; @endphp

        {{-- Define this day's running header; sethtmlpageheader switches it on
             the day's first page and it repeats until the next day. --}}
        <htmlpageheader name="{{ $headerName }}">
            <div class="page-header">
                <div class="page-header-left">
                    <div class="day-title">{{ $day === 'onbekend' ? 'Geen leverdag ingesteld' : ucfirst($day) }}</div>
                    <div class="meta">
                        {{ count($dayGroups[$day]) }} {{ count($dayGroups[$day]) === 1 ? 'klant' : 'klanten' }}
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

        @foreach($pages as $page)
            @if($firstPageRendered)
                <pagebreak />
            @endif

            @if($loop->first)
                <sethtmlpageheader name="{{ $headerName }}" value="on" show-this-page="1" />
            @endif

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: {{ $colMm }}mm; vertical-align: top; padding: 0;">
                        @foreach($page['left'] as $customer)
                            @include('pdf.partials.customer-card', ['customer' => $customer])
                        @endforeach
                    </td>
                    <td style="width: {{ $gapMm }}mm; padding: 0;"></td>
                    <td style="width: {{ $colMm }}mm; vertical-align: top; padding: 0;">
                        @foreach($page['right'] as $customer)
                            @include('pdf.partials.customer-card', ['customer' => $customer])
                        @endforeach
                    </td>
                </tr>
            </table>

            @php $firstPageRendered = true; @endphp
        @endforeach
    @endforeach
@else
    <div style="padding: 40px; text-align: center; color: #777; font-size: 9pt;">
        Geen goedgekeurde klanten gevonden.
    </div>
@endif
</body>
</html>
