<?php

use App\Mail\OrderShipped;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

test('de mail bij een voltooide bestelling bedankt en noemt de leverdag', function () {
    $order = Order::factory()->completed()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => Product::factory()->create()->id,
    ]);

    $mail = new OrderShipped($order->fresh());
    $html = $mail->render();

    expect($html)
        ->toContain('Bedankt voor uw bestelling')
        ->toContain('Wij leveren uw bestelling op de met u afgesproken leverdag.')
        // De klant kreeg eerder te horen dat de bestelling al onderweg was.
        ->not->toContain('is verzonden')
        ->not->toContain('onderweg naar u');
});

test('het onderwerp spreekt niet meer van verzonden', function () {
    $order = Order::factory()->completed()->create();

    expect((new OrderShipped($order))->envelope()->subject)
        ->toBe('Bedankt voor uw bestelling #'.$order->id.' - Slagerij Louman');
});
