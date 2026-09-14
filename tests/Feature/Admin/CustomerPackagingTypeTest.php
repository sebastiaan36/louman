<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\MpdfRenderer;

function capturePackagingOverviewPdf(): object
{
    $holder = new stdClass;
    $holder->data = null;

    $mock = Mockery::mock(MpdfRenderer::class);
    $mock->shouldReceive('loadView')
        ->once()
        ->andReturnUsing(function ($view, $data) use ($holder, $mock) {
            $holder->data = $data;

            return $mock;
        });
    $mock->shouldReceive('stream')->andReturn(response('', 200));

    app()->instance(MpdfRenderer::class, $mock);

    return $holder;
}

function packagingSettingsPayload(array $overrides = []): array
{
    return array_merge([
        'customer_category' => 'horeca',
        'discount_percentage' => '',
        'delivery_day' => 'maandag',
        'show_on_map' => true,
    ], $overrides);
}

test('admin kan de verpakking van een klant instellen', function () {
    $customer = Customer::factory()->approved()->create();

    $this->actingAs(adminUser())
        ->patch("/admin/customers/{$customer->id}/category-discount", packagingSettingsPayload(['packaging_type' => 'krat']))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($customer->fresh()->packaging_type)->toBe('krat');

    $this->actingAs(adminUser())
        ->get("/admin/customers/{$customer->id}")
        ->assertInertia(fn ($page) => $page
            ->where('customer.packaging_type', 'krat')
            ->where('customer.packaging_type_label', 'Krat')
        );
});

test('de verpakking kan weer leeg worden gemaakt', function () {
    $customer = Customer::factory()->approved()->create(['packaging_type' => 'tas']);

    $this->actingAs(adminUser())
        ->patch("/admin/customers/{$customer->id}/category-discount", packagingSettingsPayload(['packaging_type' => null]))
        ->assertRedirect();

    expect($customer->fresh()->packaging_type)->toBeNull();
});

test('een onbekende verpakking wordt geweigerd', function () {
    $customer = Customer::factory()->approved()->create();

    $this->actingAs(adminUser())
        ->from("/admin/customers/{$customer->id}")
        ->patch("/admin/customers/{$customer->id}/category-discount", packagingSettingsPayload(['packaging_type' => 'zak']))
        ->assertSessionHasErrors('packaging_type');
});

test('het besteldetail toont de verpakking van de klant', function () {
    $customer = Customer::factory()->approved()->create([
        'packaging_type' => 'doos',
        'packaging_notes' => 'Per 5 vacuüm',
    ]);
    $order = Order::factory()->create(['customer_id' => $customer->id]);

    $this->actingAs(adminUser())
        ->get("/admin/orders/{$order->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('order.customer.packaging_type', 'Doos')
            ->where('order.customer.packaging_notes', 'Per 5 vacuüm')
        );

    expect(file_get_contents(dirname(__DIR__, 3).'/resources/js/pages/admin/OrderDetail.vue'))
        ->toContain('Verpakking:')
        ->toContain('order.customer.packaging_type');
});

test('het bestellingenoverzicht toont de verpakking per klant', function () {
    $customer = Customer::factory()->approved()->create([
        'company_name' => 'Koffiehuis De Markt',
        'delivery_day' => 'maandag',
        'packaging_type' => 'krat',
        'packaging_notes' => 'Kaas apart',
    ]);
    $product = Product::factory()->create();
    $order = Order::factory()->confirmed()->create(['customer_id' => $customer->id]);
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 1]);

    $overview = capturePackagingOverviewPdf();

    $this->actingAs(adminUser())
        ->get('/admin/orders/customer-overview')
        ->assertOk();

    $card = collect($overview->data['dayGroups']['maandag'])->firstWhere('company_name', 'Koffiehuis De Markt');

    expect($card['packaging_type'])->toBe('Krat');

    $html = view('pdf.partials.customer-card', ['customer' => $card])->render();

    expect($html)->toContain('<strong>Verpakking:</strong> Krat — Kaas apart');
});

test('het klantdetail heeft een kopje Verpakking met de drie opties', function () {
    $detail = file_get_contents(dirname(__DIR__, 3).'/resources/js/pages/admin/CustomerDetail.vue');

    expect($detail)
        ->toContain('<p class="text-sm font-medium">Verpakking</p>')
        ->toContain('v-for="type in packagingTypes"')
        ->toContain("import { PACKAGING_TYPES } from '@/lib/packagingTypes';");
});
