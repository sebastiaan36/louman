<?php

use App\Models\Customer;
use App\Models\CustomerCategory;
use Inertia\Testing\AssertableInertia as Assert;

test('detailhandel staat standaard tussen de klantcategorieën', function () {
    expect(CustomerCategory::query()->orderBy('name')->pluck('name', 'slug')->all())->toBe([
        'broodjeszaak' => 'Broodjeszaak',
        'detailhandel' => 'Detailhandel',
        'groothandel' => 'Groothandel',
        'horeca' => 'Horeca',
    ]);
});

test('een klant kan in de categorie detailhandel worden gezet', function () {
    $customer = approvedCustomer();

    $this->actingAs(adminUser())
        ->patch("/admin/customers/{$customer->id}/category-discount", [
            'customer_category' => 'detailhandel',
            'show_on_map' => true,
        ])
        ->assertSessionHasNoErrors();

    expect($customer->fresh()->customer_category)->toBe('detailhandel')
        ->and($customer->fresh()->getCategoryLabel())->toBe('Detailhandel');
});

test('een onbekende klantcategorie wordt geweigerd', function () {
    $customer = approvedCustomer();

    $this->actingAs(adminUser())
        ->patch("/admin/customers/{$customer->id}/category-discount", [
            'customer_category' => 'bestaat-niet',
            'show_on_map' => true,
        ])
        ->assertSessionHasErrors(['customer_category' => 'Ongeldige klantcategorie.']);
});

test('de instellingen tonen elke klantcategorie met het aantal klanten', function () {
    Customer::factory()->approved()->count(2)->create(['customer_category' => 'horeca']);
    Customer::factory()->approved()->create(['customer_category' => 'detailhandel']);
    Customer::factory()->approved()->create(['customer_category' => null]);

    $this->actingAs(adminUser())
        ->get('/admin/settings')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Settings')
            ->where('customerCategories', fn ($categories) => collect($categories)->pluck('customers_count', 'name')->all() === [
                'Broodjeszaak' => 0,
                'Detailhandel' => 1,
                'Groothandel' => 0,
                'Horeca' => 2,
            ])
            ->where('customersWithoutCategoryCount', 1)
        );
});

test('admin kan een klantcategorie aanmaken die direct te kiezen is bij een klant', function () {
    $admin = adminUser();

    $this->actingAs($admin)
        ->post('/admin/settings/customer-categories', ['name' => '  Cateraar Plus '])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    expect(CustomerCategory::query()->where('slug', 'cateraar-plus')->value('name'))->toBe('Cateraar Plus');

    $customer = approvedCustomer();
    $this->actingAs($admin)
        ->get("/admin/customers/{$customer->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('customerCategories', fn ($options) => collect($options)->contains(['value' => 'cateraar-plus', 'label' => 'Cateraar Plus']))
        );
});

test('een klantcategorie met een bestaande naam of zonder naam wordt geweigerd', function (string $name, string $message) {
    $this->actingAs(adminUser())
        ->post('/admin/settings/customer-categories', ['name' => $name])
        ->assertSessionHasErrors(['name' => $message]);

    expect(CustomerCategory::count())->toBe(4);
})->with([
    'zelfde naam' => ['Horeca', 'Er bestaat al een categorie met deze naam.'],
    'zelfde naam andere schrijfwijze' => ['HORECA', 'Er bestaat al een categorie met deze naam.'],
    'leeg' => ['', 'Vul een naam in.'],
    'alleen tekens' => ['!!!', 'Vul een naam met letters of cijfers in.'],
]);

test('een lege klantcategorie kan worden verwijderd, een categorie met klanten niet', function () {
    $admin = adminUser();
    $empty = CustomerCategory::query()->where('slug', 'detailhandel')->first();
    $inUse = CustomerCategory::query()->where('slug', 'horeca')->first();
    Customer::factory()->approved()->create(['customer_category' => 'horeca']);

    $this->actingAs($admin)->delete("/admin/settings/customer-categories/{$empty->id}")->assertSessionHas('success');
    $this->actingAs($admin)->delete("/admin/settings/customer-categories/{$inUse->id}")->assertSessionHas('error');

    expect(CustomerCategory::query()->pluck('slug')->sort()->values()->all())->toBe(['broodjeszaak', 'groothandel', 'horeca']);
});

test('alleen beheerders kunnen klantcategorieën aanmaken', function () {
    $this->actingAs(customerUser())
        ->post('/admin/settings/customer-categories', ['name' => 'Detailhandel 2'])
        ->assertForbidden();

    expect(CustomerCategory::count())->toBe(4);
});
