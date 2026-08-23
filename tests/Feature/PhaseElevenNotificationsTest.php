<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PortalNotification;
use Database\Seeders\AcademicStructureSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseElevenNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(AcademicStructureSeeder::class);
    }

    public function test_notification_center_is_scoped_to_authenticated_user(): void
    {
        $student = User::where('email', 'student@coousiwes.test')->firstOrFail();
        $supervisor = User::where('email', 'supervisor@coousiwes.test')->firstOrFail();

        $student->notify(new PortalNotification(['title' => 'Student only', 'message' => 'Private student alert.']));
        $supervisor->notify(new PortalNotification(['title' => 'Supervisor only', 'message' => 'Private supervisor alert.']));

        $this->actingAs($student)
            ->withSession(['otp.verified' => true])
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Student only')
            ->assertDontSee('Supervisor only');
    }

    public function test_summary_returns_unread_count_and_latest_notifications(): void
    {
        $student = User::where('email', 'student@coousiwes.test')->firstOrFail();
        $student->notify(new PortalNotification(['title' => 'Realtime alert', 'message' => 'Unread notification payload.', 'tone' => 'success']));

        $this->actingAs($student)
            ->withSession(['otp.verified' => true])
            ->getJson(route('notifications.summary'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.title', 'Realtime alert')
            ->assertJsonPath('notifications.0.tone', 'success');
    }

    public function test_user_can_mark_own_notifications_read_but_not_other_users_notifications(): void
    {
        $student = User::where('email', 'student@coousiwes.test')->firstOrFail();
        $supervisor = User::where('email', 'supervisor@coousiwes.test')->firstOrFail();
        $student->notify(new PortalNotification(['title' => 'Own alert']));
        $supervisor->notify(new PortalNotification(['title' => 'Other alert']));
        $ownNotification = $student->unreadNotifications()->firstOrFail();
        $otherNotification = $supervisor->unreadNotifications()->firstOrFail();

        $this->actingAs($student)
            ->withSession(['otp.verified' => true])
            ->postJson(route('notifications.read', $ownNotification))
            ->assertOk()
            ->assertJsonPath('message', 'Notification marked as read.');

        $this->assertNotNull($ownNotification->fresh()->read_at);

        $this->actingAs($student)
            ->withSession(['otp.verified' => true])
            ->postJson(route('notifications.read', $otherNotification))
            ->assertForbidden();
    }

    public function test_mark_all_read_only_marks_authenticated_users_notifications(): void
    {
        $student = User::where('email', 'student@coousiwes.test')->firstOrFail();
        $supervisor = User::where('email', 'supervisor@coousiwes.test')->firstOrFail();
        $student->notify(new PortalNotification(['title' => 'Student alert']));
        $supervisor->notify(new PortalNotification(['title' => 'Supervisor alert']));

        $this->actingAs($student)
            ->withSession(['otp.verified' => true])
            ->postJson(route('notifications.read-all'))
            ->assertOk()
            ->assertJsonPath('message', 'All notifications marked as read.');

        $this->assertSame(0, $student->unreadNotifications()->count());
        $this->assertSame(1, $supervisor->unreadNotifications()->count());
    }

    public function test_portal_notification_exposes_broadcast_payload_for_realtime_delivery(): void
    {
        $student = User::where('email', 'student@coousiwes.test')->firstOrFail();
        $notification = new PortalNotification([
            'title' => 'Realtime ready',
            'message' => 'Broadcast payload is available.',
            'tone' => 'warning',
            'action_url' => route('notifications.index'),
        ]);

        $payload = $notification->toBroadcast($student)->data;

        $this->assertSame('Realtime ready', $payload['title']);
        $this->assertSame('Broadcast payload is available.', $payload['message']);
        $this->assertSame('warning', $payload['tone']);
        $this->assertSame(route('notifications.index'), $payload['action_url']);
    }
}
