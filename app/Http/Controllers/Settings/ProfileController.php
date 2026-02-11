<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $data = [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ];

        // If user is a customer, include customer data
        if ($user->isCustomer() && $user->customer) {
            $customer = $user->customer;
            $data['customer'] = [
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
            ];
        }

        return Inertia::render('settings/Profile', $data);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Update user fields
        $user->fill([
            'name' => $validated['name'] ?? $user->name,
            'email' => $validated['email'] ?? $user->email,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // If user is a customer, update customer fields
        if ($user->isCustomer() && $user->customer) {
            $user->customer->update([
                'company_name' => $validated['company_name'] ?? $user->customer->company_name,
                'contact_person' => $validated['contact_person'] ?? $user->customer->contact_person,
                'phone_number' => $validated['phone_number'] ?? $user->customer->phone_number,
                'street_name' => $validated['street_name'] ?? $user->customer->street_name,
                'house_number' => $validated['house_number'] ?? $user->customer->house_number,
                'postal_code' => $validated['postal_code'] ?? $user->customer->postal_code,
                'city' => $validated['city'] ?? $user->customer->city,
                'kvk_number' => $validated['kvk_number'] ?? $user->customer->kvk_number,
                'bank_account' => $validated['bank_account'] ?? $user->customer->bank_account,
                'vat_number' => $validated['vat_number'] ?? $user->customer->vat_number,
            ]);
        }

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
