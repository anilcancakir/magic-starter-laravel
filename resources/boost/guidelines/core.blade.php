{{-- magic-starter-laravel AI guidelines --}}
{{-- Source: anilcancakir/magic-starter-laravel --}}

## magic-starter-laravel

An API-only Laravel package for auth, teams, two-factor, profiles, sessions, notifications, locale and timezone, newsletter, OTP, billing and release notes, with an optional Filament admin panel and audit trail. Feature flags, contract-action bindings and runtime model resolution drive all of it, so an app changes behaviour through config and bindings rather than by naming package classes.

### Rules

| Rule | What to do, and why |
|------|---------------------|
| Model resolution | Use `MagicStarter::userModel()`, `::teamModel()`, `::membershipModel()` and `::teamInvitationModel()` rather than `App\Models\*`, because an app swaps each through config, a runtime `use*Model()` or a published model. |
| Feature gates | Check `Features::enabled()` or `Features::has*()` before feature logic, because a feature's routes, tables and fields exist only while it is listed in `magic-starter.features`. |
| Contract-action | Resolve actions through their contract (`app(Contract::class)` or injection), not `new Action`, because an override only replaces container resolution. |
| Thin controllers | Keep business logic in the action behind the contract, so an override changes it everywhere the contract is called. |
| UUID migrations | Use `MigrationHelper::primaryKey($table)`, `MigrationHelper::foreignKey($table, 'col')` and `MigrationHelper::morphColumns($table, 'name')` instead of `$table->id()`, `$table->uuid()` or `$table->morphs()`, because `use_uuids` switches every key type. |
| Overriding actions | Bind with `$this->app->singleton(Contract::class, CustomAction::class)` in `AppServiceProvider::register()`, not in `boot()`, so the binding is in place before any provider's `boot()` resolves the contract. |
| Phone numbers | Store E.164 (`+[country][number]`) and validate with the `new E164Phone` rule rather than a regex string, so every endpoint accepts the same format. |
| API resources | Wrap feature-conditional fields in `$this->when(Features::has*(), fn () => ...)`, so a disabled feature's fields disappear from the payload. |

### Architecture at a Glance

| Layer | Convention | Concrete Example |
|-------|-----------|-----------------|
| Model resolution | `MagicStarter::*Model()` static registry | `MagicStarter::userModel()`, `::teamModel()` |
| Feature checks | `Features::has*()` / `Features::enabled(string)` | `Features::hasTeamFeatures()`, `Features::enabled('sessions')` |
| Action contracts | `Contracts/` interface, `Actions/` implementation | `CreatesUsers` to `CreateUser` |
| Auth identity | `Features::emailIdentity()` / `::phoneIdentity()` | Reads `magic-starter.auth.email` and `magic-starter.auth.phone` |
| Validation | `Validator::make($input, $rules)->validate()` inside the action | So a direct call is validated too; the controller's FormRequest does not replace it |
| Migration PK | `MigrationHelper::primaryKey($table)` | UUID or int from `magic-starter.use_uuids` |
| Migration FK | `MigrationHelper::foreignKey($table, 'user_id')->constrained()->cascadeOnDelete()` | Matches the key type automatically |
| Morph columns | `MigrationHelper::morphColumns($table, 'notifiable')` | `uuidMorphs` or `morphs` from config |
| Team membership | `->using(MagicStarter::membershipModel())->withPivot('role')` | The role lives on the pivot |
| Password reset URL | Set in `MagicStarterServiceProvider::boot()` from `magic-starter.frontend_url` | Configure the URL; do not register a second one |

### Available Feature Flags

Each flag is a static method on `Features` returning its string; list it in `config('magic-starter.features')` to enable it. `src/Features.php` holds the full list.

| Method | String value | `has*()` check |
|--------|-------------|----------------|
| `Features::teams()` | `'teams'` | `Features::hasTeamFeatures()` |
| `Features::profilePhotos()` | `'profile-photos'` | `Features::hasProfilePhotoFeatures()` |
| `Features::sessions()` | `'sessions'` | `Features::hasSessionFeatures()` |
| `Features::socialLogin()` | `'social-login'` | `Features::hasSocialLoginFeatures()` |
| `Features::twoFactorAuthentication()` | `'two-factor-authentication'` | `Features::hasTwoFactorAuthenticationFeatures()` |
| `Features::extendedProfile()` | `'extended-profile'` | `Features::hasExtendedProfileFeatures()` |
| `Features::notifications()` | `'notifications'` | `Features::hasNotificationFeatures()` |
| `Features::guestAuth()` | `'guest-auth'` | `Features::hasGuestAuthFeatures()` |
| `Features::phoneOtp()` | `'phone-otp'` | `Features::hasPhoneOtpFeatures()` |
| `Features::emailVerification()` | `'email-verification'` | `Features::hasEmailVerificationFeatures()` |
| `Features::newsletterSubscription()` | `'newsletter-subscription'` | `Features::hasNewsletterSubscriptionFeatures()` |
| `Features::timezones()` | `'timezones'` | `Features::hasTimezoneFeatures()` |
| `Features::billing()` | `'billing'` | `Features::hasBillingFeatures()` |
| `Features::audit()` | `'audit'` | `Features::hasAuditFeatures()` |
| `Features::releases()` | `'releases'` | `Features::hasReleaseFeatures()` |

### Contract Bindings (defaults, all overridable)

`MagicStarterServiceProvider::register()` binds them with `$this->app->bind()`; among them:

- `CreatesUsers`: `CreateUser`
- `UpdatesUserProfiles`: `UpdateUserProfile`
- `UpdatesUserPasswords`: `UpdateUserPassword`
- `DeletesUsers`: `DeleteUser`; `SchedulesUserDeletion`: `ScheduleUserDeletion`
- `CreatesTeams`: `CreateTeam`; `UpdatesTeams`: `UpdateTeam`; `DeletesTeams`: `SubscriptionGuardedDeleteTeam` (wraps `DeleteTeam` with the billing guard)
- `AddsTeamMembers`: `AddTeamMember`; `RemovesTeamMembers`: `RemoveTeamMember`
- `InvitesTeamMembers`: `InviteTeamMember`; `AcceptsTeamInvitations`: `AcceptTeamInvitation`; `UpdatesTeamMemberRoles`: `UpdateTeamMemberRole`
- `TransfersTeamOwnership`: `TransferTeamOwnership`; `CancelsTeamInvitations`: `CancelTeamInvitation`; `ResendsTeamInvitations`: `ResendTeamInvitation`
- `CancelsUserDeletion`: `CancelUserDeletion`; `RevokesApiTokens`: `RevokeApiTokens`
- `ReleasesUserBilling`: `ReleaseUserBilling`
- `CreatesGuestUsers`: `CreateGuestUser`
- `SendsOtpCodes`: `LogOtpProvider`; `VerifiesOtpCodes`: `CacheOtpVerifier`
- `EnablesTwoFactorAuthentication`, `ConfirmsTwoFactorAuthentication`, `DisablesTwoFactorAuthentication`, `GeneratesNewRecoveryCodes`: the action of the same stem

### Admin Panel and Audit

Both are optional. Read `references/admin-panel.md` and `references/audit.md` of the `magic-starter-development` skill before touching either.

| Rule | What to do, and why |
|------|---------------------|
| Install | `php artisan magic-starter:filament:install`, then add `implements FilamentUser` and `use AuthorizesAdminPanel` to the user model and set `MAGIC_STARTER_ADMIN_EMAILS`. The user model also needs `MustVerifyEmail`, because the gate checks a verified address. |
| Plugin options | Set `userResource()`, `teamResource()`, `resource()`, `withoutResources()`, `authorizeUsing()`, `horizon()`, `pulse()` and `telescope()` before passing the plugin to `->plugin()`, because `register()` runs at that moment and ignores later setters. |
| Admin writes | Write through a package contract: `ContractAction::run()` for actions, `WritesThroughContracts` plus `updateRecordUsing()` / `createRecordUsing()` for pages. A stock `DeleteAction`, `EditAction` or `CreateAction` needs `->using()`, and `$record->update()` is off limits, because it skips the contract's rules and the audit event. |
| New app resource | Extend `MagicStarterResource` and register it with `->resource('key', Class::class)`. Cover the app's `app/Filament` with `AssertsAdminWritesUseContracts::assertAdminWritesUseContracts()`. |
| Customizing a package resource | Run `php artisan magic-starter:filament:eject <Name>` and edit the copy, because an edit under `vendor/` is lost on the next update. |
| Out of bounds | No tenancy, and no bulk delete on Users or Teams. |
| Ops tools | Mount Horizon, Pulse and Telescope on the admin host with their `*_DOMAIN` and `*_PATH` env keys, never with a path of `''`, because the panel owns the host's root. |
| Audit | `Features::audit()` records every Eloquent create, update and delete after commit. Extend `audit.exclude` and `audit.redact` rather than replacing the defaults. Use `Auditor::record()` for an explicit event and `Auditor::withoutAuditing()` to silence the listener. |
| Audit gaps | Query-builder bulk writes and pivot rows are not captured. Deleting a user purges the rows about them and nulls `actor_id` on the rest. |
| Team owner guards | `RemoveTeamMember` and `UpdateTeamMemberRole` refuse the owner. A replacement binding has to keep the guard, because the admin panel calls the contract directly. |

### Common Gotchas

1. **Guest users**: `email` and `password` may both be `null`; null-check before using either.
2. **Optional trait methods**: check `method_exists($user, 'methodName')` before calling a method from an optional trait (`HasTeams`, `HasGuestSupport`, `TwoFactorAuthenticatable`), because the app's user model may not use it.
3. **Stale Composer classmap**: `resolveConcreteModel()` wraps `class_exists()` in a try/catch and falls back to the package model, so run `composer dump-autoload` after publishing or deleting model stubs.
4. **Team membership is a pivot model**: give every team `BelongsToMany` the table and keys (`'team_user', 'user_id', 'team_id'`) and `->using(MagicStarter::membershipModel())`, as `HasTeams::teams()` does; a plain pivot loses the role, and a renamed model derives another table name.
5. **Sanctum migrations are ignored**: the package calls `Sanctum::ignoreMigrations()` and ships its own `personal_access_tokens` table with device columns (`ip_address`, `user_agent`), so do not run Sanctum's own migration as well.
6. **Password reset URL**: the package sets it with `ResetPassword::createUrlUsing()` in its `boot()`. A second `createUrlUsing()` in the app replaces it without a warning, so set `magic-starter.frontend_url` instead.
7. **Request user resolver**: a controller that issues a token and serializes the user in the same response has to set the user resolver itself; `AuthenticatesUsers::authenticatedResponse()` does it on both `$request` and `app('request')`.
8. **Model config source order**: `userModel()` resolves `MagicStarter::useUserModel()`, then `magic-starter.models.user`, then `auth.providers.users.model`. The other models read only their own key, and a published `App\Models\Team`, `TeamUser` or `TeamInvitation` replaces the package default.

### Overriding Actions

@verbatim
<code-snippet name="Override a contract action in AppServiceProvider" lang="php">
// app/Providers/AppServiceProvider.php: register(), not boot()
public function register(): void
{
    $this->app->singleton(
        \AnilcanCakir\MagicStarter\Contracts\CreatesUsers::class,
        \App\Actions\CustomCreateUser::class,
    );
}
</code-snippet>
@endverbatim

### Config Keys Reference

| Key | Default | Purpose |
|-----|---------|---------|
| `magic-starter.use_uuids` | `true` | UUID or integer primary keys (affects every migration) |
| `magic-starter.features` | `[]` | Active feature flags |
| `magic-starter.auth.email` | `true` | Email identity enabled |
| `magic-starter.auth.phone` | `false` | Phone identity enabled |
| `magic-starter.models.user` | `MAGIC_STARTER_USER_MODEL`, else `auth.providers.users.model` | User model class |
| `magic-starter.models.team` | `Team::class` | Team model class |
| `magic-starter.models.membership` | `TeamUser::class` | Pivot model class |
| `magic-starter.models.team_invitation` | `TeamInvitation::class` | Invitation model class |
| `magic-starter.route_prefix` | `'api/v1'` | Route prefix for every package endpoint |
| `magic-starter.token_expiration_minutes` | `null` | Lifetime in minutes of every issued token; with both keys null a token has no expiry of its own (`sanctum.expiration` still applies) |
| `magic-starter.remember_me_expiration_minutes` | `null` | Replaces it for a password sign-in with `remember_me: true` (two-factor included); null falls back |
| `magic-starter.two_factor.challenge_token_ttl` | `5` | Two-factor challenge window in minutes |
| `magic-starter.admin.emails` | `[]` | Emails admitted to the admin panel (`MAGIC_STARTER_ADMIN_EMAILS`); empty admits nobody |
| `magic-starter.admin.host` | `null` | Host the generated admin panel answers on (`MAGIC_STARTER_ADMIN_HOST`) |
| `magic-starter.audit.exclude` | `[PersonalAccessToken::class]` | Model classes never audited |
| `magic-starter.audit.redact` | `[]` | Attribute names stored as `[redacted]`, added to the built-in list |
| `magic-starter.audit.retention_days` | `365` | Age in days past which `magic-starter:audit:prune` deletes a row |
