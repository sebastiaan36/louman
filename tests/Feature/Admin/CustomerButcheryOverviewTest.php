<?php

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\MpdfRenderer;
use Illuminate\Support\Facades\Schema;

function captureButcheryOverviewPdf(): object
{
    $holder = new stdClass;
    $holder->data = null;
    $holder->filename = null;

    $mock = Mockery::mock(MpdfRenderer::class);
    $mock->shouldReceive('loadView')
        ->once()
        ->andReturnUsing(function ($view, $data) use ($holder, $mock) {
            $holder->data = $data;

            return $mock;
        });
    $mock->shouldReceive('stream')->andReturnUsing(function ($filename) use ($holder) {
        $holder->filename = $filename;

        return response('', 200);
    });

    app()->instance(MpdfRenderer::class, $mock);

    return $holder;
}

function butcheryAndRegularCustomersWithOrders(): void
{
    $product = Product::factory()->create();

    $butchery = Customer::factory()->approved()->create(['company_name' => 'Slagerijklant', 'delivery_day' => 'maandag', 'from_butchery' => true]);
    $regular = Customer::factory()->approved()->create(['company_name' => 'Gewone klant', 'delivery_day' => 'maandag', 'from_butchery' => false]);

    foreach ([$butchery, $regular] as $customer) {
        $order = Order::factory()->confirmed()->create(['customer_id' => $customer->id]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2]);
    }
}

function overviewCompanyNames(array $dayGroups): array
{
    return collect($dayGroups)->flatten(1)->pluck('company_name')->sort()->values()->all();
}

test('het gewone bestellingenoverzicht bevat alle klanten, ook die uit de slagerij', function () {
    butcheryAndRegularCustomersWithOrders();
    $overview = captureButcheryOverviewPdf();

    $this->actingAs(adminUser())
        ->get('/admin/orders/customer-overview')
        ->assertOk();

    expect(overviewCompanyNames($overview->data['dayGroups']))->toBe(['Gewone klant', 'Slagerijklant'])
        ->and($overview->data['title'])->toBe('Bestellingenoverzicht')
        ->and($overview->data['orderCount'])->toBe(2)
        ->and($overview->data['customerCount'])->toBe(2)
        ->and($overview->filename)->toStartWith('bestellingenoverzicht-');
});

test('het bestellingenoverzicht slagerij bevat alleen klanten uit de slagerij', function () {
    butcheryAndRegularCustomersWithOrders();
    $overview = captureButcheryOverviewPdf();

    $this->actingAs(adminUser())
        ->get('/admin/orders/customer-overview/slagerij')
        ->assertOk();

    expect(overviewCompanyNames($overview->data['dayGroups']))->toBe(['Slagerijklant'])
        ->and($overview->data['title'])->toBe('Bestellingenoverzicht slagerij')
        ->and($overview->data['orderCount'])->toBe(1)
        ->and($overview->data['customerCount'])->toBe(1)
        ->and($overview->filename)->toStartWith('bestellingenoverzicht-slagerij-');
});

test('het bestellingenoverzicht slagerij is alleen voor beheerders', function () {
    $customer = approvedCustomer();

    $this->actingAs($customer->user)
        ->get('/admin/orders/customer-overview/slagerij')
        ->assertForbidden();
});

test('admin kan een klant markeren als uit de slagerij', function () {
    $customer = Customer::factory()->approved()->create();

    $this->actingAs(adminUser())
        ->patch("/admin/customers/{$customer->id}/category-discount", [
            'customer_category' => 'horeca',
            'discount_percentage' => '',
            'delivery_day' => 'maandag',
            'show_on_map' => true,
            'from_butchery' => true,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($customer->fresh()->from_butchery)->toBeTrue();

    $this->actingAs(adminUser())
        ->get("/admin/customers/{$customer->id}")
        ->assertInertia(fn ($page) => $page->where('customer.from_butchery', true));

    $this->actingAs(adminUser())
        ->patch("/admin/customers/{$customer->id}/category-discount", [
            'customer_category' => 'horeca',
            'discount_percentage' => '',
            'delivery_day' => 'maandag',
            'show_on_map' => true,
        ]);

    expect($customer->fresh()->from_butchery)->toBeFalse();
});

test('het vinkje staat bij de klant en niet meer bij het product', function () {
    $root = dirname(__DIR__, 3);

    expect(file_get_contents($root.'/resources/js/pages/admin/CustomerDetail.vue'))
        ->toContain('id="edit_from_butchery"')
        ->toContain('Uit de slagerij');
    expect(file_get_contents($root.'/resources/js/pages/admin/ProductForm.vue'))->not->toContain('from_butchery');
    expect(file_get_contents($root.'/resources/js/pages/admin/Orders.vue'))
        ->toContain('href="/admin/orders/customer-overview/slagerij"')
        ->toContain('Bestellingenoverzicht slagerij')
        ->not->toContain('production-list/slagerij');
    expect(Schema::hasColumn('products', 'from_butchery'))->toBeFalse()
        ->and(Schema::hasColumn('customers', 'from_butchery'))->toBeTrue();
});
