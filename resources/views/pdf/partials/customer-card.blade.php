<div class="card">
    <div class="card-header">
        <div class="card-header-left">
            <div class="card-name">{{ $customer['company_name'] }}</div>
        </div>
        <div class="card-header-right">
            <div class="card-phone">Klantnr. {{ $customer['id'] }}@if($customer['phone_number']) &nbsp;&middot;&nbsp; Tel. {{ $customer['phone_number'] }}@endif</div>
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
    @if(!empty($customer['notes']))
        <div class="notes">
            <strong>Notitie:</strong> {{ implode(' • ', $customer['notes']) }}
        </div>
    @endif
</div>
