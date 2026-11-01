<?php

namespace App\Queries\Admin;

use App\Models\AdminAuditLog;
use Illuminate\Http\Request;

class AuditLogQuery
{
    public function paginate(Request $request, int $perPage = 20)
    {
        return AdminAuditLog::query()
            ->with('actor:id,name,email,role')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = trim((string) $request->string('q'));
                $query->where(function ($query) use ($term) {
                    $query->where('action', 'like', "%{$term}%")
                        ->orWhere('subject_type', 'like', "%{$term}%")
                        ->orWhere('subject_id', $term)
                        ->orWhere('actor_email', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->string('action')))
            ->when($request->filled('actor_id'), fn ($query) => $query->where('actor_id', $request->string('actor_id')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}
