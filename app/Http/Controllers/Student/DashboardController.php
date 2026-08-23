<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $student = $request->user()?->student;
        abort_unless($student instanceof Student, 403);

        return view('pages.student.dashboard', [
            'student' => $student->load([
                'user',
                'faculty',
                'department',
                'academicLevel',
                'academicSession',
                'placement.academicLevel',
                'placement.academicSession',
                'tickets',
                'payments',
                'activeSupervisorAssignment.supervisor.user',
            ]),
            'unreadNotifications' => $request->user()->unreadNotifications()->limit(5)->get(),
        ]);
    }
}
