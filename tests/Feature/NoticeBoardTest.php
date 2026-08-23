<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Notice;
use App\Models\Admin;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoticeBoardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_publish_notice_to_public_notice_board(): void
    {
        $admin = Admin::where('email', 'superadmin@coousiwes.test')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->postJson(route('admin.notices.store'), [
                'title' => 'Field posting list released',
                'body' => 'Students should check their portal for updated placement information.',
                'audience' => 'students',
                'tone' => 'info',
                'is_pinned' => true,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Notice published.');

        $this->assertDatabaseHas('notices', [
            'title' => 'Field posting list released',
            'audience' => 'students',
            'is_pinned' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Field posting list released');
    }

    public function test_expired_notices_are_not_displayed_publicly(): void
    {
        Notice::query()->create([
            'title' => 'Expired public notice',
            'body' => 'This should no longer be visible.',
            'audience' => 'all',
            'tone' => 'warning',
            'published_at' => now()->subDays(3),
            'expires_at' => now()->subDay(),
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Expired public notice');
    }

    public function test_admin_can_update_public_welcome_message(): void
    {
        $admin = Admin::where('email', 'superadmin@coousiwes.test')->firstOrFail();
        $setting = AppSetting::where('key', 'site.welcome.message')->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->withSession(['otp.verified' => true])
            ->putJson(route('admin.settings.update', $setting), [
                'group' => 'site',
                'key' => 'site.welcome.message',
                'value' => 'Custom admin welcome message.',
                'type' => 'string',
                'is_public' => true,
            ])
            ->assertOk();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Custom admin welcome message.');
    }
}


