<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\AdminAuditLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\AppSetting;
use App\Services\AppSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly AppSettingsService $settings)
    {
    }

    public function index(): View
    {
        return view('admin.settings.index', ['settings' => $this->settings->all()]);
    }

    public function update(UpdateSettingsRequest $request, AdminAuditLogger $audit): RedirectResponse
    {
        $before = $this->settings->all();
        $values = $request->validated();

        if ($request->hasFile('logo')) {
            $values['logo_url'] = $this->storeBrandAsset($request->file('logo'), $before['logo_url'] ?? null, 'logo');
        }

        if ($request->hasFile('favicon')) {
            $values['favicon_url'] = $this->storeBrandAsset($request->file('favicon'), $before['favicon_url'] ?? null, 'favicon');
        }

        unset($values['logo'], $values['favicon']);
        $after = $this->settings->update($values);

        $subject = AppSetting::query()->firstOrCreate(
            ['key' => 'app_name'],
            ['value' => $after['app_name'], 'group' => 'branding', 'type' => 'string'],
        );
        $audit->log(
            'settings.branding.updated',
            $subject,
            $this->auditValues($before),
            $this->auditValues($after),
            null,
            $request->user(),
        );

        return redirect()->route('admin.settings.index')->with('success', 'Pengaturan identitas Salamaid berhasil disimpan.');
    }

    private function storeBrandAsset(UploadedFile $file, ?string $previousUrl, string $prefix): string
    {
        $path = $file->storeAs('branding', $prefix.'-'.Str::lower(Str::random(20)).'.'.$file->extension(), 'public');

        if ($previousUrl && Str::startsWith($previousUrl, [config('filesystems.disks.public.url').'/'])) {
            Storage::disk('public')->delete(Str::after($previousUrl, config('filesystems.disks.public.url').'/'));
        }

        return Storage::disk('public')->url($path);
    }

    /** @param array<string, string> $values */
    private function auditValues(array $values): array
    {
        return collect($values)->except(['logo_url', 'favicon_url'])->all();
    }
}
