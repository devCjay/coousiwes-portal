<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\ConfirmPlacementTicketRequest;
use App\Http\Requests\Student\StorePlacementStepRequest;
use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\Student;
use App\Models\StudentPlacement;
use App\Models\Ticket;
use App\Services\AuditLogger;
use App\Services\TicketService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PlacementController extends Controller
{
    public function ticket(Request $request): View
    {
        $student = $request->user()?->student;
        abort_unless($student instanceof Student, 403);

        return view('pages.student.placement-ticket', [
            'student' => $student,
            'onlinePaymentAvailable' => $this->onlinePaymentAvailable(),
        ]);
    }

    public function confirmTicket(ConfirmPlacementTicketRequest $request, TicketService $ticketService, AuditLogger $auditLogger): JsonResponse|RedirectResponse
    {
        $student = $request->user()?->student;
        abort_unless($student instanceof Student, 403);

        $pin = $request->string('pin')->trim()->toString();
        $serialNumber = str($request->string('serial_number')->trim()->toString())->upper()->toString();

        $ticket = Ticket::query()
            ->where('serial_number', $serialNumber)
            ->first();

        if (! $ticket || ! $this->ticketPinIsValid($ticket, $pin)) {
            return AjaxResponse::error($request, 'Invalid ticket serial number or pin.', key: 'ticket');
        }

        if ($ticket->placement()->exists()) {
            return AjaxResponse::error($request, 'This ticket has already been used for a placement.', key: 'ticket');
        }

        if (! $ticket->isPayable()) {
            return AjaxResponse::error($request, 'This ticket is no longer available for placement access.', key: 'ticket');
        }

        try {
            $ticket = $ticketService->assignToStudent($ticket, $student);
        } catch (\RuntimeException $exception) {
            return AjaxResponse::error($request, $exception->getMessage(), key: 'ticket');
        }

        $request->session()->put('placement.ticket_id', $ticket->id);

        $auditLogger->record('placements.ticket_confirmed', $request->user(), $request, $ticket, [
            'student_id' => $student->id,
        ]);

        return AjaxResponse::success($request, 'Ticket confirmed. Continue your placement setup.', route('student.placements.create', absolute: false));
    }

    public function payOnline(Request $request): JsonResponse|RedirectResponse
    {
        if (! $this->onlinePaymentAvailable()) {
            return AjaxResponse::error($request, 'sorry online payment is currently not available', key: 'payment');
        }

        return AjaxResponse::success($request, 'Online payment is available. Continue to Korapay checkout.', route('student.payments.index', absolute: false));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $student = $request->user()?->student;
        abort_unless($student instanceof Student, 403);

        if (! $request->session()->has('placement.ticket_id') && ! $student->placement) {
            return redirect()->route('student.placements.ticket');
        }

        $student->load(['placement', 'academicLevel', 'academicSession']);
        $metadata = $student->placement?->metadata ?? [];
        $selectedState = $student->placement?->company_state ?? '';
        $stateRecord = collect(config('siwes_profile.states', []))->firstWhere('name', $selectedState);

        return view('pages.student.placement-create', [
            'student' => $student,
            'placement' => $student->placement,
            'levels' => AcademicLevel::query()->where('is_active', true)->orderBy('level')->get(['id', 'name', 'level']),
            'sessions' => AcademicSession::query()->orderByDesc('starts_on')->get(['id', 'name', 'starts_on', 'ends_on', 'is_active']),
            'states' => config('siwes_profile.states', []),
            'stateRecord' => $stateRecord,
            'metadata' => $metadata,
        ]);
    }

    public function storeStep(StorePlacementStepRequest $request, TicketService $ticketService, AuditLogger $auditLogger): JsonResponse|RedirectResponse
    {
        $student = $request->user()?->student;
        abort_unless($student instanceof Student, 403);

        $ticketId = $request->session()->get('placement.ticket_id') ?? $student->placement?->ticket_id;
        if (! $ticketId) {
            return AjaxResponse::error($request, 'Confirm your ticket before adding placement details.', key: 'ticket');
        }

        $validated = $request->validated();
        $placement = $student->placement()->firstOrNew(['student_id' => $student->id]);

        if ($validated['step'] === 'siwes') {
            $placement->fill([
                'ticket_id' => $ticketId,
                'academic_level_id' => $validated['academic_level_id'],
                'academic_session_id' => $validated['academic_session_id'],
                'siwes_year' => $validated['siwes_year'],
                'attachment_period' => $validated['attachment_period'],
                'metadata' => array_merge($placement->metadata ?? [], ['activation_source' => 'ticket']),
            ])->save();

            $student->update(['academic_level_id' => $validated['academic_level_id']]);
        }

        if ($validated['step'] === 'company') {
            $placement->fill([
                'ticket_id' => $ticketId,
                'company_name' => $validated['company_name'],
                'company_address' => $validated['company_address'],
                'company_state' => $validated['company_state'],
                'company_lga' => $validated['company_lga'],
                'company_supervisor_phone' => $validated['company_supervisor_phone'],
                'metadata' => array_merge($placement->metadata ?? [], ['activation_source' => 'ticket']),
            ])->save();

            $ticket = Ticket::query()->find($ticketId);
            if ($ticket) {
                try {
                    $ticketService->markUsedByStudent($ticket, $student);
                } catch (\RuntimeException $exception) {
                    return AjaxResponse::error($request, $exception->getMessage(), key: 'ticket');
                }
            }
            $request->session()->forget('placement.ticket_id');
        }

        $placement->refresh();
        $completion = $this->completion($placement);

        $auditLogger->record('placements.step_saved', $request->user(), $request, $placement, [
            'step' => $validated['step'],
            'completion' => $completion,
        ]);

        return AjaxResponse::success(
            $request,
            $completion >= 100 ? 'Placement completed. Your SIWES details have been saved.' : 'Placement step saved successfully.',
            $completion >= 100 ? route('student.placements.complete', absolute: false) : null,
            reload: false,
            data: [
                'step' => $validated['step'],
                'completion' => $completion,
            ],
        );
    }

    public function complete(Request $request): View|RedirectResponse
    {
        $student = $request->user()?->student;
        abort_unless($student instanceof Student, 403);

        if (! $student->placement) {
            return redirect()->route('student.placements.ticket');
        }

        return view('pages.student.placement-complete', [
            'student' => $student->load(['placement.academicLevel', 'placement.academicSession']),
        ]);
    }

    private function completion(?StudentPlacement $placement): int
    {
        if (! $placement) {
            return 0;
        }

        $fields = collect([
            $placement->academic_level_id,
            $placement->academic_session_id,
            $placement->siwes_year,
            $placement->attachment_period,
            $placement->company_name,
            $placement->company_address,
            $placement->company_state,
            $placement->company_lga,
            $placement->company_supervisor_phone,
        ]);

        return (int) round(($fields->filter(fn (mixed $value): bool => filled($value))->count() / $fields->count()) * 100);
    }

    private function onlinePaymentAvailable(): bool
    {
        return (string) config('siwes.payments.provider') === 'korapay'
            && filled(config('siwes.korapay.secret_key'))
            && filled(config('siwes.korapay.base_url'))
            && Ticket::query()
                ->whereNull('student_id')
                ->whereIn('status', Ticket::unusedStatuses())
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->exists();
    }

    private function ticketPinIsValid(Ticket $ticket, string $pin): bool
    {
        if ($ticket->code_hash && Hash::check($pin, (string) $ticket->code_hash)) {
            return true;
        }

        return filled($ticket->pin) && hash_equals((string) $ticket->pin, $pin);
    }
}
