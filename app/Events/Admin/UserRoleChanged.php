<?php

namespace App\Events\Admin;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRoleChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public string $from,
        public string $to,
        public ?User $actor = null,
    ) {
    }
}
