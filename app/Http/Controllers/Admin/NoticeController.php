<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NoticeController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(): View
    {
        return view('pages.admin.notices', [
            'notices' => Notice::query()->latest()->get(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $notice = Notice::query()->create($this->payload($request) + [
            'created_by' => $request->user() instanceof User ? $request->user()->id : null,
        ]);

        $this->auditLogger->record('notices.created', $request->user(), $request, $notice, $notice->only(['title', 'audience', 'tone', 'published_at']));

        return AjaxResponse::success($request, 'Notice published.', route('admin.notices.index'));
    }

    public function update(Request $request, Notice $notice): JsonResponse|RedirectResponse
    {
        $before = $notice->only(['title', 'body', 'audience', 'tone', 'published_at', 'expires_at', 'is_pinned']);

        $notice->update($this->payload($request));

        $this->auditLogger->record('notices.updated', $request->user(), $request, $notice, [
            'before' => $before,
            'after' => $notice->only(['title', 'body', 'audience', 'tone', 'published_at', 'expires_at', 'is_pinned']),
        ]);

        return AjaxResponse::success($request, 'Notice updated.', route('admin.notices.index'));
    }

    /**
     * @return array{title: string, body: string, audience: string, tone: string, published_at: string|null, expires_at: string|null, is_pinned: bool}
     */
    private function payload(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:1400'],
            'audience' => ['required', 'string', Rule::in(['all', 'students', 'supervisors'])],
            'tone' => ['required', 'string', Rule::in(['info', 'success', 'warning', 'danger'])],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
            'is_pinned' => ['sometimes', 'boolean'],
        ]);

        $validated['published_at'] = $validated['published_at'] ?? now()->toDateTimeString();
        $validated['expires_at'] = $validated['expires_at'] ?? null;
        $validated['is_pinned'] = $request->boolean('is_pinned');

        return $validated;
    }
}
