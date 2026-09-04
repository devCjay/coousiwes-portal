<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('feedback.view'), 403);

        return view('pages.admin.reports', $this->reportData());
    }

    public function export(Request $request): Response
    {
        abort_unless($request->user()?->can('feedback.view'), 403);

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, ['Student', 'Reg No', 'Supervisor', 'Score', 'Max Score', 'Percent', 'Submitted']);

        Assessment::query()
            ->with(['student.user', 'supervisor.user'])
            ->latest('submitted_at')
            ->each(function (Assessment $assessment) use ($handle): void {
                $percent = $assessment->max_score > 0 ? round(($assessment->total_score / $assessment->max_score) * 100, 2) : 0;
                fputcsv($handle, [
                    $assessment->student->user->name,
                    $assessment->student->matric_no,
                    $assessment->supervisor->user->name,
                    $assessment->total_score,
                    $assessment->max_score,
                    $percent,
                    $assessment->submitted_at?->toDateTimeString() ?? '',
                ]);
            });

        rewind($handle);

        return response((string) stream_get_contents($handle), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=assessment-report.csv',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportData(): array
    {
        $assessments = Assessment::query()->with(['student.faculty', 'student.user', 'supervisor.user'])->get();
        $averageScore = $assessments->isNotEmpty()
            ? round($assessments->avg(fn (Assessment $assessment): float => $assessment->max_score > 0 ? ($assessment->total_score / $assessment->max_score) * 100 : 0), 1)
            : 0;

        return [
            'assessmentCount' => $assessments->count(),
            'averageScore' => $averageScore,
            'studentCount' => Student::query()->count(),
            'paymentCount' => Payment::query()->count(),
            'supervisorPerformance' => $this->supervisorPerformance(),
            'completionByFaculty' => $this->completionByFaculty(),
            'scoreDistribution' => $this->scoreDistribution($assessments),
            'paymentTrends' => Payment::query()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status'),
            'activationTrends' => Student::query()->selectRaw('activation_status, count(*) as aggregate')->groupBy('activation_status')->pluck('aggregate', 'activation_status'),
            'recentAssessments' => Assessment::query()->with(['student.user', 'supervisor.user'])->latest('submitted_at')->limit(12)->get(),
        ];
    }

    /**
     * @return Collection<int, array{name: string, assessments: int<0, max>, average: 0|float}>
     */
    private function supervisorPerformance(): Collection
    {
        return Supervisor::query()
            ->with('user')
            ->withCount('assessments')
            ->with('assessments')
            ->orderByDesc('assessments_count')
            ->limit(10)
            ->get()
            ->map(fn (Supervisor $supervisor): array => [
                'name' => $supervisor->user->name,
                'assessments' => $supervisor->assessments_count,
                'average' => $supervisor->assessments->isNotEmpty()
                    ? round($supervisor->assessments->map(fn ($assessment): float => $assessment instanceof Assessment && $assessment->max_score > 0 ? ($assessment->total_score / $assessment->max_score) * 100 : 0)->avg(), 1)
                    : 0,
            ]);
    }

    /**
     * @return Collection<int, array{faculty: string, students: int<0, max>, assessed: int<0, max>}>
     */
    private function completionByFaculty(): Collection
    {
        return Student::query()
            ->with(['faculty', 'assessments'])
            ->get()
            ->groupBy('faculty.name')
            ->map(fn (Collection $students, string $faculty): array => [
                'faculty' => $faculty,
                'students' => $students->count(),
                'assessed' => $students->filter(fn (Student $student): bool => $student->assessments->isNotEmpty())->count(),
            ])
            ->values();
    }

    /**
     * @param  Collection<int, Assessment>  $assessments
     * @return Collection<int, array{range: string, count: int}>
     */
    private function scoreDistribution(Collection $assessments): Collection
    {
        $buckets = collect([
            '0-39%' => 0,
            '40-59%' => 0,
            '60-79%' => 0,
            '80-100%' => 0,
        ]);

        $assessments->each(function (Assessment $assessment) use ($buckets): void {
            $percent = $assessment->max_score > 0 ? ($assessment->total_score / $assessment->max_score) * 100 : 0;
            $key = match (true) {
                $percent < 40 => '0-39%',
                $percent < 60 => '40-59%',
                $percent < 80 => '60-79%',
                default => '80-100%',
            };
            $buckets[$key] = (int) $buckets[$key] + 1;
        });

        return $buckets->map(fn (int $count, string $range): array => ['range' => $range, 'count' => $count])->values();
    }
}
