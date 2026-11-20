<?php

namespace AnilcanCakir\MagicStarter\Tests\Http\Controllers;

use AnilcanCakir\MagicStarter\Features;
use AnilcanCakir\MagicStarter\Http\Controllers\AnalyticsPreferenceController;
use AnilcanCakir\MagicStarter\MagicStarter;
use AnilcanCakir\MagicStarter\MagicStarterServiceProvider;
use AnilcanCakir\MagicStarter\Tests\TestCase;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AnalyticsPreferenceControllerTest extends TestCase
{
    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('magic-starter.features', [
            Features::analyticsPreference(),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        MagicStarter::reset();

        \call_user_func('config', ['database.default' => 'testing']);
        \call_user_func('config', ['database.connections.testing' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);
        \call_user_func('config', [
            'magic-starter.models.user' => AnalyticsPreferenceControllerTestUser::class,
        ]);

        // The column is deliberately absent: the migration under test adds it.
        \call_user_func([\call_user_func('app', 'db.schema'), 'create'], 'users', function ($table): void {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

        (require __DIR__ . '/../../../database/migrations/add_analytics_opt_out_to_users_table.php')->up();

        $router = \call_user_func('app', 'router');
        $router->get('/user/analytics', [AnalyticsPreferenceController::class, 'show'])->middleware('auth');
        $router->put('/user/analytics', [AnalyticsPreferenceController::class, 'update'])->middleware('auth');
    }

    protected function tearDown(): void
    {
        MagicStarter::reset();
        parent::tearDown();
    }

    public function test_the_feature_constant_and_check(): void
    {
        $this->assertSame('analytics-preference', Features::analyticsPreference());
        $this->assertTrue(Features::hasAnalyticsPreferenceFeatures());

        config(['magic-starter.features' => []]);

        $this->assertFalse(Features::hasAnalyticsPreferenceFeatures());
    }

    public function test_show_returns_false_by_default(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->getJson('/user/analytics');

        $response->assertStatus(200);
        $response->assertExactJson(['opted_out' => false]);
    }

    public function test_show_reads_false_for_a_null_column(): void
    {
        // An application that owns the column may have declared it nullable.
        Schema::table('users', function ($table): void {
            $table->dropColumn('analytics_opt_out');
        });
        Schema::table('users', function ($table): void {
            $table->boolean('analytics_opt_out')->nullable();
        });
        $user = $this->createUser();

        $response = $this->actingAs($user)->getJson('/user/analytics');

        $response->assertStatus(200);
        $response->assertExactJson(['opted_out' => false]);
    }

    public function test_update_true_is_persisted_and_read_back(): void
    {
        $user = $this->createUser();

        $update = $this->actingAs($user)->putJson('/user/analytics', ['opted_out' => true]);

        $update->assertStatus(200);
        $update->assertExactJson(['opted_out' => true]);
        $this->assertSame(1, (int) DB::table('users')->where('id', $user->getKey())->value('analytics_opt_out'));

        $show = $this->actingAs($user)->getJson('/user/analytics');

        $show->assertStatus(200);
        $show->assertExactJson(['opted_out' => true]);
    }

    public function test_update_false_clears_a_previous_opt_out(): void
    {
        $user = $this->createUser();
        $this->actingAs($user)->putJson('/user/analytics', ['opted_out' => true]);

        $response = $this->actingAs($user)->putJson('/user/analytics', ['opted_out' => false]);

        $response->assertStatus(200);
        $response->assertExactJson(['opted_out' => false]);
        $this->assertSame(0, (int) DB::table('users')->where('id', $user->getKey())->value('analytics_opt_out'));
    }

    public function test_update_does_not_touch_another_user(): void
    {
        $user = $this->createUser();
        $other = $this->createUser('other@example.test');

        $this->actingAs($user)->putJson('/user/analytics', ['opted_out' => true]);

        $this->assertSame(0, (int) DB::table('users')->where('id', $other->getKey())->value('analytics_opt_out'));
    }

    public function test_update_reads_the_boolean_rule_integers_as_booleans(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->putJson('/user/analytics', ['opted_out' => 1]);

        $response->assertStatus(200);
        $response->assertExactJson(['opted_out' => true]);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidPayloads(): array
    {
        return [
            'missing' => [
                [],
            ],
            'null' => [
                ['opted_out' => null],
            ],
            'a string' => [
                ['opted_out' => 'maybe'],
            ],
            'an array' => [
                ['opted_out' => [true]],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidPayloads')]
    public function test_update_returns_422_for_a_missing_or_non_boolean_value(array $payload): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->putJson('/user/analytics', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['opted_out']);
        $this->assertSame(0, (int) DB::table('users')->where('id', $user->getKey())->value('analytics_opt_out'));
    }

    public function test_show_requires_authentication(): void
    {
        $this->getJson('/user/analytics')->assertStatus(401);
    }

    public function test_update_requires_authentication(): void
    {
        $this->putJson('/user/analytics', ['opted_out' => true])->assertStatus(401);
    }

    public function test_routes_are_registered_only_with_the_feature(): void
    {
        $this->bootRoutesWithFeatures([
            Features::analyticsPreference(),
        ]);

        $this->assertNotNull($this->matchRoute('GET', '/user/analytics'));
        $this->assertNotNull($this->matchRoute('PUT', '/user/analytics'));

        $this->bootRoutesWithFeatures([]);

        $this->assertNull($this->matchRoute('GET', '/user/analytics'));
        $this->assertNull($this->matchRoute('PUT', '/user/analytics'));
    }

    private function createUser(string $email = 'analytics@example.test'): AnalyticsPreferenceControllerTestUser
    {
        return AnalyticsPreferenceControllerTestUser::query()->create([
            'name' => 'Analytics User',
            'email' => $email,
        ]);
    }

    /**
     * @param  list<string>  $features
     */
    private function bootRoutesWithFeatures(array $features): void
    {
        config(['magic-starter.features' => $features]);

        $this->app['router']->setRoutes(new RouteCollection);

        (new MagicStarterServiceProvider($this->app))->boot();
    }

    private function matchRoute(string $method, string $uri): mixed
    {
        try {
            return $this->app['router']->getRoutes()->match(Request::create($uri, $method));
        } catch (NotFoundHttpException|MethodNotAllowedHttpException) {
            return null;
        }
    }
}

/**
 * Test user on UUID keys. `analytics_opt_out` is absent from `$fillable`, as it can be on a host's own model.
 */
final class AnalyticsPreferenceControllerTestUser extends Authenticatable
{
    use HasUuids;

    /** @var string */
    protected $table = 'users';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
    ];
}
