<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FavoriteController extends Controller
{
    /**
     * Display the customer's favorite products.
     */
    public function index(Request $request): Response
    {
        $customer = $request->user()->customer;
        $customer->load('customProductPrices');

        // Preload cart product ids once to avoid an N+1 per favorite.
        $cartIds = $customer->cartItems()->pluck('product_id')->flip();

        $favorites = $customer->favoriteProducts()
            ->with('category')
            ->active()
            ->visibleTo($customer)
            ->orderBy('product_favorites.created_at', 'desc')
            ->get()
            ->map(function (Product $product) use ($customer, $cartIds) {
                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'category' => $product->category?->name,
                    'price' => $product->getPriceForCustomer($customer),
                    'weight' => $product->weight,
                    'article_number' => $product->article_number,
                    'thumbnail_url' => $product->thumbnail_url,
                    'is_in_stock' => $product->isInStock(),
                    'is_favorite' => true, // Always true on this page
                    'in_cart' => $cartIds->has($product->id),
                ];
            });

        return Inertia::render('customer/Favorites', [
            'favorites' => $favorites,
        ]);
    }

    /**
     * Toggle a product as favorite.
     */
    public function toggle(Request $request, Product $product): RedirectResponse
    {
        $customer = $request->user()->customer;

        // A customer can only favorite products that are visible to them.
        if (! $product->isVisibleTo($customer)) {
            abort(404);
        }

        // Check if already favorited
        $exists = $customer->favoriteProducts()->where('product_id', $product->id)->exists();

        if ($exists) {
            // Remove from favorites
            $customer->favoriteProducts()->detach($product->id);
            $message = 'Product verwijderd van favorieten.';
        } else {
            // Add to favorites
            $customer->favoriteProducts()->attach($product->id);
            $message = 'Product toegevoegd aan favorieten.';
        }

        return back()->with('success', $message);
    }
}
