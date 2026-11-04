<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Pendaftaran lembaga');

        $this->get(route('public.partner.status'))
            ->assertOk()
            ->assertSee('Status pengajuan mitra');
    }
}
