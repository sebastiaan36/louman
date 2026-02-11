<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\OrderDeadline;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    /**
     * Display the customer's shopping cart.
     */
    public function index(Request $request): Response
    {
        $customer = $request->user()->customer;

        $cartItems = $customer->cartItems()
            ->with('product.category')
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
            });

        // Calculate total
        $total = $cartItems->sum('subtotal');

        // Get delivery addresses for checkout
        $deliveryAddresses = $customer->deliveryAddresses()
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($address) => [
                'id' => $address->id,
                'name' => $address->name,
                'street' => $address->street_name,
                'house_number' => $address->house_number,
                'postal_code' => $address->postal_code,
                'city' => $address->city,
                'is_default' => $address->is_default,
            ]);

        // Get customer's main address as fallback
        $mainAddress = [
            'street' => $customer->street_name,
            'house_number' => $customer->house_number,
            'postal_code' => $customer->postal_code,
            'city' => $customer->city,
        ];

        return Inertia::render('customer/Cart', [
            'cartItems' => $cartItems,
            'total' => number_format($total, 2, '.', ''),
            'itemCount' => $cartItems->count(),
            'deliveryAddresses' => $deliveryAddresses,
            'mainAddress' => $mainAddress,
            'orderDeadline' => OrderDeadline::getTimeRemaining(),
        ]);
    }

    /**
     * Add a product to the cart.
     */
    public function add(Request $request, Product $product): RedirectResponse
    {
        $customer = $request->user()->customer;

        // Validate quantity
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $quantity = $request->input('quantity', 1);

        // Check if product is active and in stock
        if (! $product->is_active) {
            return back()->with('error', 'Dit product is niet beschikbaar.');
        }

        if (! $product->isInStock()) {
            return back()->with('error', 'Dit product is niet op voorraad.');
        }

        // Check if product already in cart
        $cartItem = $customer->cartItems()->where('product_id', $product->id)->first();

        if ($cartItem) {
            // Update quantity
            $newQuantity = $cartItem->quantity + $quantity;
            $cartItem->update(['quantity' => $newQuantity]);
            $message = 'Aantal bijgewerkt in winkelwagen.';
        } else {
            // Add new item to cart
            $customer->cartItems()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
            ]);
            $message = 'Product toegevoegd aan winkelwagen.';
        }

        return back()->with('success', $message);
    }

    /**
     * Update cart item quantity.
     */
    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        // Ensure cart item belongs to the authenticated customer
        if ($cartItem->customer_id !== $request->user()->customer->id) {
            abort(403);
        }

        // Validate quantity
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cartItem->update([
            'quantity' => $request->input('quantity'),
        ]);

        return back()->with('success', 'Aantal bijgewerkt.');
    }

    /**
     * Remove item from cart.
     */
    public function remove(CartItem $cartItem): RedirectResponse
    {
        // Ensure cart item belongs to the authenticated customer
        if ($cartItem->customer_id !== request()->user()->customer->id) {
            abort(403);
        }

        $cartItem->delete();

        return back()->with('success', 'Product verwijderd uit winkelwagen.');
    }

    /**
     * Clear the entire cart.
     */
    public function clear(Request $request): RedirectResponse
    {
        $customer = $request->user()->customer;

        $customer->cartItems()->delete();

        return back()->with('success', 'Winkelwagen geleegd.');
    }

    /**
     * Reorder items from a previous order.
     */
    public function reorder(Request $request, Order $order): RedirectResponse
    {
        $customer = $request->user()->customer;

        // Ensure order belongs to the authenticated customer
        if ($order->customer_id !== $customer->id) {
            abort(403);
        }

        // Load order items with products
        $order->load('items.product');

        $addedCount = 0;
        $unavailableProducts = [];

        foreach ($order->items as $orderItem) {
            $product = $orderItem->product;

            // Check if product is still active and in stock
            if (! $product->is_active || ! $product->isInStock()) {
                $unavailableProducts[] = $product->title;
                continue;
            }

            // Check if product is already in cart
            $existingCartItem = $customer->cartItems()
                ->where('product_id', $product->id)
                ->first();

            if ($existingCartItem) {
                // Update quantity
                $existingCartItem->update([
                    'quantity' => $existingCartItem->quantity + $orderItem->quantity,
                ]);
            } else {
                // Add to cart
                $customer->cartItems()->create([
                    'product_id' => $product->id,
                    'quantity' => $orderItem->quantity,
                ]);
            }

            $addedCount++;
        }

        // Build success message
        if ($addedCount > 0 && empty($unavailableProducts)) {
            return to_route('customer.cart')
                ->with('success', "{$addedCount} " . ($addedCount === 1 ? 'product' : 'producten') . " toegevoegd aan winkelwagen.");
        } elseif ($addedCount > 0 && ! empty($unavailableProducts)) {
            $unavailableList = implode(', ', $unavailableProducts);

            return to_route('customer.cart')
                ->with('warning', "{$addedCount} " . ($addedCount === 1 ? 'product' : 'producten') . " toegevoegd. Niet beschikbaar: {$unavailableList}");
        } else {
            return back()
                ->with('error', 'Geen producten uit deze bestelling zijn momenteel beschikbaar.');
        }
    }
}
