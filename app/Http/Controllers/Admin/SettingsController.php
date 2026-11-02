<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\AdminAuditLogger;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\AppSetting;
use App\Services\AppSettingsService;
use Illuminate\Http\RedirectResponse;
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
        $after = $this->settings->update($request->validated());

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

        return back()->with('success', 'Pengaturan identitas Salamaid berhasil disimpan.');
    }

    /** @param array<string, string> $values */
    private function auditValues(array $values): array
    {
        return collect($values)->except(['logo_url', 'favicon_url'])->all();
    }
}
