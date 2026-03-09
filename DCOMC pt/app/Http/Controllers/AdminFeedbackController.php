<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminFeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $query = Feedback::with('user')->latest();

        $priorityFilter = $request->string('priority')->toString();
        if ($priorityFilter !== '') {
            if ($priorityFilter === 'high') {
                $query->whereIn('priority', [4, 5]);
            } elseif ($priorityFilter === 'medium') {
                $query->where('priority', 3);
            } elseif ($priorityFilter === 'low') {
                $query->whereIn('priority', [1, 2]);
            }
        }

        $roleFilter = $request->string('role')->toString();
        if ($roleFilter !== '') {
            $query->where('role', $roleFilter);
        }

        $items = $query->paginate(20)->withQueryString();

        return view('dashboards.admin-feedback-index', [
            'feedback' => $items,
            'priorityFilter' => $priorityFilter,
            'roleFilter' => $roleFilter,
        ]);
    }

    public function destroy(Feedback $feedback)
    {
        AuditLogger::log('feedback.delete', $feedback, [
            'subject' => $feedback->subject ?? null,
            'priority' => $feedback->priority ?? null,
            'sender_role' => $feedback->role ?? null,
            'sender_user_id' => $feedback->user_id ?? null,
        ]);
        $feedback->delete();

        return redirect()
            ->route('admin.feedback.index')
            ->with('success', 'Feedback deleted.');
    }
}
