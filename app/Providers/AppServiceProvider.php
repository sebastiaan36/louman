<?php

namespace App\Providers;

use App\Listeners\AddCcToOutgoingMail;
use App\Listeners\SetReplyToOnOutgoingMail;
use App\Models\CartItem;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\SessionGuard;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * How long a "remember me" login is kept on the device (in minutes).
     */
    public const REMEMBER_MINUTES = 45 * 24 * 60;

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
        $this->configureRateLimiting();

        Event::listen(MessageSending::class, AddCcToOutgoingMail::class);
        Event::listen(MessageSending::class, SetReplyToOnOutgoingMail::class);
        Event::listen(Login::class, fn (Login $event) => $this->limitRememberCookieLifetime($event));
    }

    /**
     * Cap the "remember me" cookie to a fixed lifetime instead of Laravel's
     * default of ~5 years, so a remembered login is kept for 45 days.
     */
    protected function limitRememberCookieLifetime(Login $event): void
    {
        if (! $event->remember) {
            return;
        }

        $guard = Auth::guard($event->guard);

        if (! $guard instanceof SessionGuard) {
            return;
        }

        $name = $guard->getRecallerName();
        $jar = app(CookieJar::class);

        foreach ($jar->getQueuedCookies() as $cookie) {
            if ($cookie->getName() !== $name) {
                continue;
            }

            $jar->queue($jar->make(
                $name,
                $cookie->getValue(),
                self::REMEMBER_MINUTES,
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttpOnly(),
                $cookie->isRaw(),
                $cookie->getSameSite(),
            ));

            break;
        }
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

    /**
     * Cap how hard the integration API can be hit. The limit is per token, so
     * one runaway process cannot crowd out the other integrations.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('integration', function (Request $request): Limit {
            $client = $request->user();

            return $client
                ? Limit::perMinute(300)->by('api-client:'.$client->getKey())
                : Limit::perMinute(30)->by('ip:'.$request->ip());
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // Minimaal 8 tekens: kort genoeg om te onthouden, en de overige eisen
        // vangen het verschil op. Vooral uncompromised() weegt zwaar, want dat
        // weigert wachtwoorden die al in een datalek zijn opgedoken.
        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(8)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
