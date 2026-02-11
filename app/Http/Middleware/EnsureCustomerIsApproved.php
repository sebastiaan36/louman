<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerIsApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->isAdmin()) {
            return to_route('admin.dashboard');
        }

        if ($user->isCustomer() && $user->customer && ! $user->customer->isApproved()) {
            return to_route('customer.awaiting-approval');
        }

        return $next($request);
    }
}
