<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerProfileIsComplete
{
    private const REQUIRED_FIELDS = [
        'contact_person',
        'phone_number',
        'street_name',
        'house_number',
        'postal_code',
        'city',
        'kvk_number',
        'bank_account',
        'vat_number',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $request->user()?->customer;

        if ($customer === null) {
            return $next($request);
        }

        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty($customer->{$field})) {
                return to_route('customer.complete-profile.edit');
            }
        }

        return $next($request);
    }
}
