<?php

namespace AnilcanCakir\MagicStarter\Tests\Http\Controllers;

use AnilcanCakir\MagicStarter\Features;
use AnilcanCakir\MagicStarter\Http\Controllers\AuthController;
use AnilcanCakir\MagicStarter\Http\Controllers\Concerns\FindsRecords;
use AnilcanCakir\MagicStarter\Http\Controllers\TeamController;
use AnilcanCakir\MagicStarter\Http\Controllers\TeamInvitationController;
use AnilcanCakir\MagicStarter\Http\Controllers\TeamMemberController;
use AnilcanCakir\MagicStarter\Http\Controllers\TeamPhotoController;
use AnilcanCakir\MagicStarter\Policies\TeamPolicy;
use AnilcanCakir\MagicStarter\Tests\Fixtures\BuildsRestorableTeamSchema;
use AnilcanCakir\MagicStarter\Tests\Fixtures\RestorableTeam;
use AnilcanCakir\MagicStarter\Tests\TestCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * An id in a team route that cannot be a uuid is a missing record, answered 404 before any query.
 *
 * PostgreSQL rejects a malformed value against a uuid column, so a lookup that reached the query ended in a 500
 * with the SQL in the body. SQLite accepts the value and finds nothing, so these tests read the query log: the
 * malformed id must never be bound to a statement.
 */
final class MalformedKeyTest extends TestCase
{
    use BuildsRestorableTeamSchema;

    private const MALFORMED = 'not-a-uuid';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'magic-starter.use_uuids' => true,
            'magic-starter.features' => [Features::teams(), Features::restorableTeams()],
        ]);

        $this->buildRestorableTeamSchema();
        Gate::policy(RestorableTeam::class, TeamPolicy::class);

        $router = $this->app['router'];
        $router->get('/teams/{team}', [TeamController::class, 'show']);
        $router->put('/teams/{team}', [TeamController::class, 'update']);
        $router->delete('/teams/{team}', [TeamController::class, 'destroy']);
        $router->post('/teams/{team}/restore', [TeamController::class, 'restore']);
        $router->get('/teams/{team}/invitations', [TeamInvitationController::class, 'index']);
        $router->delete('/teams/{team}/invitations/{invitation}', [TeamInvitationController::class, 'destroy']);
        $router->get('/teams/{team}/members', [TeamMemberController::class, 'index']);
        $router->put('/teams/{team}/members/{user}', [TeamMemberController::class, 'update']);
        $router->delete('/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy']);
        $router->delete('/teams/{team}/leave', [TeamMemberController::class, 'leave']);
        $router->delete('/teams/{team}/profile-photo', [TeamPhotoController::class, 'delete']);
        $router->put('/user/current-team', [AuthController::class, 'switchTeam']);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function routesWithAMalformedId(): array
    {
        return [
            'show a team' => ['GET', '/teams/{bad}'],
            'update a team' => ['PUT', '/teams/{bad}'],
            'delete a team' => ['DELETE', '/teams/{bad}'],
            'restore a team' => ['POST', '/teams/{bad}/restore'],
            'list invitations' => ['GET', '/teams/{bad}/invitations'],
            'cancel an invitation of a missing team' => ['DELETE', '/teams/{bad}/invitations/{bad}'],
            'list members' => ['GET', '/teams/{bad}/members'],
            'leave a team' => ['DELETE', '/teams/{bad}/leave'],
            'remove the photo of a team' => ['DELETE', '/teams/{bad}/profile-photo'],
            'cancel a malformed invitation' => ['DELETE', '/teams/{team}/invitations/{bad}'],
            'update a malformed member' => ['PUT', '/teams/{team}/members/{bad}'],
            'remove a malformed member' => ['DELETE', '/teams/{team}/members/{bad}'],
        ];
    }

    #[DataProvider('routesWithAMalformedId')]
    public function test_a_malformed_id_is_not_found_without_a_query(string $method, string $template): void
    {
        $owner = $this->makeUser();
        $team = $this->makeTeam($owner);
        $path = str_replace(['{team}', '{bad}'], [(string) $team->getKey(), self::MALFORMED], $template);

        DB::enableQueryLog();

        $this->actingAs($owner)->json($method, $path, ['name' => 'Renamed', 'role' => 'member'])->assertNotFound();

        $this->assertNothingBoundTo(self::MALFORMED);
    }

    public function test_switching_to_a_malformed_team_id_never_queries_it(): void
    {
        $owner = $this->makeUser();
        $this->makeTeam($owner);

        DB::enableQueryLog();

        $this->actingAs($owner)->putJson('/user/current-team', ['team_id' => self::MALFORMED])->assertForbidden();

        $this->assertNothingBoundTo(self::MALFORMED);
    }

    public function test_a_well_formed_unknown_id_is_still_not_found(): void
    {
        $owner = $this->makeUser();
        $this->makeTeam($owner);

        $this->actingAs($owner)->getJson('/teams/00000000-0000-7000-8000-000000000000')->assertNotFound();
    }

    public function test_integer_keys_refuse_an_id_that_is_not_a_number_before_the_query(): void
    {
        config(['magic-starter.use_uuids' => false]);
        $finder = new class
        {
            use FindsRecords;

            public function find(string $id): mixed
            {
                return $this->findOrNotFound(RestorableTeam::query(), $id);
            }
        };

        DB::enableQueryLog();

        foreach (['abc', '12abc', '9999999999999999999999'] as $malformed) {
            try {
                $finder->find($malformed);
                $this->fail("'{$malformed}' was found.");
            } catch (ModelNotFoundException $exception) {
                $this->assertSame(RestorableTeam::class, $exception->getModel());
            }
        }

        $this->assertSame([], DB::getQueryLog());
    }

    private function assertNothingBoundTo(string $value): void
    {
        foreach (DB::getQueryLog() as $entry) {
            $this->assertNotContains($value, $entry['bindings'], 'The malformed id reached a query: ' . $entry['query']);
        }
    }
}
