<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Entities\Organization;
use Modules\Core\Entities\OrganizationMember;
use App\Models\User;
use Tests\TestCase;

class PublicPartnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_information_page_is_public_and_has_registration_actions(): void
    {
        $this->get(route('public.partner'))
            ->assertOk()
            ->assertSee('Perluas dampak kebaikan bersama Salamaid.')
            ->assertSee('Daftar sebagai lembaga')
            ->assertSee(route('public.partner.register'), false)
            ->assertSee('Login admin lembaga')
            ->assertSee('Pertanyaan umum');
    }

    public function test_partner_placeholder_pages_are_public(): void
    {
        $this->get(route('public.partner.register'))
            ->assertOk()
            ->assertSee('Daftar sebagai lembaga')
            ->assertSee('Nama lembaga');

        $this->get(route('public.partner.status'))
            ->assertOk()
            ->assertSee('Status pengajuan mitra');
    }

    public function test_public_partner_registration_creates_pending_owner_and_inactive_admin(): void
    {
        $response = $this->post(route('public.partner.register.store'), [
            'name' => 'Yayasan Cahaya Sesama',
            'organization_type' => 'yayasan',
            'responsible_name' => 'Ayu Lestari',
            'email' => 'ayu@cahaya.test',
            'mobile' => '081234567890',
            'password' => 'password',
            'password_confirmation' => 'password',
            'address' => 'Jl. Kebaikan No. 1',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'description' => 'Berfokus pada pendidikan anak dan pemberdayaan keluarga.',
        ]);

        $response->assertRedirect(route('public.partner.status'))
            ->assertSessionHas('registration_reference');

        $user = User::where('email', 'ayu@cahaya.test')->firstOrFail();
        $organization = Organization::where('name', 'Yayasan Cahaya Sesama')->firstOrFail();

        $this->assertSame('organization_admin', $user->role);
        $this->assertFalse($user->is_active);
        $this->assertSame('pending', $organization->status);
        $this->assertSame($organization->id, OrganizationMember::where('user_id', $user->id)->value('organization_id'));
        $this->assertSame('owner', OrganizationMember::where('user_id', $user->id)->value('role'));
    }

    public function test_invalid_partner_registration_does_not_create_records(): void
    {
        $this->from(route('public.partner.register'))
            ->post(route('public.partner.register.store'), ['name' => 'Tidak Lengkap'])
            ->assertRedirect(route('public.partner.register'))
            ->assertSessionHasErrors(['organization_type', 'email', 'password', 'address', 'description']);

        $this->assertDatabaseMissing('users', ['email' => 'tidak-lengkap@salamaid.test']);
        $this->assertDatabaseCount('organizations', 0);
    }

    public function test_partner_registration_optimizes_logo_and_keeps_document_private(): void
    {
        Storage::fake('public');
        Storage::fake('private');

        $this->post(route('public.partner.register.store'), [
            'name' => 'Komunitas Bumi Sehat',
            'organization_type' => 'komunitas',
            'responsible_name' => 'Raka Pratama',
            'email' => 'raka@bumisehat.test',
            'mobile' => '081234567891',
            'password' => 'password',
            'password_confirmation' => 'password',
            'address' => 'Jl. Sehat No. 2',
            'city' => 'Bogor',
            'province' => 'Jawa Barat',
            'description' => 'Komunitas kesehatan masyarakat.',
            'logo' => UploadedFile::fake()->image('logo.jpg', 2400, 1600),
            'verification_document' => UploadedFile::fake()->create('legal.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('public.partner.status'));

        $organization = Organization::where('name', 'Komunitas Bumi Sehat')->firstOrFail();
        $this->assertStringStartsWith('/storage/organizations/logos/', (string) $organization->logo_url);
        $this->assertNotNull($organization->verification_document_path);
        Storage::disk('private')->assertExists($organization->verification_document_path);
    }

    public function test_partner_status_lookup_is_session_scoped_and_rejected_application_can_be_repaired(): void
    {
        $user = User::factory()->role('organization_admin')->inactive()->create([
            'name' => 'Dina Pengurus',
            'email' => 'dina@peduli.test',
            'mobile' => '081234567892',
        ]);
        $organization = Organization::create([
            'slug' => 'peduli-rejected',
            'name' => 'Lembaga Peduli',
            'organization_type' => 'yayasan',
            'responsible_name' => 'Dina Pengurus',
            'contact_email' => $user->email,
            'contact_phone' => $user->mobile,
            'address' => 'Alamat lama',
            'city' => 'Depok',
            'province' => 'Jawa Barat',
            'description' => 'Deskripsi lama',
            'application_reference' => 'SM-REJECT1234',
            'status' => 'rejected',
            'rejection_reason' => 'Mohon lengkapi alamat lembaga.',
        ]);
        OrganizationMember::create(['organization_id' => $organization->id, 'user_id' => $user->id, 'role' => 'owner']);

        $this->post(route('public.partner.status.lookup'), [
            'application_reference' => 'SM-REJECT1234',
            'email' => 'dina@peduli.test',
        ])->assertRedirect(route('public.partner.status'));

        $this->get(route('public.partner.status'))
            ->assertOk()
            ->assertSee('Perlu diperbaiki')
            ->assertSee('Mohon lengkapi alamat lembaga.')
            ->assertSee(route('public.partner.register', ['reference' => 'SM-REJECT1234']), false);

        $this->get(route('public.partner.register', ['reference' => 'SM-REJECT1234']))
            ->assertOk()
            ->assertSee('Perbaiki pengajuan lembaga')
            ->assertSee('Alamat lama');

        $this->post(route('public.partner.register.store'), [
            'application_reference' => 'SM-REJECT1234',
            'name' => 'Lembaga Peduli Baru',
            'organization_type' => 'yayasan',
            'responsible_name' => 'Dina Pengurus',
            'email' => 'dina@peduli.test',
            'mobile' => '081234567893',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
            'address' => 'Alamat baru',
            'city' => 'Depok',
            'province' => 'Jawa Barat',
            'description' => 'Deskripsi sudah dilengkapi.',
        ])->assertRedirect(route('public.partner.status'));

        $this->assertDatabaseHas('organizations', ['id' => $organization->id, 'status' => 'pending', 'name' => 'Lembaga Peduli Baru']);
        $this->assertDatabaseCount('users', 1);
        $this->assertFalse($user->fresh()->is_active);
    }
}
