<?php

namespace Modules\Core\App\Http\Controllers\Admin;

use App\Exceptions\AdminActionException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Core\App\Http\Requests\CategoryActionRequest;
use Modules\Core\App\Http\Requests\CategoryIndexRequest;
use Modules\Core\App\Http\Requests\StoreCategoryRequest;
use Modules\Core\App\Http\Requests\UpdateCategoryRequest;
use Modules\Core\App\Queries\CategoryManagementQuery;
use Modules\Core\App\Services\CategoryManagementService;
use Modules\Core\Entities\Category;

class CategoryAdminController extends Controller
{
    public function __construct(
        private CategoryManagementQuery $query,
        private CategoryManagementService $service,
    ) {
    }

    public function index(CategoryIndexRequest $request): View
    {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 15);

        return view('admin.categories.index', [
            'categories' => $this->query->paginate($filters, $perPage),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Category::class);

        return view('admin.categories.create', [
            'category' => new Category(['is_active' => true]),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        try {
            $category = $this->service->create($request->validated(), $request->user());
        } catch (AdminActionException $exception) {
            return redirect()
                ->route('admin.categories.create')
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori "'.$category->name.'" berhasil dibuat.');
    }

    public function edit(Request $request, Category $category): View
    {
        $this->authorize('update', $category);

        return view('admin.categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        try {
            $this->service->update($category, $request->validated(), $request->user());
        } catch (AdminActionException $exception) {
            return redirect()
                ->route('admin.categories.edit', $category)
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function activate(CategoryActionRequest $request, Category $category): RedirectResponse
    {
        return $this->setActive($category, true, $request, 'Kategori berhasil diaktifkan.');
    }

    public function deactivate(CategoryActionRequest $request, Category $category): RedirectResponse
    {
        return $this->setActive($category, false, $request, 'Kategori berhasil dinonaktifkan.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        try {
            $this->service->delete($category, $request->user());
        } catch (AdminActionException $exception) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori "'.$category->name.'" berhasil dihapus.');
    }

    private function setActive(Category $category, bool $active, Request $request, string $successMessage): RedirectResponse
    {
        try {
            $this->service->setActive($category, $active, $request->user());
        } catch (AdminActionException $exception) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.categories.index')
            ->with('success', $successMessage);
    }
}
