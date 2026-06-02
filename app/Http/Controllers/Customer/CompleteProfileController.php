<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CompleteProfileRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompleteProfileController extends Controller
{
    /**
     * Show the profile completion form.
     */
    public function edit(): RedirectResponse|Response
    {
        $customer = auth()->user()->customer;

        if ($customer === null) {
            abort(404);
        }

        if ($this->isComplete($customer)) {
            return to_route('dashboard');
        }

        return Inertia::render('customer/CompleteProfile', [
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
                'show_on_map' => $customer->show_on_map,
            ],
        ]);
    }

    /**
     * Save the completed profile.
     */
    public function update(CompleteProfileRequest $request): RedirectResponse
    {
        $customer = $request->user()->customer;

        $customer->update($request->safe()->only([
            'contact_person',
            'phone_number',
            'street_name',
            'house_number',
            'postal_code',
            'city',
            'kvk_number',
            'bank_account',
            'vat_number',
            'show_on_map',
        ]));

        return to_route('dashboard')->with('success', 'Je gegevens zijn opgeslagen.');
    }

    /**
     * Check whether all required customer profile fields are filled in.
     */
    private function isComplete(\App\Models\Customer $customer): bool
    {
        foreach (['contact_person', 'phone_number', 'street_name', 'house_number', 'postal_code', 'city', 'kvk_number', 'bank_account', 'vat_number'] as $field) {
            if (empty($customer->{$field})) {
                return false;
            }
        }

        return true;
    }
}
