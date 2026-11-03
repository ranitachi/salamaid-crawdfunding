<?php

namespace Modules\Core\App\Http\Controllers\Admin;

use App\Contracts\AdminAuditLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Core\App\Http\Requests\OrganizationProfileUpdateRequest;
use Modules\Core\Entities\Organization;

class OrganizationProfileController extends Controller
{
    public function edit(Organization $organization): View
    {
        $this->authorize('manageSettings', $organization);

        return view('admin.organizations.settings', compact('organization'));
    }

    public function update(OrganizationProfileUpdateRequest $request, Organization $organization, AdminAuditLogger $audit): RedirectResponse
    {
        $before = $organization->only([
            'tagline', 'description', 'address', 'contact_email', 'contact_phone',
            'website_url', 'instagram_url', 'facebook_url', 'whatsapp_url',
            'primary_color', 'secondary_color', 'logo_url', 'banner_url',
        ]);
        $values = $request->safe()->except(['logo', 'banner']);

        if ($request->hasFile('logo')) {
            $values['logo_url'] = $this->storeAsset($request->file('logo'), $organization->logo_url, 'logo');
        }

        if ($request->hasFile('banner')) {
            $values['banner_url'] = $this->storeAsset($request->file('banner'), $organization->banner_url, 'banner');
        }

        $organization->fill($values);
        $organization->save();

        $audit->log(
            'organization.profile.updated',
            $organization,
            $this->auditValues($before),
            $this->auditValues($organization->only(array_keys($before))),
            null,
            $request->user(),
        );

        return redirect()->route('admin.organizations.settings.edit', $organization)
            ->with('success', 'Profil dan branding lembaga berhasil diperbarui.');
    }

    private function storeAsset(UploadedFile $file, ?string $previousUrl, string $prefix): string
    {
        $path = $file->storeAs('organizations', $prefix.'-'.Str::lower(Str::random(20)).'.'.$file->extension(), 'public');

        if ($previousUrl && Str::startsWith($previousUrl, [config('filesystems.disks.public.url').'/'])) {
            Storage::disk('public')->delete(Str::after($previousUrl, config('filesystems.disks.public.url').'/'));
        }

        return Storage::disk('public')->url($path);
    }

    private function auditValues(array $values): array
    {
        return collect($values)->except(['logo_url', 'banner_url'])->map(fn ($value) => is_scalar($value) || $value === null ? $value : '[redacted]')->all();
    }
}
