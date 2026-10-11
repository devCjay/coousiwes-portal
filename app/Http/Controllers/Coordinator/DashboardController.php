<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\SiwesCoordinator;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $coordinator = $request->user()?->siwesCoordinator;
        abort_unless($coordinator instanceof SiwesCoordinator, 403);

        $departmentId = $coordinator->department_id;

        return view('pages.coordinator.dashboard', [
            'coordinator' => $coordinator->load(['user', 'faculty', 'department']),
            'summary' => [
                'students' => Student::query()->where('department_id', $departmentId)->count(),
                'placed_students' => Student::query()->where('department_id', $departmentId)->whereHas('placement')->count(),
                'submitted_assessments' => Assessment::query()->whereHas('student', fn ($query) => $query->where('department_id', $departmentId))->count(),
                'notifications' => $request->user()->unreadNotifications()->count(),
            ],
            'notifications' => $request->user()->unreadNotifications()->limit(8)->get(),
        ]);
    }
}
