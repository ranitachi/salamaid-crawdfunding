<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Category;
use Modules\Core\Entities\Organization;
use Tests\TestCase;

class PublicProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_filter_applies_to_priority_urgent_and_program_sections(): void
    {
        $education = Category::create(['name' => 'Pendidikan', 'slug' => 'pendidikan', 'icon' => 'book-open', 'is_active' => true]);
        $humanity = Category::create(['name' => 'Kemanusiaan', 'slug' => 'kemanusiaan', 'icon' => 'heart', 'is_active' => true]);
        $organization = Organization::create(['slug' => 'program-filter-org', 'name' => 'Program Filter Org', 'status' => 'verified', 'verified_at' => now()]);
        Campaign::create(['organization_id' => $organization->id, 'category_id' => $education->id, 'title' => 'Beasiswa Pendidikan', 'slug' => 'beasiswa-pendidikan', 'description' => 'Pendidikan', 'target_amount' => 1000000, 'deadline_at' => now()->addDays(20), 'status' => 'published', 'is_featured' => true, 'published_at' => now()]);
        Campaign::create(['organization_id' => $organization->id, 'category_id' => $humanity->id, 'title' => 'Paket Kemanusiaan', 'slug' => 'paket-kemanusiaan', 'description' => 'Kemanusiaan', 'target_amount' => 1000000, 'deadline_at' => now()->addDays(10), 'status' => 'published', 'is_featured' => true, 'published_at' => now()]);

        $response = $this->get(route('public.program', ['category' => 'pendidikan']))
            ->assertOk()
            ->assertSee('Beasiswa Pendidikan')
            ->assertDontSee('Paket Kemanusiaan');

        $response->assertSee('public-chip is-active', false);
        $response->assertSee('>Pendidikan</a>', false);
    }
}
