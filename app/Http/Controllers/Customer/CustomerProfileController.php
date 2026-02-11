<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CustomerProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerProfileController extends Controller
{
    /**
     * Display the customer profile edit form.
     */
    public function edit(): Response
    {
        $customer = auth()->user()->customer;

        return Inertia::render('customer/Profile', [
            'customer' => [
                'company_name' => $customer->company_name,
                'contact_person' => $customer->contact_person,
                'phone_number' => $customer->phone_number,
                'street_name' => $customer->street_name,
                'house_number' => $customer->house_number,
                'postal_code' => $customer->postal_code,
                'city' => $customer->city,
                'kvk_number' => $customer->kvk_number,
                'bank_account' => $customer->bank_account,
                'vat_number' => $customer->vat_number,
            ],
            'email' => auth()->user()->email,
        ]);
    }

    /**
     * Update the customer profile.
     */
    public function update(CustomerProfileUpdateRequest $request): RedirectResponse
    {
        $customer = auth()->user()->customer;
        $validated = $request->validated();

        // Update customer fields
        $customer->update([
            'company_name' => $validated['company_name'],
            'contact_person' => $validated['contact_person'],
            'phone_number' => $validated['phone_number'],
            'street_name' => $validated['street_name'],
            'house_number' => $validated['house_number'],
            'postal_code' => $validated['postal_code'],
            'city' => $validated['city'],
            'kvk_number' => $validated['kvk_number'],
            'bank_account' => $validated['bank_account'],
            'vat_number' => $validated['vat_number'],
        ]);

        // Update user fields (name and email)
        auth()->user()->update([
            'name' => $validated['contact_person'],
            'email' => $validated['email'],
        ]);

        return back()->with('success', 'Profiel succesvol bijgewerkt.');
    }
}
