<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhaseTwoShellRoutesTest extends TestCase
{
    #[DataProvider('shellRouteProvider')]
    public function test_phase_two_public_and_role_shell_previews_render(string $route): void
    {
        $this->get(route($route))->assertOk();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function shellRouteProvider(): array
    {
        return [
            'home' => ['home'],
            'admin login' => ['login.admin'],
            'supervisor login' => ['login.supervisor'],
            'student login' => ['login.student'],
        ];
    }
}
