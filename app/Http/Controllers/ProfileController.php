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
        $user = $request->user();
        $student = method_exists($user, 'student') ? $user->student : null;

        if ($student instanceof Student) {
            return redirect()->route($student->hasCompleteProfile() ? 'student.profile.show' : 'student.profile.edit');
        }

        $relations = ['roles'];
        if (method_exists($user, 'student')) {
            $relations[] = 'student';
        }
        if (method_exists($user, 'supervisor')) {
            $relations[] = 'supervisor';
        }

        return view('pages.profile.show', [
            'user' => $user->loadMissing($relations),
        ]);
    }
}
