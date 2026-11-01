<?php

namespace Modules\Core\App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Core\Entities\Organization;

class OrganizationStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public string $from,
        public string $to,
        public ?User $actor = null,
        public ?string $reason = null,
    ) {
    }
}
