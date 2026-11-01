<?php

namespace Modules\Transparency\App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Transparency\Entities\CampaignUpdate;

class CampaignUpdateStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CampaignUpdate $update,
        public string $from,
        public string $to,
        public ?User $actor = null,
        public ?string $reason = null,
    ) {
    }
}
