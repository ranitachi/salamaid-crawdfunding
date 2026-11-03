<?php

namespace Modules\Catalog\App\Http\Controllers\Admin;

use App\Exceptions\AdminActionException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Modules\Catalog\App\Enums\CampaignStatus;
use Modules\Catalog\App\Http\Requests\CampaignIndexRequest;
use Modules\Catalog\App\Http\Requests\RejectCampaignRequest;
use Modules\Catalog\App\Http\Requests\SuspendCampaignRequest;
use Modules\Catalog\App\Queries\CampaignModerationQuery;
use Modules\Catalog\App\Services\CampaignModerationService;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Category;
use Modules\Core\Entities\Organization;

class CampaignModerationController extends Controller
{
    public function __construct(
        private CampaignModerationQuery $query,
        private CampaignModerationService $service,
    ) {
    }

    public function index(CampaignIndexRequest $request): View
    {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 15);

        return view('admin.campaigns.index', [
            'campaigns' => $this->query->paginate($filters, $perPage, $request->user()),
            'statuses' => CampaignStatus::options(),
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'organizations' => Organization::query()->when($request->user()->isOrganizationAdmin(), fn ($query) => $query->whereIn('id', $request->user()->managedOrganizationIds()))->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Campaign::class);

        return view('admin.campaigns.create', [
            'categories' => Category::query()->active()->orderBy('name')->get(['id', 'name']),
            'organizations' => Organization::query()->whereIn('id', $request->user()->managedOrganizationIds())->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Campaign::class);
        $data = $request->validate([
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['required', 'string', 'max:190', 'alpha_dash', 'unique:campaigns,slug'],
            'description' => ['required', 'string', 'max:10000'],
            'target_amount' => ['required', 'integer', 'min:1'],
            'deadline_at' => ['nullable', 'date'],
            'location_label' => ['nullable', 'string', 'max:190'],
            'cover_url' => ['nullable', 'url', 'max:1000'],
        ]);
        abort_unless(in_array((string) $data['organization_id'], $request->user()->managedOrganizationIds(), true), 403);
        $data['status'] = 'draft';
        $campaign = Campaign::create($data);

        return to_route('admin.campaigns.show', $campaign)->with('success', 'Campaign berhasil dibuat sebagai draft.');
    }

    public function show(Campaign $campaign, Request $request): View
    {
        $this->authorize('view', $campaign);

        $campaign = $this->query->detail($campaign);
        $user = $request->user();

        return view('admin.campaigns.show', [
            'campaign' => $campaign,
            'canModerate' => Gate::forUser($user)->allows('moderate', $campaign),
            'canFeature' => Gate::forUser($user)->allows('feature', $campaign),
            'canDelete' => Gate::forUser($user)->allows('delete', $campaign) && ! $campaign->isPublished(),
            'canForceDelete' => Gate::forUser($user)->allows('forceDelete', $campaign)
                && $campaign->canBePermanentlyDeleted(),
        ]);
    }

    public function submit(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('moderate', $campaign);

        return $this->run(
            $campaign,
            fn () => $this->service->submitForReview($campaign, $request->user()),
            'Campaign dikirim ke antrean review.',
        );
    }

    public function publish(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('moderate', $campaign);

        return $this->run(
            $campaign,
            fn () => $this->service->publish($campaign, $request->user()),
            'Campaign berhasil dipublikasikan.',
        );
    }

    public function reject(RejectCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        return $this->run(
            $campaign,
            fn () => $this->service->reject($campaign, $request->user(), (string) $request->validated('reason')),
            'Campaign ditolak dan alasan sudah dicatat.',
        );
    }

    public function suspend(SuspendCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        return $this->run(
            $campaign,
            fn () => $this->service->suspend($campaign, $request->user(), (string) $request->validated('reason')),
            'Campaign berhasil ditangguhkan.',
        );
    }

    public function restore(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('moderate', $campaign);

        return $this->run(
            $campaign,
            fn () => $this->service->restore($campaign, $request->user()),
            'Status campaign berhasil dipulihkan.',
        );
    }

    public function close(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('moderate', $campaign);

        return $this->run(
            $campaign,
            fn () => $this->service->close($campaign, $request->user()),
            'Campaign berhasil ditutup.',
        );
    }

    public function feature(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('feature', $campaign);

        return $this->run(
            $campaign,
            fn () => $this->service->feature($campaign, $request->user()),
            'Campaign ditandai sebagai featured.',
        );
    }

    public function unfeature(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('feature', $campaign);

        return $this->run(
            $campaign,
            fn () => $this->service->unfeature($campaign, $request->user()),
            'Status featured campaign dilepas.',
        );
    }

    public function destroy(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('delete', $campaign);

        try {
            $this->service->delete($campaign, $request->user());
        } catch (AdminActionException $exception) {
            return $this->redirectToCampaign($campaign, $exception->getMessage(), false);
        }

        return redirect()
            ->route('admin.campaigns.index')
            ->with('success', 'Campaign dihapus dari daftar aktif.');
    }

    public function forceDestroy(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('forceDelete', $campaign);

        try {
            $this->service->forceDelete($campaign, $request->user());
        } catch (AdminActionException $exception) {
            return $this->redirectToCampaign($campaign, $exception->getMessage(), false);
        }

        return redirect()
            ->route('admin.campaigns.index')
            ->with('success', 'Campaign dihapus permanen.');
    }

    private function run(Campaign $campaign, callable $action, string $successMessage): RedirectResponse
    {
        try {
            $action();
        } catch (AdminActionException $exception) {
            return $this->redirectToCampaign($campaign, $exception->getMessage(), false);
        }

        return $this->redirectToCampaign($campaign->refresh(), $successMessage);
    }

    private function redirectToCampaign(Campaign $campaign, string $message, bool $success = true): RedirectResponse
    {
        return redirect()
            ->route('admin.campaigns.show', $campaign)
            ->with($success ? 'success' : 'error', $message);
    }
}
