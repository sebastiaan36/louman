<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CustomerInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomerInvitationController extends Controller
{
    /**
     * Show the password form for an invited customer.
     */
    public function show(string $token): Response
    {
        $invitation = $this->findValidInvitation($token);

        return Inertia::render('auth/AcceptInvitation', [
            'token' => $token,
            'email' => $invitation->email,
            'companyName' => $invitation->customer->company_name,
        ]);
    }

    /**
     * Accept the invitation: create the user, link to the customer, and log in.
     */
    public function accept(string $token, Request $request): RedirectResponse
    {
        $invitation = $this->findValidInvitation($token);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($invitation, $validated) {
            $user = User::create([
                'name' => $invitation->customer->company_name,
                'email' => $invitation->email,
                'password' => $validated['password'],
                'role' => 'customer',
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            $invitation->customer->update(['user_id' => $user->id]);

            $invitation->update(['accepted_at' => now()]);

            return $user;
        });

        Auth::login($user);

        return to_route('customer.complete-profile.edit');
    }

    private function findValidInvitation(string $token): CustomerInvitation
    {
        $invitation = CustomerInvitation::with('customer')
            ->where('token', hash('sha256', $token))
            ->first();

        if (! $invitation || ! $invitation->isValid()) {
            throw new NotFoundHttpException('Deze uitnodigingslink is ongeldig of verlopen.');
        }

        return $invitation;
    }
}
