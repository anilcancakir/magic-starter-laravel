# Releases

Release notes that staff write per app version in the admin panel and that any client reads from one public endpoint.

- [Quick Reference](#quick-reference)
- [Introduction](#introduction)
- [Enabling](#enabling)
- [Managing Releases](#managing-releases)
- [List Releases](#list-releases)
- [Configuration](#configuration)
- [Overriding the Actions](#overriding-the-actions)
- [Gotchas](#gotchas)
- [Related Documentation](#related-documentation)

<a name="quick-reference"></a>
## Quick Reference

| Item | Value |
|:-----|:------|
| Feature | `Features::releases()`, then `php artisan magic-starter:install --features=releases` and `php artisan migrate`. |
| Endpoint | `GET {prefix}/releases`, no authentication, 60 requests a minute per IP. |
| Answer | Published releases newest first, at most `releases.limit` (50 by default), and `meta.latest_build` for `ios` and `android`. |
| Staff | A Releases resource in the admin panel, under the key `releases`. |
| Override | `CreatesAppReleases`, `UpdatesAppReleases` and `DeletesAppReleases`. |

<a name="introduction"></a>
## Introduction

The releases module lets staff write the release notes of each app version and lets any client read the published ones. A mobile app can show "What's new" after an update, and compare its own build number with the latest one to decide whether to suggest an update.

Staff curate releases in the [admin panel](admin-panel.md). Clients read them from one public endpoint, `GET /releases`. A web deploy is not a release: only a version you ship to a store belongs here.

---

<a name="enabling"></a>
## Enabling

Releases are opt-in:

```php
// config/magic-starter.php
'features' => [
    Features::releases(),
],
```

Run `php artisan magic-starter:install --features=releases` to publish the migration, then `php artisan migrate`. To publish it by hand, use the `magic-starter-migrations` tag:

```bash
php artisan vendor:publish --tag=magic-starter-migrations
```

While the feature is off, the route is not registered. With it on, the public settings endpoint reports `features.releases`, so a client can tell whether the server keeps release notes at all.

The migration creates `app_releases`:

| Column | Type | Description |
|--------|------|-------------|
| `id` | string | Primary key (UUID or integer per app config). |
| `version` | string | Unique, `MAJOR.MINOR.PATCH`, for example `1.1.0`. |
| `status` | string | `draft` or `published`. |
| `published_at` | timestamp\|null | When the release became visible. |
| `ios_build` | integer\|null | The iOS build number of this version. |
| `android_build` | integer\|null | The Android build number of this version. |
| `items` | json | The notes: a list of `{ type, text }` entries. |

---

<a name="managing-releases"></a>
## Managing Releases

With the feature on, the admin panel mounts a Releases resource under the key `releases`. Staff create, edit and delete releases there, and each one is either a draft or published.

- A draft is never returned by the endpoint.
- Publishing with an empty `published_at` stamps the current time.
- A `published_at` in the future is refused. There is no scheduling: a release is visible from the moment it is published.

Drop the resource with `->withoutResources(['releases'])`, or swap it with `->resource('releases', ReleaseResource::class)`:

```php
->plugin(
    MagicStarterPlugin::make()->resource('releases', \App\Filament\Resources\Releases\ReleaseResource::class)
)
```

See [Admin Panel](admin-panel.md#configuring-the-plugin) for the other plugin options. Every write the panel makes goes through the [action contracts](#overriding-the-actions) below.

---

<a name="list-releases"></a>
## List Releases

**`GET /releases`**

Returns the published releases, newest first. The route sits under `magic-starter.route_prefix` and needs no authentication, so a client can call it before sign-in.

The route is throttled to 60 requests per minute per IP (the `magic-starter-releases` rate limiter). The answer is cached for one hour and refreshed after every save or delete, so a change staff make shows on the next request instead of an hour later.

### Response

```json
{
    "data": [
        {
            "version": "1.1.0",
            "published_at": "2026-10-20T09:00:00.000000Z",
            "ios_build": 15,
            "android_build": 15,
            "items": [
                {
                    "type": "new",
                    "text": {
                        "en": "...",
                        "tr": "..."
                    }
                }
            ]
        }
    ],
    "meta": {
        "latest_build": {
            "ios": 15,
            "android": 15
        }
    }
}
```

### Rules

- Only published releases are returned.
- Releases are ordered by `published_at`, newest first, then by id.
- At most `releases.limit` releases are returned.
- `type` is one of `new`, `improved` or `fixed`.
- `text` carries every key of `magic-starter.supported_locales`. A locale the staff left empty shows the `defaults.locale` text, so a client can read its own locale without a fallback of its own.
- `meta.latest_build.ios` and `meta.latest_build.android` hold the highest build number across all published releases, not only the ones in `data`. Each is `null` when no published release carries a build for that platform.

---

<a name="configuration"></a>
## Configuration

```php
// config/magic-starter.php
'releases' => [
    'limit' => 50,
],
```

| Key | Default | Description |
|-----|---------|-------------|
| `releases.limit` | `50` | The most releases the endpoint returns. |

---

<a name="overriding-the-actions"></a>
## Overriding the Actions

The panel writes releases through three contracts. Bind your own to change what a create, an update or a delete does:

| Contract | Method |
|----------|--------|
| `CreatesAppReleases` | `create(Authenticatable $user, array $input): Model` |
| `UpdatesAppReleases` | `update(Authenticatable $user, Model $release, array $input): void` |
| `DeletesAppReleases` | `delete(Authenticatable $user, Model $release): void` |

```php
// App\Providers\AppServiceProvider::register()
$this->app->bind(CreatesAppReleases::class, \App\Actions\CreateAppRelease::class);
```

Bind in `register()`, not `boot()`. The default update action validates through the default create action, so bind both when you change the rules.

<a name="gotchas"></a>
## Gotchas

| Mistake | Fix |
|:--------|:----|
| Treating a web deploy as a release | Only a version you ship to a store belongs here. |
| Expecting a draft on the endpoint | A draft is never returned. Publish it first. |
| Scheduling a release with a future `published_at` | It is refused; a release is visible from the moment it is published. |
| Reading `meta.latest_build` as the newest entry of `data` | It holds the highest build across every published release, not only the ones in `data`. |
| Binding the action contracts in `boot()` | Bind in `register()`. |

<a name="related-documentation"></a>
## Related Documentation

- [Admin Panel](./admin-panel.md): mounting, swapping or dropping the Releases resource.
- [Profile](./profile.md): the settings endpoint that reports `features.releases`.
- [Configuration](../getting-started/configuration.md): the `releases` feature toggle.
- [Action Contracts](../architecture/action-contracts.md): how a binding replaces a default action.
