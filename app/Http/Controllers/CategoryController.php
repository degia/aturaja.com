<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::with('children')
            ->withCount(['children', 'transactions'])
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('categories.index', [
            'categories' => $categories->groupBy('type'),
            'categoryIsDeletable' => fn (Category $category): bool => $category->children_count === 0 && $category->transactions_count === 0,
        ]);
    }

    public function create(): View
    {
        $parents = Category::whereNull('parent_id')->active()->orderBy('name')->get();

        return view('categories.create', compact('parents'));
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($this->validated($request));

        return redirect()->route('categories.index')->with('status', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category): View
    {
        $parents = Category::whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->active()
            ->orderBy('name')
            ->get();

        return view('categories.edit', compact('category', 'parents'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request));

        return redirect()->route('categories.index')->with('status', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->children()->count() > 0 || $category->transactions()->count() > 0) {
            return back()->with('error', 'Kategori masih memiliki sub-kategori atau dipakai transaksi.');
        }

        $category->delete();

        return redirect()->route('categories.index')->with('status', 'Kategori berhasil dihapus.');
    }

    public function archive(Category $category): RedirectResponse
    {
        $category->update(['is_archived' => ! $category->is_archived]);

        return redirect()->route('categories.index')->with(
            'status',
            $category->is_archived ? 'Kategori diarsipkan.' : 'Kategori dikembalikan.'
        );
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(Category::TYPES)],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'icon' => ['nullable', 'string', 'max:32'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        if ($data['parent_id'] ?? false) {
            $parent = Category::find($data['parent_id']);

            if ($parent && $parent->type !== $data['type']) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Sub-kategori harus bertipe sama dengan kategori induk.',
                ]);
            }
        }

        return $data;
    }
}