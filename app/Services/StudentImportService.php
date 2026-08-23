<?php

namespace App\Services;

use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Student;
use App\Models\StudentImport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StudentImportService
{
    public const array HEADERS = [
        'name',
        'email',
        'phone',
        'matric_no',
        'faculty_code',
        'department_code',
        'level',
        'academic_session',
        'activation_status',
    ];

    public function __construct(private readonly StudentManager $studentManager) {}

    /**
     * @return array{rows: array<int, array<string, mixed>>, errors: array<int, array<string, mixed>>, total: int}
     */
    public function preview(UploadedFile $file): array
    {
        $rows = $this->parse($file->getRealPath() ?: $file->path(), $file->getClientOriginalExtension());

        return $this->validateRows($rows);
    }

    public function createImport(UploadedFile $file, int $uploadedBy): StudentImport
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
                $this->studentManager->create($this->resolveRow($row));
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

    public function template(string $format): string
    {
        $sample = [
            self::HEADERS,
            ['Ada Okoye', 'ada.okoye@example.test', '08030000000', '2026/CSC/001', 'AGRIC', 'AGE', '300', '2026/2027', 'inactive'],
        ];

        if ($format === 'xlsx') {
            $spreadsheet = new Spreadsheet;
            $spreadsheet->getActiveSheet()->fromArray($sample);
            $writer = new Xlsx($spreadsheet);
            ob_start();
            $writer->save('php://output');

            return (string) ob_get_clean();
        }

        $handle = fopen('php://temp', 'w+');
        foreach ($sample as $row) {
            fputcsv($handle, $row);
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
            $headers = array_map(fn (mixed $value): string => strtolower(trim((string) $value)), array_values(array_shift($rows) ?? []));

            return array_values(array_filter(array_map(fn (array $row): array => $this->combine($headers, array_values($row)), $rows)));
        }

        $handle = fopen($path, 'r');
        $headers = array_map(fn (string $value): string => strtolower(trim($value)), fgetcsv($handle) ?: []);
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

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{rows: array<int, array<string, mixed>>, errors: array<int, array<string, mixed>>, total: int}
     */
    private function validateRows(array $rows): array
    {
        $errors = [];
        $emails = [];
        $matricNumbers = [];

        foreach ($rows as $index => $row) {
            $validator = Validator::make($row, [
                'name' => ['required', 'string', 'max:160'],
                'email' => ['required', 'email', 'max:160'],
                'matric_no' => ['required', 'string', 'max:40'],
                'faculty_code' => ['required', 'string'],
                'department_code' => ['required', 'string'],
                'level' => ['required', 'integer'],
                'academic_session' => ['required', 'string'],
                'activation_status' => ['nullable', 'in:inactive,active,suspended'],
            ]);

            $messages = $validator->errors()->all();

            if (in_array(strtolower((string) ($row['email'] ?? '')), $emails, true)) {
                $messages[] = 'Duplicate email inside import file.';
            }
            if (in_array(strtolower((string) ($row['matric_no'] ?? '')), $matricNumbers, true)) {
                $messages[] = 'Duplicate matric number inside import file.';
            }
            if (User::query()->where('email', $row['email'] ?? null)->exists()) {
                $messages[] = 'Email already exists.';
            }
            if (Student::query()->where('matric_no', $row['matric_no'] ?? null)->exists()) {
                $messages[] = 'Matric number already exists.';
            }

            $resolved = $this->resolveRow($row, false);
            if ($resolved === null) {
                $messages[] = 'Academic mapping could not be resolved.';
            }

            $emails[] = strtolower((string) ($row['email'] ?? ''));
            $matricNumbers[] = strtolower((string) ($row['matric_no'] ?? ''));

            if ($messages !== []) {
                $errors[$index] = ['row' => $index + 2, 'messages' => $messages];
            }
        }

        return ['rows' => $rows, 'errors' => $errors, 'total' => count($rows)];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>|null
     */
    private function resolveRow(array $row, bool $throw = true): ?array
    {
        $faculty = Faculty::query()->where('code', $row['faculty_code'] ?? null)->first();
        $department = Department::query()->where('faculty_id', $faculty?->id)->where('code', $row['department_code'] ?? null)->first();
        $level = AcademicLevel::query()->where('level', $row['level'] ?? null)->first();
        $session = AcademicSession::query()->where('name', $row['academic_session'] ?? null)->first();

        if (! $faculty || ! $department || ! $level || ! $session) {
            if ($throw) {
                throw new \RuntimeException('Academic mapping could not be resolved.');
            }

            return null;
        }

        return [
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'] ?? null,
            'matric_no' => $row['matric_no'],
            'faculty_id' => $faculty->id,
            'department_id' => $department->id,
            'academic_level_id' => $level->id,
            'academic_session_id' => $session->id,
            'activation_status' => $row['activation_status'] ?? Student::STATUS_INACTIVE,
        ];
    }
}
