<?php

namespace App\Events\Admin;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public bool $active,
        public ?User $actor = null,
    ) {
    }
}
