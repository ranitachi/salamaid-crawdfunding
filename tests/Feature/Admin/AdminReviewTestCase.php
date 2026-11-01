<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Modules\Catalog\Entities\Campaign;
use Modules\Catalog\Entities\CampaignMedia;
use Modules\Core\Entities\Category;
use Modules\Core\Entities\Organization;
use Tests\TestCase;

abstract class AdminReviewTestCase extends TestCase
{
    use RefreshDatabase;

    protected function user(string $role = 'reviewer', bool $active = true): User
    {
        return User::create([
            'name' => ucfirst($role).' User',
            'email' => $role.'-'.Str::uuid().'@salamaid.test',
            'password' => 'password',
            'role' => $role,
            'is_active' => $active,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    protected function organization(string $status = 'pending', array $overrides = []): Organization
    {
        return Organization::create(array_merge([
            'slug' => 'org-'.Str::uuid(),
            'name' => 'Organisasi '.Str::random(6),
            'description' => 'Deskripsi organisasi untuk pengujian.',
            'address' => 'Jakarta',
            'contact_email' => 'org-'.Str::uuid().'@salamaid.test',
            'contact_phone' => '+62 811 0000 0000',
            'bank_account_json' => [
                'bank' => 'BCA',
                'account_number' => '1234567890',
                'account_name' => 'Yayasan Pengujian',
            ],
            'status' => $status,
            'verified_at' => $status === 'verified' ? now() : null,
        ], $overrides));
    }

    /**
     * @param array<string, mixed> $overrides
     */
    protected function campaign(Organization $organization, string $status = 'under_review', array $overrides = []): Campaign
    {
        return Campaign::create(array_merge([
            'organization_id' => $organization->id,
            'category_id' => null,
            'title' => 'Campaign '.Str::random(8),
            'description' => 'Deskripsi campaign yang lengkap untuk kebutuhan moderasi.',
            'target_amount' => 10_000_000,
            'collected_amount' => 0,
            'deadline_at' => now()->addMonth(),
            'location_label' => 'Indonesia',
            'status' => $status,
            'is_featured' => false,
        ], $overrides));
    }

    protected function media(Campaign $campaign): CampaignMedia
    {
        return CampaignMedia::create([
            'campaign_id' => $campaign->id,
            'type' => 'image',
            'url' => 'https://example.test/campaign-'.Str::random(6).'.jpg',
            'caption' => 'Media campaign',
            'sort' => 0,
        ]);
    }

    protected function category(string $name = 'Pendidikan'): Category
    {
        return Category::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
        ]);
    }
}
