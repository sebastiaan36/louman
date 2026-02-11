<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
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
            'customer_category' => $customer->customer_category,
            'customer_category_label' => $customer->getCategoryLabel(),
            'discount_percentage' => $customer->discount_percentage,
            'approved_at' => $customer->approved_at?->format('d-m-Y H:i'),
            'created_at' => $customer->created_at->format('d-m-Y H:i'),
        ];

        $deliveryAddresses = $customer->deliveryAddresses->map(fn ($address) => [
            'id' => $address->id,
            'name' => $address->name,
            'street' => $address->street,
            'house_number' => $address->house_number,
            'postal_code' => $address->postal_code,
            'city' => $address->city,
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
        ], [
            'customer_category.required' => 'Selecteer een klantcategorie.',
            'customer_category.in' => 'Ongeldige klantcategorie.',
            'discount_percentage.required' => 'Selecteer een kortingspercentage.',
            'discount_percentage.in' => 'Ongeldig kortingspercentage.',
        ]);

        $customer->update([
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'customer_category' => $validated['customer_category'],
            'discount_percentage' => $validated['discount_percentage'],
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
        ], [
            'customer_category.required' => 'Selecteer een klantcategorie.',
            'customer_category.in' => 'Ongeldige klantcategorie.',
            'discount_percentage.required' => 'Selecteer een kortingspercentage.',
            'discount_percentage.in' => 'Ongeldig kortingspercentage.',
        ]);

        $customer->update([
            'customer_category' => $validated['customer_category'],
            'discount_percentage' => $validated['discount_percentage'],
        ]);

        return back()->with('success', "Klantgegevens bijgewerkt.");
    }
}
