<?php

namespace Tests\Feature\Admin;

use App\Contracts\AdminAuditLogger;
use Illuminate\Support\Facades\Event;
use Mockery;
use Modules\Core\App\Events\CategoryChanged;
use Modules\Core\Entities\Category;

class CategoryManagementTest extends AdminReviewTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_admin_can_list_and_filter_categories(): void
    {
        $admin = $this->user('admin');
        $active = Category::create(['name' => 'Pendidikan', 'slug' => 'pendidikan', 'is_active' => true]);
        $inactive = Category::create(['name' => 'Kesehatan', 'slug' => 'kesehatan', 'is_active' => false]);

        $this->actingAs($admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertSee($active->name)
            ->assertSee($inactive->name);

        $this->actingAs($admin)
            ->get(route('admin.categories.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee($inactive->name)
            ->assertViewHas('categories', fn ($paginator) => $paginator->total() === 1);

        $this->actingAs($admin)
            ->get(route('admin.categories.index', ['search' => 'Kese']))
            ->assertOk()
            ->assertSee($inactive->name)
            ->assertDontSee($active->name);
    }

    public function test_non_manager_cannot_manage_categories(): void
    {
        $reviewer = $this->user('reviewer');
        $support = $this->user('support');
        $category = Category::create(['name' => 'Pendidikan', 'slug' => 'pendidikan']);

        $this->actingAs($reviewer)->get(route('admin.categories.index'))->assertForbidden();
        $this->actingAs($support)->get(route('admin.categories.index'))->assertForbidden();

        $this->actingAs($reviewer)
            ->post(route('admin.categories.store'), ['name' => 'Baru'])
            ->assertForbidden();
        $this->actingAs($reviewer)
            ->patch(route('admin.categories.update', $category), ['name' => 'Ubah'])
            ->assertForbidden();
        $this->actingAs($reviewer)
            ->delete(route('admin.categories.destroy', $category))
            ->assertForbidden();

        $this->assertSame('Pendidikan', $category->fresh()->name);
    }

    public function test_admin_can_create_category_with_unique_slug_and_audit(): void
    {
        $admin = $this->user('admin');

        Event::fake([CategoryChanged::class]);

        $logger = Mockery::mock(AdminAuditLogger::class);
        $logger->shouldReceive('log')
            ->once()
            ->withArgs(function (
                string $action,
                $subject,
                array $before,
                array $after,
                ?string $reason,
                $actor,
                array $context,
            ) use ($admin) {
                return $action === 'category.created'
                    && $before === []
                    && $after['name'] === 'Pendidikan'
                    && $after['slug'] === 'pendidikan'
                    && $after['is_active'] === true
                    && $actor?->getKey() === $admin->getKey()
                    && $context['entity'] === 'category';
            });

        $this->app->instance(AdminAuditLogger::class, $logger);

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Pendidikan',
                'slug' => '',
                'icon' => 'book-open',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'name' => 'Pendidikan',
            'slug' => 'pendidikan',
            'is_active' => true,
        ]);

        Event::assertDispatched(
            CategoryChanged::class,
            fn (CategoryChanged $event) => $event->action === 'created' && $event->category->slug === 'pendidikan',
        );
    }

    public function test_duplicate_slug_is_rejected(): void
    {
        $admin = $this->user('admin');
        Category::create(['name' => 'Pendidikan', 'slug' => 'pendidikan']);

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Pendidikan Baru',
                'slug' => 'pendidikan',
            ])
            ->assertSessionHasErrors('slug');

        $this->assertSame(1, Category::query()->count());

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Pendidikan',
                'slug' => '',
            ])
            ->assertRedirect(route('admin.categories.create'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'sudah dipakai'));

        $this->assertSame(1, Category::query()->count());
    }

    public function test_admin_can_update_category_and_unique_slug_ignores_self(): void
    {
        $admin = $this->user('admin');
        $category = Category::create(['name' => 'Pendidikan', 'slug' => 'pendidikan']);
        Category::create(['name' => 'Kesehatan', 'slug' => 'kesehatan']);

        $this->actingAs($admin)
            ->patch(route('admin.categories.update', $category), [
                'name' => 'Pendidikan Baru',
                'slug' => 'pendidikan',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.categories.edit', $category))
            ->assertSessionHas('success');

        $this->assertSame('Pendidikan Baru', $category->fresh()->name);

        $this->actingAs($admin)
            ->patch(route('admin.categories.update', $category), [
                'name' => 'Pendidikan Baru',
                'slug' => 'kesehatan',
            ])
            ->assertSessionHasErrors('slug');

        $this->assertSame('Pendidikan Baru', $category->fresh()->name);
        $this->assertSame('pendidikan', $category->fresh()->slug);
    }

    public function test_admin_can_activate_and_deactivate_category(): void
    {
        $admin = $this->user('admin');
        $category = Category::create(['name' => 'Pendidikan', 'slug' => 'pendidikan', 'is_active' => true]);

        $this->actingAs($admin)
            ->post(route('admin.categories.deactivate', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertFalse($category->fresh()->is_active);

        $this->actingAs($admin)
            ->post(route('admin.categories.activate', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertTrue($category->fresh()->is_active);
    }

    public function test_category_used_by_campaign_cannot_be_deleted(): void
    {
        $admin = $this->user('admin');
        $organization = $this->organization('verified');
        $category = Category::create(['name' => 'Pendidikan', 'slug' => 'pendidikan']);
        $campaign = $this->campaign($organization, 'draft', ['category_id' => $category->id]);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'masih dipakai'));

        $this->assertDatabaseHas('categories', ['id' => $category->id]);

        $campaign->delete();

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'masih dipakai'));

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_category_campaign_count_includes_soft_deleted_campaigns(): void
    {
        $admin = $this->user('admin');
        $organization = $this->organization('verified');
        $category = Category::create(['name' => 'Pendidikan', 'slug' => 'pendidikan']);
        $first = $this->campaign($organization, 'draft', ['category_id' => $category->id]);
        $this->campaign($organization, 'draft', ['category_id' => $category->id]);
        $first->delete();

        $this->actingAs($admin)
            ->get(route('admin.categories.index'))
            ->assertOk()
            ->assertViewHas('categories', function ($paginator) {
                $row = $paginator->first();

                return $row->slug === 'pendidikan' && $row->campaigns_count === 2;
            });
    }

    public function test_unused_category_can_be_deleted_with_audit(): void
    {
        $admin = $this->user('admin');
        $category = Category::create(['name' => 'Pendidikan', 'slug' => 'pendidikan']);

        $logger = Mockery::mock(AdminAuditLogger::class);
        $logger->shouldReceive('log')
            ->once()
            ->withArgs(fn (
                string $action,
                $subject,
                array $before,
                array $after,
                ?string $reason,
                $actor,
                array $context,
            ) => $action === 'category.deleted'
                && $subject->getKey() === $category->getKey()
                && $before['slug'] === 'pendidikan'
                && $after === []
                && $actor?->getKey() === $admin->getKey());

        $this->app->instance(AdminAuditLogger::class, $logger);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_requires_name_and_valid_status(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => '',
                'is_active' => 'maybe',
            ])
            ->assertSessionHasErrors(['name', 'is_active']);

        $this->assertSame(0, Category::query()->count());
    }

    public function test_admin_can_open_category_create_and_edit_forms(): void
    {
        $admin = $this->user('admin');
        $category = Category::create(['name' => 'Pendidikan', 'slug' => 'pendidikan']);

        $this->actingAs($admin)
            ->get(route('admin.categories.create'))
            ->assertOk()
            ->assertSee('Tambah Kategori');

        $this->actingAs($admin)
            ->get(route('admin.categories.edit', $category))
            ->assertOk()
            ->assertSee($category->name)
            ->assertSee('Simpan Perubahan');
    }
}
