<?php

use App\Mail\OrderShipped;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\ApiAbility;
use Illuminate\Support\Facades\Mail;

it('exposes an order with its lines and VAT breakdown', function () {
    $customer = approvedCustomer();
    $customer->update(['customer_number' => 'K-001']);

    $product = Product::factory()->create(['article_number' => 'ART-1', 'title' => 'Kaasbroodje']);
    $order = Order::factory()->create(['customer_id' => $customer->id, 'total' => 10.00]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 4,
        'price' => 2.50,
    ]);

    $this->getJson("/api/v1/orders/{$order->id}", apiHeaders([ApiAbility::OrdersRead]))
        ->assertOk()
        ->assertJsonPath('data.customer_number', 'K-001')
        ->assertJsonPath('data.total_excluding_vat', '10.00')
        ->assertJsonPath('data.vat_amount', '0.90')
        ->assertJsonPath('data.total_including_vat', '10.90')
        ->assertJsonPath('data.items.0.article_number', 'ART-1')
        ->assertJsonPath('data.items.0.line_total', '10.00');
});

it('filters orders by status', function () {
    Order::factory()->confirmed()->create();
    Order::factory()->pending()->create();

    $this->getJson('/api/v1/orders?status=confirmed', apiHeaders([ApiAbility::OrdersRead]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'confirmed');
});

it('rejects an unknown status filter', function () {
    $this->getJson('/api/v1/orders?status=verzonden', apiHeaders([ApiAbility::OrdersRead]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');
});

it('writes the status back and notifies the customer when an order is completed', function () {
    Mail::fake();

    $customer = approvedCustomer();
    $customer->update(['packing_slip_email' => 'pakbon@klant.nl']);
    $order = Order::factory()->confirmed()->create(['customer_id' => $customer->id]);

    $this->patchJson("/api/v1/orders/{$order->id}/status", [
        'status' => 'completed',
    ], apiHeaders([ApiAbility::OrdersWrite]))
        ->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.status_label', 'Voltooid');

    expect($order->fresh()->status)->toBe('completed');

    Mail::assertQueued(OrderShipped::class, fn (OrderShipped $mail) => $mail->hasTo('pakbon@klant.nl'));
});

it('does not resend the notification when the status is unchanged', function () {
    Mail::fake();

    $order = Order::factory()->completed()->create();

    $this->patchJson("/api/v1/orders/{$order->id}/status", [
        'status' => 'completed',
    ], apiHeaders([ApiAbility::OrdersWrite]))->assertOk();

    Mail::assertNothingQueued();
});

it('rejects an unknown status', function () {
    $order = Order::factory()->create();

    $this->patchJson("/api/v1/orders/{$order->id}/status", [
        'status' => 'onderweg',
    ], apiHeaders([ApiAbility::OrdersWrite]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');
});

it('records the status change in the audit log', function () {
    Mail::fake();

    $order = Order::factory()->confirmed()->create();

    $this->patchJson("/api/v1/orders/{$order->id}/status", [
        'status' => 'completed',
    ], apiHeaders([ApiAbility::OrdersWrite]))->assertOk();

    $log = AuditLog::where('action', 'api.order.status_updated')->firstOrFail();

    expect($log->metadata['previous_status'])->toBe('confirmed')
        ->and($log->metadata['new_status'])->toBe('completed')
        ->and($log->user_id)->toBeNull();
});

it('does not let a read-only token change the status', function () {
    $order = Order::factory()->create();

    $this->patchJson("/api/v1/orders/{$order->id}/status", [
        'status' => 'completed',
    ], apiHeaders([ApiAbility::OrdersRead]))->assertForbidden();
});
