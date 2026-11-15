<?php

namespace AnilcanCakir\MagicStarter\Tests\Http\Controllers;

use AnilcanCakir\MagicStarter\MagicStarter;
use AnilcanCakir\MagicStarter\Support\TwoFactorAuthenticationProvider;
use AnilcanCakir\MagicStarter\Tests\Fixtures\ConcreteTeam;
use AnilcanCakir\MagicStarter\Tests\Fixtures\ConcreteTeamUser;
use AnilcanCakir\MagicStarter\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;

/**
 * The lifetime of every token the package issues.
 *
 * `token_expiration_minutes` sets it for every sign-in path, and
 * `remember_me_expiration_minutes` replaces it for a password sign-in that sent
 * `remember_me: true`, the 2FA continuation of one included. Both null keeps a
 * token without its own `expires_at`.
 */
class AuthTokenLifetimeTest extends TestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            \Laravel\Sanctum\SanctumServiceProvider::class,
            \AnilcanCakir\MagicStarter\MagicStarterServiceProvider::class,
        ];
    }

    /**
     * Enable the gated routes BEFORE the providers boot, since the route file reads the features then.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.key', 'base64:XG8o1YFkL9FzM3NqZ8eB4Q6dC9yQ/K1R+g3M5T3r8P4=');
        $app['config']->set('magic-starter.features', [
            'two-factor-authentication',
            'guest-auth',
            'phone-otp',
        ]);
        $app['config']->set('auth.providers.users.model', AuthTokenLifetimeTestUser::class);
        $app['config']->set('magic-starter.models.user', AuthTokenLifetimeTestUser::class);
        $app['config']->set('magic-starter.models.team', ConcreteTeam::class);
        $app['config']->set('magic-starter.models.membership', ConcreteTeamUser::class);
    }

    protected function setUp(): void
    {
        parent::setUp();
        MagicStarter::reset();

        Schema::create('users', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_guest')->default(false);
            $table->string('device_id', 255)->unique()->nullable();
            $table->string('phone')->unique()->nullable();
            $table->char('phone_country', 2)->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->string('locale')->default('en');
            $table->string('timezone')->default('UTC');
            $table->string('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuidMorphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        foreach ([
            'create_teams_table.php',
            'create_team_user_table.php',
        ] as $file) {
            (require __DIR__ . '/../../../database/migrations/' . $file)->up();
        }

        // Second precision, the precision the `expires_at` column stores.
        $this->freezeSecond();
    }

    protected function tearDown(): void
    {
        MagicStarter::reset();
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function account(bool $twoFactor = false, ?string $secret = null): AuthTokenLifetimeTestUser
    {
        return AuthTokenLifetimeTestUser::create([
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => Hash::make('Password123!'),
            'two_factor_secret' => $twoFactor ? encrypt($secret) : null,
            'two_factor_confirmed_at' => $twoFactor ? now() : null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra  Fields merged into the credentials.
     */
    private function login(array $extra = []): TestResponse
    {
        return $this->postJson('/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'Password123!',
            ...$extra,
        ]);
    }

    /**
     * Read the `expires_at` of the token a response issued.
     */
    private function expiryOf(TestResponse $response): ?Carbon
    {
        $response->assertSuccessful();

        $token = Sanctum::personalAccessTokenModel()::findToken((string) $response->json('data.token'));

        $this->assertNotNull($token, 'The response must carry a persisted token.');

        return $token->expires_at;
    }

    private function assertExpiresIn(int $minutes, ?Carbon $expiresAt): void
    {
        $this->assertNotNull($expiresAt, 'The token must carry its own expiry.');
        $this->assertTrue(
            $expiresAt->equalTo(now()->addMinutes($minutes)),
            "Expected an expiry {$minutes} minutes from now, got {$expiresAt->toIso8601String()}.",
        );
    }

    /**
     * Sign in through a confirmed second factor and return the final response.
     */
    private function loginThroughTwoFactor(bool $remember): TestResponse
    {
        $provider = app(TwoFactorAuthenticationProvider::class);
        $secret = $provider->engine->generateSecretKey();
        $this->account(twoFactor: true, secret: $secret);

        $challenge = $this->login([
            'remember_me' => $remember,
        ])->assertOk()->assertJsonPath('two_factor', true);

        return $this->postJson('/auth/two-factor-challenge', [
            'two_factor_token' => (string) $challenge->json('two_factor_token'),
            'code' => $provider->engine->getCurrentOtp($secret),
        ]);
    }

    // ------------------------------------------------------------------
    // Password login and remember_me
    // ------------------------------------------------------------------

    public function test_a_remembered_login_lasts_the_remember_me_lifetime(): void
    {
        config([
            'magic-starter.token_expiration_minutes' => 60,
            'magic-starter.remember_me_expiration_minutes' => 43200,
        ]);
        $this->account();

        $this->assertExpiresIn(43200, $this->expiryOf($this->login(['remember_me' => true])));
    }

    public function test_a_login_that_is_not_remembered_lasts_the_token_lifetime(): void
    {
        config([
            'magic-starter.token_expiration_minutes' => 60,
            'magic-starter.remember_me_expiration_minutes' => 43200,
        ]);
        $this->account();

        $this->assertExpiresIn(60, $this->expiryOf($this->login(['remember_me' => false])));
    }

    public function test_a_login_without_remember_me_lasts_the_token_lifetime(): void
    {
        config([
            'magic-starter.token_expiration_minutes' => 60,
            'magic-starter.remember_me_expiration_minutes' => 43200,
        ]);
        $this->account();

        $this->assertExpiresIn(60, $this->expiryOf($this->login()));
    }

    public function test_a_remembered_login_falls_back_to_the_token_lifetime(): void
    {
        config([
            'magic-starter.token_expiration_minutes' => 60,
            'magic-starter.remember_me_expiration_minutes' => null,
        ]);
        $this->account();

        $this->assertExpiresIn(60, $this->expiryOf($this->login(['remember_me' => true])));
    }

    public function test_a_token_does_not_expire_when_neither_lifetime_is_set(): void
    {
        config([
            'magic-starter.token_expiration_minutes' => null,
            'magic-starter.remember_me_expiration_minutes' => null,
        ]);
        $this->account();

        $this->assertNull($this->expiryOf($this->login(['remember_me' => true])));
        $this->assertNull($this->expiryOf($this->login()));
    }

    public function test_a_zero_lifetime_means_no_expiry(): void
    {
        config([
            'magic-starter.token_expiration_minutes' => 0,
            'magic-starter.remember_me_expiration_minutes' => 0,
        ]);
        $this->account();

        $this->assertNull($this->expiryOf($this->login(['remember_me' => true])));
    }

    public function test_a_remember_me_that_is_not_a_boolean_is_refused(): void
    {
        $this->account();

        $this->login(['remember_me' => 'maybe'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['remember_me']);
    }

    // ------------------------------------------------------------------
    // Two-factor continuation
    // ------------------------------------------------------------------

    public function test_the_two_factor_challenge_carries_remember_me_to_the_token(): void
    {
        config([
            'magic-starter.token_expiration_minutes' => 60,
            'magic-starter.remember_me_expiration_minutes' => 43200,
        ]);

        $this->assertExpiresIn(43200, $this->expiryOf($this->loginThroughTwoFactor(remember: true)));
    }

    public function test_the_two_factor_challenge_keeps_a_login_that_is_not_remembered_short(): void
    {
        config([
            'magic-starter.token_expiration_minutes' => 60,
            'magic-starter.remember_me_expiration_minutes' => 43200,
        ]);

        $this->assertExpiresIn(60, $this->expiryOf($this->loginThroughTwoFactor(remember: false)));
    }

    public function test_a_challenge_token_minted_without_the_flag_is_not_remembered(): void
    {
        config([
            'magic-starter.token_expiration_minutes' => 60,
            'magic-starter.remember_me_expiration_minutes' => 43200,
        ]);
        $provider = app(TwoFactorAuthenticationProvider::class);
        $secret = $provider->engine->generateSecretKey();
        $user = $this->account(twoFactor: true, secret: $secret);

        // The payload a release before `remember_me` minted.
        $challengeToken = encrypt(json_encode([
            'user_id' => $user->getKey(),
            'expires_at' => now()->addMinutes(5)->timestamp,
        ]));

        $response = $this->postJson('/auth/two-factor-challenge', [
            'two_factor_token' => $challengeToken,
            'code' => $provider->engine->getCurrentOtp($secret),
        ]);

        $this->assertExpiresIn(60, $this->expiryOf($response));
    }

    // ------------------------------------------------------------------
    // The other sign-in paths
    // ------------------------------------------------------------------

    public function test_registration_lasts_the_token_lifetime(): void
    {
        config(['magic-starter.token_expiration_minutes' => 60]);

        $response = $this->postJson('/auth/register', [
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertExpiresIn(60, $this->expiryOf($response));
    }

    public function test_an_otp_sign_in_lasts_the_token_lifetime(): void
    {
        config(['magic-starter.token_expiration_minutes' => 60]);
        AuthTokenLifetimeTestUser::create([
            'phone' => '+14155552671',
        ]);
        Cache::put('otp_+14155552671', '123456', 300);

        $response = $this->postJson('/auth/otp/verify', [
            'phone' => '+14155552671',
            'code' => '123456',
        ]);

        $this->assertExpiresIn(60, $this->expiryOf($response));
    }

    public function test_a_guest_sign_in_lasts_the_token_lifetime(): void
    {
        config(['magic-starter.token_expiration_minutes' => 60]);

        $response = $this->postJson('/auth/guest', [
            'device_id' => 'lifetime-device-001',
        ]);

        $this->assertExpiresIn(60, $this->expiryOf($response));
    }
}

final class AuthTokenLifetimeTestUser extends \Illuminate\Foundation\Auth\User
{
    use \AnilcanCakir\MagicStarter\Support\ConditionallyUsesUuids;
    use \AnilcanCakir\MagicStarter\Traits\HasGuestSupport;
    use \AnilcanCakir\MagicStarter\Traits\HasProfilePhoto;
    use \AnilcanCakir\MagicStarter\Traits\HasTeams;
    use \AnilcanCakir\MagicStarter\Traits\TwoFactorAuthenticatable;
    use \Laravel\Sanctum\HasApiTokens;

    protected $table = 'users';

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_guest' => 'boolean',
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
