<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Customer;
use App\Models\CustomerCategory;
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
        Setting::MAIL_REGISTRATION_CC => null,
        Setting::MAIL_CANCELLATION_NOTIFICATION => null,
        Setting::MAIL_SUPPORT_NOTIFICATION => null,
        Setting::SUPPORT_PHONE => '020-4470930',
        Setting::SUPPORT_EMAIL => null,
        Setting::MAIL_CC => null,
        Setting::MAIL_REPLY_TO => null,
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

        $customerCategories = CustomerCategory::query()
            ->withCount('customers')
            ->orderBy('name')
            ->get()
            ->map(fn (CustomerCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'customers_count' => $category->customers_count,
            ]);

        return Inertia::render('admin/Settings', [
            'settings' => $settings,
            'customerCategories' => $customerCategories,
            'customersWithoutCategoryCount' => Customer::query()->whereNull('customer_category')->count(),
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
