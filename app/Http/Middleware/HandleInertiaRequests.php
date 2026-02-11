<?php

namespace App\Http\Middleware;

use App\Models\CartItem;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $customer = $request->user()?->customer;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'cartCount' => $customer?->cartItems()->count() ?? 0,
            'cartItems' => $customer ? $this->getCartItems($customer) : [],
            'cartTotal' => $customer ? $this->getCartTotal($customer) : '0.00',
        ];
    }

    /**
     * Get formatted cart items for customer.
     */
    private function getCartItems($customer): array
    {
        return $customer->cartItems()
            ->with('product')
            ->get()
            ->map(function (CartItem $cartItem) use ($customer) {
                $product = $cartItem->product;

                // Check if product is still active and available
                $isAvailable = $product->is_active && $product->isInStock();
                $price = $product->getPriceForCustomer($customer);

                return [
                    'id' => $cartItem->id,
                    'product_id' => $product->id,
                    'product_title' => $product->title,
                    'product_price' => $price,
                    'product_weight' => $product->weight,
                    'product_thumbnail' => $product->thumbnail_url,
                    'quantity' => $cartItem->quantity,
                    'subtotal' => (float) $price * $cartItem->quantity,
                    'is_available' => $isAvailable,
                ];
            })
            ->toArray();
    }

    /**
     * Calculate cart total for customer.
     */
    private function getCartTotal($customer): string
    {
        $total = $customer->cartItems()
            ->with('product')
            ->get()
            ->sum(function (CartItem $cartItem) use ($customer) {
                $product = $cartItem->product;
                $price = $product->getPriceForCustomer($customer);

                return (float) $price * $cartItem->quantity;
            });

        return number_format($total, 2, '.', '');
    }
}
