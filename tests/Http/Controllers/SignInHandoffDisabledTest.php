<?php

namespace AnilcanCakir\MagicStarter\Tests\Http\Controllers;

use AnilcanCakir\MagicStarter\MagicStarterServiceProvider;
use AnilcanCakir\MagicStarter\Tests\TestCase;
use Laravel\Sanctum\SanctumServiceProvider;

/**
 * A deployment that never lists the sign-in handoff feature has neither of its routes.
 */
class SignInHandoffDisabledTest extends TestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            SanctumServiceProvider::class,
            MagicStarterServiceProvider::class,
        ];
    }

    public function test_both_routes_are_absent_while_the_feature_is_off(): void
    {
        $this->assertSame([], config('magic-starter.features'));

        $this->postJson('/auth/handoff')->assertNotFound();
        $this->postJson('/auth/handoff/exchange', [
            'code' => 'anything',
        ])->assertNotFound();
    }
}
