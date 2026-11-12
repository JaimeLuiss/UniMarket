<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'active' => ['nullable', 'boolean'],
        ]);

        $categories = Category::query()
            ->withCount('listings')
            ->when(isset($filters['active']), fn (Builder $query) => $query->where('is_active', (bool) $filters['active']))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query
                ->where('name', 'like', '%'.$search.'%'))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.categories.index', compact('categories', 'filters'));
    }

    public function create(): View
    {
        return view('admin.categories.form', ['category' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $this->ensureUniqueName($validated['name']);

        Category::query()->create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['name']),
            'is_active' => true,
        ]);

        return redirect()->route('admin.categories.index')->with('status', 'La categoría fue creada.');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $this->ensureUniqueName($validated['name'], $category->id);

        $category->update([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['name'], $category->id),
        ]);

        return redirect()->route('admin.categories.index')->with('status', 'La categoría fue actualizada.');
    }

    public function updateStatus(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate(['is_active' => ['required', 'boolean']]);
        $category->update(['is_active' => (bool) $validated['is_active']]);

        return back()->with('status', $category->is_active
            ? 'La categoría fue activada.'
            : 'La categoría fue desactivada.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->listings()->exists()) {
            return back()->withErrors([
                'category' => 'No se puede eliminar una categoría que tiene publicaciones. Desactívala en su lugar.',
            ]);
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('status', 'La categoría fue eliminada.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:100000'],
        ];
    }

    private function uniqueSlug(string $name, ?int $ignoreCategoryId = null): string
    {
        $baseSlug = Str::slug($name) ?: 'categoria';
        $slug = $baseSlug;
        $suffix = 2;

        while (Category::query()
            ->where('slug', $slug)
            ->when($ignoreCategoryId, fn (Builder $query) => $query->whereKeyNot($ignoreCategoryId))
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }

    private function ensureUniqueName(string $name, ?int $ignoreCategoryId = null): void
    {
        $duplicateExists = Category::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower(trim($name))])
            ->when($ignoreCategoryId, fn (Builder $query) => $query->whereKeyNot($ignoreCategoryId))
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'name' => 'Ya existe una categoría con ese nombre.',
            ]);
        }
    }
}
