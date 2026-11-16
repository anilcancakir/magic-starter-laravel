<p align="center">
  <img src="https://raw.githubusercontent.com/anilcancakir/magic-starter-laravel/master/.github/magic-logo.svg" width="120" alt="Magic Starter Laravel logo" />
</p>

<h1 align="center">Magic Starter Laravel</h1>

<p align="center">
  <strong>Ship the API behind sign-in, settings and teams without writing the controllers.</strong><br/>
  A Laravel package that serves authentication, two-factor, social sign-in, profile, teams, notifications, billing and a support desk as Sanctum-token JSON routes, each behind an opt-in feature toggle and each action replaceable from your app through a contract binding; it is the backend of the Flutter starter <code>magic_starter</code>.
</p>

<p align="center">
  <a href="https://github.com/anilcancakir/magic-starter-laravel/actions/workflows/ci.yml"><img src="https://img.shields.io/github/actions/workflow/status/anilcancakir/magic-starter-laravel/ci.yml?branch=master&label=CI" alt="CI"></a>
  <a href="https://packagist.org/packages/anilcancakir/magic-starter-laravel"><img src="https://img.shields.io/packagist/v/anilcancakir/magic-starter-laravel.svg" alt="Packagist version"></a>
  <a href="https://packagist.org/packages/anilcancakir/magic-starter-laravel"><img src="https://img.shields.io/packagist/dt/anilcancakir/magic-starter-laravel.svg" alt="Packagist downloads"></a>
  <a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/License-MIT-blue.svg" alt="License: MIT"></a>
</p>

<p align="center">
  <a href="https://anilcancakir.com/magic-starter-laravel">Documentation</a> ·
  <a href="https://packagist.org/packages/anilcancakir/magic-starter-laravel">Packagist</a> ·
  <a href="https://github.com/anilcancakir/magic-starter-laravel/issues">Issues</a>
</p>

## At a glance

| Fact | Value |
|---|---|
| Package | [`anilcancakir/magic-starter-laravel`](https://packagist.org/packages/anilcancakir/magic-starter-laravel) |
| Install | `composer require anilcancakir/magic-starter-laravel`, then `php artisan magic-starter:install` |
| Namespace | `AnilcanCakir\MagicStarter\` |
| Entry points | `MagicStarterServiceProvider` (auto-discovered), `Features` (toggle keys and checks), `MagicStarter` (model resolution: `userModel()`, `teamModel()`, `billableModel()`), the action contracts under `Contracts\`, `magic-starter:install`; with Filament installed, `magic-starter:filament:install` and `MagicStarterPlugin` |
| Requires | PHP `^8.2` with `ext-gd` and `ext-exif`; Laravel 12.32 or later, or 13 (`illuminate/*` `^12.32\|^13.0`). CI runs Laravel 12 on PHP 8.2, 8.3 and 8.4 |
| Dependencies | `laravel/sanctum`, `laravel/socialite` with the Apple and Microsoft providers, `laravel/cashier`, `stripe/stripe-php`, `onesignal/onesignal-php-api`, `pragmarx/google2fa`, `bacon/bacon-qr-code`, `firebase/php-jwt`, `sebastian/diff`. Optional: Filament, Horizon, Pulse, Telescope, `geoip2/geoip2` |
| Client | [`magic_starter`](https://pub.dev/packages/magic_starter), the Flutter screens that call these routes |
| License | MIT |

## Install

```bash
composer require anilcancakir/magic-starter-laravel
php artisan magic-starter:install
php artisan migrate
```

`magic-starter:install` asks which features to enable (all of them are preselected), the route prefix (`api/v1` by default) and the frontend URL for email links. It picks UUID or integer primary keys from your existing `users` table, and UUID on a fresh app. It then publishes `config/magic-starter.php` with the chosen features switched on, the core and feature migrations stamped in dependency order, the language files and a `UserFactory`, removes Laravel's stock `create_users_table` migration, and publishes a `User` model carrying the package traits. With `teams` it also publishes the `Team`, `TeamUser` and `TeamInvitation` models and a `TeamPolicy`. It offers to run the migrations at the end, defaulting to no.

| Option | Effect |
|---|---|
| `--all` | Enable every feature the installer offers, without prompting |
| `--features=<key>` | Enable one feature; repeat it for more (`--features=teams --features=sessions`). A key the installer does not offer is dropped silently |
| `--uuid`, `--no-uuid` | Force UUID or auto-incrementing integer keys instead of detecting them |
| `--route-prefix=<prefix>` | Write the route prefix into the config |
| `--frontend-url=<url>` | Write the base URL email links open into the config |
| `--force` | Overwrite files it published before, including a customised `app/Models/User.php` |

Failure modes worth knowing before the first run:

- Passing any of `--all`, `--features`, `--route-prefix` or `--frontend-url`, or running with `--no-interaction`, turns every prompt off, the migration prompt included, so run `php artisan migrate` yourself. A non-interactive run with no `--all` and no `--features` enables every installable feature.
- A customised `app/Models/User.php` is kept and the installer warns with the traits it lacks; only Laravel's stock model is replaced without `--force`.
- With `billing` selected and `REVENUECAT_SECRET_API_KEY` set but `REVENUECAT_WEBHOOK_SECRET` empty, the installer publishes nothing and exits non-zero, because a store rail that cannot authenticate its webhook can never record a purchase.
- Email links are built from `MAGIC_STARTER_FRONTEND_URL`, then `app.frontend_url`, then the API's own URL. Set it when the app runs on another host: verification opens `{frontend}/auth/verify-email` and password reset `{frontend}/auth/reset-password`, pages the client has to serve.

## Feature toggles

Every feature is off until its key is in the `features` array of `config/magic-starter.php`; a feature that is off registers none of its routes, commands or scheduled tasks. Check one with `Features::enabled(Features::teams())` or the matching `Features::hasTeamFeatures()`.

| Key | What it turns on | Offered by the installer |
|---|---|---|
| `two-factor-authentication` | TOTP enrolment with a QR code, confirmation, recovery codes and the sign-in challenge | yes |
| `teams` | Team CRUD, invitations, member roles, leaving and switching the current team | yes |
| `restorable-teams` | A team delete that hides the team for a window in which its owner can restore it | no, uncomment it |
| `profile-photos` | Photo upload and delete for users, and for teams with `teams` on | yes |
| `sessions` | The signed-in devices (Sanctum tokens) of the caller, with revocation | yes |
| `social-login` | Google, Apple, GitHub and Microsoft sign-in, connect and disconnect | yes |
| `sign-in-handoff` | A single-use code that moves a signed-in session to another origin | no, uncomment it |
| `newsletter-subscription` | The caller's newsletter toggle and a public unsubscribe link | yes |
| `extended-profile` | Phone, timezone and locale on the profile | yes |
| `notifications` | Listing, unread count, mark read, delete and the preference matrix | yes |
| `onesignal` | The OneSignal push channel, and with `notifications` the device push-state routes | no, uncomment it |
| `guest-auth` | Guest sign-in by device id and claiming a guest session into an account | yes |
| `phone-otp` | Phone sign-in with a one-time code | yes |
| `email-verification` | Signed verification links and resending them | yes |
| `timezones` | A paginated, searchable timezone list | yes |
| `billing` | Subscriptions on Stripe and the stores with one entitlement | yes |
| `campaigns` | Time-boxed Stripe promotion codes staff run from the admin panel | yes |
| `audit` | A trail of model changes and admin panel actions | yes |
| `releases` | Release notes staff write in the admin panel, read from a public `GET releases` | yes |
| `support` | A support desk: customer conversations and a staff inbox | yes |
| `support-csat`, `support-feedback`, `support-attachments`, `support-client-context` | Satisfaction rating, the in-app feedback card, attachments, and the client info a conversation carries | no, add by hand |
| `support-proposals` | Subscription changes the support AI asks for and staff approve; needs `support` and `billing` | no, add by hand |
| `ai-runs` | A log of the model calls your app makes | yes |
| `ai-prompts` | Prompt overrides staff tune in the admin panel | yes |

## Authentication

Login, registration, forgot and reset password, logout and the current user; login and registration answer with a Sanctum token and the user. Sign-in works by email, by phone or by both, set by `magic-starter.auth.email` and `magic-starter.auth.phone`. `token_expiration_minutes` sets the token lifetime and `remember_me_expiration_minutes` the lifetime of a login sent with `remember_me: true`. Two-factor, phone OTP, guest sign-in and email verification sit behind their own toggles.

Phone OTP ships with `LogOtpProvider`, which writes the code to the log and sends nothing: bind your own `SendsOtpCodes` before the feature reaches a real user. See [Authentication](https://anilcancakir.com/magic-starter-laravel/docs/basics/authentication) and [Two-factor authentication](https://anilcancakir.com/magic-starter-laravel/docs/basics/two-factor-authentication).

## Social sign-in and sign-in handoff

Social sign-in covers Google, Apple, GitHub and Microsoft (`MAGIC_STARTER_SOCIAL_PROVIDERS`) two ways: a native ID token posted to `auth/social/{provider}/token`, and a browser flow the backend hosts (`auth/social/{provider}/redirect`, then `auth/social/exchange` with a one-time code). A signed-in user links and unlinks providers and sets a first password. The callback URLs in `MAGIC_STARTER_SOCIAL_IOS_REDIRECT` and `MAGIC_STARTER_SOCIAL_ANDROID_REDIRECT` must equal the client's.

The sign-in handoff lets a signed-in page mint a single-use code (`POST auth/handoff`, 60 seconds by default) that another origin trades at `POST auth/handoff/exchange` for the same answer a login gives, two-factor included, so no bearer token ever travels in a URL. See [Social login](https://anilcancakir.com/magic-starter-laravel/docs/basics/social-login) and [Sign-in handoff](https://anilcancakir.com/magic-starter-laravel/docs/basics/sign-in-handoff).

## Profile, sessions and account deletion

Profile and password updates, profile photos, the extended profile fields, and the list of signed-in devices with revocation of one or all the others. Deleting an account schedules it: every token is revoked at once, a sign-in during the grace period (`MAGIC_STARTER_ACCOUNT_DELETION_GRACE_DAYS`, 30 by default) cancels it, and `immediately: true` asks for deletion at once. The purge itself is `magic-starter:purge-deleted-users`, which the package registers and does not schedule: without your schedule line nobody is ever deleted. See [Profile](https://anilcancakir.com/magic-starter-laravel/docs/basics/profile) and [Account deletion](https://anilcancakir.com/magic-starter-laravel/docs/basics/account-deletion).

## Teams

Create, update and delete teams, invite by email, accept and cancel invitations, change member roles, remove members, leave and switch the current team; ownership moves from the admin panel. With `restorable-teams` a delete hides the team for a window and `magic-starter:purge-trashed-teams` removes it afterwards; schedule that command yourself. See [Teams](https://anilcancakir.com/magic-starter-laravel/docs/basics/teams).

## Notifications and push

Database notifications with listing, unread count, mark one or all as read and delete, plus a per-type, per-channel preference matrix (`NotificationPreferenceRegistry`) that gates the channels a notification is sent through. With `onesignal` on, push goes through OneSignal to the external id `MAGIC_STARTER_EXTERNAL_ID_PREFIX` (`user_` by default) plus the user's key; the client composes the same string, and a mismatch is accepted by OneSignal and delivered to nobody. See [Notifications](https://anilcancakir.com/magic-starter-laravel/docs/basics/notifications).

## Billing

Billing sells subscriptions on two rails and keeps one entitlement for both: the card rail is Stripe through Laravel Cashier, the store rail is the App Store and Google Play through RevenueCat. `magic-starter.billing.billable` names what you bill, `user` or `team`, and the entitlement columns, the Cashier customer columns and the `subscriptions` foreign key all land on that table, with keys that follow `use_uuids`. `billing:manifest` prints what Stripe, the stores and RevenueCat have to hold, and `billing:doctor` checks that they hold it; the package never changes a vendor's configuration.

> [!WARNING]
> **Do not run `php artisan vendor:publish --tag=cashier-migrations`.** Cashier's migrations hard-code the `users` table and integer keys, so they put the customer columns on the wrong table under team billing and break `subscription_items` under UUID keys. `magic-starter:install` publishes the package's own three replacements in dependency order.

> [!IMPORTANT]
> **A config published before `billing.billable` existed has no `billable` key, and the `user` default answers for it.** If you bill a team, set `'billable' => 'team'` in `config/magic-starter.php` before re-running the installer, or the entitlement and Cashier migrations target `users`.

See [Billing](https://anilcancakir.com/magic-starter-laravel/docs/basics/billing).

## Support desk

With `support` on, every customer contact becomes a conversation that only its opener reads and staff answer from the admin panel; a visitor without an account follows the thread through a mailed link. An AI desk answers in the thread once you bind `RespondsToSupport`: the package ships no model, no prompt and no default responder. `SupportToolRegistry` holds the reads your support AI may make about the customer's own account, with customer text passed through `UntrustedText`, and `ProposeBillingChangeTool` lets it ask for a subscription change that only a staff approval applies. See [Support desk](https://anilcancakir.com/magic-starter-laravel/docs/basics/support), [Support AI tools](https://anilcancakir.com/magic-starter-laravel/docs/basics/support-tools) and [Support proposals](https://anilcancakir.com/magic-starter-laravel/docs/basics/support-proposals).

## AI run log and prompt overrides

`ai-runs` records each model call your app makes as one run with its provider attempts, token usage, cost and masked bodies, read-only in the admin panel; `magic-starter:ai-runs:prune` runs hourly. `ai-prompts` lets you register a prompt in code with its boundary and required phrases, and staff tune its body in the admin panel with a diff against the code default and a stale check when that default changes. See [AI run log](https://anilcancakir.com/magic-starter-laravel/docs/guides/ai-runs) and [AI prompt overrides](https://anilcancakir.com/magic-starter-laravel/docs/guides/ai-prompts).

## Mail through OneSignal

The package registers an `onesignal` mail transport and a router transport. Laravel still renders every mail with your views; the router sends a mail class through OneSignal only when its switch is on and the class is listed, so you move one mail at a time. `magic-starter:mail:doctor` checks the setup. See [Mail on OneSignal](https://anilcancakir.com/magic-starter-laravel/docs/guides/mail-onesignal).

## Admin panel and audit trail

`php artisan magic-starter:filament:install` adds a Filament panel (`^4.13.3` or `^5.8.3`) with a dashboard and resources for users, plus teams, billing, newsletter subscribers, audits, releases, campaigns, support and the AI features, each shown only while its feature is on. Add `implements FilamentUser` and `use AuthorizesAdminPanel` to the user model and list staff in `MAGIC_STARTER_ADMIN_EMAILS`: an empty list admits nobody. `MagicStarterPlugin` can put Horizon, Pulse and Telescope behind the same gate, and every write goes through the action contract the API calls. `magic-starter:filament:eject` copies a resource into the app to change it.

With `audit` on, every Eloquent create, update and delete is written to `magic_starter_audits` after commit with the actor and request; passwords, hidden attributes and encrypted casts are redacted, and `magic-starter:audit:prune` runs daily against `audit.retention_days`. Query-builder bulk writes and pivot rows are not captured. See [Admin panel](https://anilcancakir.com/magic-starter-laravel/docs/basics/admin-panel) and [Audit trail](https://anilcancakir.com/magic-starter-laravel/docs/basics/audit).

## Overriding an action

Controllers stay thin and inject an interface from `Contracts\`; the service provider binds each one to a default under `Actions\`. Bind your own class to change the behaviour of the API and the admin panel at once. `php artisan vendor:publish --tag=magic-starter-stubs` copies skeletons to `app/Actions/MagicStarter`; each one throws until you implement it.

```php
namespace App\Providers;

use AnilcanCakir\MagicStarter\Contracts\CreatesTeams;
use App\Actions\MagicStarter\CreateTeam; // your implementation of CreatesTeams
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CreatesTeams::class, CreateTeam::class);
    }
}
```

Models resolve at runtime: `MagicStarter::userModel()` and `teamModel()` pick up a published `App\Models\User` or `App\Models\Team`, and `MigrationHelper::primaryKey()`, `foreignKey()` and `morphColumns()` switch between UUID and integer keys from `use_uuids`. See [Action contracts](https://anilcancakir.com/magic-starter-laravel/docs/architecture/action-contracts), [Models](https://anilcancakir.com/magic-starter-laravel/docs/architecture/models) and [Service provider](https://anilcancakir.com/magic-starter-laravel/docs/architecture/service-provider).

## For AI coding agents

| Do | Because |
|----|---------|
| Let Laravel Boost install the `magic-starter-development` skill, shipped at `resources/boost/skills/magic-starter-development` | It carries the contract, feature, model and billing setup rules in the shape an agent reads. |
| Change a route in `src/routes/api.php` together with the `magic_starter` client | The Flutter controllers call these routes one to one; change one side, keep the other in sync. |
| Gate code on `Features::enabled()` or a `Features::has...Features()` check | A feature that is off registers no routes and no commands, so its endpoints answer 404. |
| Resolve models through `MagicStarter::userModel()`, `teamModel()` and `billableModel()` | A host may publish its own `App\Models\*`; a hard-coded class misses it. |
| Change behaviour by binding a class to a `Contracts\*` interface in a service provider's `register()` | The API and the admin panel both call the contract, so one binding changes both. |
| Build package-related columns with `MigrationHelper`, never `$table->id()` | `use_uuids` decides the key type at runtime. |
| Name the host's middleware in `magic-starter.route_middleware` | The package routes join no middleware group, so the host's `api` group does not run on them. |
| Bind `SendsOtpCodes` before `phone-otp` reaches a real user | The default `LogOtpProvider` only writes the code to the log. |
| Schedule `magic-starter:purge-deleted-users`, and `magic-starter:purge-trashed-teams` with `restorable-teams` | The package registers both and schedules neither. |
| Keep `MAGIC_STARTER_EXTERNAL_ID_PREFIX` and the social callback URLs equal to the client's values | OneSignal accepts a mismatched external id and delivers to nobody; a mismatched callback sends the browser flow where the app is not listening. |
| Never publish `cashier-migrations` | They target `users` with integer keys whatever `billing.billable` and `use_uuids` say. |

## Moving from fluttersdk/magic-starter-laravel

The package moved from `fluttersdk/magic-starter-laravel` to `anilcancakir/magic-starter-laravel` and its namespace from `FlutterSdk\MagicStarter\` to `AnilcanCakir\MagicStarter\`; from 1.0.0 the old package is abandoned in favour of this one.

Replace `FlutterSdk\MagicStarter\` with `AnilcanCakir\MagicStarter\` across your app first, including the published `config/magic-starter.php`, models, policies and action stubs, then swap the package in one resolution:

```bash
composer remove fluttersdk/magic-starter-laravel --no-update
composer require anilcancakir/magic-starter-laravel
```

Renaming first matters: Composer boots the application while it removes a package, and a config that still names the old namespace stops it. The [migration guide](https://anilcancakir.com/magic-starter-laravel/docs/getting-started/migrating-from-fluttersdk) walks through every step.

## Dependencies

`laravel/sanctum` for the tokens; `laravel/socialite` with `socialiteproviders/apple` and `socialiteproviders/microsoft` for social sign-in, and `firebase/php-jwt` for the ID token keys and the Apple client secret; `pragmarx/google2fa` and `bacon/bacon-qr-code` for two-factor; `laravel/cashier` and `stripe/stripe-php` for the card rail; `onesignal/onesignal-php-api` for push and mail; `sebastian/diff` for the prompt override diff. Filament powers the optional admin panel, Horizon, Pulse and Telescope join it when installed, and `geoip2/geoip2` adds a location to sessions.

## Links

- [Documentation](https://anilcancakir.com/magic-starter-laravel/docs/getting-started/installation): installation, configuration, every feature and the architecture
- [Packagist](https://packagist.org/packages/anilcancakir/magic-starter-laravel)
- [llms.txt](llms.txt) for AI coding agents, and the Boost skill at `resources/boost/skills/magic-starter-development`
- [`magic_starter`](https://pub.dev/packages/magic_starter), the Flutter client
- [Changelog](CHANGELOG.md)
- [Report an issue](https://github.com/anilcancakir/magic-starter-laravel/issues)
- [Security policy](SECURITY.md)

## License

MIT, see [LICENSE](LICENSE). Built by [Anılcan Çakır](https://anilcancakir.com).
