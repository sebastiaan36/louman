<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                'price' => $product->price,
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
                'price' => $product->price,
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
     * Import products from a CSV file.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $path = $request->file('csv_file')->getPathname();

        // Auto-detect delimiter: read first line and count commas vs semicolons
        $firstLine = fgets(fopen($path, 'r'));
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $handle = fopen($path, 'r');

        // Strip UTF-8 BOM if present (Excel adds this)
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = null;
        $imported = 0;
        $updated = 0;
        $skipped = [];
        $rowNumber = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;

            if ($headers === null) {
                $headers = array_map('trim', $row);
                continue;
            }

            if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            }

            $data = array_combine($headers, array_map('trim', $row));

            if (empty($data['title'] ?? '')) {
                $skipped[] = "Rij {$rowNumber}: naam is leeg";
                continue;
            }

            if (empty($data['article_number'] ?? '')) {
                $skipped[] = "Rij {$rowNumber} ({$data['title']}): artikelnummer is leeg";
                continue;
            }

                $productData = $this->buildProductData($data);

            $existing = Product::where('article_number', $data['article_number'])->first();

            if ($existing) {
                $updateData = $this->filterUpdateData($productData, $headers);
                $existing->update($updateData);
                $updated++;
            } else {
                Product::create(array_merge(['article_number' => $data['article_number']], $productData));
                $imported++;
            }
        }

        fclose($handle);

        $parts = [];
        if ($imported > 0) {
            $parts[] = "{$imported} ".($imported === 1 ? 'product' : 'producten').' aangemaakt';
        }
        if ($updated > 0) {
            $parts[] = "{$updated} ".($updated === 1 ? 'product' : 'producten').' bijgewerkt';
        }
        $summary = implode(', ', $parts) ?: 'Geen wijzigingen.';
        if (! empty($skipped)) {
            $summary .= '. '.count($skipped).' '.(count($skipped) === 1 ? 'rij' : 'rijen').' overgeslagen.';
        }

        return redirect()->route('admin.products.index')
            ->with('success', $summary)
            ->with('import_results', ['imported' => $imported, 'updated' => $updated, 'skipped' => $skipped]);
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
    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $products = Product::orderBy('article_number')->get();

        return response()->stream(function () use ($products) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens it correctly
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'article_number', 'title', 'description',
                'price',
                'in_stock', 'is_active', 'weight', 'category_id',
                'ingredients', 'allergens', 'nutrition_facts',
            ], ';');

            foreach ($products as $product) {
                fputcsv($handle, [
                    $product->article_number,
                    $product->title,
                    $product->description ?? '',
                    $product->price,
                    $product->in_stock ? 1 : 0,
                    $product->is_active ? 1 : 0,
                    $product->weight ?? '',
                    $product->category_id ?? '',
                    $product->ingredients ? json_encode($product->ingredients, JSON_UNESCAPED_UNICODE) : '',
                    $product->allergens ? json_encode($product->allergens, JSON_UNESCAPED_UNICODE) : '',
                    $product->nutrition_facts ? json_encode($product->nutrition_facts, JSON_UNESCAPED_UNICODE) : '',
                ], ';');
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="producten-'.now()->format('Y-m-d').'.csv"',
        ]);
    }

    private function buildProductData(array $data): array
    {
        $nutritionFields = ['energy', 'fat', 'saturated_fat', 'carbohydrates', 'sugars', 'protein', 'salt', 'fiber'];
        $nutritionFacts = [];

        $rawJson = $data['nutrition_facts'] ?? '';
        if ($rawJson !== '') {
            $decoded = json_decode($rawJson, true);
            if (is_array($decoded)) {
                foreach ($nutritionFields as $field) {
                    if (isset($decoded[$field]) && is_numeric($decoded[$field])) {
                        $nutritionFacts[$field] = (float) $decoded[$field];
                    }
                }
            }
        }

        foreach ($nutritionFields as $field) {
            $val = $data["nutrition_{$field}"] ?? '';
            if ($val !== '' && is_numeric($val)) {
                $nutritionFacts[$field] = (float) $val;
            }
        }

        return [
            'title'           => $data['title'] ?? '',
            'description'     => $data['description'] ?? '',
            'price'           => is_numeric($data['price'] ?? '') ? (float) $data['price'] : 0,
            'in_stock'           => ($data['in_stock'] ?? '') !== '' ? (bool) (int) $data['in_stock'] : true,
            'is_active'          => ($data['is_active'] ?? '') !== '' ? (bool) (int) $data['is_active'] : true,
            'weight'             => ($data['weight'] ?? '') !== '' ? $data['weight'] : null,
            'category_id'        => $this->resolveCategory($data['category_id'] ?? ''),
            'ingredients'        => $this->parseTagString($data['ingredients'] ?? null),
            'allergens'          => $this->parseTagString($data['allergens'] ?? null),
            'nutrition_facts'    => ! empty($nutritionFacts) ? $nutritionFacts : null,
        ];
    }

    private function filterUpdateData(array $productData, array $headers): array
    {
        $nutritionColumns = array_map(fn ($f) => "nutrition_{$f}", ['energy', 'fat', 'saturated_fat', 'carbohydrates', 'sugars', 'protein', 'salt', 'fiber']);

        // Map each product field to the CSV column(s) that should trigger its update
        $fieldMap = [
            'title'       => ['title'],
            'description' => ['description'],
            'price'       => ['price'],
            'in_stock'           => ['in_stock'],
            'is_active'          => ['is_active'],
            'weight'             => ['weight'],
            'category_id'        => ['category_id'],
            'ingredients'        => ['ingredients'],
            'allergens'          => ['allergens'],
            'nutrition_facts'    => array_merge(['nutrition_facts'], $nutritionColumns),
        ];

        $updateData = [];
        foreach ($fieldMap as $field => $columns) {
            foreach ($columns as $col) {
                if (in_array($col, $headers)) {
                    $updateData[$field] = $productData[$field];
                    break;
                }
            }
        }

        return $updateData;
    }

    private function parseTagString(?string $tags): ?array
    {
        if (empty($tags)) {
            return null;
        }

        $decoded = json_decode($tags, true);
        if (is_array($decoded)) {
            $items = array_filter(array_map('trim', $decoded), fn ($t) => ! empty($t));

            return ! empty($items) ? array_values($items) : null;
        }

        $items = array_filter(array_map('trim', explode(',', $tags)), fn ($t) => ! empty($t));

        return ! empty($items) ? array_values($items) : null;
    }

    private function resolveCategory(string $value): ?int
    {
        if ($value === '' || ! is_numeric($value)) {
            return null;
        }

        $id = (int) $value;

        return \App\Models\Category::where('id', $id)->exists() ? $id : null;
    }
}
