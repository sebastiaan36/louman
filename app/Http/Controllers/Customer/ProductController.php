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
        $sort = $request->input('sort', 'article_asc');

        $allowedSorts = ['article_asc', 'article_desc', 'price_asc', 'price_desc', 'favorites', 'popularity'];
        if (! in_array($sort, $allowedSorts)) {
            $sort = 'article_asc';
        }

        // Build query
        $query = Product::with('category')->active();

        // Apply sort
        if ($sort === 'favorites') {
            $query->leftJoin('product_favorites', function ($join) use ($customer) {
                $join->on('products.id', '=', 'product_favorites.product_id')
                    ->where('product_favorites.customer_id', $customer->id);
            })
                ->select('products.*')
                ->orderByRaw('CASE WHEN product_favorites.customer_id IS NOT NULL THEN 0 ELSE 1 END')
                ->orderByRaw('CAST(products.article_number AS UNSIGNED) ASC');
        } elseif ($sort === 'popularity') {
            $query->withCount('orderItems')
                ->orderByDesc('order_items_count')
                ->orderByRaw('CAST(article_number AS UNSIGNED) ASC');
        } elseif ($sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        } elseif ($sort === 'article_desc') {
            $query->orderByRaw('CAST(article_number AS UNSIGNED) DESC');
        } else {
            $query->orderByRaw('CAST(article_number AS UNSIGNED) ASC');
        }

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

        // Eager-load favorites and cart items for this customer to avoid N+1
        $favoriteIds = $customer->favoriteProducts()->pluck('product_id')->flip();
        $cartIds = $customer->cartItems()->pluck('product_id')->flip();

        // Get products
        $products = $query->get()->map(function (Product $product) use ($customer, $favoriteIds, $cartIds) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'category' => $product->category?->name,
                'price' => $product->getPriceForCustomer($customer),
                'weight' => $product->weight,
                'article_number' => $product->article_number,
                'thumbnail_url' => $product->thumbnail_url,
                'is_in_stock' => $product->isInStock(),
                'is_favorite' => $favoriteIds->has($product->id),
                'in_cart' => $cartIds->has($product->id),
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
                'sort' => $sort,
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
                'suggested_retail_price' => $product->suggested_retail_price,
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
