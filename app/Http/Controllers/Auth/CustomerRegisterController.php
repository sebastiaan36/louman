<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CustomerRegisterRequest;
use App\Models\Customer;
use App\Models\User;
use App\Notifications\CustomerRegistered;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class CustomerRegisterController extends Controller
{
    /**
     * Display the customer registration form.
     */
    public function create(): Response
    {
        return Inertia::render('auth/CustomerRegister');
    }

    /**
     * Handle an incoming customer registration request.
     */
    public function store(CustomerRegisterRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->contact_person,
                'email' => $request->email,
                'password' => $request->password,
                'role' => 'customer',
            ]);

            $customer = Customer::create([
                'user_id' => $user->id,
                'company_name' => $request->company_name,
                'contact_person' => $request->contact_person,
                'phone_number' => $request->phone_number,
                'street_name' => $request->street_name,
                'house_number' => $request->house_number,
                'postal_code' => $request->postal_code,
                'city' => $request->city,
                'kvk_number' => $request->kvk_number,
                'bank_account' => $request->bank_account,
                'vat_number' => $request->vat_number,
                'show_on_map' => $request->boolean('show_on_map', true),
            ]);

            return $user->load('customer');
        });

        event(new Registered($user));

        $admins = User::where('role', 'admin')->get();
        Notification::send($admins, new CustomerRegistered($user->customer));

        Auth::login($user);

        return to_route('customer.awaiting-approval')
            ->with('status', 'Bedankt voor je registratie! Controleer je email om je emailadres te verifiëren. Je account moet nog worden goedgekeurd voordat je toegang krijgt.');
    }
}
