<table class="card">
    <tr>
        <td>
            <table class="card-header">
                <tr>
                    <td class="card-header-left">
                        <div class="card-name">{{ $customer['company_name'] }}</div>
                    </td>
                    <td class="card-header-right">
                        @php
                            $contactParts = [];
                            if (! empty($customer['number'])) {
                                $contactParts[] = 'Klantnr. '.e($customer['number']);
                            }
                            if (! empty($customer['phone_number'])) {
                                $contactParts[] = 'Tel. '.e($customer['phone_number']);
                            }
                        @endphp
                        <div class="card-phone">{!! implode(' &nbsp;&middot;&nbsp; ', $contactParts) !!}</div>
                        @if($customer['is_pickup'])
                            <div class="pickup-badge">Ophalen</div>
                        @endif
                        @if(empty($customer['products']))
                            <div class="card-empty-label">geen bestelling</div>
                        @endif
                    </td>
                </tr>
            </table>
            @if(!empty($customer['products']))
                @foreach($customer['products'] as $product)
                    {{-- One table per product so the divider line is a table border
                         (cell borders are not drawn on the production mPDF build). --}}
                    <table class="product @unless($loop->first) product-divider @endunless">
                        <tr>
                            <td class="art">{{ $product['article_number'] }}</td>
                            <td class="name">{{ $product['title'] }}@if(! empty($product['weight'])) <span class="weight">— {{ $product['weight'] }}</span>@endif</td>
                            <td class="qty">{{ $product['quantity'] }}</td>
                        </tr>
                    </table>
                @endforeach
            @endif
            @if(!empty($customer['packaging_notes']))
                <table class="notes">
                    <tr><td><strong>Verpakking:</strong> {{ $customer['packaging_notes'] }}</td></tr>
                </table>
            @endif
            @if(!empty($customer['notes']))
                <table class="notes">
                    <tr><td><strong>Notitie:</strong> {{ implode(' • ', $customer['notes']) }}</td></tr>
                </table>
            @endif
        </td>
    </tr>
</table>
