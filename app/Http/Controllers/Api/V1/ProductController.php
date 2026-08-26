<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\UpdateProductStockRequest;
use App\Http\Requests\Api\V1\UpsertProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Articles. The external package is leading here: it creates and updates
 * products, the portal only displays them.
 */
class ProductController extends ApiController
{
    /**
     * List articles, newest changes first.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Product::query()->with(['category', 'subcategory']);

        $this->applyUpdatedSince($query, $request);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $products = $query->orderBy('updated_at')->orderBy('id')
            ->paginate($this->perPage($request));

        return ProductResource::collection($products);
    }

    /**
     * Show a single article by its article number.
     */
    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load(['category', 'subcategory']));
    }

    /**
     * Create or replace an article. Sending the same payload twice leaves the
     * article in exactly the same state.
     */
    public function upsert(UpsertProductRequest $request, string $articleNumber): JsonResponse
    {
        $data = $request->validated();

        $product = Product::firstOrNew(['article_number' => $articleNumber]);
        $wasExisting = $product->exists;

        $category = $this->resolveCategory($data['category'] ?? null);
        $subcategory = $this->resolveCategory($data['subcategory'] ?? null, $category);

        $product->fill([
            'external_id' => $data['external_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'price' => $data['price'],
            'price_per_kg' => $data['price_per_kg'] ?? null,
            'suggested_retail_price' => $data['suggested_retail_price'] ?? null,
            'weight' => $data['weight'] ?? null,
            'ingredients' => $data['ingredients'] ?? null,
            'allergens' => $data['allergens'] ?? null,
            'nutrition_facts' => $data['nutrition_facts'] ?? null,
            'category_id' => $category?->id,
            'subcategory_id' => $subcategory?->id,
            'in_stock' => $data['in_stock'],
            'is_active' => $data['is_active'],
        ]);

        // Private-label visibility is managed in the portal. Only honour the
        // flag when it is sent explicitly, so a payload that leaves it out can
        // never turn a private-label article into a public one.
        if (array_key_exists('is_private_label', $data) && $data['is_private_label'] !== null) {
            $product->is_private_label = $data['is_private_label'];
        }

        $product->synced_at = now();
        $product->save();

        AuditLog::recordForApiClient(
            $this->client($request),
            $wasExisting ? 'api.product.updated' : 'api.product.created',
            ($wasExisting ? 'Artikel bijgewerkt' : 'Artikel aangemaakt').' via koppeling: '.$product->title,
            $product,
            ['article_number' => $product->article_number],
        );

        return (new ProductResource($product->load(['category', 'subcategory'])))
            ->response()
            ->setStatusCode($wasExisting ? 200 : 201);
    }

    /**
     * Update availability only.
     */
    public function updateStock(UpdateProductStockRequest $request, Product $product): ProductResource
    {
        $product->update([
            'in_stock' => $request->validated('in_stock'),
            'synced_at' => now(),
        ]);

        AuditLog::recordForApiClient(
            $this->client($request),
            'api.product.stock_updated',
            'Leverbaarheid bijgewerkt via koppeling: '.$product->title,
            $product,
            ['article_number' => $product->article_number, 'in_stock' => $product->in_stock],
        );

        return new ProductResource($product->load(['category', 'subcategory']));
    }

    /**
     * Find a category by name, creating it when the external package uses a
     * grouping the portal does not know yet.
     */
    private function resolveCategory(?string $name, ?Category $parent = null): ?Category
    {
        if (blank($name)) {
            return null;
        }

        return Category::firstOrCreate(
            ['name' => $name, 'parent_id' => $parent?->id],
            ['sort_order' => 0],
        );
    }
}
