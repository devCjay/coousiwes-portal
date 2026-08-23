<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentPlacement;
use App\Models\Supervisor;
use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $totalStudents = Student::query()->count();
        $activatedStudents = Student::query()
            ->where('activation_status', Student::STATUS_ACTIVE)
            ->count();
        $totalSupervisors = Supervisor::query()->count();
        $activeSupervisors = Supervisor::query()
            ->where('status', Supervisor::STATUS_ACTIVE)
            ->count();
        $openTickets = Ticket::query()
            ->whereIn('status', Ticket::unusedStatuses())
            ->count();
        $urgentTickets = Ticket::query()
            ->whereIn('status', Ticket::unusedStatuses())
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now()->addDays(3))
            ->count();
        $verifiedPayments = Payment::query()
            ->where('status', Payment::STATUS_SUCCESSFUL)
            ->count();
        $pendingActivation = Student::query()
            ->doesntHave('placement')
            ->count();
        $submittedForms = StudentPlacement::query()->count();
        $feedbackReceived = Assessment::query()
            ->whereNotNull('feedback')
            ->where('feedback', '!=', '')
            ->count();
        $ticketCount = Ticket::query()->count();
        $unusedTickets = Ticket::query()
            ->whereIn('status', Ticket::unusedStatuses())
            ->count();
        $unusedTicketPercentage = $ticketCount > 0 ? (int) round(($unusedTickets / $ticketCount) * 100) : 0;
        $usedTickets = Ticket::query()
            ->whereIn('status', Ticket::usedStatuses())
            ->count();
        $ticketChartTotal = max($usedTickets + $unusedTickets, 1);

        $ticketDistribution = collect([
            [
                'label' => 'Used',
                'count' => $usedTickets,
                'color' => '#129645',
                'class' => 'bg-brand-600',
            ],
            [
                'label' => 'Unused',
                'count' => $unusedTickets,
                'color' => '#22d3ee',
                'class' => 'bg-cyan-400',
            ],
        ]);

        $facultyDistribution = DB::table('students')
            ->join('faculties', 'faculties.id', '=', 'students.faculty_id')
            ->whereNull('students.deleted_at')
            ->whereNull('faculties.deleted_at')
            ->groupBy('faculties.id', 'faculties.name', 'faculties.code')
            ->orderByDesc('students_count')
            ->limit(10)
            ->get([
                'faculties.name',
                'faculties.code',
                DB::raw('count(students.id) as students_count'),
            ]);
        $maxFacultyStudents = max((int) $facultyDistribution->max('students_count'), 1);

        $genderCounts = Student::query()
            ->selectRaw('lower(trim(coalesce(gender, ""))) as gender_key, count(*) as students_count')
            ->groupBy('gender_key')
            ->pluck('students_count', 'gender_key');
        $maleStudents = (int) ($genderCounts['male'] ?? $genderCounts['m'] ?? 0);
        $femaleStudents = (int) ($genderCounts['female'] ?? $genderCounts['f'] ?? 0);
        $otherStudents = max($totalStudents - $maleStudents - $femaleStudents, 0);
        $genderChartTotal = max($maleStudents + $femaleStudents + $otherStudents, 1);

        $genderDistribution = collect([
            [
                'label' => 'Male',
                'count' => $maleStudents,
                'color' => '#0ea5e9',
                'class' => 'bg-sky-500',
            ],
            [
                'label' => 'Female',
                'count' => $femaleStudents,
                'color' => '#ec4899',
                'class' => 'bg-pink-500',
            ],
            [
                'label' => 'Not specified',
                'count' => $otherStudents,
                'color' => '#94a3b8',
                'class' => 'bg-slate-400',
            ],
        ]);

        $recentStudents = Student::query()
            ->with(['user', 'faculty', 'department', 'academicSession'])
            ->latest()
            ->limit(8)
            ->get();

        return view('pages.admin.dashboard', [
            'stats' => [
                'totalStudents' => $totalStudents,
                'activatedStudents' => $activatedStudents,
                'totalSupervisors' => $totalSupervisors,
                'activeSupervisors' => $activeSupervisors,
                'openTickets' => $openTickets,
                'urgentTickets' => $urgentTickets,
                'verifiedPayments' => $verifiedPayments,
                'unusedTicketPercentage' => $unusedTicketPercentage,
                'pendingActivation' => $pendingActivation,
                'submittedForms' => $submittedForms,
                'feedbackReceived' => $feedbackReceived,
            ],
            'facultyDistribution' => $facultyDistribution,
            'maxFacultyStudents' => $maxFacultyStudents,
            'ticketDistribution' => $ticketDistribution,
            'ticketChartTotal' => $ticketChartTotal,
            'genderDistribution' => $genderDistribution,
            'genderChartTotal' => $genderChartTotal,
            'recentStudents' => $recentStudents,
        ]);
    }
}
