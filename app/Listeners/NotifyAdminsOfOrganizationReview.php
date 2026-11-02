<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\AdminActionRequired;
use Modules\Core\App\Events\OrganizationStatusChanged;

class NotifyAdminsOfOrganizationReview
{
    public function handle(OrganizationStatusChanged $event): void
    {
        if ($event->to !== 'pending') {
            return;
        }

        User::query()->where('is_active', true)->get()->each(function (User $user) use ($event): void {
            if ($user->hasAdminPermission('organizations.review')) {
                $user->notify(new AdminActionRequired([
                    'kind' => 'organization_review',
                    'title' => 'Organisasi menunggu verifikasi',
                    'message' => 'Organisasi "'.$event->organization->name.'" perlu ditinjau.',
                    'url' => route('admin.organizations.show', $event->organization),
                    'subject_id' => $event->organization->getKey(),
                    'created_at' => now()->toIso8601String(),
                ]));
            }
        });
    }
}
