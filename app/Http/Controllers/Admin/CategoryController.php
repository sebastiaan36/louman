<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(): Response
    {
        $categories = Category::withCount('products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'sort_order' => $category->sort_order,
                'products_count' => $category->products_count,
            ]);

        return Inertia::render('admin/Categories', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return back()->with('success', 'Categorie toegevoegd.');
    }

    /**
     * Update the specified category.
     */
    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return back()->with('success', 'Categorie bijgewerkt.');
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->count() > 0) {
            return back()->with('error', 'Deze categorie bevat nog producten en kan niet worden verwijderd.');
        }

        $category->delete();

        return back()->with('success', 'Categorie verwijderd.');
    }
}
