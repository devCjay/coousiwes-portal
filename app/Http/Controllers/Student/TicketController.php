<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $student = $request->user()?->student;
        abort_unless($student instanceof Student, 403);

        return view('pages.student.tickets', [
            'student' => $student->load('tickets.placement'),
            'tickets' => $student->tickets()->latest()->get(),
        ]);
    }
}
