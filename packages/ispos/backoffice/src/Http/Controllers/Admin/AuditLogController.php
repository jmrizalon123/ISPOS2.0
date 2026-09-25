<?php

namespace Ispos\Backoffice\Http\Controllers\Admin;

use Ispos\Backoffice\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('audit.view');

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->toString();
                $q->where(function ($query) use ($search) {
                    $query->where('action', 'like', "%{$search}%")
                        ->orWhere('module', 'like', "%{$search}%")
                        ->orWhere('record_type', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->string('module')))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'module' => $request->string('module')->toString(),
            ],
            'modules' => AuditLog::query()->distinct()->orderBy('module')->pluck('module'),
        ]);
    }
}
