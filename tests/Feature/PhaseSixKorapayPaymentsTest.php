<?php

namespace Tests\Feature;

use App\Models\AcademicLevel;
use App\Models\AcademicSession;
use App\Models\AppSetting;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Ticket;
use App\Models\Admin;
use App\Models\User;
use App\Services\StudentManager;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhaseSixKorapayPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('siwes.korapay.secret_key', 'test-secret');
        Config::set('siwes.korapay.webhook_secret', 'test-secret');
        Config::set('siwes.payments.ticket_amount', 5000);
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(AcademicStructureSeeder::class);

        Admin::where('email', 'admin@coousiwes.test')
            ->firstOrFail()
            ->assignRole('ticket-manager')
            ->givePermissionTo('tickets.generate');
    }

    public function test_admin_can_generate_activation_tickets_for_students(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $student = $this->student();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.tickets.store'), ['student_ids' => [$student->id]])
            ->assertOk()
            ->assertJsonPath('message', '1 ticket(s) generated.');

        $this->assertDatabaseHas('tickets', [
            'student_id' => $student->id,
            'amount' => 5000,
            'status' => Ticket::STATUS_UNUSED,
        ]);
    }

    public function test_admin_can_generate_unassigned_ticket_stock_by_quantity(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.tickets.store'), ['quantity' => 3])
            ->assertOk()
            ->assertJsonPath('message', '3 ticket(s) generated.');

        $this->assertSame(3, Ticket::query()->whereNull('student_id')->where('status', Ticket::STATUS_GENERATED)->count());
        $ticket = Ticket::query()->whereNull('student_id')->firstOrFail();
        $this->assertMatchesRegularExpression('/^SIWES-\d{12}$/', (string) $ticket->serial_number);
        $this->assertMatchesRegularExpression('/^\d{6}$/', (string) $ticket->pin);
    }

    public function test_admin_can_view_complete_used_ticket_details_from_ticket_list(): void
    {
        $admin = Admin::where('email', 'admin@coousiwes.test')->firstOrFail();
        $student = $this->student();
        $usedTicket = $student->tickets()->create([
            'serial_number' => 'SIWES-896051760345',
            'pin' => '123456',
            'code_hash' => 'used-ticket-code',
            'amount' => 5000,
            'currency' => 'NGN',
            'status' => Ticket::STATUS_USED,
            'used_at' => now(),
            'expires_at' => now()->addDays(10),
        ]);
        $student->payments()->create([
            'ticket_id' => $usedTicket->id,
            'provider' => 'korapay',
            'reference' => 'KORA-USED-001',
            'amount' => 5000,
            'currency' => 'NGN',
            'status' => Payment::STATUS_SUCCESSFUL,
            'verified_at' => now(),
            'paid_at' => now(),
        ]);
        $student->placement()->create([
            'ticket_id' => $usedTicket->id,
            'academic_level_id' => $student->academic_level_id,
            'academic_session_id' => $student->academic_session_id,
            'siwes_year' => 2026,
            'attachment_period' => 'April to October',
            'company_name' => 'Ticket Detail Works Ltd',
            'company_address' => '10 Industrial Road',
            'company_state' => 'Anambra',
            'company_lga' => 'Awka South',
            'company_supervisor_phone' => '08030000000',
        ]);
        Ticket::query()->create([
            'serial_number' => 'SIWES-242197974414',
            'pin' => '654321',
            'code_hash' => 'unused-ticket-code',
            'amount' => 5000,
            'currency' => 'NGN',
            'status' => Ticket::STATUS_UNUSED,
            'expires_at' => now()->addDays(10),
        ]);

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->get(route('admin.tickets.index'))
            ->assertOk()
            ->assertSee('View Details')
            ->assertSee('SIWES-896051760345')
            ->assertSee($student->user->name)
            ->assertSee($student->matric_no)
            ->assertSee('KORA-USED-001')
            ->assertSee('Ticket Detail Works Ltd')
            ->assertDontSee('ticket-details-'.Ticket::where('serial_number', 'SIWES-242197974414')->firstOrFail()->id);
    }

    public function test_student_can_initialize_korapay_checkout_for_payable_ticket(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => true,
                'data' => ['checkout_url' => 'https://checkout.korapay.com/pay/test'],
            ]),
        ]);

        $student = $this->student();
        $ticket = new Ticket([
            'code_hash' => 'hashed-code',
            'amount' => 5000,
            'currency' => 'NGN',
            'status' => Ticket::STATUS_UNUSED,
            'expires_at' => now()->addDays(10),
        ]);
        $student->tickets()->save($ticket);

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->postJson(route('student.payments.initialize'), ['ticket_id' => $ticket->id])
            ->assertOk()
            ->assertJsonPath('message', 'Korapay checkout initialized.')
            ->assertJsonPath('redirect', 'https://checkout.korapay.com/pay/test');

        $this->assertDatabaseHas('payments', [
            'student_id' => $student->id,
            'ticket_id' => $ticket->id,
            'provider' => 'korapay',
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    public function test_student_can_initialize_workshop_fee_checkout_when_module_is_active(): void
    {
        AppSetting::query()->updateOrCreate(
            ['key' => 'payment.workshop_fee_enabled'],
            ['group' => 'payment', 'value' => true, 'type' => 'boolean']
        );
        AppSetting::query()->updateOrCreate(
            ['key' => 'payment.workshop_fee_amount'],
            ['group' => 'payment', 'value' => 2500, 'type' => 'integer']
        );

        Http::fake([
            '*' => Http::response([
                'status' => true,
                'data' => ['checkout_url' => 'https://checkout.korapay.com/pay/workshop'],
            ]),
        ]);

        $student = $this->student();

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->postJson(route('student.workshop.initialize'))
            ->assertOk()
            ->assertJsonPath('message', 'Workshop fee checkout initialized.')
            ->assertJsonPath('redirect', 'https://checkout.korapay.com/pay/workshop');

        $this->assertDatabaseHas('payments', [
            'student_id' => $student->id,
            'ticket_id' => null,
            'purpose' => Payment::PURPOSE_WORKSHOP_FEE,
            'amount' => 2500,
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    public function test_student_claims_unassigned_ticket_when_initializing_payment(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => true,
                'data' => ['checkout_url' => 'https://checkout.korapay.com/pay/test'],
            ]),
        ]);

        $student = $this->student();
        $ticket = Ticket::query()->create([
            'code_hash' => 'hashed-unassigned-code',
            'amount' => 5000,
            'currency' => 'NGN',
            'status' => Ticket::STATUS_GENERATED,
            'expires_at' => now()->addDays(10),
        ]);

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->postJson(route('student.payments.initialize'), ['ticket_id' => $ticket->id])
            ->assertOk();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'student_id' => $student->id,
            'status' => Ticket::STATUS_UNUSED,
        ]);
        $this->assertDatabaseHas('payments', [
            'student_id' => $student->id,
            'ticket_id' => $ticket->id,
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    public function test_payment_callback_verifies_reference_and_activates_ticket(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success'],
            ]),
        ]);

        $student = $this->student();
        $ticket = new Ticket([
            'code_hash' => 'callback-code',
            'amount' => 5000,
            'currency' => 'NGN',
            'status' => Ticket::STATUS_UNUSED,
            'expires_at' => now()->addDays(10),
        ]);
        $student->tickets()->save($ticket);
        $payment = Payment::query()->create([
            'student_id' => $student->id,
            'ticket_id' => $ticket->id,
            'reference' => 'SIWES-CALLBACK',
            'amount' => 5000,
            'currency' => 'NGN',
        ]);

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->get(route('student.payments.callback', ['reference' => $payment->reference]))
            ->assertRedirect(route('student.payments.index'));

        $this->assertSame(Payment::STATUS_SUCCESSFUL, $payment->fresh()->status);
        $this->assertSame(Ticket::STATUS_USED, $ticket->fresh()->status);
        $this->assertSame(Student::STATUS_ACTIVE, $student->fresh()->activation_status);
    }

    public function test_workshop_payment_callback_marks_fee_successful_without_ticket(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => true,
                'data' => ['status' => 'success'],
            ]),
        ]);

        $student = $this->student();
        $payment = Payment::query()->create([
            'student_id' => $student->id,
            'ticket_id' => null,
            'purpose' => Payment::PURPOSE_WORKSHOP_FEE,
            'reference' => 'WORKSHOP-CALLBACK',
            'amount' => 2500,
            'currency' => 'NGN',
        ]);

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->get(route('student.payments.callback', ['reference' => $payment->reference]))
            ->assertRedirect(route('student.workshop.checkout'));

        $this->assertSame(Payment::STATUS_SUCCESSFUL, $payment->fresh()->status);
    }

    public function test_student_payment_page_shows_used_ticket_serial_number(): void
    {
        $student = $this->student();
        $ticket = new Ticket([
            'serial_number' => 'SIWES-656484753637',
            'code_hash' => 'used-ticket-code',
            'amount' => 5000,
            'currency' => 'NGN',
            'status' => Ticket::STATUS_USED,
            'used_at' => now(),
            'expires_at' => now()->addDays(10),
        ]);
        $student->tickets()->save($ticket);

        $this->actingAs($student->user)
            ->withSession(['otp.verified' => true])
            ->get(route('student.payments.index'))
            ->assertOk()
            ->assertSee('SIWES-656484753637')
            ->assertSee('Used Ticket')
            ->assertDontSee('Pay With Korapay');
    }

    public function test_korapay_webhook_is_signature_checked_and_idempotent(): void
    {
        $student = $this->student();
        $ticket = new Ticket([
            'code_hash' => 'webhook-code',
            'amount' => 5000,
            'currency' => 'NGN',
            'status' => Ticket::STATUS_UNUSED,
            'expires_at' => now()->addDays(10),
        ]);
        $student->tickets()->save($ticket);
        $payment = Payment::query()->create([
            'student_id' => $student->id,
            'ticket_id' => $ticket->id,
            'reference' => 'SIWES-WEBHOOK',
            'amount' => 5000,
            'currency' => 'NGN',
        ]);
        $payload = [
            'event' => 'charge.success',
            'data' => [
                'id' => 'evt_123',
                'reference' => $payment->reference,
                'status' => 'success',
            ],
        ];
        $signature = hash_hmac('sha256', json_encode($payload['data'], JSON_UNESCAPED_SLASHES), 'test-secret');

        $this->postJson(route('webhooks.korapay'), $payload, ['x-korapay-signature' => $signature])->assertOk();
        $this->postJson(route('webhooks.korapay'), $payload, ['x-korapay-signature' => $signature])->assertOk();

        $this->assertSame(Payment::STATUS_SUCCESSFUL, $payment->fresh()->status);
        $this->assertSame('evt_123', $payment->fresh()->webhook_event_id);
        $this->assertSame(Ticket::STATUS_USED, $ticket->fresh()->status);
    }

    public function test_invalid_webhook_signature_is_rejected(): void
    {
        $this->postJson(route('webhooks.korapay'), ['data' => ['reference' => 'missing']], ['x-korapay-signature' => 'bad'])
            ->assertUnauthorized();
    }

    private function student(): Student
    {
        $student = app(StudentManager::class)->create([
            'name' => 'Payment Student',
            'email' => 'payment-student@example.test',
            'phone' => '08030000000',

            'matric_no' => '2026/PAY/001',
            'faculty_id' => Faculty::where('code', 'AGRIC')->firstOrFail()->id,
            'department_id' => Department::where('code', 'AGE')->firstOrFail()->id,
            'course_id' => Course::where('code', 'BSC-AGE')->firstOrFail()->id,
            'academic_level_id' => AcademicLevel::where('level', 300)->firstOrFail()->id,
            'academic_session_id' => AcademicSession::where('name', '2026/2027')->firstOrFail()->id,
            'activation_status' => Student::STATUS_INACTIVE,
        ]);

        $student->update([
            'gender' => 'Male',
            'date_of_birth' => '2001-01-01',
            'address' => 'Awka campus',
            'metadata' => [
                'nationality' => 'Nigerian',
                'state' => 'Anambra',
                'lga' => 'Awka South',
                'bank_name' => 'Access Bank',
                'account_number' => '0123456789',
                'sort_code' => '044',
            ],
        ]);

        return $student;
    }
}


