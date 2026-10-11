<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\SiwesCoordinator;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $coordinator = $request->user()?->siwesCoordinator;
        abort_unless($coordinator instanceof SiwesCoordinator, 403);

        $filters = $this->filters($request);
        $students = null;

        if ($request->boolean('fetch')) {
            $students = $this->studentQuery($coordinator, $filters)
                ->latest()
                ->paginate(25)
                ->withQueryString();
        }

        return view('pages.coordinator.students', [
            'coordinator' => $coordinator->load(['department', 'faculty']),
            'students' => $students,
            'filters' => $filters,
            'levels' => AcademicLevel::query()->where('is_active', true)->orderBy('level')->get(),
            'sessions' => AcademicSession::query()->orderByDesc('starts_on')->get(),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $coordinator = $request->user()?->siwesCoordinator;
        abort_unless($coordinator instanceof SiwesCoordinator, 403);

        $students = $this->studentQuery($coordinator, $this->filters($request))
            ->orderBy('matric_no')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Department Students');
        $sheet->fromArray([
            'Student Name',
            'Reg No',
            'Faculty',
            'Department',
            'Level',
            'Session',
            'Phone',
            'Email',
            'Placement Company',
            'Placement State',
            'Placement LGA',
        ], null, 'A1');

        $students->values()->each(function (Student $student, int $index) use ($sheet): void {
            $placement = $student->placement;

            $sheet->fromArray([
                $student->user?->name ?? 'N/A',
                $student->matric_no,
                $student->faculty?->name ?? 'N/A',
                $student->department?->name ?? 'N/A',
                $student->academicLevel?->name ?? 'N/A',
                $student->academicSession?->name ?? 'N/A',
                $student->user?->phone ?: 'N/A',
                $student->user?->email ?: 'N/A',
                $placement?->company_name ?: 'N/A',
                $placement?->company_state ?: 'N/A',
                $placement?->company_lga ?: 'N/A',
            ], null, 'A'.($index + 2));
        });

        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $path = tempnam(sys_get_temp_dir(), 'coordinator-students-');
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, 'coordinator-department-students.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * @return array{academic_level_id: int|null, academic_session_id: int|null}
     */
    private function filters(Request $request): array
    {
        return [
            'academic_level_id' => $request->integer('academic_level_id') ?: null,
            'academic_session_id' => $request->integer('academic_session_id') ?: null,
        ];
    }

    /**
     * @param  array{academic_level_id: int|null, academic_session_id: int|null}  $filters
     */
    private function studentQuery(SiwesCoordinator $coordinator, array $filters)
    {
        return Student::query()
            ->with(['user', 'faculty', 'department', 'academicLevel', 'academicSession', 'placement'])
            ->where('department_id', $coordinator->department_id)
            ->when($filters['academic_level_id'], fn ($query, int $levelId) => $query->where('academic_level_id', $levelId))
            ->when($filters['academic_session_id'], fn ($query, int $sessionId) => $query->where('academic_session_id', $sessionId));
    }
}
