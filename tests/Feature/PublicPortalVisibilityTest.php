<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPortalVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_landing_page_only_promotes_student_and_supervisor_access(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('images/coou-logo.png')
            ->assertSee('Student Portal')
            ->assertSee('Supervisor Portal')
            ->assertSee('Notice Board')
            ->assertSee('SIWES student portal is open')
            ->assertSee('Welcome to COOU SIWES')
            ->assertSee('data-welcome-toast', false)
            ->assertSee('images/siwes-landing-hero.png')
            ->assertSee('All rights reserved.')
            ->assertDontSee('Admin Console')
            ->assertDontSee(route('login.admin'));
    }

    public function test_student_login_does_not_show_admin_role_switcher(): void
    {
        $this->get(route('login.student'))
            ->assertOk()
            ->assertSee('images/coou-logo.png')
            ->assertSee('images/student-auth-overlay.png')
            ->assertSee('Student')
            ->assertSee('Supervisor')
            ->assertDontSee('data-welcome-toast', false)
            ->assertDontSee('OTP protected')
            ->assertDontSee('A one-time code is required before dashboard access.')
            ->assertDontSee('Admin</a>', false);
    }
}
