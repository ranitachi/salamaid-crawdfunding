<?php

namespace App\Listeners;

use App\Notifications\AdminActionRequired;
use App\Models\User;
use Modules\Catalog\App\Events\CampaignStatusChanged;

class NotifyAdminsOfCampaignReview
{
    public function handle(CampaignStatusChanged $event): void
    {
        if ($event->to !== 'under_review') {
            return;
        }

        User::query()->where('is_active', true)->get()->each(function (User $user) use ($event): void {
            if ($user->hasAdminPermission('campaigns.review')) {
                $user->notify(new AdminActionRequired([
                    'kind' => 'campaign_review',
                    'title' => 'Campaign menunggu review',
                    'message' => 'Campaign "'.$event->campaign->title.'" perlu ditinjau.',
                    'url' => route('admin.campaigns.show', $event->campaign),
                    'subject_id' => $event->campaign->getKey(),
                    'created_at' => now()->toIso8601String(),
                ]));
            }
        });
    }
}
