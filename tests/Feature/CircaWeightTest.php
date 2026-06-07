<?php

use App\Mail\OrderConfirmation;
use App\Mail\OrderShipped;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

function orderWithWeightedProduct(): Order
{
    $customer = approvedCustomer();
    $order = Order::factory()->confirmed()->create(['customer_id' => $customer->id]);
    $product = Product::factory()->create(['weight' => '500 gram']);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    return $order->load(['customer.user', 'deliveryAddress', 'items.product']);
}

test('pakbon toont gewicht met circa', function () {
    $order = orderWithWeightedProduct();

    $html = view('pdf.packing-slip', ['order' => $order])->render();

    expect($html)->toContain('circa 500 gram');
});

test('bestelbevestigingsmail toont gewicht met circa', function () {
    $order = orderWithWeightedProduct();

    $html = (new OrderConfirmation($order))->render();

    expect($html)->toContain('circa 500 gram');
});

test('verzondenmail toont gewicht met circa', function () {
    $order = orderWithWeightedProduct();

    $html = (new OrderShipped($order))->render();

    expect($html)->toContain('circa 500 gram');
});
