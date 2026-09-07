<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerProfileIsComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $request->user()?->customer;

        if ($customer === null) {
            return $next($request);
        }

        if (! $customer->hasCompleteProfile()) {
            return to_route('customer.complete-profile.edit');
        }

        return $next($request);
    }
}
