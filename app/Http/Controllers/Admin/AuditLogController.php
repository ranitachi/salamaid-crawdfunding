<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Queries\Admin\AuditLogQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogQuery $query)
    {
    }

    public function index(Request $request): View
    {
        return view('admin.audit-logs.index', ['logs' => $this->query->paginate($request)]);
    }
}
