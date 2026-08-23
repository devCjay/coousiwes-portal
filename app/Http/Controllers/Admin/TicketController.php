<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GenerateTicketsRequest;
use App\Models\Student;
use App\Models\Ticket;
use App\Services\AuditLogger;
use App\Services\TicketService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('tickets.view'), 403);

        $tickets = Ticket::query()
            ->with(['student.user', 'student.department', 'student.payments'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($inner) use ($search): void {
                    $inner->where('serial_number', 'like', "%{$search}%")
                        ->orWhere('id', is_numeric($search) ? (int) $search : 0)
                        ->orWhereHas('student', fn ($studentQuery) => $studentQuery->where('matric_no', 'like', "%{$search}%"))
                        ->orWhereHas('student.user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), function ($query) use ($request): void {
                $statuses = $request->string('status')->toString() === Ticket::STATUS_USED
                    ? Ticket::usedStatuses()
                    : Ticket::unusedStatuses();

                $query->whereIn('status', $statuses);
            })
            ->when($request->boolean('not_printed'), fn ($query) => $query->whereNull('metadata->printed_at'))
            ->when($request->filled('activated_by'), function ($query) use ($request): void {
                $student = $request->string('activated_by')->toString();
                $query->whereIn('status', Ticket::usedStatuses())
                    ->whereHas('student.user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$student}%"));
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('pages.admin.tickets', [
            'tickets' => $tickets,
            'ticketTotal' => Ticket::query()->count(),
            'availableTickets' => Ticket::query()
                ->whereNull('student_id')
                ->whereIn('status', Ticket::unusedStatuses())
                ->count(),
        ]);
    }

    public function store(GenerateTicketsRequest $request): JsonResponse|RedirectResponse
    {
        $count = 0;
        if ($request->filled('quantity')) {
            $count = $this->ticketService->generateMany($request->integer('quantity'), $request->user());

            return AjaxResponse::success($request, "{$count} ticket(s) generated.", reload: true);
        }

        Student::query()->whereIn('id', $request->array('student_ids'))->each(function (Student $student) use ($request, &$count): void {
            $ticket = $this->ticketService->generateFor($student, $request->user());
            $this->auditLogger->record('tickets.generated', $request->user(), $request, $ticket, [
                'student_id' => $student->id,
                'amount' => $ticket->amount,
            ]);
            $count++;
        });

        return AjaxResponse::success($request, "{$count} ticket(s) generated.", reload: true);
    }

    public function revoke(Request $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        abort_unless($request->user()?->can('tickets.revoke'), 403);

        $ticket->update([
            'student_id' => null,
            'status' => Ticket::STATUS_UNUSED,
            'assigned_at' => null,
        ]);
        $this->auditLogger->record('tickets.revoked', $request->user(), $request, $ticket);

        return AjaxResponse::success($request, 'Ticket returned to unused stock.');
    }
}
