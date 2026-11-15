<?php

namespace AnilcanCakir\MagicStarter\Tests\Models;

use AnilcanCakir\MagicStarter\Models\Team;
use AnilcanCakir\MagicStarter\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;

class ModelsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'magic-starter.models.user' => ConfiguredUser::class,
            'magic-starter.models.team' => ConfiguredTeam::class,
            'magic-starter.models.membership' => ConfiguredMembership::class,
            'magic-starter.models.team_invitation' => ConfiguredTeamInvitation::class,
        ]);

        \AnilcanCakir\MagicStarter\MagicStarter::useUserModel(ConfiguredUser::class);
        \AnilcanCakir\MagicStarter\MagicStarter::useTeamModel(ConfiguredTeam::class);
        \AnilcanCakir\MagicStarter\MagicStarter::useMembershipModel(ConfiguredMembership::class);
        \AnilcanCakir\MagicStarter\MagicStarter::useTeamInvitationModel(ConfiguredTeamInvitation::class);
    }

    public function test_team_relationships_resolve_configured_models(): void
    {
        $teamClass = \AnilcanCakir\MagicStarter\MagicStarter::teamModel();
        $team = new $teamClass;

        $owner = $team->owner();
        $users = $team->users();
        $invitations = $team->invitations();

        $this->assertSame(ConfiguredUser::class, $owner->getRelated()::class);
        $this->assertSame(ConfiguredUser::class, $users->getRelated()::class);
        $this->assertSame(ConfiguredMembership::class, $users->getPivotClass());
        $this->assertSame(ConfiguredTeamInvitation::class, $invitations->getRelated()::class);
    }

    public function test_team_uses_casts_method_for_personal_team(): void
    {
        $reflection = new \ReflectionClass(Team::class);

        $declaredProperties = array_map(
            static fn (\ReflectionProperty $property): string => $property->getName(),
            array_filter(
                $reflection->getProperties(),
                static fn (\ReflectionProperty $property): bool => $property->getDeclaringClass()->getName() === Team::class,
            ),
        );

        $this->assertNotContains('casts', $declaredProperties);

        $castsMethod = $reflection->getMethod('casts');
        $teamClass = \AnilcanCakir\MagicStarter\MagicStarter::teamModel();
        $casts = $castsMethod->invoke(new $teamClass);

        $this->assertSame([
            'personal_team' => 'boolean',
            'restore_until' => 'datetime',
        ], $casts);
    }

    public function test_team_invitation_team_relation_uses_configured_team_model(): void
    {
        $invitationClass = \AnilcanCakir\MagicStarter\MagicStarter::teamInvitationModel();
        $relation = (new $invitationClass)->team();

        $this->assertSame(ConfiguredTeam::class, $relation->getRelated()::class);
    }

    public function test_team_user_uses_expected_table_and_uuid_key_shape(): void
    {
        $membershipClass = \AnilcanCakir\MagicStarter\MagicStarter::membershipModel();
        $teamUser = new $membershipClass;

        $this->assertSame('team_user', $teamUser->getTable());
        $this->assertFalse($teamUser->getIncrementing());
        $this->assertSame('string', $teamUser->getKeyType());
    }

    public function test_personal_access_token_uses_uuid_key_shape(): void
    {
        $class = implode('\\', ['AnilcanCakir', 'MagicStarter', 'Models', 'PersonalAccessToken']);

        $this->assertTrue(class_exists($class));

        $token = (new \ReflectionClass($class))->newInstance();

        $this->assertFalse($token->getIncrementing());
        $this->assertSame('string', $token->getKeyType());
        $this->assertTrue(is_subclass_of($class, Model::class));
    }
}

class ConfiguredUser extends \AnilcanCakir\MagicStarter\Tests\Fixtures\ConcreteUser {}

class ConfiguredTeam extends \AnilcanCakir\MagicStarter\Tests\Fixtures\ConcreteTeam {}

class ConfiguredMembership extends \AnilcanCakir\MagicStarter\Tests\Fixtures\ConcreteTeamUser {}

class ConfiguredTeamInvitation extends \AnilcanCakir\MagicStarter\Tests\Fixtures\ConcreteTeamInvitation {}
