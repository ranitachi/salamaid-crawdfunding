<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Requests\PublicOrganizationRegistrationRequest;
use App\Services\OrganizationRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PartnerController
{
    public function index(): View
    {
        return view('public.partners.index');
    }

    public function register(): View
    {
        return view('public.partners.register');
    }

    public function store(PublicOrganizationRegistrationRequest $request, OrganizationRegistrationService $service): RedirectResponse
    {
        $result = $service->register($request);

        return redirect()->route('public.partner.status')->with([
            'registration_reference' => $result['reference'],
            'status_message' => 'Pengajuan lembaga berhasil dikirim dan sedang menunggu verifikasi Admin Salamaid.',
        ]);
    }

    public function status(): View
    {
        return view('public.partners.status');
    }
}
