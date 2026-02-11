<?php

namespace App\Providers;

use App\Models\CartItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRouteBindings();
    }

    protected function configureRouteBindings(): void
    {
        // Scope CartItem to authenticated customer
        Route::bind('cartItem', function (string $value) {
            $user = request()->user();

            if (! $user || ! $user->customer) {
                abort(404);
            }

            return CartItem::where('id', $value)
                ->where('customer_id', $user->customer->id)
                ->firstOrFail();
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
