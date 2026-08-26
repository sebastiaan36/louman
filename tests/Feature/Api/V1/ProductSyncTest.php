<?php

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Support\ApiAbility;

it('creates an article that does not exist yet', function () {
    $response = $this->putJson('/api/v1/products/ART-100', productPayload([
        'external_id' => 'EXT-9',
        'price_per_kg' => 8.50,
        'weight' => '250 g',
        'ingredients' => ['bloem', 'kaas'],
        'allergens' => ['gluten', 'melk'],
        'category' => 'Broodjes',
        'nutrition_facts' => ['energy' => 250, 'fat' => 10],
    ]), apiHeaders([ApiAbility::ProductsWrite]));

    $response->assertCreated()
        ->assertJsonPath('data.article_number', 'ART-100')
        ->assertJsonPath('data.title', 'Kaasbroodje')
        ->assertJsonPath('data.external_id', 'EXT-9');

    $product = Product::where('article_number', 'ART-100')->firstOrFail();

    expect($product->price)->toBe('1.25')
        ->and($product->ingredients)->toBe(['bloem', 'kaas'])
        ->and($product->category?->name)->toBe('Broodjes')
        ->and($product->synced_at)->not->toBeNull();
});

it('updates an existing article and returns 200 instead of 201', function () {
    Product::factory()->create(['article_number' => 'ART-100', 'title' => 'Oude naam']);

    $this->putJson('/api/v1/products/ART-100', productPayload(['title' => 'Nieuwe naam']), apiHeaders([ApiAbility::ProductsWrite]))
        ->assertOk()
        ->assertJsonPath('data.title', 'Nieuwe naam');

    expect(Product::count())->toBe(1);
});

it('clears fields that are left out, because the external package is leading', function () {
    Product::factory()->create([
        'article_number' => 'ART-100',
        'price_per_kg' => 9.99,
        'weight' => '1 kg',
    ]);

    $this->putJson('/api/v1/products/ART-100', productPayload(), apiHeaders([ApiAbility::ProductsWrite]))
        ->assertOk();

    $product = Product::where('article_number', 'ART-100')->firstOrFail();

    expect($product->price_per_kg)->toBeNull()
        ->and($product->weight)->toBeNull();
});

it('keeps an article private label when the flag is not sent', function () {
    Product::factory()->create([
        'article_number' => 'ART-100',
        'is_private_label' => true,
    ]);

    $this->putJson('/api/v1/products/ART-100', productPayload(), apiHeaders([ApiAbility::ProductsWrite]))
        ->assertOk();

    expect(Product::where('article_number', 'ART-100')->first()->is_private_label)->toBeTrue();
});

it('places a subcategory under the category it was sent with', function () {
    $this->putJson('/api/v1/products/ART-100', productPayload([
        'category' => 'Brood',
        'subcategory' => 'Zuurdesem',
    ]), apiHeaders([ApiAbility::ProductsWrite]))->assertCreated();

    $product = Product::where('article_number', 'ART-100')->firstOrFail();
    $subcategory = Category::find($product->subcategory_id);

    expect($subcategory?->name)->toBe('Zuurdesem')
        ->and($subcategory?->parent_id)->toBe($product->category_id);
});

it('updates availability without touching the rest of the article', function () {
    $product = Product::factory()->create([
        'article_number' => 'ART-100',
        'title' => 'Kaasbroodje',
        'in_stock' => true,
    ]);

    $this->patchJson('/api/v1/products/ART-100/stock', ['in_stock' => false], apiHeaders([ApiAbility::ProductsWrite]))
        ->assertOk()
        ->assertJsonPath('data.in_stock', false);

    expect($product->fresh())
        ->in_stock->toBeFalse()
        ->title->toBe('Kaasbroodje');
});

it('returns 404 for availability on an unknown article', function () {
    $this->patchJson('/api/v1/products/ONBEKEND/stock', ['in_stock' => false], apiHeaders([ApiAbility::ProductsWrite]))
        ->assertNotFound();
});

it('records every article change in the audit log', function () {
    $this->putJson('/api/v1/products/ART-100', productPayload(), apiHeaders([ApiAbility::ProductsWrite]))
        ->assertCreated();

    $log = AuditLog::where('action', 'api.product.created')->firstOrFail();

    expect($log->user_id)->toBeNull()
        ->and($log->metadata['article_number'])->toBe('ART-100')
        ->and($log->metadata)->toHaveKey('api_client');
});

it('lists only articles changed since the given moment', function () {
    $old = Product::factory()->create(['article_number' => 'ART-OLD']);
    $old->forceFill(['updated_at' => now()->subDays(10)])->saveQuietly();

    Product::factory()->create(['article_number' => 'ART-NEW']);

    $response = $this->getJson(
        '/api/v1/products?updated_since='.urlencode(now()->subDay()->toIso8601String()),
        apiHeaders([ApiAbility::ProductsRead]),
    );

    $response->assertOk()->assertJsonCount(1, 'data');
    expect($response->json('data.0.article_number'))->toBe('ART-NEW');
});

it('rejects an unparsable updated_since', function () {
    $this->getJson('/api/v1/products?updated_since=gisteren', apiHeaders([ApiAbility::ProductsRead]))
        ->assertStatus(422)
        ->assertJsonValidationErrors('updated_since');
});
