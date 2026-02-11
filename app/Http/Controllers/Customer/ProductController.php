<?php

namespace App\Http\Controllers\Customer;

use App\Helpers\OrderDeadline;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): Response
    {
        $customer = $request->user()->customer;

        // Get filter parameters
        $categoryId = $request->input('category');
        $search = $request->input('search');

        // Build query
        $query = Product::with('category')
            ->active()
            ->orderBy('created_at', 'desc');

        // Apply category filter
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('article_number', 'like', "%{$search}%");
            });
        }

        // Get products
        $products = $query->get()->map(function (Product $product) use ($customer) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'category' => $product->category?->name,
                'price' => $product->getPriceForCustomer($customer),
                'weight' => $product->weight,
                'article_number' => $product->article_number,
                                'thumbnail_url' => $product->thumbnail_url,
                'is_in_stock' => $product->isInStock(),
                'is_favorite' => $customer->favoriteProducts()->where('product_id', $product->id)->exists(),
                'in_cart' => $customer->cartItems()->where('product_id', $product->id)->exists(),
            ];
        });

        // Get categories for filter
        $categories = Category::orderBy('sort_order')->orderBy('name')->get();

        return Inertia::render('customer/Products', [
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'category' => $categoryId,
                'search' => $search,
            ],
            'orderDeadline' => OrderDeadline::getTimeRemaining(),
        ]);
    }

    /**
     * Display the specified product.
     */
    public function show(Request $request, Product $product): Response
    {
        $customer = $request->user()->customer;

        // Only show active products
        if (! $product->is_active) {
            abort(404);
        }

        return Inertia::render('customer/ProductDetail', [
            'product' => [
                'id' => $product->id,
                'title' => $product->title,
                'category' => $product->category?->name,
                'category_id' => $product->category_id,
                'price' => $product->getPriceForCustomer($customer),
                'description' => $product->description,
                'ingredients' => $product->ingredients,
                'allergens' => $product->allergens,
                'nutrition_facts' => $product->nutrition_facts,
                'weight' => $product->weight,
                'article_number' => $product->article_number,
                                'photo_url' => $product->photo_url,
                'thumbnail_url' => $product->thumbnail_url,
                'is_in_stock' => $product->isInStock(),
                'is_favorite' => $customer->favoriteProducts()->where('product_id', $product->id)->exists(),
                'in_cart' => $customer->cartItems()->where('product_id', $product->id)->exists(),
            ],
            'orderDeadline' => OrderDeadline::getTimeRemaining(),
        ]);
    }
}
