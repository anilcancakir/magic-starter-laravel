# Migrating from fluttersdk/magic-starter-laravel

How to move a Laravel application from `fluttersdk/magic-starter-laravel` 0.0.x to `anilcancakir/magic-starter-laravel` 1.0.0: a new Composer name and a new PHP namespace, with no change to the API your Flutter client talks to.

- [Quick Reference](#quick-reference)
- [Drain the Queue](#drain-the-queue)
- [Rename the Namespace](#rename-the-namespace)
- [Swap the Package](#swap-the-package)
- [Run the Migration](#run-the-migration)
- [Laravel Boost](#laravel-boost)
- [What Does Not Change](#what-does-not-change)
- [Gotchas](#gotchas)
- [Related Documentation](#related-documentation)

<a name="quick-reference"></a>
## Quick Reference

From 1.0.0 the old package is abandoned on Packagist and names `anilcancakir/magic-starter-laravel` as its replacement. Nothing else about the package changes: the repository moved to a new owner, the namespace changed from `FlutterSdk\MagicStarter\` to `AnilcanCakir\MagicStarter\`, and 1.0.0 is the first release under the new name.

Rename your code first and swap the package second: the published `config/magic-starter.php` calls `Features::` methods by their full class name, and Composer boots the application while it removes a package, so a config that still names the old namespace stops `composer remove` with a fatal error.

```bash
php artisan queue:work --stop-when-empty
rg -l0 'FlutterSdk\\+MagicStarter' app config database routes tests \
    | xargs -0 -r perl -pi -e 's/FlutterSdk(\\+)MagicStarter/AnilcanCakir$1MagicStarter/g'
composer remove fluttersdk/magic-starter-laravel --no-update
composer require anilcancakir/magic-starter-laravel
cp vendor/anilcancakir/magic-starter-laravel/database/migrations/rewrite_stored_class_names.php \
    "database/migrations/$(date +%Y_%m_%d_%H%M%S)_rewrite_stored_class_names.php"
php artisan migrate
```

| Step | What you do | Details |
|:-----|:------------|:--------|
| 1 | Stop dispatching and run the queue empty. | [Drain the Queue](#drain-the-queue) |
| 2 | Rename the namespace in your application code. | [Rename the Namespace](#rename-the-namespace) |
| 3 | Replace the Composer package in one resolution. | [Swap the Package](#swap-the-package) |
| 4 | Run the upgrade migration. | [Run the Migration](#run-the-migration) |
| 5 | Re-select the package in Laravel Boost, if you use it. | [Laravel Boost](#laravel-boost) |

<a name="drain-the-queue"></a>
## Drain the Queue

A queued job stores the class name it was dispatched with. The package queues jobs of its own (the support responder, the RevenueCat entitlement sync, the trial card check and the account purge among them), and a job pushed before the upgrade names a `FlutterSdk\MagicStarter\` class that no longer exists afterwards. The worker cannot unserialize it and the job lands in `failed_jobs`, where a retry cannot succeed either.

Stop whatever dispatches work, then let the workers finish what is queued:

```bash
php artisan queue:work --stop-when-empty
```

Do the same for every named queue and connection your application uses. `--stop-when-empty` exits as soon as nothing is ready, so it does not wait for a job that was pushed with a delay: let those fall due and run, or look for them in your queue backend (for example the `delayed` set on Redis or `available_at` in the `jobs` table), before you continue.

Laravel must already be on 12.32 or later: the new package requires `illuminate/*` `^12.32|^13.0`, and Composer cannot resolve it on an older framework once the old package is gone.

<a name="rename-the-namespace"></a>
## Rename the Namespace

Every class moved from `FlutterSdk\MagicStarter\` to `AnilcanCakir\MagicStarter\`. Your application references some of them: contract bindings in a service provider, `use` lines in the action, model and policy stubs you published, the `use` lines at the top of the published `config/magic-starter.php`, and the `Features::` calls inside it. Strings can carry the namespace too, in the doubled-backslash form (`FlutterSdk\\MagicStarter\\Models\\Team`), for example in a config array or a morph map.

One pattern covers both forms and keeps the number of backslashes it finds:

```bash
rg -l0 'FlutterSdk\\+MagicStarter' app config database routes tests
rg -l0 'FlutterSdk\\+MagicStarter' app config database routes tests \
    | xargs -0 -r perl -pi -e 's/FlutterSdk(\\+)MagicStarter/AnilcanCakir$1MagicStarter/g'
```

The first command lists the files that match, the second rewrites them in place. Run the list again afterwards: it must print nothing. Check the rest of the project too (`bootstrap/`, `phpstan.neon`, `phpunit.xml`) with `rg FlutterSdk` over the repository, leaving `vendor/` out.

A file you published from the old package extends or implements a class of the old namespace, so it fails until it is renamed. Rename it in place; republishing it with `--force` to get the new namespace discards your edits.

<a name="swap-the-package"></a>
## Swap the Package

```bash
composer remove fluttersdk/magic-starter-laravel --no-update
composer require anilcancakir/magic-starter-laravel
```

`--no-update` only edits `composer.json`, so Composer resolves once, with the new package, and the application boots against the renamed code. Package auto-discovery registers `AnilcanCakir\MagicStarter\MagicStarterServiceProvider`; a provider you registered by hand in `bootstrap/providers.php` or `config/app.php` needs the same rename as the rest of your code. While the old package is still required, Composer reports it as abandoned and names the new one as the replacement.

The vendor path changes too: a command or note that copies a migration from `vendor/fluttersdk/magic-starter-laravel/database/migrations/` reads `vendor/anilcancakir/magic-starter-laravel/database/migrations/` from now on.

<a name="run-the-migration"></a>
## Run the Migration

The package stores PHP class names in the database. The upgrade migration `rewrite_stored_class_names` rewrites them to the new namespace. A fresh install publishes it with every other migration; an application that is already installed copies the one file under a timestamp later than its latest migration, then migrates:

```bash
cp vendor/anilcancakir/magic-starter-laravel/database/migrations/rewrite_stored_class_names.php \
    "database/migrations/$(date +%Y_%m_%d_%H%M%S)_rewrite_stored_class_names.php"
php artisan migrate
```

Copy the file; do not publish the `magic-starter-migrations` tag for this, because that tag publishes the whole migrations directory.

| Table | Column | Holds |
|:------|:-------|:------|
| `magic_starter_audits` | `auditable_type` | The class of the audited model. |
| `notifications` | `type` | The class of the notification that wrote the row. |
| `magic_starter_mail_sends` | `mail_class` | The class of the mail or notification that was sent. |
| `ai_runs` | `subject_type` | The class of the model a run was made for. |
| `ai_runs`, `ai_run_attempts` | `error_class` | The class of the exception a call failed with. |
| `billing_events`, `billing_grants`, `billing_trials`, `support_proposals` | `billable_type` | The class of the billable model, which is the package `Team` when you did not publish your own. |

Without it, the old names stay in those columns: they point at classes that no longer exist, and a query by the new class name does not find the rows. `Auditor` queries `auditable_type` by morph class, so the audit history of a package model such as `Team` would read as empty. Columns that only ever hold your own user model (`actor_type`, `notifiable_type`, `tokenable_type`) are not touched, and neither is any value outside the old namespace. The migration skips a table or column that does not exist, a second run changes nothing, and `php artisan migrate:rollback` swaps the names back.

<a name="laravel-boost"></a>
## Laravel Boost

The package ships its agent skill, `magic-starter-development`, under `resources/boost/skills/`. Boost installs a skill for the Composer package you selected, so run `php artisan boost:install` again and select `anilcancakir/magic-starter-laravel`. The skill is then installed under the new Composer name.

<a name="what-does-not-change"></a>
## What Does Not Change

- The `config/magic-starter.php` keys and every `MAGIC_STARTER_*` environment variable.
- The route names, the `api/v1` route prefix and the request and response shapes your Flutter client relies on.
- The table names and their columns.
- The artisan commands (`magic-starter:install` and the rest) and the translation namespace `magic-starter::`.

<a name="gotchas"></a>
## Gotchas

| Mistake | Fix |
|:--------|:----|
| Running `composer remove` before renaming the code | The published config still names the old `Features` class and the application fails to boot inside Composer. Rename first, then `composer remove ... --no-update` and `composer require`. |
| Upgrading with jobs still in the queue | Drain the queue first. A payload naming the old class cannot be retried afterwards. |
| Replacing only `FlutterSdk\MagicStarter` | The doubled-backslash form in config and strings needs the same rename; the pattern above covers both. |
| Searching only `app/` | Search `config/`, `database/`, `routes/` and `tests/` too, and `bootstrap/` for a hand-registered provider. |
| Republishing stubs with `--force` to pick up the namespace | Rename the namespace in place instead; `--force` overwrites your changes. |
| Skipping the upgrade migration | The audit, notification, mail, AI run and billing rows keep the old class names. |
| Publishing the `magic-starter-migrations` tag to get the new migration | It publishes the whole directory. Copy `rewrite_stored_class_names.php` alone, as above. |

<a name="related-documentation"></a>
## Related Documentation

- [Installation](./installation.md): the install command and the `User` model traits.
- [Configuration](./configuration.md): the config keys that stay as they are.
- [Action Contracts](../architecture/action-contracts.md): how a binding names the classes you override.
- [Audit Trail](../basics/audit.md): the table behind `magic_starter_audits`.
