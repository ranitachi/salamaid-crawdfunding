<?php

namespace App\Http\Controllers\Admin;

use App\Models\HelpCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HelpCategoryAdminController
{
    public function index(): View
    {
        return view('admin.help.categories.index', [
            'categories' => HelpCategory::query()->withCount('articles')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        HelpCategory::create($data);

        return back()->with('success', 'Kategori bantuan berhasil ditambahkan.');
    }

    public function update(Request $request, HelpCategory $helpCategory): RedirectResponse
    {
        $data = $this->validated($request, $helpCategory);
        $helpCategory->update($data);
        $helpCategory->articles()->update(['category' => $helpCategory->name]);

        return back()->with('success', 'Kategori bantuan berhasil diperbarui.');
    }

    public function destroy(HelpCategory $helpCategory): RedirectResponse
    {
        if ($helpCategory->articles()->exists()) {
            return back()->with('error', 'Kategori masih digunakan artikel bantuan dan tidak dapat dihapus.');
        }

        $helpCategory->delete();
        return back()->with('success', 'Kategori bantuan berhasil dihapus.');
    }

    private function validated(Request $request, ?HelpCategory $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:help_categories,name'.($category ? ','.$category->id : '')],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        return $data;
    }
}
