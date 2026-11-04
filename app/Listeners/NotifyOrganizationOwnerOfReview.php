<?php

namespace App\Listeners;

use App\Notifications\AdminActionRequired;
use Modules\Core\App\Events\OrganizationStatusChanged;

class NotifyOrganizationOwnerOfReview
{
    public function handle(OrganizationStatusChanged $event): void
    {
        $owner = $event->organization->members()
            ->where('role', 'owner')
            ->with('user')
            ->first()?->user;

        if (! $owner) {
            return;
        }

        [$title, $message] = match ($event->to) {
            'verified' => ['Lembaga berhasil diverifikasi', 'Pengajuan lembaga Anda telah diverifikasi oleh Admin Salamaid.'],
            'rejected' => ['Pengajuan perlu diperbaiki', 'Pengajuan lembaga Anda belum dapat disetujui. Silakan cek catatan reviewer dan perbaiki pengajuan Anda.'],
            'suspended' => ['Lembaga ditangguhkan', 'Lembaga Anda sementara ditangguhkan oleh Admin Salamaid. Silakan cek informasi status pengajuan.'],
            'pending' => ['Pengajuan kembali menunggu verifikasi', 'Perbaikan pengajuan lembaga Anda telah diterima dan kembali menunggu verifikasi Admin Salamaid.'],
            default => ['Pembaruan status lembaga', 'Status pengajuan lembaga Anda telah diperbarui.'],
        };

        $owner->notify(new AdminActionRequired([
            'kind' => 'organization_status',
            'title' => $title,
            'message' => $message,
            'url' => route('public.partner.status'),
            'subject_id' => $event->organization->getKey(),
            'application_reference' => $event->organization->application_reference,
            'status' => $event->to,
            'created_at' => now()->toIso8601String(),
        ]));
    }
}
