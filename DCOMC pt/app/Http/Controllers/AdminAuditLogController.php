<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::with('actor')->latest();

        $action = trim((string) $request->get('action', ''));
        if ($action !== '') {
            $query->where('action', 'like', '%' . $action . '%');
        }

        $actorRole = trim((string) $request->get('role', ''));
        if ($actorRole !== '') {
            $query->where('actor_role', $actorRole);
        }

        $items = $query->paginate(25)->withQueryString();

        return view('dashboards.admin-audit-logs', [
            'logs' => $items,
            'action' => $action,
            'actorRole' => $actorRole,
        ]);
    }
}
