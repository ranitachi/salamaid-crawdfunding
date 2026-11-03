<?php

namespace Tests\Feature\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Entities\OrganizationMember;
use Modules\Core\Entities\Organization;

class OrganizationReviewTest extends AdminReviewTestCase
{
    public function test_organization_admin_can_update_own_public_profile_and_branding(): void
    {
        Storage::fake('public');
        $admin = $this->user('organization_admin');
        $organization = $this->organization('verified');
        OrganizationMember::create(['organization_id' => $organization->id, 'user_id' => $admin->id, 'role' => 'owner']);

        $this->actingAs($admin)->get(route('admin.organizations.settings.edit', $organization))->assertOk();
        $this->actingAs($admin)->put(route('admin.organizations.settings.update', $organization), [
            'tagline' => 'Bersama menghadirkan dampak',
            'description' => 'Profil publik baru',
            'address' => 'Bandung',
            'contact_email' => 'publik@lembaga.test',
            'contact_phone' => '+62 812 0000 0000',
            'primary_color' => '#AA0000',
            'secondary_color' => '#FFF1F0',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertRedirect(route('admin.organizations.settings.edit', $organization));

        $organization->refresh();
        $this->assertSame('Bersama menghadirkan dampak', $organization->tagline);
        $this->assertSame('#AA0000', $organization->primary_color);
        $this->assertNotEmpty($organization->logo_url);
        $this->assertDatabaseHas('admin_audit_logs', ['action' => 'organization.profile.updated', 'subject_id' => $organization->id]);
    }

    public function test_organization_admin_cannot_update_another_organization(): void
    {
        $admin = $this->user('organization_admin');
        $owned = $this->organization('verified');
        $other = $this->organization('verified');
        OrganizationMember::create(['organization_id' => $owned->id, 'user_id' => $admin->id, 'role' => 'owner']);

        $this->actingAs($admin)
            ->get(route('admin.organizations.settings.edit', $other))
            ->assertForbidden();
    }

    public function test_reviewer_can_view_and_filter_organizations(): void
    {
        $reviewer = $this->user('reviewer');
        $pending = $this->organization('pending', ['name' => 'Organisasi Pending']);
        $verified = $this->organization('verified', ['name' => 'Organisasi Verified']);

        $response = $this->actingAs($reviewer)->get(route('admin.organizations.index', [
            'status' => 'pending',
        ]));

        $response->assertOk()
            ->assertSee('Organisasi Pending')
            ->assertDontSee('Organisasi Verified')
            ->assertViewHas('organizations', fn ($paginator) => $paginator->total() === 1);

        $this->assertDatabaseHas('organizations', ['id' => $pending->id, 'status' => 'pending']);
        $this->assertDatabaseHas('organizations', ['id' => $verified->id, 'status' => 'verified']);
    }

    public function test_reviewer_can_see_organization_detail_and_sensitive_bank_account(): void
    {
        $reviewer = $this->user('reviewer');
        $organization = $this->organization('pending');

        $response = $this->actingAs($reviewer)->get(route('admin.organizations.show', $organization));

        $response->assertOk()
            ->assertSee($organization->name)
            ->assertSee('1234567890')
            ->assertViewHas('canViewSensitive', true)
            ->assertViewHas('canReview', true);
    }

    public function test_support_can_view_but_cannot_see_bank_account_or_review(): void
    {
        $support = $this->user('support');
        $organization = $this->organization('pending');

        $response = $this->actingAs($support)->get(route('admin.organizations.show', $organization));

        $response->assertOk()
            ->assertDontSee('1234567890')
            ->assertSee('hanya dapat dilihat')
            ->assertViewHas('canViewSensitive', false)
            ->assertViewHas('canReview', false);
    }

    public function test_support_cannot_approve_reject_or_suspend_organization(): void
    {
        $support = $this->user('support');
        $organization = $this->organization('pending');

        $this->actingAs($support)
            ->post(route('admin.organizations.approve', $organization))
            ->assertForbidden();

        $this->actingAs($support)
            ->post(route('admin.organizations.reject', $organization), ['reason' => 'Dokumen belum lengkap.'])
            ->assertForbidden();

        $this->actingAs($support)
            ->post(route('admin.organizations.suspend', $organization), ['reason' => 'Dokumen belum lengkap.'])
            ->assertForbidden();

        $this->assertSame('pending', $organization->fresh()->status);
    }

    public function test_reviewer_can_approve_organization(): void
    {
        $reviewer = $this->user('reviewer');
        $organization = $this->organization('pending');

        $response = $this->actingAs($reviewer)->post(route('admin.organizations.approve', $organization));

        $response->assertRedirect(route('admin.organizations.show', $organization))
            ->assertSessionHas('success');

        $organization->refresh();

        $this->assertSame('verified', $organization->status);
        $this->assertNotNull($organization->verified_at);
        $this->assertSame($reviewer->id, $organization->reviewed_by);
        $this->assertNotNull($organization->reviewed_at);
    }

    public function test_reject_organization_requires_reason(): void
    {
        $reviewer = $this->user('reviewer');
        $organization = $this->organization('pending');

        $response = $this->actingAs($reviewer)->post(route('admin.organizations.reject', $organization), []);

        $response->assertSessionHasErrors('reason');
        $this->assertSame('pending', $organization->fresh()->status);

        $response = $this->actingAs($reviewer)->post(route('admin.organizations.reject', $organization), [
            'reason' => 'Dokumen legalitas belum lengkap.',
        ]);

        $response->assertRedirect(route('admin.organizations.show', $organization))
            ->assertSessionHas('success');

        $organization->refresh();

        $this->assertSame('rejected', $organization->status);
        $this->assertSame('Dokumen legalitas belum lengkap.', $organization->rejection_reason);
        $this->assertSame($reviewer->id, $organization->reviewed_by);
    }

    public function test_verified_organization_can_be_suspended_and_restored(): void
    {
        $reviewer = $this->user('reviewer');
        $organization = $this->organization('verified');

        $this->actingAs($reviewer)
            ->post(route('admin.organizations.suspend', $organization), [
                'reason' => 'Ditemukan pelanggaran ketentuan organisasi.',
            ])
            ->assertSessionHas('success');

        $organization->refresh();
        $this->assertSame('suspended', $organization->status);
        $this->assertSame('Ditemukan pelanggaran ketentuan organisasi.', $organization->suspension_reason);
        $this->assertNotNull($organization->suspended_at);

        $this->actingAs($reviewer)
            ->post(route('admin.organizations.restore', $organization))
            ->assertSessionHas('success');

        $organization->refresh();
        $this->assertSame('verified', $organization->status);
        $this->assertNull($organization->suspension_reason);
        $this->assertNull($organization->suspended_at);
    }

    public function test_illegal_organization_status_transition_is_rejected(): void
    {
        $reviewer = $this->user('reviewer');
        $organization = $this->organization('verified');

        $response = $this->actingAs($reviewer)->post(route('admin.organizations.reject', $organization), [
            'reason' => 'Mencoba transisi yang tidak diizinkan.',
        ]);

        $response->assertRedirect(route('admin.organizations.show', $organization))
            ->assertSessionHas('error');

        $this->assertSame('verified', $organization->fresh()->status);
    }

    public function test_user_without_organization_permission_is_denied(): void
    {
        $finance = $this->user('finance');
        $organization = $this->organization('pending');

        $this->actingAs($finance)
            ->get(route('admin.organizations.index'))
            ->assertForbidden();

        $this->actingAs($finance)
            ->get(route('admin.organizations.show', $organization))
            ->assertForbidden();

        $this->actingAs($finance)
            ->post(route('admin.organizations.approve', $organization))
            ->assertForbidden();
    }

    public function test_inactive_reviewer_and_guest_are_denied(): void
    {
        $inactive = $this->user('reviewer', false);
        $organization = $this->organization('pending');

        $this->actingAs($inactive)
            ->get(route('admin.organizations.index'))
            ->assertForbidden();

        $this->get(route('admin.organizations.index'))->assertForbidden();
    }

    public function test_rejected_organization_can_be_restored_to_pending(): void
    {
        $reviewer = $this->user('reviewer');
        $organization = $this->organization('rejected', [
            'rejection_reason' => 'Dokumen kurang.',
        ]);

        $this->actingAs($reviewer)
            ->post(route('admin.organizations.restore', $organization))
            ->assertSessionHas('success');

        $organization->refresh();

        $this->assertSame('pending', $organization->status);
        $this->assertNull($organization->rejection_reason);
    }
}
