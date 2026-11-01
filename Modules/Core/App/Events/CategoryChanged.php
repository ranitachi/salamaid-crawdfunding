<?php

namespace Modules\Core\App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Core\Entities\Category;

class CategoryChanged
{
    use Dispatchable, SerializesModels;

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     */
    public function __construct(
        public Category $category,
        public string $action,
        public array $before = [],
        public array $after = [],
        public ?User $actor = null,
    ) {
    }
}
