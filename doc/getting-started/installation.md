# Installation

How to install Magic Starter Laravel, the feature-gated JSON API backend for the Magic Starter Flutter app, into a Laravel application.

- [Quick Reference](#quick-reference)
- [Introduction](#introduction)
- [Requirements](#requirements)
- [Installing the Package](#installing-the-package)
- [Service Provider Auto-Discovery](#service-provider-auto-discovery)
- [Running the Install Command](#running-the-install-command)
- [Frontend URL for Non-Localhost Deployments](#frontend-url-non-localhost)
- [Publishable Assets](#publishable-assets)
- [User Model Setup](#user-model-setup)
- [Binding Action Contracts](#binding-action-contracts)
- [Gotchas](#gotchas)
- [Related Documentation](#related-documentation)

<a name="quick-reference"></a>
## Quick Reference

```bash
composer require anilcancakir/magic-starter-laravel
php artisan magic-starter:install
php artisan migrate
```

| Step | What you do | Details |
|:-----|:------------|:--------|
| Require | `composer require anilcancakir/magic-starter-laravel`; the service provider registers itself. | [Installing the Package](#installing-the-package) |
| Install | `php artisan magic-starter:install` publishes the config, the migrations for the features you pick, and the stubs. | [Running the Install Command](#running-the-install-command) |
| Frontend URL | Set `MAGIC_STARTER_FRONTEND_URL` when the frontend host or scheme differs from `APP_URL`. | [Frontend URL for Non-Localhost Deployments](#frontend-url-non-localhost) |
| User model | Add `HasApiTokens`, and `HasUuids` when `use_uuids` is `true`, plus the package traits. | [User Model Setup](#user-model-setup) |
| Customise | Bind your own class to a contract in `AppServiceProvider`. | [Binding Action Contracts](#binding-action-contracts) |

<a name="introduction"></a>
## Introduction

Magic Starter Laravel is a modular backend package that provides authentication, team management, user profiles, session tracking, notifications, and more for Laravel applications. Inspired by Jetstream's architecture, it uses a contract-action pattern where every piece of business logic is bound through an interface, making every default replaceable without touching vendor code.

The package ships feature-gated routes, migrations, and resources: you enable only the modules your application needs via a single configuration file.

<a name="requirements"></a>
## Requirements

| Dependency | Version |
|:-----------|:--------|
| PHP | ^8.2 |
| Laravel (`illuminate/*`) | ^12.32 \| ^13.0 |
| `ext-gd`, `ext-exif` | `*` |
| Laravel Sanctum | ^4.0 |
| Laravel Socialite | ^5.0 (bundled) |
| socialiteproviders/apple | ^6.1 (bundled) |
| socialiteproviders/microsoft | ^4.9 (bundled) |
| firebase/php-jwt | ^7.0 (bundled) |
| pragmarx/google2fa | ^8.0 \| ^9.0 |
| bacon/bacon-qr-code | ^3.0 |

> [!TIP]
> For IP geolocation in session management, the optional `geoip2/geoip2` package is suggested but not required.

<a name="installing-the-package"></a>
## Installing the Package

Install the package via Composer:

```bash
composer require anilcancakir/magic-starter-laravel
```

<a name="service-provider-auto-discovery"></a>
## Service Provider Auto-Discovery

The package registers `AnilcanCakir\MagicStarter\MagicStarterServiceProvider` automatically through Laravel's package auto-discovery mechanism (via `extra.laravel.providers` in the package's `composer.json`). No manual provider registration is needed.

The service provider handles:

- **Configuration merging**: merges the package config under the `magic-starter` key.
- **Action contract binding**: binds all action interfaces to default implementations (see [Binding Action Contracts](#binding-action-contracts)).
- **Sanctum setup**: uses a custom `PersonalAccessToken` model with device info columns and disables Sanctum's default migrations.
- **Password reset URL**: customizes the reset link to point at your frontend URL from config.
- **Rate limiting**: registers named rate limiters for all package endpoints (auth, 2FA, OTP, guest, settings, email verification).
- **Event listeners**: auto-creates a personal team on user registration (when teams enabled) and gates notification channels (when notifications enabled).
- **Route loading**: loads the package's API routes unless explicitly ignored via `MagicStarter::ignoreRoutes()`.

<a name="running-the-install-command"></a>
## Running the Install Command

The package ships an Artisan command that publishes configuration and feature-relevant migrations:

```bash
php artisan magic-starter:install
```

### Interactive Mode

When run without flags, the command uses [Laravel Prompts](https://laravel.com/docs/prompts) to walk you through setup:

1. **Features**: a `multiselect` prompt with every feature pre-selected.
2. **Route prefix**: a `text` input (defaults to `api/v1`).
3. **Frontend URL**: a `text` input (defaults to `http://localhost:3000`), used for email verification, password reset, and other email links. **Important:** Set this to your frontend base URL when its host or scheme differs from `APP_URL` (see [Frontend URL](#frontend-url-non-localhost) below).
4. **Run migrations**: a `confirm` prompt to run `php artisan migrate` immediately.

UUID vs. integer primary keys are auto-detected from your existing `users` table schema. If no `users` table exists (fresh install), UUID is used by default.

### Non-Interactive Mode

Pass flags to skip all prompts. Useful in CI/CD pipelines:

```bash
php artisan magic-starter:install \
    --all \
    --uuid \
    --route-prefix=api/v1 \
    --frontend-url=https://app.example.com
```

### Available Options

| Option | Description |
|:-------|:------------|
| `--all` | Install all features without prompting |
| `--features=*` | Features to install (comma-separated, repeatable) |
| `--uuid` | Use UUID primary keys |
| `--no-uuid` | Use auto-incrementing integer primary keys |
| `--route-prefix=` | Route prefix for package routes |
| `--frontend-url=` | Frontend application URL for email links |
| `--force` | Overwrite existing published files |

The `--features` option accepts: `two-factor-authentication`, `teams`, `profile-photos`, `sessions`, `social-login`, `newsletter-subscription`, `analytics-preference`, `extended-profile`, `notifications`, `guest-auth`, `phone-otp`, `email-verification`, `timezones`, `billing`, `audit`, `support`, `releases`, `campaigns`, `ai-runs`, `ai-prompts`. A name outside that list is dropped silently.

When `--all` is passed, every feature is enabled regardless of `--features`. With neither flag and no terminal to prompt on, every feature is enabled as well.

> [!NOTE]
> When neither `--uuid` nor `--no-uuid` is provided, the installer auto-detects your existing `users` table schema. If no `users` table exists (fresh install), UUID is used by default.

<a name="frontend-url-non-localhost"></a>
## Frontend URL for Non-Localhost Deployments

The backend signs email links (verification, password reset, and other email links) using `APP_URL` as the base. When your email links should open a frontend whose host or scheme differs from `APP_URL`, configure `frontend_url` to rewrite the link base to your frontend URL. Without it, email links point at the backend host (e.g. `https://api.example.com/email/verify/{id}/{hash}`) instead of opening the intended frontend app or deep link.

**Solution:** Configure the `frontend_url` setting to point to your frontend's base URL. This rewrites the base for all package email links.

### Setting the Frontend URL

You can provide the frontend URL in three ways:

1. **During install with the flag:**
   ```bash
   php artisan magic-starter:install --all --frontend-url=https://app.example.com
   ```

2. **During interactive install:**
   When prompted for "Frontend URL", enter your frontend app URL (e.g. `https://app.example.com`).

3. **Via environment variable:**
   Set `MAGIC_STARTER_FRONTEND_URL` in your `.env`:
   ```env
   MAGIC_STARTER_FRONTEND_URL=https://app.example.com
   ```

### Localhost Development

For localhost development (backend at `http://localhost:8000`, frontend at `http://localhost:3000`), the hosts differ so you should keep `frontend_url` set. The installer defaults the prompt to `http://localhost:3000`, which covers this common case:

```bash
php artisan magic-starter:install --all --frontend-url=http://localhost:3000
```

> [!IMPORTANT]
> Set `MAGIC_STARTER_FRONTEND_URL` (or `--frontend-url`) whenever your frontend's host or scheme differs from `APP_URL`. `APP_URL` sets the backend base and is correct for API routes, but email links need the frontend base. A backend and frontend sharing the same origin do not need a separate `frontend_url`.

<a name="publishable-assets"></a>
## Publishable Assets

The install command publishes the config, the migrations, the translations, a factory and the `User` model stub. Action stubs are **not** published by default: the package ships with working implementations out of the box.

### Published by the Install Command

| Asset | Destination | Publish Tag |
|:------|:------------|:------------|
| Config | `config/magic-starter.php` | `magic-starter-config` |
| Migrations | `database/migrations/` | `magic-starter-migrations` |
| Model stubs | `app/Models/` (only when the teams feature is selected) | `magic-starter-models` |
| Policy stubs | `app/Policies/` (only when the teams feature is selected) | `magic-starter-policies` |
| User model stub | `app/Models/User.php` | `magic-starter-user-model` |
| Translations | `lang/vendor/magic-starter/` | `magic-starter-lang` |
| User factory | `database/factories/UserFactory.php` | `magic-starter-factories` |

### Published Manually

To customize default implementations, publish the action stubs with `vendor:publish`. Any other tag above can be published the same way when you skipped the install command.

| Asset | Destination | Publish Tag |
|:------|:------------|:------------|
| Action stubs | `app/Actions/MagicStarter/` | `magic-starter-stubs` |

```bash
php artisan vendor:publish --tag=magic-starter-stubs
```

<a name="user-model-setup"></a>
## User Model Setup

Add the relevant traits to your `User` model. `HasApiTokens` (from Sanctum) is required for token authentication:

```php
use AnilcanCakir\MagicStarter\Traits\HasTeams;
use AnilcanCakir\MagicStarter\Traits\HasProfilePhoto;
use AnilcanCakir\MagicStarter\Traits\HasSocialAccounts;
use AnilcanCakir\MagicStarter\Traits\NormalizesEmail;
use AnilcanCakir\MagicStarter\Traits\HasNotifications;
use AnilcanCakir\MagicStarter\Traits\TwoFactorAuthenticatable;
use AnilcanCakir\MagicStarter\Traits\HasGuestSupport;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasUuids;
    use HasTeams;
    use HasProfilePhoto;
    use HasSocialAccounts;
    use NormalizesEmail;
    use HasNotifications;          // when notifications feature is enabled
    use TwoFactorAuthenticatable;  // when two-factor-authentication feature is enabled
    use HasGuestSupport;           // when guest-auth feature is enabled

    protected $appends = [
        'profile_photo_url',
    ];
}
```

### Required Traits

| Trait | Source | Purpose |
|:------|:-------|:--------|
| `HasApiTokens` | `laravel/sanctum` | Token-based API authentication |
| `HasUuids` | `illuminate/database` | UUID primary keys (only when `use_uuids` is `true`) |
| `HasTeams` | `AnilcanCakir\MagicStarter\Traits` | Team membership, current team, personal team |
| `HasProfilePhoto` | `AnilcanCakir\MagicStarter\Traits` | Profile photo URL, `null` when none is stored |
| `HasSocialAccounts` | `AnilcanCakir\MagicStarter\Traits` | The `socialAccounts()` relation and `hasPassword()`, for social login and account deletion |
| `NormalizesEmail` | `AnilcanCakir\MagicStarter\Traits` | Lower-cases an email on assignment, so `Bob@x.io` and `bob@x.io` cannot become two accounts |

### Optional Traits

| Trait | Source | When to Add |
|:------|:-------|:------------|
| `HasNotifications` | `AnilcanCakir\MagicStarter\Traits` | `notifications` feature is enabled |
| `TwoFactorAuthenticatable` | `AnilcanCakir\MagicStarter\Traits` | `two-factor-authentication` feature is enabled |
| `HasGuestSupport` | `AnilcanCakir\MagicStarter\Traits` | `guest-auth` feature is enabled |

> [!IMPORTANT]
> When `use_uuids` is `true` (the default for fresh installs), you **must** add the `HasUuids` trait to your User model. Without it, user creation will fail with a `NOT NULL constraint` error on the `id` column. If you opted for auto-incrementing integers (`--no-uuid`), omit `HasUuids`.

<a name="binding-action-contracts"></a>
## Binding Action Contracts

The service provider binds all action contracts to default implementations: **no manual binding is required** for the package to work out of the box.

To customize behavior, publish the action stubs and override the bindings in your `AppServiceProvider`:

```bash
php artisan vendor:publish --tag=magic-starter-stubs
```

```php
use AnilcanCakir\MagicStarter\Contracts\CreatesUsers;
use AnilcanCakir\MagicStarter\Contracts\CreatesTeams;
use AnilcanCakir\MagicStarter\Contracts\UpdatesTeams;
use AnilcanCakir\MagicStarter\Contracts\DeletesTeams;
use AnilcanCakir\MagicStarter\Contracts\UpdatesUserProfiles;
use AnilcanCakir\MagicStarter\Contracts\UpdatesUserPasswords;
use AnilcanCakir\MagicStarter\Contracts\DeletesUsers;
use AnilcanCakir\MagicStarter\Contracts\AddsTeamMembers;
use AnilcanCakir\MagicStarter\Contracts\InvitesTeamMembers;
use AnilcanCakir\MagicStarter\Contracts\RemovesTeamMembers;
use AnilcanCakir\MagicStarter\Contracts\UpdatesTeamMemberRoles;

public function register(): void
{
    $this->app->bind(CreatesUsers::class, \App\Actions\MagicStarter\CreateUser::class);
    $this->app->bind(CreatesTeams::class, \App\Actions\MagicStarter\CreateTeam::class);
    $this->app->bind(UpdatesTeams::class, \App\Actions\MagicStarter\UpdateTeam::class);
    $this->app->bind(DeletesTeams::class, \App\Actions\MagicStarter\DeleteTeam::class);
    $this->app->bind(UpdatesUserProfiles::class, \App\Actions\MagicStarter\UpdateUserProfile::class);
    $this->app->bind(UpdatesUserPasswords::class, \App\Actions\MagicStarter\UpdateUserPassword::class);
    $this->app->bind(DeletesUsers::class, \App\Actions\MagicStarter\DeleteUser::class);
    $this->app->bind(AddsTeamMembers::class, \App\Actions\MagicStarter\AddTeamMember::class);
    $this->app->bind(InvitesTeamMembers::class, \App\Actions\MagicStarter\InviteTeamMember::class);
    $this->app->bind(RemovesTeamMembers::class, \App\Actions\MagicStarter\RemoveTeamMember::class);
    $this->app->bind(UpdatesTeamMemberRoles::class, \App\Actions\MagicStarter\UpdateTeamMemberRole::class);
}
```

> [!NOTE]
> Published action stubs throw `RuntimeException` by default. You must implement the business logic in each action class before deploying.

<a name="gotchas"></a>
## Gotchas

| Mistake | Fix |
|:--------|:----|
| Creating users with `use_uuids` on and no `HasUuids` on the `User` model | Add `HasUuids`, or install with `--no-uuid`. Without it user creation fails with a `NOT NULL` error on `id`. |
| Leaving `frontend_url` unset when the frontend host differs from `APP_URL` | Set `MAGIC_STARTER_FRONTEND_URL`, or the email links point at the backend host. |
| Publishing the action stubs and deploying | A published stub throws `RuntimeException` until you implement it. |
| Passing a misspelt name to `--features` | The name is dropped without a warning. Check the summary the command prints. |
| Re-running `magic-starter:install` to refresh published files | Existing files are kept. Pass `--force` to overwrite them. |

<a name="related-documentation"></a>
## Related Documentation

- [Configuration](./configuration.md): feature flags, identity strategy, model overrides and UUID settings.
- [Authentication](../basics/authentication.md): login, registration, password reset and the other sign-in flows.
- [Teams](../basics/teams.md): team management, invitations and member roles.
- [Action Contracts](../architecture/action-contracts.md): every contract you can bind your own class to.
