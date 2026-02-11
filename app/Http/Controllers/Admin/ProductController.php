<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(): Response
    {
        $products = Product::with('category')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'title' => $product->title,
                'category' => $product->category?->name,
                'price_groothandel' => $product->price_groothandel,
                'price_broodjeszaak' => $product->price_broodjeszaak,
                'price_horeca' => $product->price_horeca,
                'article_number' => $product->article_number,
                'in_stock' => $product->in_stock,
                'photo_url' => $product->thumbnail_url,
                'is_active' => $product->is_active,
            ]);

        return Inertia::render('admin/Products', [
            'products' => $products,
        ]);
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): Response
    {
        $categories = Category::orderBy('sort_order')->orderBy('name')->get();

        return Inertia::render('admin/ProductForm', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $data['photo'] = $this->handlePhotoUpload($request->file('photo'));
        }

        // Convert comma-separated strings to arrays
        $data['ingredients'] = $this->parseTagString($data['ingredients'] ?? null);
        $data['allergens'] = $this->parseTagString($data['allergens'] ?? null);

        Product::create($data);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product toegevoegd.');
    }

    /**
     * Show the form for editing a product.
     */
    public function edit(Product $product): Response
    {
        $categories = Category::orderBy('sort_order')->orderBy('name')->get();

        return Inertia::render('admin/ProductForm', [
            'product' => [
                'id' => $product->id,
                'category_id' => $product->category_id,
                'title' => $product->title,
                'price_groothandel' => $product->price_groothandel,
                'price_broodjeszaak' => $product->price_broodjeszaak,
                'price_horeca' => $product->price_horeca,
                'description' => $product->description,
                'ingredients' => implode(', ', $product->ingredients ?? []),
                'allergens' => implode(', ', $product->allergens ?? []),
                'nutrition_facts' => $product->nutrition_facts,
                'weight' => $product->weight,
                'article_number' => $product->article_number,
                'in_stock' => $product->in_stock,
                'photo_url' => $product->photo_url,
                'is_active' => $product->is_active,
            ],
            'categories' => $categories,
        ]);
    }

    /**
     * Update a product.
     */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo
            if ($product->photo) {
                Storage::disk('public')->delete($product->photo);
                Storage::disk('public')->delete('products/thumbs/'.basename($product->photo));
            }
            $data['photo'] = $this->handlePhotoUpload($request->file('photo'));
        }

        // Convert comma-separated strings to arrays
        $data['ingredients'] = $this->parseTagString($data['ingredients'] ?? null);
        $data['allergens'] = $this->parseTagString($data['allergens'] ?? null);

        $product->update($data);

        return redirect()->route('admin.products.index')
            ->with('success', 'Product bijgewerkt.');
    }

    /**
     * Remove a product.
     */
    public function destroy(Product $product): RedirectResponse
    {
        // Delete photo
        if ($product->photo) {
            Storage::disk('public')->delete($product->photo);
            Storage::disk('public')->delete('products/thumbs/'.basename($product->photo));
        }

        $product->delete();

        return back()->with('success', 'Product verwijderd.');
    }

    /**
     * Handle photo upload and thumbnail generation.
     */
    private function handlePhotoUpload($file): string
    {
        $manager = new ImageManager(new Driver);

        // Generate unique filename
        $filename = uniqid().'.'.$file->getClientOriginalExtension();
        $path = 'products/'.$filename;

        // Save original
        Storage::disk('public')->put($path, file_get_contents($file));

        // Create thumbnail
        $image = $manager->read($file);
        $image->scale(width: 300);
        $thumbnailPath = 'products/thumbs/'.$filename;
        Storage::disk('public')->put($thumbnailPath, (string) $image->encode());

        return $path;
    }

    /**
     * Parse comma-separated tag string to array.
     */
    private function parseTagString(?string $tags): ?array
    {
        if (empty($tags)) {
            return null;
        }

        return array_filter(
            array_map('trim', explode(',', $tags)),
            fn ($tag) => ! empty($tag)
        );
    }
}
