<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('payments.view'), 403);

        $payments = Payment::query()
            ->with(['student.user', 'ticket'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($inner) use ($search): void {
                    $inner->where('reference', 'like', "%{$search}%")
                        ->orWhere('provider_status', 'like', "%{$search}%")
                        ->orWhereHas('student', fn ($studentQuery) => $studentQuery->where('matric_no', 'like', "%{$search}%"))
                        ->orWhereHas('student.user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('pages.admin.payments', [
            'payments' => $payments,
            'paymentTotal' => Payment::query()->count(),
            'verifiedTotal' => Payment::query()->where('status', Payment::STATUS_SUCCESSFUL)->count(),
        ]);
    }
}
