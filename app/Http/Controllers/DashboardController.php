<?php

namespace App\Http\Controllers;

use App\Helpers\OrderDeadline;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with statistics.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        $stats = [];

        if ($isAdmin) {
            // Admin statistics, without the revenue figures: those live on the
            // separate statistics page only.
            $stats = $this->getAdminStats();
            unset(
                $stats['stats']['currentMonthRevenue'],
                $stats['stats']['revenueChangePercentage'],
                $stats['stats']['revenueIncreased'],
            );
        } else {
            // Customer statistics (if needed in future)
            $stats = $this->getCustomerStats($user);
        }

        return Inertia::render('Dashboard', $stats);
    }

    /**
     * Display the separate statistics page with all admin figures,
     * including revenue. Reachable by direct link only (not in the menu).
     */
    public function statistics(): Response
    {
        $data = $this->getAdminStats();

        // Year boundaries in the business timezone, converted to UTC for the
        // query, so orders are bucketed by their local (Amsterdam) date.
        $tz = config('app.business_timezone');
        $startUtc = Carbon::now($tz)->startOfYear()->utc();
        $endUtc = Carbon::now($tz)->endOfYear()->utc();
        $revenueStatuses = ['pending', 'confirmed', 'completed'];

        $data['stats']['currentYearRevenue'] = number_format(
            (float) Order::whereBetween('created_at', [$startUtc, $endUtc])
                ->whereIn('status', $revenueStatuses)
                ->sum('total'),
            2, '.', ''
        );
        $data['stats']['ordersThisYear'] = Order::whereBetween('created_at', [$startUtc, $endUtc])->count();
        $data['chart'] = $this->getMonthlyChart($startUtc, $endUtc, $revenueStatuses);

        return Inertia::render('admin/Statistics', $data);
    }

    /**
     * Build per-month order count and revenue for the given (UTC) range,
     * bucketing each order by its month in the business timezone.
     *
     * @param  array<int, string>  $revenueStatuses
     * @return array<int, array{label: string, orders: int, revenue: float}>
     */
    private function getMonthlyChart(Carbon $startUtc, Carbon $endUtc, array $revenueStatuses): array
    {
        $labels = ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'];

        $months = array_map(fn (string $label) => [
            'label' => $label,
            'orders' => 0,
            'revenue' => 0.0,
        ], $labels);

        Order::whereBetween('created_at', [$startUtc, $endUtc])
            ->whereIn('status', $revenueStatuses)
            ->get(['created_at', 'total'])
            ->each(function (Order $order) use (&$months) {
                $index = (int) $order->created_at->copy()->setTimezone(config('app.business_timezone'))->format('n') - 1;
                $months[$index]['orders']++;
                $months[$index]['revenue'] += (float) $order->total;
            });

        return array_values($months);
    }

    /**
     * Get admin dashboard statistics.
     */
    private function getAdminStats(): array
    {
        // Pending orders count
        $pendingOrdersCount = Order::where('status', 'pending')->count();

        // Revenue this month
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $currentMonthRevenue = Order::where('created_at', '>=', $currentMonth)
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->sum('total');

        $lastMonthRevenue = Order::whereBetween('created_at', [$lastMonth, $lastMonthEnd])
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->sum('total');

        // Calculate percentage change
        $revenueChange = 0;
        $revenueChangePercentage = 0;

        if ($lastMonthRevenue > 0) {
            $revenueChange = $currentMonthRevenue - $lastMonthRevenue;
            $revenueChangePercentage = round(($revenueChange / $lastMonthRevenue) * 100, 1);
        } elseif ($currentMonthRevenue > 0) {
            $revenueChangePercentage = 100;
        }

        // Pending customers count
        $pendingCustomersCount = Customer::whereNull('approved_at')->count();

        // Orders this month count
        $ordersThisMonthCount = Order::where('created_at', '>=', $currentMonth)->count();

        // Total customers count
        $totalCustomersCount = Customer::whereNotNull('approved_at')->count();

        // Active products count
        $activeProductsCount = Product::where('is_active', true)->count();

        return [
            'stats' => [
                'pendingOrders' => $pendingOrdersCount,
                'currentMonthRevenue' => number_format((float) $currentMonthRevenue, 2, '.', ''),
                'revenueChangePercentage' => $revenueChangePercentage,
                'revenueIncreased' => $revenueChangePercentage >= 0,
                'pendingCustomers' => $pendingCustomersCount,
                'ordersThisMonth' => $ordersThisMonthCount,
                'totalCustomers' => $totalCustomersCount,
                'activeProducts' => $activeProductsCount,
            ],
        ];
    }

    /**
     * Get customer dashboard statistics.
     */
    private function getCustomerStats($user): array
    {
        $customer = $user->customer;

        if (! $customer) {
            return [
                'stats' => [],
                'orderDeadline' => null,
            ];
        }

        // Count open orders (not completed or cancelled)
        $openOrdersCount = $customer->orders()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->count();

        // Count favorite products (quick order)
        $quickOrderCount = $customer->favoriteProducts()->count();

        return [
            'stats' => [
                'openOrders' => $openOrdersCount,
                'quickOrderProducts' => $quickOrderCount,
            ],
            'orderDeadline' => OrderDeadline::getTimeRemaining(),
        ];
    }
}
