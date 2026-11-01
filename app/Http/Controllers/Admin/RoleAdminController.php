<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\AdminActionException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\RoleManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleAdminController extends Controller
{
    public function index(Request $request, RoleManagementService $service): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.roles.index', [
            'roles' => $service->usage(),
        ]);
    }

    public function destroy(Request $request, string $role, RoleManagementService $service): RedirectResponse
    {
        $this->authorize('viewAny', User::class);

        try {
            $service->delete($role, $request->user());
        } catch (AdminActionException $exception) {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role berhasil dihapus.');
    }
}
