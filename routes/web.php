<?php

use App\Http\Controllers\Admin\AcademicStructureController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AppSettingController;
use App\Http\Controllers\Admin\AssessmentRubricController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ControlCenterController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GenerateListController;
use App\Http\Controllers\Admin\NoticeController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudentImportController;
use App\Http\Controllers\Admin\SupervisorAssignmentController;
use App\Http\Controllers\Admin\SupervisorController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\AccountPasswordController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\OtpChallengeController;
use App\Http\Controllers\Cron\StudentImportCronController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationCenterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\FeedbackController;
use App\Http\Controllers\Student\PaymentController;
use App\Http\Controllers\Student\PlacementController;
use App\Http\Controllers\Student\ProfileDataController as StudentProfileDataController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Student\TicketController as StudentTicketController;
use App\Http\Controllers\Supervisor\AssessmentController;
use App\Http\Controllers\Supervisor\AssignedStudentController;
use App\Http\Controllers\Supervisor\DashboardController as SupervisorDashboardController;
use App\Http\Controllers\Webhooks\KorapayWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/cron/student-imports/process', StudentImportCronController::class)
    ->middleware('throttle:cron')
    ->name('cron.student-imports.process');

Route::post('/webhooks/korapay', KorapayWebhookController::class)->middleware('throttle:webhooks')->name('webhooks.korapay');

Route::middleware('guest:web,admin')->group(function () {
    Route::redirect('/login', '/login/student')->name('login');
    Route::get('/login/admin', [AuthenticatedSessionController::class, 'create'])
        ->defaults('role', 'admin')
        ->name('login.admin');
    Route::get('/login/supervisor', [AuthenticatedSessionController::class, 'create'])
        ->defaults('role', 'supervisor')
        ->name('login.supervisor');
    Route::get('/login/student', [AuthenticatedSessionController::class, 'create'])
        ->defaults('role', 'student')
        ->name('login.student');

    Route::post('/login/{role}', [AuthenticatedSessionController::class, 'store'])
        ->whereIn('role', ['admin', 'supervisor', 'student'])
        ->middleware('throttle:login')
        ->name('login.store');
});

Route::middleware('auth:web,admin')->group(function () {
    Route::get('/otp/verify', [OtpChallengeController::class, 'show'])
        ->middleware('otp.unverified')
        ->name('otp.show');
    Route::post('/otp/verify', [OtpChallengeController::class, 'verify'])
        ->middleware(['otp.unverified', 'throttle:otp'])
        ->name('otp.verify');
    Route::post('/otp/resend', [OtpChallengeController::class, 'resend'])
        ->middleware(['otp.unverified', 'throttle:otp'])
        ->name('otp.resend');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/notifications', [NotificationCenterController::class, 'index'])
        ->middleware('otp.verified')
        ->name('notifications.index');
    Route::get('/notifications/summary', [NotificationCenterController::class, 'summary'])
        ->middleware(['otp.verified', 'throttle:notifications'])
        ->name('notifications.summary');
    Route::post('/notifications/read-all', [NotificationCenterController::class, 'markAllRead'])
        ->middleware(['otp.verified', 'throttle:notifications'])
        ->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationCenterController::class, 'markRead'])
        ->middleware(['otp.verified', 'throttle:notifications'])
        ->name('notifications.read');

    Route::get('/profile', [ProfileController::class, 'show'])
        ->middleware('otp.verified')
        ->name('profile.show');
    Route::get('/profile/photo/{user}', ProfilePhotoController::class)
        ->middleware('otp.verified')
        ->name('profile.photo');
    Route::get('/account/password', [AccountPasswordController::class, 'edit'])
        ->middleware('otp.verified')
        ->name('account.password.edit');
    Route::put('/account/password', [AccountPasswordController::class, 'update'])
        ->middleware(['otp.verified', 'throttle:10,1'])
        ->name('account.password.update');

    Route::get('/admin/dashboard', AdminDashboardController::class)
        ->middleware(['otp.verified', 'role.portal:super-admin,admin'])
        ->name('admin.dashboard');

    Route::middleware(['otp.verified', 'role.portal:super-admin,admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/generate-list', [GenerateListController::class, 'index'])
            ->middleware('permission:generate-list.view')
            ->name('generate-list.index');
        Route::get('/generate-list/master', [GenerateListController::class, 'master'])
            ->middleware(['permission:generate-list.export', 'throttle:exports'])
            ->name('generate-list.master');
        Route::get('/generate-list/placement', [GenerateListController::class, 'placement'])
            ->middleware(['permission:generate-list.export', 'throttle:exports'])
            ->name('generate-list.placement');
        Route::get('/generate-list/payments', [GenerateListController::class, 'payments'])
            ->middleware(['permission:generate-list.export', 'throttle:exports'])
            ->name('generate-list.payments');
        Route::get('/generate-list/ticket-fee-payments', [GenerateListController::class, 'ticketFeePayments'])
            ->middleware(['permission:generate-list.export', 'throttle:exports'])
            ->name('generate-list.ticket-fee-payments');
        Route::get('/generate-list/workshop-fee-payments', [GenerateListController::class, 'workshopFeePayments'])
            ->middleware(['permission:generate-list.export', 'throttle:exports'])
            ->name('generate-list.workshop-fee-payments');

        Route::get('/academics', [AcademicStructureController::class, 'index'])
            ->middleware('permission:academics.manage')
            ->name('academics.index');
        Route::post('/academics/faculties', [AcademicStructureController::class, 'storeFaculty'])
            ->middleware('permission:academics.manage')
            ->name('academics.faculties.store');
        Route::put('/academics/faculties/{faculty}', [AcademicStructureController::class, 'updateFaculty'])
            ->middleware('permission:academics.manage')
            ->name('academics.faculties.update');
        Route::delete('/academics/faculties/{faculty}', [AcademicStructureController::class, 'destroyFaculty'])
            ->middleware('permission:academics.manage')
            ->name('academics.faculties.destroy');
        Route::post('/academics/faculties/{faculty}/restore', [AcademicStructureController::class, 'restoreFaculty'])
            ->middleware('permission:academics.manage')
            ->name('academics.faculties.restore');

        Route::post('/academics/departments', [AcademicStructureController::class, 'storeDepartment'])
            ->middleware('permission:academics.manage')
            ->name('academics.departments.store');
        Route::put('/academics/departments/{department}', [AcademicStructureController::class, 'updateDepartment'])
            ->middleware('permission:academics.manage')
            ->name('academics.departments.update');
        Route::delete('/academics/departments/{department}', [AcademicStructureController::class, 'destroyDepartment'])
            ->middleware('permission:academics.manage')
            ->name('academics.departments.destroy');


        Route::post('/academics/levels', [AcademicStructureController::class, 'storeLevel'])
            ->middleware('permission:academics.manage')
            ->name('academics.levels.store');
        Route::put('/academics/levels/{academicLevel}', [AcademicStructureController::class, 'updateLevel'])
            ->middleware('permission:academics.manage')
            ->name('academics.levels.update');

        Route::post('/academics/sessions', [AcademicStructureController::class, 'storeSession'])
            ->middleware('permission:academics.manage')
            ->name('academics.sessions.store');
        Route::put('/academics/sessions/{academicSession}', [AcademicStructureController::class, 'updateSession'])
            ->middleware('permission:academics.manage')
            ->name('academics.sessions.update');
        Route::post('/academics/sessions/{academicSession}/activate', [AcademicStructureController::class, 'activateSession'])
            ->middleware('permission:academics.manage')
            ->name('academics.sessions.activate');

        Route::get('/settings', [AppSettingController::class, 'index'])
            ->middleware('permission:settings.view')
            ->name('settings.index');
        Route::post('/settings', [AppSettingController::class, 'store'])
            ->middleware('permission:settings.update')
            ->name('settings.store');
        Route::post('/settings/bulk', [AppSettingController::class, 'bulk'])
            ->middleware('permission:settings.update')
            ->name('settings.bulk');
        Route::post('/settings/email/test', [AppSettingController::class, 'testEmailConnection'])
            ->middleware('permission:settings.update')
            ->name('settings.email.test');
        Route::post('/settings/cache/clear', [AppSettingController::class, 'clearCache'])
            ->middleware(['permission:settings.view', 'throttle:10,1'])
            ->name('settings.cache.clear');
        Route::post('/settings/database/seed', [AppSettingController::class, 'seedDatabase'])
            ->middleware(['role.portal:super-admin', 'permission:settings.update', 'throttle:3,1'])
            ->name('settings.database.seed');
        Route::post('/settings/imports/process', [AppSettingController::class, 'processQueuedImports'])
            ->middleware(['permission:students.import', 'throttle:6,1'])
            ->name('settings.imports.process');
        Route::put('/settings/{appSetting}', [AppSettingController::class, 'update'])
            ->middleware('permission:settings.update')
            ->name('settings.update');

        Route::get('/notices', [NoticeController::class, 'index'])
            ->middleware('permission:settings.view')
            ->name('notices.index');
        Route::post('/notices', [NoticeController::class, 'store'])
            ->middleware('permission:settings.update')
            ->name('notices.store');
        Route::put('/notices/{notice}', [NoticeController::class, 'update'])
            ->middleware('permission:settings.update')
            ->name('notices.update');

        Route::middleware('role.portal:super-admin')->prefix('control')->name('control.')->group(function () {
            Route::get('/', ControlCenterController::class)
                ->middleware('permission:admins.manage')
                ->name('index');
            Route::post('/admins', [AdminUserController::class, 'store'])
                ->middleware('permission:admins.manage')
                ->name('admins.store');
            Route::put('/admins/{admin}', [AdminUserController::class, 'update'])
                ->middleware('permission:admins.manage')
                ->name('admins.update');
            Route::post('/roles', [RoleManagementController::class, 'store'])
                ->middleware('permission:roles.manage')
                ->name('roles.store');
            Route::put('/roles/{role}', [RoleManagementController::class, 'update'])
                ->middleware('permission:roles.manage')
                ->name('roles.update');
            Route::get('/audit-logs', [AuditLogController::class, 'index'])
                ->middleware('permission:audit.view')
                ->name('audit.index');
            Route::get('/audit-logs/export', [AuditLogController::class, 'export'])
                ->middleware(['permission:audit.view', 'throttle:exports'])
                ->name('audit.export');
        });

        Route::get('/assessments/rubric', [AssessmentRubricController::class, 'index'])
            ->middleware('permission:settings.view')
            ->name('assessments.rubric.index');
        Route::post('/assessments/rubric', [AssessmentRubricController::class, 'store'])
            ->middleware('permission:settings.update')
            ->name('assessments.rubric.store');
        Route::put('/assessments/rubric/{assessmentRubricItem}', [AssessmentRubricController::class, 'update'])
            ->middleware('permission:settings.update')
            ->name('assessments.rubric.update');

        Route::get('/reports', [ReportController::class, 'index'])
            ->middleware('permission:feedback.view')
            ->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])
            ->middleware(['permission:feedback.view', 'throttle:exports'])
            ->name('reports.export');

        Route::get('/students', [StudentController::class, 'index'])
            ->middleware('permission:students.view')
            ->name('students.index');
        Route::post('/students', [StudentController::class, 'store'])
            ->middleware('permission:students.create')
            ->name('students.store');
        Route::get('/students/export', [StudentController::class, 'export'])
            ->middleware(['permission:students.export', 'throttle:exports'])
            ->name('students.export');
        Route::get('/students/posting-list', [StudentController::class, 'postingList'])
            ->middleware(['permission:students.export', 'throttle:exports'])
            ->name('students.posting-list');
        Route::get('/students/template/{format}', [StudentController::class, 'template'])
            ->middleware('permission:students.import')
            ->name('students.template');
        Route::get('/students/{student}', [StudentController::class, 'show'])
            ->middleware('permission:students.view')
            ->name('students.show');
        Route::put('/students/{student}', [StudentController::class, 'update'])
            ->middleware('permission:students.update')
            ->name('students.update');
        Route::post('/students/{student}/reset-password', [StudentController::class, 'resetPassword'])
            ->middleware('permission:students.update')
            ->name('students.reset-password');
        Route::post('/students/{student}/suspend', [StudentController::class, 'suspend'])
            ->middleware('permission:students.suspend')
            ->name('students.suspend');
        Route::post('/students/{student}/reactivate', [StudentController::class, 'reactivate'])
            ->middleware('permission:students.update')
            ->name('students.reactivate');
        Route::delete('/students/bulk-delete', [StudentController::class, 'destroyMany'])
            ->middleware('permission:students.update')
            ->name('students.destroy-many');
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])
            ->middleware('permission:students.update')
            ->name('students.destroy');
        Route::post('/students/imports/preview', [StudentImportController::class, 'preview'])
            ->middleware('permission:students.import')
            ->name('students.imports.preview');
        Route::post('/students/imports', [StudentImportController::class, 'store'])
            ->middleware('permission:students.import')
            ->name('students.imports.store');
        Route::post('/students/imports/{studentImport}/process', [StudentImportController::class, 'process'])
            ->middleware('permission:students.import')
            ->name('students.imports.process');

        Route::get('/tickets', [TicketController::class, 'index'])
            ->middleware('permission:tickets.view')
            ->name('tickets.index');
        Route::post('/tickets', [TicketController::class, 'store'])
            ->middleware('permission:tickets.generate')
            ->name('tickets.store');
        Route::post('/tickets/{ticket}/revoke', [TicketController::class, 'revoke'])
            ->middleware('permission:tickets.revoke')
            ->name('tickets.revoke');

        Route::get('/payments', [AdminPaymentController::class, 'index'])
            ->middleware('permission:payments.view')
            ->name('payments.index');

        Route::get('/supervisors', [SupervisorController::class, 'index'])
            ->middleware('permission:supervisors.view')
            ->name('supervisors.index');
        Route::post('/supervisors', [SupervisorController::class, 'store'])
            ->middleware('permission:supervisors.create')
            ->name('supervisors.store');
        Route::get('/supervisors/export', [SupervisorController::class, 'export'])
            ->middleware(['permission:supervisors.view', 'throttle:exports'])
            ->name('supervisors.export');
        Route::get('/supervisors/assignments/export', [SupervisorAssignmentController::class, 'export'])
            ->middleware(['permission:supervisors.view', 'throttle:exports'])
            ->name('supervisors.assignments.export');
        Route::get('/supervisors/{supervisor}', [SupervisorController::class, 'show'])
            ->middleware('permission:supervisors.view')
            ->name('supervisors.show');
        Route::put('/supervisors/{supervisor}', [SupervisorController::class, 'update'])
            ->middleware('permission:supervisors.update')
            ->name('supervisors.update');
        Route::post('/supervisors/{supervisor}/suspend', [SupervisorController::class, 'suspend'])
            ->middleware('permission:supervisors.suspend')
            ->name('supervisors.suspend');
        Route::post('/supervisors/{supervisor}/reactivate', [SupervisorController::class, 'reactivate'])
            ->middleware('permission:supervisors.update')
            ->name('supervisors.reactivate');
        Route::post('/supervisor-assignments', [SupervisorAssignmentController::class, 'store'])
            ->middleware('permission:supervisors.assign')
            ->name('supervisor-assignments.store');
        Route::post('/supervisor-assignments/bulk', [SupervisorAssignmentController::class, 'bulk'])
            ->middleware('permission:supervisors.assign')
            ->name('supervisor-assignments.bulk');
        Route::post('/supervisor-assignments/bulk-revoke', [SupervisorAssignmentController::class, 'bulkRevoke'])
            ->middleware('permission:supervisors.assign')
            ->name('supervisor-assignments.bulk-revoke');
        Route::post('/supervisor-assignments/{assignment}/revoke', [SupervisorAssignmentController::class, 'revoke'])
            ->middleware('permission:supervisors.assign')
            ->name('supervisor-assignments.revoke');
    });

    Route::get('/supervisor/dashboard', SupervisorDashboardController::class)
        ->middleware(['otp.verified', 'role.portal:supervisor'])
        ->name('supervisor.dashboard');
    Route::get('/supervisor/students', [AssignedStudentController::class, 'index'])
        ->middleware(['otp.verified', 'role.portal:supervisor'])
        ->name('supervisor.students.index');
    Route::get('/supervisor/assessments', [AssessmentController::class, 'index'])
        ->middleware(['otp.verified', 'role.portal:supervisor'])
        ->name('supervisor.assessments.index');
    Route::post('/supervisor/assessments', [AssessmentController::class, 'store'])
        ->middleware(['otp.verified', 'role.portal:supervisor'])
        ->name('supervisor.assessments.store');
    Route::get('/student/dashboard', StudentDashboardController::class)
        ->middleware(['otp.verified', 'role.portal:student', 'student.profile.complete'])
        ->name('student.dashboard');
    Route::get('/student/profile', [StudentProfileController::class, 'show'])
        ->middleware(['otp.verified', 'role.portal:student'])
        ->name('student.profile.show');
    Route::get('/student/profile/setup', [StudentProfileController::class, 'edit'])
        ->middleware(['otp.verified', 'role.portal:student'])
        ->name('student.profile.edit');
    Route::post('/student/profile/setup', [StudentProfileController::class, 'updateStep'])
        ->middleware(['otp.verified', 'role.portal:student'])
        ->name('student.profile.step');
    Route::get('/student/profile/complete', [StudentProfileController::class, 'complete'])
        ->middleware(['otp.verified', 'role.portal:student'])
        ->name('student.profile.complete');
    Route::prefix('/student/profile-data')->middleware(['otp.verified', 'role.portal:student'])->name('student.profile-data.')->group(function () {
        Route::get('/nationalities', [StudentProfileDataController::class, 'nationalities'])->name('nationalities');
        Route::get('/states', [StudentProfileDataController::class, 'states'])->name('states');
        Route::get('/lgas', [StudentProfileDataController::class, 'lgas'])->name('lgas');
        Route::get('/banks', [StudentProfileDataController::class, 'banks'])->name('banks');
        Route::get('/faculties', [StudentProfileDataController::class, 'faculties'])->name('faculties');
        Route::get('/departments', [StudentProfileDataController::class, 'departments'])->name('departments');
    });
    Route::put('/student/profile', [StudentProfileController::class, 'update'])
        ->middleware(['otp.verified', 'role.portal:student'])
        ->name('student.profile.update');
    Route::get('/student/payments', [PaymentController::class, 'index'])
        ->middleware(['otp.verified', 'role.portal:student', 'student.profile.complete'])
        ->name('student.payments.index');
    Route::post('/student/payments/initialize', [PaymentController::class, 'initialize'])
        ->middleware(['otp.verified', 'role.portal:student', 'student.profile.complete'])
        ->name('student.payments.initialize');
    Route::get('/student/payments/callback', [PaymentController::class, 'callback'])
        ->middleware(['otp.verified', 'role.portal:student', 'student.profile.complete'])
        ->name('student.payments.callback');
    Route::get('/student/workshop-fee', [PaymentController::class, 'workshop'])
        ->middleware(['otp.verified', 'role.portal:student', 'student.profile.complete'])
        ->name('student.workshop.checkout');
    Route::post('/student/workshop-fee/initialize', [PaymentController::class, 'initializeWorkshop'])
        ->middleware(['otp.verified', 'role.portal:student', 'student.profile.complete', 'throttle:10,1'])
        ->name('student.workshop.initialize');
    Route::get('/student/tickets', [StudentTicketController::class, 'index'])
        ->middleware(['otp.verified', 'role.portal:student', 'student.profile.complete'])
        ->name('student.tickets.index');
    Route::get('/student/placements/ticket', [PlacementController::class, 'ticket'])
        ->middleware(['otp.verified', 'role.portal:student', 'student.profile.complete', 'student.workshop.paid'])
        ->name('student.placements.ticket');
    Route::post('/student/placements/ticket', [PlacementController::class, 'confirmTicket'])
        ->middleware(['otp.verified', 'role.portal:student', 'student.profile.complete', 'student.workshop.paid', 'throttle:10,1'])
        ->name('student.placements.ticket.confirm');
    Route::post('/student/placements/pay-online', [PlacementController::class, 'payOnline'])
        ->middleware(['otp.verified', 'role.portal:student', 'student.profile.complete', 'student.workshop.paid', 'throttle:10,1'])
        ->name('student.placements.pay-online');
    Route::get('/student/placements/create', [PlacementController::class, 'create'])
        ->middleware(['otp.verified', 'role.portal:student', 'student.profile.complete', 'student.workshop.paid'])
        ->name('student.placements.create');
    Route::post('/student/placements/create', [PlacementController::class, 'storeStep'])
        ->middleware(['otp.verified', 'role.portal:student', 'student.profile.complete', 'student.workshop.paid'])
        ->name('student.placements.store-step');
    Route::get('/student/placements/complete', [PlacementController::class, 'complete'])
        ->middleware(['otp.verified', 'role.portal:student', 'student.profile.complete'])
        ->name('student.placements.complete');
    Route::get('/student/feedback', [FeedbackController::class, 'index'])
        ->middleware(['otp.verified', 'role.portal:student', 'student.profile.complete'])
        ->name('student.feedback.index');
});
