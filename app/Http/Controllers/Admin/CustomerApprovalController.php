<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DeliveryAddress;
use App\Notifications\CustomerApproved;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerApprovalController extends Controller
{
    /**
     * Display a listing of pending customers.
     */
    public function index(): Response
    {
        $pendingCustomers = Customer::with('user')
            ->whereNull('approved_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'company_name' => $customer->company_name,
                'contact_person' => $customer->contact_person,
                'email' => $customer->user->email,
                'phone_number' => $customer->phone_number,
                'kvk_number' => $customer->kvk_number,
                'vat_number' => $customer->vat_number,
                'bank_account' => $customer->bank_account,
                'city' => $customer->city,
                'registered_at' => $customer->created_at->format('d-m-Y H:i'),
            ]);

        return Inertia::render('admin/PendingCustomers', [
            'customers' => $pendingCustomers,
        ]);
    }

    /**
     * Display a listing of all customers.
     */
    public function allCustomers(): Response
    {
        $customers = Customer::with('user')
            ->whereNotNull('approved_at')
            ->orderBy('company_name')
            ->get()
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'company_name' => $customer->company_name,
                'contact_person' => $customer->contact_person,
                'email' => $customer->user->email,
                'phone_number' => $customer->phone_number,
                'city' => $customer->city,
                'approved_at' => $customer->approved_at->format('d-m-Y'),
            ]);

        return Inertia::render('admin/Customers', [
            'customers' => $customers,
        ]);
    }

    /**
     * Display customer details with order history.
     */
    public function show(Customer $customer): Response
    {
        $customer->load(['user', 'deliveryAddresses', 'orders.items.product']);

        $customerData = [
            'id' => $customer->id,
            'company_name' => $customer->company_name,
            'contact_person' => $customer->contact_person,
            'email' => $customer->user->email,
            'phone_number' => $customer->phone_number,
            'kvk_number' => $customer->kvk_number,
            'vat_number' => $customer->vat_number,
            'bank_account' => $customer->bank_account,
            'street_name' => $customer->street_name,
            'house_number' => $customer->house_number,
            'postal_code' => $customer->postal_code,
            'city' => $customer->city,
            'packing_slip_email' => $customer->packing_slip_email,
            'customer_category' => $customer->customer_category,
            'customer_category_label' => $customer->getCategoryLabel(),
            'discount_percentage' => $customer->discount_percentage,
            'delivery_day' => $customer->delivery_day,
            'approved_at' => $customer->approved_at?->format('d-m-Y H:i'),
            'created_at' => $customer->created_at->format('d-m-Y H:i'),
        ];

        $deliveryAddresses = $customer->deliveryAddresses->map(fn ($address) => [
            'id' => $address->id,
            'name' => $address->name,
            'street_name' => $address->street_name,
            'house_number' => $address->house_number,
            'postal_code' => $address->postal_code,
            'city' => $address->city,
            'notes' => $address->notes,
            'is_default' => $address->is_default,
        ]);

        $orders = $customer->orders()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($order) => [
                'id' => $order->id,
                'created_at' => $order->created_at->format('d-m-Y H:i'),
                'total' => number_format((float) $order->total, 2, '.', ''),
                'status' => $order->status,
                'items_count' => $order->items->count(),
            ]);

        return Inertia::render('admin/CustomerDetail', [
            'customer' => $customerData,
            'deliveryAddresses' => $deliveryAddresses,
            'orders' => $orders,
        ]);
    }

    /**
     * Approve a customer.
     */
    public function approve(Request $request, Customer $customer): RedirectResponse
    {
        if ($customer->isApproved()) {
            return back()->with('error', 'Deze klant is al goedgekeurd.');
        }

        $validated = $request->validate([
            'customer_category' => ['required', 'in:groothandel,broodjeszaak,horeca'],
            'discount_percentage' => ['required', 'in:0,2.5,3,5'],
            'delivery_day' => ['required', 'in:maandag,dinsdag,woensdag,donderdag,vrijdag,zaterdag,zondag'],
        ], [
            'customer_category.required' => 'Selecteer een klantcategorie.',
            'customer_category.in' => 'Ongeldige klantcategorie.',
            'discount_percentage.required' => 'Selecteer een kortingspercentage.',
            'discount_percentage.in' => 'Ongeldig kortingspercentage.',
            'delivery_day.required' => 'Selecteer een leverdag.',
            'delivery_day.in' => 'Ongeldige leverdag.',
        ]);

        $customer->update([
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'customer_category' => $validated['customer_category'],
            'discount_percentage' => $validated['discount_percentage'],
            'delivery_day' => $validated['delivery_day'],
        ]);

        $customer->user->notify(new CustomerApproved);

        return back()->with('success', "Klant {$customer->company_name} is goedgekeurd.");
    }

    /**
     * Update customer category and discount.
     */
    public function updateCategoryAndDiscount(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'customer_category' => ['required', 'in:groothandel,broodjeszaak,horeca'],
            'discount_percentage' => ['required', 'in:0,2.5,3,5'],
            'delivery_day' => ['required', 'in:maandag,dinsdag,woensdag,donderdag,vrijdag,zaterdag,zondag'],
        ], [
            'customer_category.required' => 'Selecteer een klantcategorie.',
            'customer_category.in' => 'Ongeldige klantcategorie.',
            'discount_percentage.required' => 'Selecteer een kortingspercentage.',
            'discount_percentage.in' => 'Ongeldig kortingspercentage.',
            'delivery_day.required' => 'Selecteer een leverdag.',
            'delivery_day.in' => 'Ongeldige leverdag.',
        ]);

        $customer->update([
            'customer_category' => $validated['customer_category'],
            'discount_percentage' => $validated['discount_percentage'],
            'delivery_day' => $validated['delivery_day'],
        ]);

        return back()->with('success', "Klantgegevens bijgewerkt.");
    }

    /**
     * Update customer information.
     */
    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
            'kvk_number' => ['required', 'string', 'max:8'],
            'vat_number' => ['required', 'string', 'max:14'],
            'bank_account' => ['required', 'string', 'max:34'],
            'street_name' => ['required', 'string', 'max:255'],
            'house_number' => ['required', 'string', 'max:10'],
            'postal_code' => ['required', 'string', 'max:7'],
            'city' => ['required', 'string', 'max:255'],
            'packing_slip_email' => ['nullable', 'email', 'max:255'],
        ], [
            'company_name.required' => 'Bedrijfsnaam is verplicht.',
            'contact_person.required' => 'Contactpersoon is verplicht.',
            'phone_number.required' => 'Telefoonnummer is verplicht.',
            'kvk_number.required' => 'KVK nummer is verplicht.',
            'vat_number.required' => 'BTW nummer is verplicht.',
            'bank_account.required' => 'IBAN is verplicht.',
            'street_name.required' => 'Straatnaam is verplicht.',
            'house_number.required' => 'Huisnummer is verplicht.',
            'postal_code.required' => 'Postcode is verplicht.',
            'city.required' => 'Plaats is verplicht.',
            'packing_slip_email.email' => 'Pakbon email moet een geldig email adres zijn.',
        ]);

        $customer->update($validated);

        return back()->with('success', "Klantgegevens bijgewerkt.");
    }

    /**
     * Store a new delivery address for a customer.
     */
    public function storeDeliveryAddress(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'street_name' => ['required', 'string', 'max:255'],
            'house_number' => ['required', 'string', 'max:10'],
            'postal_code' => ['required', 'string', 'max:7'],
            'city' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_default' => ['boolean'],
        ]);

        if ($validated['is_default'] ?? false) {
            $customer->deliveryAddresses()->update(['is_default' => false]);
        }

        $customer->deliveryAddresses()->create($validated);

        return back()->with('success', 'Afleveradres toegevoegd.');
    }

    /**
     * Update a delivery address for a customer.
     */
    public function updateDeliveryAddress(Request $request, Customer $customer, DeliveryAddress $deliveryAddress): RedirectResponse
    {
        if ($deliveryAddress->customer_id !== $customer->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'street_name' => ['required', 'string', 'max:255'],
            'house_number' => ['required', 'string', 'max:10'],
            'postal_code' => ['required', 'string', 'max:7'],
            'city' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_default' => ['boolean'],
        ]);

        if ($validated['is_default'] ?? false) {
            $customer->deliveryAddresses()->update(['is_default' => false]);
        }

        $deliveryAddress->update($validated);

        return back()->with('success', 'Afleveradres bijgewerkt.');
    }

    /**
     * Delete a delivery address for a customer.
     */
    public function destroyDeliveryAddress(Customer $customer, DeliveryAddress $deliveryAddress): RedirectResponse
    {
        if ($deliveryAddress->customer_id !== $customer->id) {
            abort(404);
        }

        $deliveryAddress->delete();

        return back()->with('success', 'Afleveradres verwijderd.');
    }
}
