<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentImport extends Model
{
    public const string STATUS_PREVIEWED = 'previewed';

    public const string STATUS_QUEUED = 'queued';

    public const string STATUS_PROCESSING = 'processing';

    public const string STATUS_COMPLETED = 'completed';

    public const string STATUS_FAILED = 'failed';

    protected $fillable = [
        'uploaded_by',
        'original_filename',
        'stored_path',
        'status',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'auto_activate_students',
        'preview_rows',
        'error_report',
        'started_at',
        'finished_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'preview_rows' => 'array',
            'error_report' => 'array',
            'auto_activate_students' => 'boolean',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
