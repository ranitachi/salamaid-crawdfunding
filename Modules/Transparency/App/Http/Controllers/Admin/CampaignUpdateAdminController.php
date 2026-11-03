<?php

namespace Modules\Transparency\App\Http\Controllers\Admin;

use App\Exceptions\AdminActionException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Modules\Catalog\Entities\Campaign;
use Modules\Operations\Entities\Payout;
use Modules\Transparency\App\Enums\CampaignUpdateStatus;
use Modules\Transparency\App\Enums\CampaignUpdateType;
use Modules\Transparency\App\Http\Requests\CampaignUpdateIndexRequest;
use Modules\Transparency\App\Http\Requests\CampaignUpdateReasonRequest;
use Modules\Transparency\App\Http\Requests\StoreCampaignUpdateAttachmentRequest;
use Modules\Transparency\App\Http\Requests\StoreCampaignUpdateRequest;
use Modules\Transparency\App\Queries\CampaignUpdateQuery;
use Modules\Transparency\App\Services\CampaignUpdateReviewService;
use Modules\Transparency\Entities\CampaignUpdate;
use Modules\Transparency\Entities\CampaignUpdateAttachment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CampaignUpdateAdminController extends Controller
{
    public function __construct(
        private CampaignUpdateQuery $query,
        private CampaignUpdateReviewService $service,
    ) {
    }

    public function index(CampaignUpdateIndexRequest $request): View
    {
        $filters = $request->validated();

        return view('admin.transparency.index', [
            'updates' => $this->query->paginate($filters, (int) ($filters['per_page'] ?? 15), $request->user()),
            'counts' => $this->query->statusCounts($request->user()),
            'statuses' => CampaignUpdateStatus::options(),
            'types' => CampaignUpdateType::options(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', CampaignUpdate::class);

        return view('admin.transparency.create', [
            'campaigns' => Campaign::query()->when($request->user()->isOrganizationAdmin(), fn ($q) => $q->whereIn('organization_id', $request->user()->managedOrganizationIds()))->orderBy('title')->get(['id', 'title', 'organization_id']),
            'payouts' => Payout::query()->where('status', 'paid')->when($request->user()->isOrganizationAdmin(), fn ($q) => $q->whereIn('organization_id', $request->user()->managedOrganizationIds()))->with('organization:id,name')
                ->latest('paid_at')->get(['id', 'organization_id', 'amount', 'paid_at']),
            'types' => CampaignUpdateType::options(),
            'selectedCampaign' => $request->query('campaign_id'),
        ]);
    }

    public function store(StoreCampaignUpdateRequest $request): RedirectResponse
    {
        try {
            $update = $this->service->create($request->validated(), $request->user());
        } catch (AdminActionException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.transparency.show', $update)
            ->with('success', 'Update tersimpan sebagai draft.');
    }

    public function show(CampaignUpdate $update): View
    {
        $this->authorize('view', $update);

        return view('admin.transparency.show', [
            'update' => $this->query->detail($update),
            'status' => $update->statusEnum(),
            'evidence' => config('transparency.evidence'),
        ]);
    }

    public function submit(Request $request, CampaignUpdate $update): RedirectResponse
    {
        $this->authorize('submit', $update);

        return $this->run($update, fn () => $this->service->submit($update, $request->user()), 'Update dikirim ke antrean review.');
    }

    public function approve(Request $request, CampaignUpdate $update): RedirectResponse
    {
        $this->authorize('review', $update);

        return $this->run($update, fn () => $this->service->approve($update, $request->user()), 'Update disetujui dan siap dipublikasikan.');
    }

    public function reject(CampaignUpdateReasonRequest $request, CampaignUpdate $update): RedirectResponse
    {
        return $this->run(
            $update,
            fn () => $this->service->reject($update, $request->user(), (string) $request->validated('reason')),
            'Revisi diminta dan alasan sudah dicatat.',
        );
    }

    public function publish(Request $request, CampaignUpdate $update): RedirectResponse
    {
        $this->authorize('publish', $update);

        return $this->run($update, fn () => $this->service->publish($update, $request->user()), 'Update dipublikasikan.');
    }

    public function unpublish(CampaignUpdateReasonRequest $request, CampaignUpdate $update): RedirectResponse
    {
        return $this->run(
            $update,
            fn () => $this->service->unpublish($update, $request->user(), (string) $request->validated('reason')),
            'Update ditarik dari publikasi.',
        );
    }

    public function storeAttachment(StoreCampaignUpdateAttachmentRequest $request, CampaignUpdate $update): RedirectResponse
    {
        return $this->run(
            $update,
            fn () => $this->service->attach($update, $request->file('file'), $request->user()),
            'Bukti berhasil diunggah.',
        );
    }

    public function downloadAttachment(CampaignUpdate $update, CampaignUpdateAttachment $attachment): StreamedResponse
    {
        $this->authorize('view', $update);

        $disk = Storage::disk($attachment->disk);
        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroyAttachment(Request $request, CampaignUpdate $update, CampaignUpdateAttachment $attachment): RedirectResponse
    {
        $this->authorize('manageAttachments', $update);

        return $this->run(
            $update,
            fn () => $this->service->removeAttachment($update, $attachment, $request->user()),
            'Bukti dihapus.',
        );
    }

    private function run(CampaignUpdate $update, callable $action, string $successMessage): RedirectResponse
    {
        try {
            $action();
        } catch (AdminActionException $exception) {
            return redirect()->route('admin.transparency.show', $update)->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.transparency.show', $update)->with('success', $successMessage);
    }
}
