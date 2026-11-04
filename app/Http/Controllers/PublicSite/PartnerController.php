<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Requests\PublicOrganizationRegistrationRequest;
use App\Http\Requests\PublicPartnerStatusRequest;
use App\Services\OrganizationRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Modules\Core\Entities\Organization;

class PartnerController
{
    public function index(): View
    {
        return view('public.partners.index');
    }

    public function register(Request $request): View
    {
        $organization = $this->sessionOrganization($request);

        return view('public.partners.register', ['organization' => $organization]);
    }

    public function store(PublicOrganizationRegistrationRequest $request, OrganizationRegistrationService $service): RedirectResponse
    {
        $isRepair = $request->filled('application_reference');
        $result = $isRepair ? $service->updateRejected($request) : $service->register($request);

        $request->session()->put('partner_status', [
            'organization_id' => $result['organization']->id,
            'reference' => $result['reference'],
        ]);

        return redirect()->route('public.partner.status')->with([
            'registration_reference' => $result['reference'],
            'status_message' => $isRepair
                ? 'Perbaikan pengajuan berhasil dikirim dan kembali menunggu verifikasi Admin Salamaid.'
                : 'Pengajuan lembaga berhasil dikirim dan sedang menunggu verifikasi Admin Salamaid.',
        ]);
    }

    public function lookup(PublicPartnerStatusRequest $request, OrganizationRegistrationService $service): RedirectResponse
    {
        $organization = $service->findByReferenceAndEmail(
            $request->validated('application_reference'),
            $request->validated('email'),
        );

        if (! $organization) {
            return back()->withInput()->withErrors(['application_reference' => 'Nomor pengajuan atau email tidak cocok.']);
        }

        $request->session()->put('partner_status', [
            'organization_id' => $organization->id,
            'reference' => $organization->application_reference,
        ]);

        return redirect()->route('public.partner.status');
    }

    public function clearStatus(Request $request): RedirectResponse
    {
        $request->session()->forget('partner_status');

        return redirect()->route('public.partner.status');
    }

    public function status(Request $request): View
    {
        return view('public.partners.status', ['organization' => $this->sessionOrganization($request)]);
    }

    private function sessionOrganization(Request $request): ?Organization
    {
        $status = $request->session()->get('partner_status', []);
        if (empty($status['organization_id']) || empty($status['reference'])) return null;

        return Organization::query()
            ->whereKey($status['organization_id'])
            ->where('application_reference', $status['reference'])
            ->first();
    }
}
