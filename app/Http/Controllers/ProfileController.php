<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $student = $request->user()->student;

        if ($student instanceof Student) {
            return redirect()->route($student->hasCompleteProfile() ? 'student.profile.show' : 'student.profile.edit');
        }

        return view('pages.profile.show', [
            'user' => $request->user()->loadMissing(['student', 'supervisor', 'roles']),
        ]);
    }
}
