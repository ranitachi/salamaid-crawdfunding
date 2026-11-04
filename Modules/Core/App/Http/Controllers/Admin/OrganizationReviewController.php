<?php

namespace Modules\Core\App\Http\Controllers\Admin;

use App\Exceptions\AdminActionException;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Modules\Core\App\Enums\OrganizationStatus;
use Modules\Core\App\Http\Requests\OrganizationIndexRequest;
use Modules\Core\App\Http\Requests\RejectOrganizationRequest;
use Modules\Core\App\Http\Requests\SuspendOrganizationRequest;
use Modules\Core\App\Queries\OrganizationReviewQuery;
use Modules\Core\App\Services\OrganizationReviewService;
use Modules\Core\Entities\Organization;

class OrganizationReviewController extends Controller
{
    public function __construct(
        private OrganizationReviewQuery $query,
        private OrganizationReviewService $service,
    ) {
    }

    public function index(OrganizationIndexRequest $request): View
    {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 15);

        return view('admin.organizations.index', [
            'organizations' => $this->query->paginate($filters, $perPage),
            'statuses' => OrganizationStatus::options(),
            'filters' => $filters,
        ]);
    }

    public function show(Organization $organization, Request $request): View
    {
        $this->authorize('view', $organization);

        $organization = $this->query->detail($organization);

        return view('admin.organizations.show', [
            'organization' => $organization,
            'canViewSensitive' => Gate::forUser($request->user())->allows('viewSensitive', $organization),
            'canReview' => Gate::forUser($request->user())->allows('review', $organization),
        ]);
    }

    public function verificationDocument(Organization $organization, Request $request): StreamedResponse|RedirectResponse
    {
        $this->authorize('viewSensitive', $organization);

        $path = (string) $organization->verification_document_path;

        if ($path === '' || ! Storage::disk('private')->exists($path)) {
            return back()->with('error', 'Dokumen verifikasi tidak tersedia.');
        }

        $extension = (string) Str::of(pathinfo($path, PATHINFO_EXTENSION))->lower()->match('/^[a-z0-9]{1,10}$/');
        $filename = 'dokumen-verifikasi-'.Str::slug($organization->name).($extension !== '' ? '.'.$extension : '');

        return Storage::disk('private')->download($path, $filename, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function approve(Organization $organization, Request $request): RedirectResponse
    {
        $this->authorize('review', $organization);

        try {
            $this->service->approve($organization, $request->user());
        } catch (AdminActionException $exception) {
            return $this->redirectToOrganization($organization, $exception->getMessage(), false);
        }

        return $this->redirectToOrganization($organization, 'Organisasi berhasil diverifikasi.');
    }

    public function reject(RejectOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        try {
            $this->service->reject($organization, $request->user(), (string) $request->validated('reason'));
        } catch (AdminActionException $exception) {
            return $this->redirectToOrganization($organization, $exception->getMessage(), false);
        }

        return $this->redirectToOrganization($organization, 'Organisasi ditolak dan alasan sudah dicatat.');
    }

    public function suspend(SuspendOrganizationRequest $request, Organization $organization): RedirectResponse
    {
        try {
            $this->service->suspend($organization, $request->user(), (string) $request->validated('reason'));
        } catch (AdminActionException $exception) {
            return $this->redirectToOrganization($organization, $exception->getMessage(), false);
        }

        return $this->redirectToOrganization($organization, 'Organisasi berhasil ditangguhkan.');
    }

    public function restore(Organization $organization, Request $request): RedirectResponse
    {
        $this->authorize('review', $organization);

        try {
            $this->service->restore($organization, $request->user());
        } catch (AdminActionException $exception) {
            return $this->redirectToOrganization($organization, $exception->getMessage(), false);
        }

        return $this->redirectToOrganization($organization, 'Status organisasi berhasil dipulihkan.');
    }

    private function redirectToOrganization(Organization $organization, string $message, bool $success = true): RedirectResponse
    {
        return redirect()
            ->route('admin.organizations.show', $organization)
            ->with($success ? 'success' : 'error', $message);
    }
}
