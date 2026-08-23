<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentRubricItem;
use App\Models\Student;
use App\Models\Supervisor;
use App\Models\SupervisorStudentAssignment;
use Illuminate\Support\Facades\DB;

class AssessmentService
{
    /**
     * @param  array<int|string, int>  $scores
     */
    public function submit(Supervisor $supervisor, Student $student, array $scores, string $feedback): Assessment
    {
        return DB::transaction(function () use ($supervisor, $student, $scores, $feedback): Assessment {
            $assignment = SupervisorStudentAssignment::query()
                ->where('supervisor_id', $supervisor->id)
                ->where('student_id', $student->id)
                ->whereNull('revoked_at')
                ->first();

            if (! $assignment) {
                throw new \RuntimeException('Student is not actively assigned to this supervisor.');
            }

            if ($assignment->assessment()->exists()) {
                throw new \RuntimeException('Assessment has already been submitted for this assignment.');
            }

            $rubricItems = AssessmentRubricItem::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
            $totalScore = 0;
            $maxScore = 0;

            foreach ($rubricItems as $item) {
                $score = (int) ($scores[$item->id] ?? -1);
                if ($score < 0 || $score > $item->max_score) {
                    throw new \RuntimeException("Invalid score for {$item->name}.");
                }

                $totalScore += $score * $item->weight;
                $maxScore += $item->max_score * $item->weight;
            }

            $assessment = Assessment::query()->create([
                'supervisor_id' => $supervisor->id,
                'student_id' => $student->id,
                'supervisor_student_assignment_id' => $assignment->id,
                'total_score' => $totalScore,
                'max_score' => $maxScore,
                'status' => Assessment::STATUS_SUBMITTED,
                'feedback' => $feedback,
                'submitted_at' => now(),
            ]);

            foreach ($rubricItems as $item) {
                $assessment->scores()->create([
                    'assessment_rubric_item_id' => $item->id,
                    'score' => (int) $scores[$item->id],
                    'max_score' => $item->max_score,
                ]);
            }

            return $assessment->refresh();
        });
    }
}
