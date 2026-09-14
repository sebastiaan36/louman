<?php

use App\Models\Customer;

test('admin kan filteren op klanten die nog geen account hebben', function () {
    $withAccount = Customer::factory()->approved()->create(['company_name' => 'Met Account BV']);
    $withoutAccount = Customer::factory()->approved()->create(['company_name' => 'Zonder Account BV', 'user_id' => null]);

    $this->actingAs(adminUser())
        ->get('/admin/customers?account=without')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('customers', 1)
            ->where('customers.0.company_name', 'Zonder Account BV')
            ->where('customers.0.has_account', false)
            ->where('filters.account', 'without')
        );

    $this->actingAs(adminUser())
        ->get('/admin/customers?account=with')
        ->assertInertia(fn ($page) => $page
            ->has('customers', 1)
            ->where('customers.0.company_name', 'Met Account BV')
            ->where('customers.0.has_account', true)
        );

    expect($withAccount->user)->not->toBeNull()
        ->and($withoutAccount->user)->toBeNull();
});

test('zonder filter of met een onbekende waarde worden alle klanten getoond', function () {
    Customer::factory()->approved()->create();
    Customer::factory()->approved()->create(['user_id' => null]);

    $this->actingAs(adminUser())
        ->get('/admin/customers')
        ->assertInertia(fn ($page) => $page->has('customers', 2)->where('filters.account', 'all'));

    $this->actingAs(adminUser())
        ->get('/admin/customers?account=iets-anders')
        ->assertInertia(fn ($page) => $page->has('customers', 2)->where('filters.account', 'all'));
});

test('het accountfilter werkt samen met zoeken', function () {
    Customer::factory()->approved()->create(['company_name' => 'Bakker Jansen', 'user_id' => null]);
    Customer::factory()->approved()->create(['company_name' => 'Bakker De Vries']);
    Customer::factory()->approved()->create(['company_name' => 'Slager Pietersen', 'user_id' => null]);

    $this->actingAs(adminUser())
        ->get('/admin/customers?account=without&search=Bakker')
        ->assertInertia(fn ($page) => $page
            ->has('customers', 1)
            ->where('customers.0.company_name', 'Bakker Jansen')
        );
});

test('de klantenpagina heeft een dropdown voor het accountfilter', function () {
    $pagina = file_get_contents(dirname(__DIR__, 3).'/resources/js/pages/admin/Customers.vue');

    expect($pagina)
        ->toContain('<SelectItem value="without">Nog geen account</SelectItem>')
        ->toContain('<SelectItem value="with">Met account</SelectItem>')
        ->toContain("params.set('account', selectedAccount.value)");
});
