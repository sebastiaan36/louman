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
            // Admin statistics
            $stats = $this->getAdminStats();
        } else {
            // Customer statistics (if needed in future)
            $stats = $this->getCustomerStats($user);
        }

        return Inertia::render('Dashboard', $stats);
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
