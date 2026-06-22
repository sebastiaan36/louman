<div class="card">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-name">{{ $customer['company_name'] }}</div>
        </div>
        <div class="card-header-right">
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
        </div>
    </div>
    @if(!empty($customer['products']))
        <table class="products">
            @foreach($customer['products'] as $product)
                <tr>
                    <td class="art">{{ $product['article_number'] }}</td>
                    <td class="name">{{ $product['title'] }}@if(! empty($product['weight'])) <span class="weight">— {{ $product['weight'] }}</span>@endif</td>
                    <td class="qty">{{ $product['quantity'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif
    @if(!empty($customer['packaging_notes']))
        <div class="notes">
            <strong>Verpakking:</strong> {{ $customer['packaging_notes'] }}
        </div>
    @endif
    @if(!empty($customer['notes']))
        <div class="notes">
            <strong>Notitie:</strong> {{ implode(' • ', $customer['notes']) }}
        </div>
    @endif
</div>
