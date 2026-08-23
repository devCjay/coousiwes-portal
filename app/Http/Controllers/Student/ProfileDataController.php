<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileDataController extends Controller
{
    public function nationalities(): JsonResponse
    {
        return response()->json([
            'nationalities' => config('siwes_profile.nationalities', []),
        ]);
    }

    public function states(): JsonResponse
    {
        return response()->json([
            'states' => config('siwes_profile.states', []),
        ]);
    }

    public function lgas(Request $request): JsonResponse
    {
        $state = (string) $request->query('state');
        $record = collect(config('siwes_profile.states', []))->firstWhere('name', $state);

        return response()->json([
            'lgas' => $record['lgas'] ?? [],
        ]);
    }

    public function banks(): JsonResponse
    {
        return response()->json([
            'banks' => config('siwes_profile.banks', []),
        ]);
    }

    public function faculties(): JsonResponse
    {
        return response()->json([
            'faculties' => Faculty::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
        ]);
    }

    public function departments(Request $request): JsonResponse
    {
        return response()->json([
            'departments' => Department::query()
                ->where('is_active', true)
                ->when($request->filled('faculty_id'), fn ($query) => $query->where('faculty_id', $request->integer('faculty_id')))
                ->orderBy('name')
                ->get(['id', 'faculty_id', 'name', 'code']),
        ]);
    }
}
