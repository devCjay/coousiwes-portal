<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user()?->student;
        abort_unless($student !== null, 403);

        return view('pages.student.feedback', [
            'student' => $student->load(['user', 'department', 'placement.academicLevel']),
            'assessments' => $student->assessments()
                ->with(['supervisor.user', 'scores.rubricItem'])
                ->latest('submitted_at')
                ->get(),
        ]);
    }
}
