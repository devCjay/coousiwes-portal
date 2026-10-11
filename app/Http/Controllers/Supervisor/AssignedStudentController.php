<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\AssessmentRubricItem;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AssignedStudentController extends Controller
{
    public function index(Request $request): View
    {
        $supervisor = $request->user()?->supervisor;
        abort_unless($supervisor instanceof Supervisor, 403);

        return view('pages.supervisor.students', [
            'supervisor' => $supervisor->load('user'),
            'assignments' => $supervisor->activeAssignments()
                ->with([
                    'student.user',
                    'student.faculty',
                    'student.department',
                    'student.academicLevel',
                    'student.academicSession',
                    'student.placement.academicLevel',
                    'student.placement.academicSession',
                    'assessment.scores.rubricItem',
                ])
                ->latest('assigned_at')
                ->paginate(20)
                ->withQueryString(),
            'rubricItems' => AssessmentRubricItem::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $supervisor = $request->user()?->supervisor;
        abort_unless($supervisor instanceof Supervisor, 403);

        $assignments = $supervisor->activeAssignments()
            ->with([
                'student.user',
                'student.department',
                'student.placement',
                'assessment',
            ])
            ->latest('assigned_at')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Assigned Students');
        $sheet->fromArray([
            'Student Name',
            'Reg No',
            'Department',
            'Placement Address',
            'LGA',
            'State',
            'Logbook Score',
        ], null, 'A1');

        $assignments->values()->each(function ($assignment, int $index) use ($sheet): void {
            $student = $assignment->student;
            $placement = $student?->placement;
            $assessment = $assignment->assessment;

            $sheet->fromArray([
                $student?->user?->name ?? 'N/A',
                $student?->matric_no ?? 'N/A',
                $student?->department?->name ?? 'N/A',
                $placement?->company_address ?: 'N/A',
                $placement?->company_lga ?: 'N/A',
                $placement?->company_state ?: 'N/A',
                '',
            ], null, 'A'.($index + 2));
        });

        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $path = tempnam(sys_get_temp_dir(), 'assigned-students-');
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, 'assigned-students-list.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }
}
