<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentPlacement;
use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class GenerateListController extends Controller
{
    public function index(): View
    {
        return view('pages.admin.generate-list', [
            'studentCount' => Student::query()->count(),
            'facultyCount' => Faculty::query()->count(),
            'departmentCount' => Department::query()->count(),
            'faculties' => Faculty::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => Department::query()->where('is_active', true)->orderBy('name')->get(['id', 'faculty_id', 'name']),
            'sessions' => AcademicSession::query()->orderByDesc('starts_on')->get(['id', 'name']),
            'levels' => AcademicLevel::query()->where('is_active', true)->orderBy('level')->get(['id', 'name', 'level']),
        ]);
    }

    public function master(Request $request): Response
    {
        abort_unless($request->user()?->can('generate-list.export'), 403);
        $filters = $this->validatedFilters($request);

        $students = Student::query()
            ->with(['user', 'faculty', 'department', 'academicLevel', 'academicSession', 'placement.academicLevel', 'placement.academicSession'])
            ->when($filters['faculty_id'] ?? null, fn ($query, int $facultyId) => $query->where('faculty_id', $facultyId))
            ->when($filters['department_id'] ?? null, fn ($query, int $departmentId) => $query->where('department_id', $departmentId))
            ->when($filters['academic_session_id'] ?? null, fn ($query, int $sessionId) => $query->where('academic_session_id', $sessionId))
            ->when($filters['academic_level_id'] ?? null, fn ($query, int $levelId) => $query->where('academic_level_id', $levelId))
            ->orderBy(
                Faculty::query()
                    ->select('name')
                    ->whereColumn('faculties.id', 'students.faculty_id')
                    ->limit(1),
            )
            ->orderBy(
                Department::query()
                    ->select('name')
                    ->whereColumn('departments.id', 'students.department_id')
                    ->limit(1),
            )
            ->orderBy('matric_no')
            ->get();

        $year = (int) ($students->first(fn (Student $student): bool => $student->placement !== null)?->placement?->siwes_year ?? now()->year);
        $session = $this->sessionLabel($students->first()?->placement?->academicSession ?? $students->first()?->academicSession, $year);

        return $this->xlsResponse('exports.master-list', [
            'students' => $students,
            'year' => $year,
            'session' => $session,
        ], "MASTER LIST {$year}.xls");
    }

    public function placement(Request $request): Response
    {
        abort_unless($request->user()?->can('generate-list.export'), 403);
        $filters = $this->validatedFilters($request);

        $placements = StudentPlacement::query()
            ->with(['student.user', 'student.faculty', 'student.department', 'student.academicLevel', 'student.academicSession', 'academicLevel', 'academicSession'])
            ->whereHas('student')
            ->when($filters['faculty_id'] ?? null, fn ($query, int $facultyId) => $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('faculty_id', $facultyId)))
            ->when($filters['department_id'] ?? null, fn ($query, int $departmentId) => $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('department_id', $departmentId)))
            ->when($filters['academic_session_id'] ?? null, fn ($query, int $sessionId) => $query->where('academic_session_id', $sessionId))
            ->when($filters['academic_level_id'] ?? null, fn ($query, int $levelId) => $query->where('academic_level_id', $levelId))
            ->orderBy(
                Student::query()
                    ->select('faculty_id')
                    ->whereColumn('students.id', 'student_placements.student_id')
                    ->limit(1),
            )
            ->orderBy(
                Student::query()
                    ->select('department_id')
                    ->whereColumn('students.id', 'student_placements.student_id')
                    ->limit(1),
            )
            ->orderBy(
                Student::query()
                    ->select('matric_no')
                    ->whereColumn('students.id', 'student_placements.student_id')
                    ->limit(1),
            )
            ->get();

        $year = (int) ($placements->first()?->siwes_year ?? now()->year);
        $session = $this->sessionLabel($placements->first()?->academicSession, $year);

        return $this->xlsResponse('exports.placement-list', [
            'placements' => $placements,
            'year' => $year,
            'session' => $session,
        ], "PLACEMENT LIST {$year}.xls");
    }

    public function ticketFeePayments(Request $request): Response
    {
        abort_unless($request->user()?->can('generate-list.export'), 403);
        $filters = $this->validatedFilters($request);

        $onlinePayments = $this->paymentQuery($filters, Payment::PURPOSE_ACTIVATION_TICKET)
            ->with(['student.user', 'student.faculty', 'student.department'])
            ->get()
            ->map(fn (Payment $payment): array => $this->paymentRow(
                $payment->student,
                (int) $payment->amount,
                $payment->currency,
                $payment->provider === 'manual' ? 'Cash' : 'Online',
                $payment->paid_at ?? $payment->verified_at ?? $payment->created_at,
            ));

        $paidTicketIds = Payment::query()
            ->where('purpose', Payment::PURPOSE_ACTIVATION_TICKET)
            ->where('status', Payment::STATUS_SUCCESSFUL)
            ->whereNotNull('ticket_id')
            ->pluck('ticket_id')
            ->all();

        $cashTickets = Ticket::query()
            ->with(['student.user', 'student.faculty', 'student.department'])
            ->whereNotNull('student_id')
            ->when($paidTicketIds !== [], fn ($query) => $query->whereNotIn('id', $paidTicketIds))
            ->whereHas('student', fn ($studentQuery) => $this->applyStudentFilters($studentQuery, $filters))
            ->get()
            ->map(fn (Ticket $ticket): array => $this->paymentRow(
                $ticket->student,
                (int) $ticket->amount,
                $ticket->currency,
                'Cash',
                $ticket->assigned_at ?? $ticket->created_at,
            ));

        return $this->xlsResponse('exports.payment-list', [
            'title' => 'TICKET FEE PAYMENT LIST',
            'payments' => $onlinePayments->concat($cashTickets)
                ->sortBy(fn (array $row): string => $row['faculty'].'|'.$row['department'].'|'.$row['name'])
                ->values(),
        ], 'TICKET FEE PAYMENT LIST.xls');
    }

    public function workshopFeePayments(Request $request): Response
    {
        abort_unless($request->user()?->can('generate-list.export'), 403);
        $filters = $this->validatedFilters($request);

        $payments = $this->paymentQuery($filters, Payment::PURPOSE_WORKSHOP_FEE)
            ->with(['student.user', 'student.faculty', 'student.department'])
            ->get()
            ->map(fn (Payment $payment): array => $this->paymentRow(
                $payment->student,
                (int) $payment->amount,
                $payment->currency,
                $payment->provider === 'manual' ? 'Cash' : 'Online',
                $payment->paid_at ?? $payment->verified_at ?? $payment->created_at,
            ))
            ->sortBy(fn (array $row): string => $row['faculty'].'|'.$row['department'].'|'.$row['name'])
            ->values();

        return $this->xlsResponse('exports.payment-list', [
            'title' => 'WORKSHOP FEE PAYMENT LIST',
            'payments' => $payments,
        ], 'WORKSHOP FEE PAYMENT LIST.xls');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function xlsResponse(string $view, array $data, string $filename): Response
    {
        return response(view($view, $data)->render(), 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function sessionLabel(?AcademicSession $session, int $year): string
    {
        return $session?->name ?? ($year - 1).'/'.$year;
    }

    /**
     * @param  array{faculty_id?: int, department_id?: int, academic_session_id?: int, academic_level_id?: int}  $filters
     */
    private function paymentQuery(array $filters, string $purpose): \Illuminate\Database\Eloquent\Builder
    {
        return Payment::query()
            ->where('purpose', $purpose)
            ->where('status', Payment::STATUS_SUCCESSFUL)
            ->whereHas('student', fn ($studentQuery) => $this->applyStudentFilters($studentQuery, $filters))
            ->orderBy('created_at');
    }

    /**
     * @param  array{faculty_id?: int, department_id?: int, academic_session_id?: int, academic_level_id?: int}  $filters
     */
    private function applyStudentFilters(mixed $query, array $filters): mixed
    {
        return $query
            ->when($filters['faculty_id'] ?? null, fn ($studentQuery, int $facultyId) => $studentQuery->where('faculty_id', $facultyId))
            ->when($filters['department_id'] ?? null, fn ($studentQuery, int $departmentId) => $studentQuery->where('department_id', $departmentId))
            ->when($filters['academic_session_id'] ?? null, fn ($studentQuery, int $sessionId) => $studentQuery->where('academic_session_id', $sessionId))
            ->when($filters['academic_level_id'] ?? null, fn ($studentQuery, int $levelId) => $studentQuery->where('academic_level_id', $levelId));
    }

    /**
     * @return array{name: string, matric_no: string, department: string, faculty: string, amount: string, method: string, payment_date: string}
     */
    private function paymentRow(?Student $student, int $amount, string $currency, string $method, mixed $paymentDate): array
    {
        return [
            'name' => $student?->user?->name ?? 'N/A',
            'matric_no' => $student?->matric_no ?? 'N/A',
            'department' => $student?->department?->name ?? 'N/A',
            'faculty' => $student?->faculty?->name ?? 'N/A',
            'amount' => trim($currency.' '.number_format($amount)),
            'method' => $method,
            'payment_date' => $paymentDate ? $paymentDate->format('Y-m-d H:i:s') : 'N/A',
        ];
    }

    /**
     * @return array{faculty_id?: int, department_id?: int, academic_session_id?: int, academic_level_id?: int}
     */
    private function validatedFilters(Request $request): array
    {
        return $request->validate([
            'faculty_id' => ['nullable', 'integer', Rule::exists('faculties', 'id')->whereNull('deleted_at')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->whereNull('deleted_at')],
            'academic_session_id' => ['nullable', 'integer', Rule::exists('academic_sessions', 'id')->whereNull('deleted_at')],
            'academic_level_id' => ['nullable', 'integer', Rule::exists('academic_levels', 'id')->whereNull('deleted_at')],
        ]);
    }
}
