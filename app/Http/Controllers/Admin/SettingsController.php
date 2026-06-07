<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * The mail-related settings managed on this screen, with their defaults.
     *
     * @var array<string, string|null>
     */
    private const MAIL_SETTINGS = [
        Setting::MAIL_ORDER_NOTIFICATION => 'info@louman-jordaan.nl',
        Setting::MAIL_REGISTRATION_NOTIFICATION => null,
        Setting::MAIL_CANCELLATION_NOTIFICATION => null,
        Setting::MAIL_CC => null,
    ];

    /**
     * Show the admin settings screen.
     */
    public function edit(): Response
    {
        $settings = [];
        foreach (self::MAIL_SETTINGS as $key => $default) {
            $settings[$key] = Setting::get($key, $default);
        }

        return Inertia::render('admin/Settings', [
            'settings' => $settings,
        ]);
    }

    /**
     * Persist the admin settings.
     */
    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        foreach (array_keys(self::MAIL_SETTINGS) as $key) {
            Setting::set($key, $request->input($key) ?: null);
        }

        return back()->with('success', 'Instellingen opgeslagen.');
    }
}
