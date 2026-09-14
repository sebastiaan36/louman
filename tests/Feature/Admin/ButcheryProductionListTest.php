<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function captureProductionListPdf(): object
{
    $captured = new class
    {
        public ?string $view = null;

        public array $data = [];
    };

    $pdfMock = Mockery::mock(Barryvdh\DomPDF\PDF::class);
    $pdfMock->shouldReceive('stream')->andReturn(response('', 200));

    Pdf::shouldReceive('loadView')
        ->once()
        ->andReturnUsing(function ($view, $data) use ($captured, $pdfMock) {
            $captured->view = $view;
            $captured->data = $data;

            return $pdfMock;
        });

    return $captured;
}

function confirmedOrderWithButcheryAndRegularProduct(): void
{
    $customer = approvedCustomer();
    $order = Order::factory()->confirmed()->create(['customer_id' => $customer->id]);

    $regular = Product::factory()->create(['title' => 'Gewone rookworst', 'from_butchery' => false]);
    $butchery = Product::factory()->create([
        'title' => 'Huisgemaakte leverworst',
        'is_private_label' => true,
        'from_butchery' => true,
    ]);

    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $regular->id, 'quantity' => 4]);
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $butchery->id, 'quantity' => 6]);
}

test('de gewone productielijst laat producten uit de slagerij weg', function () {
    confirmedOrderWithButcheryAndRegularProduct();
    $captured = captureProductionListPdf();

    $this->actingAs(adminUser())
        ->get('/admin/orders/production-list')
        ->assertOk();

    expect($captured->data['title'])->toBe('PRODUCTIELIJST')
        ->and(array_column($captured->data['products'], 'title'))->toBe(['Gewone rookworst'])
        ->and($captured->data['products'][0]['quantity'])->toBe(4);
});

test('de productielijst slagerij bevat alleen producten uit de slagerij', function () {
    confirmedOrderWithButcheryAndRegularProduct();
    $captured = captureProductionListPdf();

    $this->actingAs(adminUser())
        ->get('/admin/orders/production-list/slagerij')
        ->assertOk();

    expect($captured->view)->toBe('pdf.production-list')
        ->and($captured->data['title'])->toBe('PRODUCTIELIJST SLAGERIJ')
        ->and(array_column($captured->data['products'], 'title'))->toBe(['Huisgemaakte leverworst'])
        ->and($captured->data['products'][0]['quantity'])->toBe(6);
});

test('de productielijst slagerij is alleen voor beheerders', function () {
    $customer = approvedCustomer();

    $this->actingAs($customer->user)
        ->get('/admin/orders/production-list/slagerij')
        ->assertForbidden();
});

test('het vinkje Uit de slagerij wordt opgeslagen bij een private label product', function () {
    Storage::fake('public');

    $this->actingAs(adminUser())
        ->post('/admin/products', productPayload([
            'photo' => UploadedFile::fake()->image('worst.jpg'),
            'article_number' => '901',
            'is_private_label' => '1',
            'from_butchery' => '1',
        ]))
        ->assertRedirect();

    expect(Product::firstWhere('title', 'Kaasbroodje')->from_butchery)->toBeTrue();
});

test('Uit de slagerij wordt uitgezet als het product geen private label is', function () {
    $product = Product::factory()->create(['is_private_label' => true, 'from_butchery' => true]);

    $this->actingAs(adminUser())
        ->patch("/admin/products/{$product->id}", productPayload([
            'article_number' => $product->article_number,
            'is_private_label' => '0',
            'from_butchery' => '1',
        ]))
        ->assertRedirect();

    expect($product->fresh()->from_butchery)->toBeFalse();
});

test('het productformulier krijgt het vinkje mee en de bestellingenpagina heeft de downloadknop', function () {
    $product = Product::factory()->create(['is_private_label' => true, 'from_butchery' => true]);

    $this->actingAs(adminUser())
        ->get("/admin/products/{$product->id}/edit")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('product.from_butchery', true));

    $root = dirname(__DIR__, 3);
    expect(file_get_contents($root.'/resources/js/pages/admin/ProductForm.vue'))
        ->toContain('id="from_butchery"')
        ->toContain('Uit de slagerij');
    expect(file_get_contents($root.'/resources/js/pages/admin/Orders.vue'))
        ->toContain('href="/admin/orders/production-list/slagerij"')
        ->toContain('Productielijst slagerij');
});
