<?php

namespace Modules\Operations\App\Http\Controllers\Admin;

use App\Exceptions\AdminActionException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Operations\App\Http\Requests\MarkPayoutPaidRequest;
use Modules\Operations\App\Http\Requests\RejectPayoutRequest;
use Modules\Operations\App\Queries\Admin\PayoutMonitoringQuery;
use Modules\Operations\App\Services\Admin\PayoutBalanceService;
use Modules\Operations\App\Services\Admin\PayoutService;
use Modules\Operations\Entities\Payout;

class PayoutAdminController extends Controller
{
    public function __construct(
        private readonly PayoutMonitoringQuery $query,
        private readonly PayoutService $service,
        private readonly PayoutBalanceService $balance,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payout::class);

        return view('admin.payouts.index', [
            'payouts' => $this->query->paginate($request),
            'summary' => $this->query->summary(),
        ]);
    }

    public function show(Payout $payout): View
    {
        $this->authorize('view', $payout);
        $payout->load(['organization:id,name,slug,status', 'requester:id,name,email', 'approver:id,name,email']);

        return view('admin.payouts.show', [
            'payout' => $payout,
            'availableAmount' => $this->balance->availableAmount($payout->organization_id, (string) $payout->getKey()),
        ]);
    }

    public function approve(Request $request, Payout $payout): RedirectResponse
    {
        $this->authorize('approve', $payout);

        return $this->run($payout, fn () => $this->service->approve($payout, $request->user()), 'Payout berhasil disetujui.');
    }

    public function reject(RejectPayoutRequest $request, Payout $payout): RedirectResponse
    {
        $this->authorize('reject', $payout);

        return $this->run($payout, fn () => $this->service->reject($payout, $request->user(), (string) $request->validated('reason')), 'Payout ditolak.');
    }

    public function markPaid(MarkPayoutPaidRequest $request, Payout $payout): RedirectResponse
    {
        $this->authorize('markPaid', $payout);

        return $this->run($payout, fn () => $this->service->markPaid($payout, $request->user(), (string) $request->validated('evidence_url')), 'Payout ditandai sudah dibayar.');
    }

    private function run(Payout $payout, callable $action, string $successMessage): RedirectResponse
    {
        try {
            $action();
        } catch (AdminActionException $exception) {
            return redirect()->route('admin.payouts.show', $payout)->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.payouts.show', $payout->refresh())->with('success', $successMessage);
    }
}
