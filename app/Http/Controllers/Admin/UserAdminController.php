<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\AdminActionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActivateUserRequest;
use App\Http\Requests\Admin\DeactivateUserRequest;
use App\Http\Requests\Admin\SendUserPasswordResetRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Http\Requests\Admin\UserIndexRequest;
use App\Models\User;
use App\Queries\Admin\UserManagementQuery;
use App\Services\Admin\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserAdminController extends Controller
{
    public function __construct(
        private UserManagementQuery $query,
        private UserManagementService $service,
    ) {
    }

    public function index(UserIndexRequest $request): View
    {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 15);

        return view('admin.users.index', [
            'users' => $this->query->paginate($filters, $perPage),
            'roles' => (array) config('admin.roles', []),
            'filters' => $filters,
        ]);
    }

    public function show(User $user, Request $request): View
    {
        $this->authorize('view', $user);

        return view('admin.users.show', [
            'user' => $user,
            'memberships' => $this->query->memberships($user),
            'roles' => (array) config('admin.roles', []),
            'isSelf' => $request->user()->is($user),
        ]);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        return $this->run(
            $user,
            fn () => $this->service->updateRole($user, (string) $request->validated('role'), $request->user()),
            'Role pengguna berhasil diperbarui.',
        );
    }

    public function activate(ActivateUserRequest $request, User $user): RedirectResponse
    {
        return $this->run(
            $user,
            fn () => $this->service->activate($user, $request->user()),
            'Pengguna berhasil diaktifkan.',
        );
    }

    public function deactivate(DeactivateUserRequest $request, User $user): RedirectResponse
    {
        return $this->run(
            $user,
            fn () => $this->service->deactivate($user, $request->user()),
            'Pengguna berhasil dinonaktifkan.',
        );
    }

    public function sendPasswordReset(SendUserPasswordResetRequest $request, User $user): RedirectResponse
    {
        return $this->run(
            $user,
            fn () => $this->service->sendPasswordResetLink($user, $request->user()),
            'Tautan reset password telah dikirim ke email pengguna.',
        );
    }

    private function run(User $user, callable $action, string $successMessage): RedirectResponse
    {
        try {
            $action();
        } catch (AdminActionException $exception) {
            return redirect()
                ->route('admin.users.show', $user)
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', $successMessage);
    }
}
