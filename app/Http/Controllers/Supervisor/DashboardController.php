<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $supervisor = $request->user()?->supervisor;
        abort_unless($supervisor instanceof Supervisor, 403);

        $assignments = $supervisor->activeAssignments()
            ->with(['student.user', 'student.department', 'student.academicLevel'])
            ->latest('assigned_at')
            ->get();

        return view('pages.supervisor.dashboard', [
            'supervisor' => $supervisor->load('user'),
            'assignments' => $assignments,
            'unreadNotifications' => $request->user()->unreadNotifications()->limit(5)->get(),
        ]);
    }
}
