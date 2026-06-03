<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdministratorRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdministratorController extends Controller
{
    /**
     * Display the list of administrator accounts.
     */
    public function index(): Response
    {
        $administrators = User::where('role', 'admin')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at->format('d-m-Y'),
                'is_self' => $user->id === auth()->id(),
            ]);

        return Inertia::render('admin/Administrators', [
            'administrators' => $administrators,
        ]);
    }

    /**
     * Store a new administrator account.
     */
    public function store(StoreAdministratorRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'admin',
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        AuditLog::record('administrator.created', "Beheerder {$user->name} aangemaakt", $user);

        return back()->with('success', "Beheerder {$user->name} is aangemaakt.");
    }

    /**
     * Delete an administrator account.
     */
    public function destroy(User $administrator): RedirectResponse
    {
        if (! $administrator->isAdmin()) {
            abort(404);
        }

        if ($administrator->id === auth()->id()) {
            return back()->with('error', 'Je kunt je eigen account niet verwijderen.');
        }

        if (User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Er moet minimaal één beheerder overblijven.');
        }

        $name = $administrator->name;
        $administrator->delete();

        AuditLog::record('administrator.deleted', "Beheerder {$name} verwijderd", null, [
            'name' => $name,
        ]);

        return back()->with('success', "Beheerder {$name} is verwijderd.");
    }
}
