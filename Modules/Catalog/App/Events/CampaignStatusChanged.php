<?php

namespace Modules\Catalog\App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Catalog\Entities\Campaign;

class CampaignStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Campaign $campaign,
        public string $from,
        public string $to,
        public ?User $actor = null,
        public ?string $reason = null,
    ) {
    }
}
