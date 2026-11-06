<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuditHistoryIndexRequest;
use App\Models\AuditLog;
use App\Services\AuditHistoryService;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AuditHistoryController extends Controller
{
    public function index(AuditHistoryIndexRequest $request, AuditHistoryService $service): View
    {
        Gate::authorize('viewAny', AuditLog::class);

        return view('admin.audit-logs.index', $service->page($request->user(), $request->validated()));
    }
}
