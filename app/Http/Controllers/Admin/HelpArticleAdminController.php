<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HelpArticle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HelpArticleAdminController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:190'],
            'status' => ['nullable', 'in:published,draft'],
        ]);

        $articles = HelpArticle::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(fn ($query) => $query->where('question', 'like', "%{$search}%")->orWhere('category', 'like', "%{$search}%"));
            })
            ->when(($filters['status'] ?? null) === 'published', fn ($query) => $query->where('is_published', true))
            ->when(($filters['status'] ?? null) === 'draft', fn ($query) => $query->where('is_published', false))
            ->orderBy('sort_order')->orderBy('id')
            ->paginate(15)->withQueryString();

        return view('admin.help.index', compact('articles', 'filters'));
    }

    public function create(): View
    {
        return view('admin.help.create', ['article' => new HelpArticle(['category' => 'Umum', 'is_published' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $article = HelpArticle::create($this->validated($request));

        return to_route('admin.help.edit', $article)->with('success', 'Artikel bantuan berhasil dibuat.');
    }

    public function edit(HelpArticle $help): View
    {
        return view('admin.help.edit', ['article' => $help]);
    }

    public function update(Request $request, HelpArticle $help): RedirectResponse
    {
        $help->update($this->validated($request));

        return back()->with('success', 'Artikel bantuan berhasil diperbarui.');
    }

    public function destroy(HelpArticle $help): RedirectResponse
    {
        $help->delete();

        return to_route('admin.help.index')->with('success', 'Artikel bantuan berhasil dihapus.');
    }

    /** @return array{category:string,question:string,answer:string,sort_order:int,is_published:bool} */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:80'],
            'question' => ['required', 'string', 'max:190'],
            'answer' => ['required', 'string', 'max:10000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_published'] = (bool) ($data['is_published'] ?? false);

        return $data;
    }
}
