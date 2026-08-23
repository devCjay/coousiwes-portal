<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssignedStudentController extends Controller
{
    public function index(Request $request): View
    {
        $supervisor = $request->user()?->supervisor;
        abort_unless($supervisor instanceof Supervisor, 403);

        return view('pages.supervisor.students', [
            'supervisor' => $supervisor->load('user'),
            'assignments' => $supervisor->activeAssignments()->with(['student.user', 'student.department', 'student.academicLevel'])->latest('assigned_at')->get(),
        ]);
    }
}
