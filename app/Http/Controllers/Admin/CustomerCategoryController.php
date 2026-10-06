<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCustomerCategoryRequest;
use App\Models\AuditLog;
use App\Models\CustomerCategory;
use Illuminate\Http\RedirectResponse;

class CustomerCategoryController extends Controller
{
    /**
     * Add a customer category; it is shown right away in the customer settings.
     */
    public function store(StoreCustomerCategoryRequest $request): RedirectResponse
    {
        $category = CustomerCategory::create([
            'slug' => $request->slug(),
            'name' => $request->validated('name'),
        ]);

        AuditLog::record('customer_category.created', "Klantcategorie {$category->name} aangemaakt", $category);

        return back()->with('success', "Klantcategorie {$category->name} is toegevoegd.");
    }

    /**
     * Remove a customer category, only while no customer is in it.
     */
    public function destroy(CustomerCategory $customerCategory): RedirectResponse
    {
        if ($customerCategory->customers()->exists()) {
            return back()->with('error', "Klantcategorie {$customerCategory->name} heeft nog klanten en kan niet worden verwijderd.");
        }

        $name = $customerCategory->name;
        $customerCategory->delete();

        AuditLog::record('customer_category.deleted', "Klantcategorie {$name} verwijderd", null, ['name' => $name]);

        return back()->with('success', "Klantcategorie {$name} is verwijderd.");
    }
}
