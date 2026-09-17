<?php

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function sourceProductForDuplicate(): Product
{
    Storage::fake('public');
    Storage::disk('public')->put('products/bron.jpg', 'foto');
    Storage::disk('public')->put('products/thumbs/bron.jpg', 'thumb');

    $customer = Customer::factory()->approved()->create();

    $product = Product::factory()->create([
        'title' => 'Grillworst naturel',
        'article_number' => '123',
        'price' => '4.50',
        'price_per_kg' => '18.00',
        'description' => 'Ambachtelijk gegrild.',
        'ingredients' => ['varkensvlees', 'zout'],
        'allergens' => ['selderij'],
        'nutrition_facts' => ['energy' => '900', 'fat' => '25'],
        'weight' => '250 gram',
        'photo' => 'products/bron.jpg',
        'is_private_label' => true,
        'from_butchery' => true,
    ]);
    $product->visibleToCustomers()->sync([$customer->id]);

    return $product;
}

test('het duplicaatformulier is gevuld met alle gegevens behalve het artikelnummer', function () {
    $product = sourceProductForDuplicate();

    $this->actingAs(adminUser())
        ->get("/admin/products/{$product->id}/duplicate")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/ProductForm')
            ->where('duplicateOf', $product->id)
            ->where('product.id', null)
            ->where('product.article_number', '')
            ->where('product.title', 'Grillworst naturel')
            ->where('product.price', '4.50')
            ->where('product.price_per_kg', '18.00')
            ->where('product.ingredients', 'varkensvlees, zout')
            ->where('product.allergens', 'selderij')
            ->where('product.nutrition_facts.energy', '900')
            ->where('product.weight', '250 gram')
            ->where('product.is_private_label', true)
            ->where('product.from_butchery', true)
            ->where('product.visible_customer_ids', $product->visibleToCustomers()->pluck('customers.id')->all())
            ->where('product.photo_url', $product->photo_url)
        );
});

test('opslaan van een duplicaat maakt een nieuw product met een kopie van de foto', function () {
    $product = sourceProductForDuplicate();

    $this->actingAs(adminUser())
        ->post('/admin/products', productPayload([
            'title' => 'Grillworst naturel',
            'article_number' => '124',
            'price' => '4.50',
            'duplicate_of' => $product->id,
            'is_private_label' => '1',
            'from_butchery' => '1',
            'visible_customer_ids' => $product->visibleToCustomers()->pluck('customers.id')->all(),
        ]))
        ->assertRedirect('/admin/products')
        ->assertSessionHasNoErrors();

    $copy = Product::where('article_number', '124')->firstOrFail();

    expect($copy->id)->not->toBe($product->id)
        ->and($copy->photo)->not->toBeNull()
        ->and($copy->photo)->not->toBe($product->photo)
        ->and($copy->from_butchery)->toBeTrue()
        ->and($copy->visibleToCustomers()->pluck('customers.id')->all())->toBe($product->visibleToCustomers()->pluck('customers.id')->all());

    Storage::disk('public')->assertExists($copy->photo);
    Storage::disk('public')->assertExists('products/thumbs/'.basename($copy->photo));
    Storage::disk('public')->assertExists('products/bron.jpg');
});

test('een duplicaat mag niet hetzelfde artikelnummer krijgen', function () {
    $product = sourceProductForDuplicate();

    $this->actingAs(adminUser())
        ->from("/admin/products/{$product->id}/duplicate")
        ->post('/admin/products', productPayload([
            'article_number' => '123',
            'duplicate_of' => $product->id,
        ]))
        ->assertSessionHasErrors('article_number');

    expect(Product::count())->toBe(1);
});

test('een nieuwe foto bij een duplicaat wint van de gekopieerde foto', function () {
    $product = sourceProductForDuplicate();

    $this->actingAs(adminUser())
        ->post('/admin/products', productPayload([
            'article_number' => '125',
            'duplicate_of' => $product->id,
            'photo' => UploadedFile::fake()->image('nieuw.jpg'),
        ]))
        ->assertSessionHasNoErrors();

    $copy = Product::where('article_number', '125')->firstOrFail();

    expect($copy->photo)->toEndWith('.jpg')
        ->and(Storage::disk('public')->get($copy->photo))->not->toBe('foto');
});

test('zonder duplicaat blijft een foto verplicht bij een nieuw product', function () {
    $this->actingAs(adminUser())
        ->from('/admin/products/create')
        ->post('/admin/products', productPayload(['article_number' => '900']))
        ->assertSessionHasErrors('photo');
});

test('de productenlijst heeft een knop Dupliceren en het formulier kent de duplicaatstand', function () {
    $root = dirname(__DIR__, 3);

    expect(file_get_contents($root.'/resources/js/pages/admin/Products.vue'))
        ->toContain('/admin/products/${product.id}/duplicate${listQuery}')
        ->toContain('Dupliceren');
    expect(file_get_contents($root.'/resources/js/pages/admin/ProductForm.vue'))
        ->toContain('const isEdit = computed(() => !!props.product?.id);')
        ->toContain("formData.append('duplicate_of', props.duplicateOf.toString());")
        ->toContain('Product dupliceren');
});
