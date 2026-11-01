<?php

namespace Modules\Payment\Policies;

use App\Models\User;
use Modules\Payment\Entities\Payment;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyAdminPermission('payments.view', 'payments.manage');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $this->viewAny($user);
    }

    /** Retry webhook, reconcile, inquiry status. */
    public function manage(User $user, Payment $payment): bool
    {
        return $user->hasAdminPermission('payments.manage');
    }
}
