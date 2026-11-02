<?php

namespace Modules\Operations\App\Http\Controllers\Admin;

use App\Exceptions\AdminActionException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Operations\App\Http\Requests\MarkPayoutPaidRequest;
use Modules\Operations\App\Http\Requests\RejectPayoutRequest;
use Modules\Operations\App\Queries\Admin\PayoutMonitoringQuery;
use Modules\Operations\App\Services\Admin\PayoutBalanceService;
use Modules\Operations\App\Services\Admin\PayoutService;
use Modules\Operations\Entities\Payout;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        return $this->run($payout, fn () => $this->service->markPaid($payout, $request->user(), $request->validatedEvidence()), 'Payout ditandai sudah dibayar.');
    }

    /**
     * Streams this payout's private evidence. Only the evidence_path stored on the
     * bound payout is read, so another payout's file cannot be requested.
     */
    public function downloadEvidence(Payout $payout): StreamedResponse
    {
        $this->authorize('downloadEvidence', $payout);
        abort_unless($payout->hasPrivateEvidence(), 404);

        $disk = Storage::disk((string) config('operations.evidence.disk'));
        $path = (string) $payout->evidence_path;
        abort_unless($disk->exists($path), 404);

        $mime = (string) $disk->mimeType($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return $disk->download($path, "bukti-payout-{$payout->getKey()}.{$extension}", [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
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
