<?php

use App\Models\Order;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;

/**
 * Timestamps are stored in UTC but shown in Amsterdam time. On the live site
 * an order placed at 15:01 was shown as 13:01 because every screen called
 * format() on the raw UTC value.
 */
test('formatLocal toont een UTC-tijdstip in Nederlandse tijd', function () {
    // Zomertijd: UTC+2.
    expect(CarbonImmutable::parse('2026-09-14 13:01:00', 'UTC')->formatLocal('d-m-Y H:i'))->toBe('14-09-2026 15:01');
    expect(Carbon::parse('2026-09-14 13:01:00', 'UTC')->formatLocal('d-m-Y H:i'))->toBe('14-09-2026 15:01');

    // Wintertijd: UTC+1, en over de datumgrens heen.
    expect(CarbonImmutable::parse('2026-01-10 23:30:00', 'UTC')->formatLocal('d-m-Y H:i'))->toBe('11-01-2026 00:30');
});

test('formatLocal laat het oorspronkelijke object ongemoeid', function () {
    $utc = Carbon::parse('2026-09-14 13:01:00', 'UTC');
    $utc->formatLocal('H:i');

    expect($utc->timezoneName)->toBe('UTC');
});

test('een tijdstip uit de database wordt in Nederlandse tijd getoond', function () {
    Date::setTestNow('2026-09-14 13:01:00');
    $order = Order::factory()->create();

    expect($order->fresh()->created_at->formatLocal('d-m-Y H:i'))->toBe('14-09-2026 15:01');

    Date::setTestNow();
});

test('geen scherm, mail of PDF formatteert een datum nog rechtstreeks in UTC', function () {
    $root = dirname(__DIR__, 2);
    $files = array_merge(
        glob($root.'/app/Http/Controllers/*.php'),
        glob($root.'/app/Http/Controllers/*/*.php'),
        glob($root.'/app/Console/Commands/*.php'),
        glob($root.'/app/Notifications/*.php'),
        glob($root.'/app/Mail/*.php'),
        glob($root.'/resources/views/emails/*.blade.php'),
        glob($root.'/resources/views/pdf/*.blade.php'),
        glob($root.'/resources/views/pdf/partials/*.blade.php'),
    );

    $offenders = [];
    foreach ($files as $file) {
        // De API en de statistieken rekenen bewust in UTC; die vallen buiten deze lijst.
        if (str_contains($file, '/Api/') || str_ends_with($file, 'DashboardController.php')) {
            continue;
        }
        if (preg_match("/->format\\('[^']*(d|H|Y|j|G)[^']*'\\)/", file_get_contents($file))) {
            $offenders[] = str_replace($root.'/', '', $file);
        }
    }

    expect($offenders)->toBe([]);
});
