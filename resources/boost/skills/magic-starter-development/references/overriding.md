# Overriding and Customization

## Where to Find It

- `src/MagicStarterServiceProvider.php`: `register()` binds every contract to its default action
- `src/Contracts/`: one interface per action; these are the override surface
- `src/MagicStarter.php`: `useUserModel()`, `useTeamModel()`, `useMembershipModel()`, `useTeamInvitationModel()`, `ignoreRoutes()`, `serializeUserUsing()`
- `config/magic-starter.php`: feature flags, model classes, the identity strategy

## What to Watch For

### Override an action with a binding

Call `$this->app->singleton(Contract::class, CustomAction::class)` in `AppServiceProvider::register()`. The package binds its defaults with `bind()` in its own `register()`, and the app's provider registers after it, so the app's binding replaces the default before any `boot()` resolves the contract. Override only what differs and leave the rest on the defaults.

### Override a model through config or at runtime

Set `magic-starter.models.user` (or `MAGIC_STARTER_USER_MODEL`) for the user model; it falls back to `auth.providers.users.model`. The team, membership and team-invitation models use their own `models` keys, and a published `App\Models\Team`, `TeamUser` or `TeamInvitation` is picked up without a config change. A runtime `MagicStarter::useUserModel(CustomUser::class)` (or one of its siblings) wins over config, which makes it the right tool in a test's `setUp()`.

### Replace the package routes

Call `MagicStarter::ignoreRoutes()` in `AppServiceProvider::register()`, before the package boots, and define your own routes. The package's named rate limiters (`magic-starter-auth-login` and the others) are registered either way.

### Publish and edit the config

`php artisan vendor:publish --tag=magic-starter-config` copies the file. Turn a feature on by uncommenting its `Features::*()` line in the `features` array and off by removing it. A flag gates its routes and its resource fields together.

### Prefer the smallest override

Most apps override nothing. Extend a model with your domain relations (a `Team` gaining `statusPages()`, for example) rather than replacing it, and register notification types in `NotificationPreferenceRegistry` from a provider's `boot()`. Override an action when the business rule differs, not to log or decorate; observers and events carry side effects. To expose an extra user column, register it with `MagicStarter::serializeUserUsing()`, which merges its fields into `UserResource`.

## Contract Reference

| Contract | Default action | Method signature |
|---|---|---|
| `CreatesUsers` | `CreateUser` | `create(array $input): Authenticatable` |
| `UpdatesUserProfiles` | `UpdateUserProfile` | `update(Authenticatable $user, array $input): void` |
| `UpdatesUserPasswords` | `UpdateUserPassword` | `update(Authenticatable $user, array $input): void` |
| `DeletesUsers` | `DeleteUser` | `delete(Authenticatable $user): void` |
| `SchedulesUserDeletion` | `ScheduleUserDeletion` | `schedule(Authenticatable $user, bool $orphan = false, bool $immediately = false): array` |
| `CreatesTeams` | `CreateTeam` | `create(Authenticatable $user, array $input): Model` |
| `UpdatesTeams` | `UpdateTeam` | `update(Authenticatable $user, Model $team, array $input): void` |
| `DeletesTeams` | `SubscriptionGuardedDeleteTeam` | `delete(Model $team): void` |
| `AddsTeamMembers` | `AddTeamMember` | `add(Authenticatable $user, Model $team, string $email, string $role): void` |
| `RemovesTeamMembers` | `RemoveTeamMember` | `remove(Authenticatable $user, Model $team, Model $teamMember): void` |
| `InvitesTeamMembers` | `InviteTeamMember` | `invite(Authenticatable $user, Model $team, string $email, string $role): Model` |
| `AcceptsTeamInvitations` | `AcceptTeamInvitation` | `accept(Authenticatable $user, Model $invitation): void` |
| `UpdatesTeamMemberRoles` | `UpdateTeamMemberRole` | `update(Authenticatable $user, Model $team, Model $teamMember, string $role): void` |
| `TransfersTeamOwnership` | `TransferTeamOwnership` | `transfer(Authenticatable $actor, Model $team, Model $newOwner): void` |
| `CancelsTeamInvitations` | `CancelTeamInvitation` | `cancel(Authenticatable $actor, Model $invitation): void` |
| `ResendsTeamInvitations` | `ResendTeamInvitation` | `resend(Authenticatable $actor, Model $invitation): void` |
| `CancelsUserDeletion` | `CancelUserDeletion` | `cancel(Authenticatable $user): bool` |
| `ReleasesUserBilling` | `ReleaseUserBilling` | `release(Authenticatable $user): array` |
| `RevokesApiTokens` | `RevokeApiTokens` | `revoke(Authenticatable $user, ?string $tokenId = null): void` |
| `CreatesGuestUsers` | `CreateGuestUser` | `create(array $input): Authenticatable` |
| `SendsOtpCodes` | `LogOtpProvider` | `send(string $phone, string $code): void` |
| `VerifiesOtpCodes` | `CacheOtpVerifier` | `verify(string $phone, string $code): bool` |
| `EnablesTwoFactorAuthentication` | `EnableTwoFactorAuthentication` | `enable(mixed $user): array` |
| `ConfirmsTwoFactorAuthentication` | `ConfirmTwoFactorAuthentication` | `confirm(mixed $user, string $code): void` |
| `DisablesTwoFactorAuthentication` | `DisableTwoFactorAuthentication` | `disable(mixed $user): void` |
| `GeneratesNewRecoveryCodes` | `GenerateNewRecoveryCodes` | `generate(mixed $user): array` |

`MagicStarterServiceProvider::register()` binds more (social sign-in, sign-in handoff, support, releases, campaigns, billing); read it for the full list.

### Team owner guards live in the actions

`RemoveTeamMember` refuses the owner (`owner_not_removable`, `owner_cannot_leave` when the owner asks to go, or `personal_team_cannot_leave`), and `UpdateTeamMemberRole` refuses to change the owner's role (`owner_role_locked`) or to hand out `owner` (`role_not_assignable`), each as a `ValidationException` carrying that `code`. The HTTP API calls the public static `ensureRemovable()` and `ensureAssignable()` itself before the contract and maps a refusal to 403, so the API holds whatever you bind. The admin panel and `PurgeDeletedUsersCommand` call only the contracts, so a replacement of either action has to keep the guard: call the static helper from your override. Ownership itself moves only through `TransfersTeamOwnership`.
