<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\DeliveryAddressRequest;
use App\Models\DeliveryAddress;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DeliveryAddressController extends Controller
{
    /**
     * Display the delivery addresses.
     */
    public function index(): Response
    {
        $customer = auth()->user()->customer;

        $addresses = $customer->deliveryAddresses()
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (DeliveryAddress $address) => [
                'id' => $address->id,
                'name' => $address->name,
                'street_name' => $address->street_name,
                'house_number' => $address->house_number,
                'postal_code' => $address->postal_code,
                'city' => $address->city,
                'notes' => $address->notes,
                'is_default' => $address->is_default,
            ]);

        return Inertia::render('customer/DeliveryAddresses', [
            'addresses' => $addresses,
        ]);
    }

    /**
     * Store a new delivery address.
     */
    public function store(DeliveryAddressRequest $request): RedirectResponse
    {
        $customer = auth()->user()->customer;

        if ($request->is_default) {
            $customer->deliveryAddresses()->update(['is_default' => false]);
        }

        $customer->deliveryAddresses()->create($request->validated());

        return back()->with('success', 'Afleveradres toegevoegd.');
    }

    /**
     * Update a delivery address.
     */
    public function update(DeliveryAddressRequest $request, DeliveryAddress $deliveryAddress): RedirectResponse
    {
        if ($deliveryAddress->customer_id !== auth()->user()->customer->id) {
            abort(403);
        }

        if ($request->is_default) {
            auth()->user()->customer->deliveryAddresses()->update(['is_default' => false]);
        }

        $deliveryAddress->update($request->validated());

        return back()->with('success', 'Afleveradres bijgewerkt.');
    }

    /**
     * Delete a delivery address.
     */
    public function destroy(DeliveryAddress $deliveryAddress): RedirectResponse
    {
        if ($deliveryAddress->customer_id !== auth()->user()->customer->id) {
            abort(403);
        }

        $deliveryAddress->delete();

        return back()->with('success', 'Afleveradres verwijderd.');
    }
}
