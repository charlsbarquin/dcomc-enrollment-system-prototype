<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = ActivityLog::with('user')->latest();

        $action = trim((string) $request->get('action', ''));
        if ($action !== '') {
            $query->where('action', 'like', '%' . $action . '%');
        }

        $role = trim((string) $request->get('role', ''));
        if ($role !== '') {
            $query->where('role', $role);
        }

        $userId = trim((string) $request->get('user_id', ''));
        if ($userId !== '' && ctype_digit($userId)) {
            $query->where('user_id', (int) $userId);
        }

        $routeName = trim((string) $request->get('route', ''));
        if ($routeName !== '') {
            $query->where('route_name', 'like', '%' . $routeName . '%');
        }

        $items = $query->paginate(30)->withQueryString();

        return view('dashboards.admin-activity-logs', [
            'logs' => $items,
            'action' => $action,
            'role' => $role,
            'userId' => $userId,
            'routeName' => $routeName,
        ]);
    }
}
