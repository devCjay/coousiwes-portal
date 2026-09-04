<?php

namespace App\Services;

use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentImport;
use App\Models\User;
use App\Support\PaymentSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StudentImportService
{
    public const array HEADERS = [
        'surname',
        'first_name',
        'other_name',
        'reg_no',
    ];

    public function __construct(
        private readonly StudentManager $studentManager,
        private readonly TicketService $ticketService,
    ) {}

    /**
     * @return array{rows: array<int, array<string, mixed>>, errors: array<int, array<string, mixed>>, total: int}
     */
    public function preview(UploadedFile $file): array
    {
        $rows = $this->parse($file->getRealPath() ?: $file->path(), $file->getClientOriginalExtension());

        return $this->validateRows($rows);
    }

    public function createImport(UploadedFile $file, ?int $uploadedBy, bool $autoActivate = false, bool $markWorkshopFeePaid = false): StudentImport
    {
        $storedPath = $file->store('student-imports');
        $preview = $this->preview($file);

        return StudentImport::query()->create([
            'uploaded_by' => $uploadedBy,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'status' => StudentImport::STATUS_PREVIEWED,
            'total_rows' => $preview['total'],
            'preview_rows' => array_slice($preview['rows'], 0, 10),
            'error_report' => $preview['errors'],
            'failed_rows' => count($preview['errors']),
            'auto_activate_students' => $autoActivate,
            'mark_workshop_fee_paid' => $markWorkshopFeePaid,
        ]);
    }

    public function process(StudentImport $import): StudentImport
    {
        $import->update([
            'status' => StudentImport::STATUS_PROCESSING,
            'started_at' => now(),
        ]);

        $path = storage_path('app/private/'.$import->stored_path);
        if (! file_exists($path)) {
            $path = storage_path('app/'.$import->stored_path);
        }

        $extension = pathinfo($import->original_filename, PATHINFO_EXTENSION);
        $preview = $this->validateRows($this->parse($path, $extension));
        $created = 0;
        $errors = $preview['errors'];

        foreach ($preview['rows'] as $index => $row) {
            if (isset($errors[$index])) {
                continue;
            }

            try {
                $student = $this->studentManager->create($this->resolveRow($row, true, $import->auto_activate_students));
                $this->generateTicketWhenAutoActivated($student, $import->auto_activate_students);
                $this->markWorkshopFeePaid($student, $import->mark_workshop_fee_paid);
                $created++;
            } catch (\Throwable $throwable) {
                $errors[$index] = [
                    'row' => $index + 2,
                    'messages' => [$throwable->getMessage()],
                ];
            }
        }

        $import->update([
            'status' => StudentImport::STATUS_COMPLETED,
            'processed_rows' => $preview['total'],
            'successful_rows' => $created,
            'failed_rows' => count($errors),
            'error_report' => $errors,
            'finished_at' => now(),
        ]);

        return $import->refresh();
    }

    public function processChunk(StudentImport $import, int $limit): StudentImport
    {
        $limit = max(500, min($limit, 2000));

        $import->update([
            'status' => StudentImport::STATUS_PROCESSING,
            'started_at' => $import->started_at ?: now(),
        ]);

        $path = storage_path('app/private/'.$import->stored_path);
        if (! file_exists($path)) {
            $path = storage_path('app/'.$import->stored_path);
        }

        $extension = pathinfo($import->original_filename, PATHINFO_EXTENSION);
        $rows = $this->parse($path, $extension);
        $start = (int) $import->processed_rows;
        $chunk = array_slice($rows, $start, $limit);
        $preview = $this->validateRows($chunk, $start);
        $created = 0;
        $errors = $import->error_report ?? [];

        foreach ($preview['rows'] as $index => $row) {
            $globalIndex = $start + $index;

            if (isset($preview['errors'][$globalIndex]) || isset($errors[$globalIndex])) {
                continue;
            }

            try {
                $student = $this->studentManager->create($this->resolveRow($row, true, $import->auto_activate_students));
                $this->generateTicketWhenAutoActivated($student, $import->auto_activate_students);
                $this->markWorkshopFeePaid($student, $import->mark_workshop_fee_paid);
                $created++;
            } catch (\Throwable $throwable) {
                $errors[$globalIndex] = [
                    'row' => $globalIndex + 2,
                    'messages' => [$throwable->getMessage()],
                ];
            }
        }

        $errors = array_replace($errors, $preview['errors']);
        $processedRows = min($start + count($chunk), count($rows));

        $import->update([
            'status' => $processedRows >= count($rows) ? StudentImport::STATUS_COMPLETED : StudentImport::STATUS_QUEUED,
            'total_rows' => count($rows),
            'processed_rows' => $processedRows,
            'successful_rows' => (int) $import->successful_rows + $created,
            'failed_rows' => count($errors),
            'error_report' => $errors,
            'finished_at' => $processedRows >= count($rows) ? now() : null,
        ]);

        return $import->refresh();
    }

    /**
     * @return array{processed_imports: int, processed_rows: int, completed_imports: int, remaining_imports: int}
     */
    public function processQueued(int $limit): array
    {
        $limit = max(500, min($limit, 2000));
        $import = StudentImport::query()
            ->whereIn('status', [StudentImport::STATUS_QUEUED, StudentImport::STATUS_PROCESSING])
            ->oldest()
            ->first();

        if ($import) {
            $before = (int) $import->processed_rows;
            $processed = $this->processChunk($import, $limit);
            $processedRows = max(0, (int) $processed->processed_rows - $before);
        } else {
            $processed = null;
            $processedRows = 0;
        }

        return [
            'processed_imports' => $processed ? 1 : 0,
            'processed_rows' => $processedRows,
            'completed_imports' => $processed?->status === StudentImport::STATUS_COMPLETED ? 1 : 0,
            'remaining_imports' => StudentImport::query()->whereIn('status', [StudentImport::STATUS_QUEUED, StudentImport::STATUS_PROCESSING])->count(),
        ];
    }

    public function template(string $format): string
    {
        $sample = [
            self::HEADERS,
        ];

        if ($format === 'xlsx') {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray($sample);

            foreach (range('A', 'D') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);
            ob_start();
            $writer->save('php://output');

            return (string) ob_get_clean();
        }

        $handle = fopen('php://temp', 'w+');
        foreach ($sample as $row) {
            fputcsv($handle, $row, ',', '"', '\\');
        }
        rewind($handle);

        return (string) stream_get_contents($handle);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parse(string $path, string $extension): array
    {
        if (strtolower($extension) === 'xlsx') {
            $sheet = IOFactory::load($path)->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, true);
            $headers = array_map(fn (mixed $value): string => $this->normalizeHeader((string) $value), array_values(array_shift($rows) ?? []));

            return array_values(array_filter(array_map(fn (array $row): array => $this->combine($headers, array_values($row)), $rows)));
        }

        $handle = fopen($path, 'r');
        $headers = array_map(fn (string $value): string => $this->normalizeHeader($value), fgetcsv($handle) ?: []);
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $this->combine($headers, $row);
        }
        fclose($handle);

        return array_values(array_filter($rows));
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, mixed>  $row
     * @return array<string, mixed>
     */
    private function combine(array $headers, array $row): array
    {
        $combined = [];
        foreach ($headers as $index => $header) {
            if ($header !== '') {
                $combined[$header] = is_string($row[$index] ?? null) ? trim($row[$index]) : $row[$index] ?? null;
            }
        }

        return array_filter($combined, fn (mixed $value): bool => $value !== null && $value !== '');
    }

    private function normalizeHeader(string $header): string
    {
        $normalized = strtolower(trim($header));
        $normalized = str_replace([' ', '-', '.'], '_', $normalized);

        return match ($normalized) {
            'matric', 'matric_number', 'matric_no', 'matriculation_number', 'reg_no', 'reg_number', 'registration_number' => 'matric_no',
            'surname', 'last', 'last_name' => 'last_name',
            'firstname', 'first' => 'first_name',
            'other_name', 'other_names', 'middlename', 'middle', 'middle_name' => 'middle_name',
            'faculty_name', 'faculty_code' => 'faculty',
            'department_name', 'department_code', 'course', 'course_name', 'course_code' => 'department',
            'session' => 'academic_session',
            default => $normalized,
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{rows: array<int, array<string, mixed>>, errors: array<int, array<string, mixed>>, total: int}
     */
    private function validateRows(array $rows, int $rowOffset = 0): array
    {
        $errors = [];
        $emails = [];
        $matricNumbers = [];

        foreach ($rows as $index => $row) {
            $validator = Validator::make($row, [
                'first_name' => ['required_without:name', 'string', 'max:80'],
                'middle_name' => ['nullable', 'string', 'max:80'],
                'last_name' => ['nullable', 'string', 'max:80'],
                'name' => ['nullable', 'string', 'max:160'],
                'email' => ['nullable', 'email', 'max:160'],
                'phone' => ['nullable', 'string', 'max:40'],
                'matric_no' => ['required', 'string', 'max:40'],
                'faculty' => ['nullable', 'string'],
                'department' => ['nullable', 'string'],
                'level' => ['nullable', 'integer'],
                'academic_session' => ['nullable', 'string'],
                'activation_status' => ['nullable', 'in:inactive,active,suspended'],
            ]);

            $messages = $validator->errors()->all();

            $email = strtolower((string) ($row['email'] ?? ''));
            if ($email !== '' && in_array($email, $emails, true)) {
                $messages[] = 'Duplicate email inside import file.';
            }
            if (in_array(strtolower((string) ($row['matric_no'] ?? '')), $matricNumbers, true)) {
                $messages[] = 'Duplicate reg no inside import file.';
            }
            if ($email !== '' && User::query()->where('email', $row['email'] ?? null)->exists()) {
                $messages[] = 'Email already exists.';
            }
            if (Student::query()->where('matric_no', $row['matric_no'] ?? null)->exists()) {
                $messages[] = 'Matric number already exists.';
            }

            $resolved = $this->resolveRow($row, false);
            if ($resolved === null) {
                $messages[] = 'Academic mapping could not be resolved.';
            }

            if ($email !== '') {
                $emails[] = $email;
            }
            $matricNumbers[] = strtolower((string) ($row['matric_no'] ?? ''));

            if ($messages !== []) {
                $errors[$rowOffset + $index] = ['row' => $rowOffset + $index + 2, 'messages' => $messages];
            }
        }

        return ['rows' => $rows, 'errors' => $errors, 'total' => count($rows)];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    private function resolveRow(array $row, bool $throw = true, ?bool $autoActivate = null): ?array
    {
        $facultyValue = $row['faculty'] ?? null;
        $departmentValue = $row['department'] ?? null;
        $faculty = filled($facultyValue)
            ? Faculty::query()
                ->where('code', $facultyValue)
                ->orWhere('name', $facultyValue)
                ->first()
            : null;
        $department = filled($departmentValue) && $faculty
            ? Department::query()
                ->where('faculty_id', $faculty->id)
                ->where(function ($query) use ($departmentValue): void {
                    $query->where('code', $departmentValue)->orWhere('name', $departmentValue);
                })
                ->first()
            : null;
        $level = filled($row['level'] ?? null) ? AcademicLevel::query()->where('level', $row['level'])->first() : null;
        $session = filled($row['academic_session'] ?? null) ? AcademicSession::query()->where('name', $row['academic_session'])->first() : null;

        if (
            (filled($facultyValue) && ! $faculty)
            || (filled($departmentValue) && ! $department)
            || (filled($row['level'] ?? null) && ! $level)
            || (filled($row['academic_session'] ?? null) && ! $session)
        ) {
            if ($throw) {
                throw new \RuntimeException('Academic mapping could not be resolved.');
            }

            return null;
        }

        $activationStatus = match ($autoActivate) {
            true => Student::STATUS_ACTIVE,
            false => Student::STATUS_INACTIVE,
            default => $row['activation_status'] ?? Student::STATUS_INACTIVE,
        };

        return [
            'name' => $row['name'] ?? collect([$row['last_name'] ?? null, $row['first_name'] ?? null, $row['middle_name'] ?? null])
                ->filter(fn (mixed $value): bool => filled($value))
                ->implode(' '),
            'email' => $row['email'] ?? null,
            'phone' => $row['phone'] ?? null,
            'matric_no' => $row['matric_no'],
            'faculty_id' => $faculty?->id,
            'department_id' => $department?->id,
            'academic_level_id' => $level?->id,
            'academic_session_id' => $session?->id,
            'activation_status' => $activationStatus,
        ];
    }

    private function generateTicketWhenAutoActivated(Student $student, bool $autoActivate): void
    {
        if (! $autoActivate) {
            return;
        }

        $this->ticketService->generateFor($student);
    }

    private function markWorkshopFeePaid(Student $student, bool $markPaid): void
    {
        if (! $markPaid) {
            return;
        }

        $student->payments()->firstOrCreate(
            [
                'purpose' => Payment::PURPOSE_WORKSHOP_FEE,
                'status' => Payment::STATUS_SUCCESSFUL,
            ],
            [
                'provider' => 'manual',
                'reference' => 'WORKSHOP-MANUAL-'.$student->matric_no,
                'amount' => max(0, PaymentSettings::workshopAmount()),
                'currency' => PaymentSettings::currency(),
                'provider_status' => 'manual_verified',
                'verified_at' => now(),
                'paid_at' => now(),
            ]
        );
    }
}
