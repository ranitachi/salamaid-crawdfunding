<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DashboardFilterRequest;
use App\Queries\Admin\DashboardQuery;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardQuery $query)
    {
    }

    public function __invoke(DashboardFilterRequest $request): View
    {
        return view('admin.dashboard', $this->query->data($request->validated()));
    }
}
