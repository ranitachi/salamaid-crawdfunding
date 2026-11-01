<?php

namespace Modules\Core\App\Services;

use App\Contracts\AdminAuditLogger;
use App\Exceptions\AdminActionException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\App\Events\CategoryChanged;
use Modules\Core\Entities\Category;

class CategoryManagementService
{
    public function __construct(private AdminAuditLogger $audit)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, User $actor): Category
    {
        $name = trim((string) ($data['name'] ?? ''));
        $slug = $this->normalizeSlug($data['slug'] ?? null, $name);

        $this->assertSlugAvailable($slug);

        return DB::transaction(function () use ($data, $name, $slug, $actor) {
            $category = Category::create([
                'name' => $name,
                'slug' => $slug,
                'icon' => $this->nullableString($data['icon'] ?? null),
                'is_active' => $this->boolean($data['is_active'] ?? true),
            ]);

            $after = $category->only(['name', 'slug', 'icon', 'is_active']);

            $this->audit->log(
                'category.created',
                $category,
                [],
                $after,
                null,
                $actor,
                ['module' => 'Core', 'entity' => 'category'],
            );

            event(new CategoryChanged($category, 'created', [], $after, $actor));

            return $category;
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Category $category, array $data, User $actor): Category
    {
        $name = trim((string) ($data['name'] ?? ''));
        $slug = $this->normalizeSlug($data['slug'] ?? null, $name);

        $this->assertSlugAvailable($slug, $category->getKey());

        return DB::transaction(function () use ($category, $data, $name, $slug, $actor) {
            /** @var Category $locked */
            $locked = Category::query()->whereKey($category->getKey())->lockForUpdate()->firstOrFail();

            $before = $locked->only(['name', 'slug', 'icon', 'is_active']);

            $locked->forceFill([
                'name' => $name,
                'slug' => $slug,
                'icon' => $this->nullableString($data['icon'] ?? null),
                'is_active' => $this->boolean($data['is_active'] ?? $locked->is_active),
            ])->save();

            $after = $locked->only(['name', 'slug', 'icon', 'is_active']);

            $this->audit->log(
                'category.updated',
                $locked,
                $before,
                $after,
                null,
                $actor,
                ['module' => 'Core', 'entity' => 'category'],
            );

            event(new CategoryChanged($locked, 'updated', $before, $after, $actor));

            return $locked->refresh();
        });
    }

    public function setActive(Category $category, bool $active, User $actor): Category
    {
        if ((bool) $category->is_active === $active) {
            return $category;
        }

        return DB::transaction(function () use ($category, $active, $actor) {
            /** @var Category $locked */
            $locked = Category::query()->whereKey($category->getKey())->lockForUpdate()->firstOrFail();

            $before = $locked->only(['is_active']);

            $locked->forceFill(['is_active' => $active])->save();

            $after = $locked->only(['is_active']);
            $action = $active ? 'category.activated' : 'category.deactivated';

            $this->audit->log(
                $action,
                $locked,
                $before,
                $after,
                null,
                $actor,
                ['module' => 'Core', 'entity' => 'category'],
            );

            event(new CategoryChanged($locked, $active ? 'activated' : 'deactivated', $before, $after, $actor));

            return $locked->refresh();
        });
    }

    public function delete(Category $category, User $actor): void
    {
        $usage = $category->campaigns()->withTrashed()->count();

        if ($usage > 0) {
            throw AdminActionException::requirement(sprintf(
                'Kategori "%s" masih dipakai oleh %d campaign dan tidak dapat dihapus.',
                $category->name,
                $usage,
            ));
        }

        $before = $category->only(['name', 'slug', 'icon', 'is_active']);

        $this->audit->log(
            'category.deleted',
            $category,
            $before,
            [],
            null,
            $actor,
            ['module' => 'Core', 'entity' => 'category'],
        );

        $category->delete();

        event(new CategoryChanged($category, 'deleted', $before, [], $actor));
    }

    private function normalizeSlug(?string $slug, string $name): string
    {
        $normalized = Str::slug(trim((string) $slug) !== '' ? (string) $slug : $name);

        if ($normalized === '') {
            throw AdminActionException::requirement('Slug kategori tidak valid.');
        }

        return $normalized;
    }

    private function assertSlugAvailable(string $slug, ?string $ignoreId = null): void
    {
        $exists = Category::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw AdminActionException::requirement('Slug kategori sudah dipakai. Gunakan slug lain.');
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function boolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
