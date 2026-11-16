# Models and Migrations

## Where to Find It

- `src/Support/ConditionallyUsesUuids.php`: runtime UUID or integer keys
- `src/Support/MigrationHelper.php`: `primaryKey()`, `foreignKey()`, `morphColumns()`
- `src/Models/`: the package models (`Team`, `TeamUser`, `TeamInvitation`, `PersonalAccessToken` and the rest), all on `ConditionallyUsesUuids`
- `src/Traits/`: `HasProfilePhoto`, `HasNotifications`, `HasTeams`, `HasGuestSupport`, `TwoFactorAuthenticatable` and the other user traits
- `config/magic-starter.php`: `use_uuids`, `profile_photo_disk`

## What to Watch For

### Every package model uses `ConditionallyUsesUuids`

With `magic-starter.use_uuids` true (the default), the trait sets `$incrementing = false` and `$keyType = 'string'` when a model is built and writes an ordered UUID on create. Under implicit route-model binding, a route key that is not a UUID resolves to a missing model (404) instead of a query error. A lookup that skips the binding does not get that guard: `TeamController` takes `{team}` as a `string` and calls `findOrFail()`, so a malformed id reaches the database there. With it false, keys are ordinary auto-increment integers. Give every column a `@property` annotation, and accessors and computed attributes `@property-read`.

### Relations resolve their classes at runtime

A relation never names `App\Models\*`. Model classes come from `MagicStarter::userModel()`, `teamModel()`, `membershipModel()` and `teamInvitationModel()`, and a team `BelongsToMany` names the pivot table and keys (`team_user`, `user_id`, `team_id`) and chains `->using(MagicStarter::membershipModel())->withPivot('role')`, as `HasTeams::teams()` and `Team::users()` do. Define casts in the `casts()` method and use `$fillable` rather than `$guarded`. The package's own models do not soft delete; the team model may opt in for the `restorable-teams` feature, and every place that touches a trashed team checks `SoftDeletingTeams::usedBy()` first.

### The user traits

- `HasProfilePhoto`: `getProfilePhotoUrlAttribute()` returns the configured disk's URL when `profile_photo_path` is set, and null otherwise, so the client draws its own initials.
- `HasNotifications`: `notificationSettings()` (a `MorphMany<NotificationSetting, $this>`), `prefers()` with its three-step fallback (an unregistered type allows, a stored override wins, then the registry defaults), and `routeNotificationForOneSignal()` returning `['external_id' => [MagicStarter::onesignalExternalIdPrefix() . $this->getKey()]]` for the `OneSignalChannel` driver.
- `HasTeams`: `ownedTeams()`, `teams()` (with the pivot), `personalTeam()`, `allTeams()`, `currentTeam()`, `getCurrentTeamOrPersonal()`, `belongsToTeam()`, `ownsTeam()` and `hasTeamRole()`, which reads `pivot->role`.
- `HasGuestSupport`: `isGuest()` reads `is_guest`; `isRegistered()` is true for a non-guest with an email and password or a phone and password. A guest may have a null email and a null password, so guard both.
- `TwoFactorAuthenticatable`: `two_factor_secret` and `two_factor_recovery_codes` are encrypted; `twoFactorSecret()` and `recoveryCodes()` decrypt them. Check `hasEnabledTwoFactorAuthentication()` (a non-null `two_factor_confirmed_at`) before you rely on a secret.

### `MigrationHelper` handles both key types

`MigrationHelper::primaryKey($table)` emits `uuid('id')->primary()` or `id()`, `MigrationHelper::foreignKey($table, 'user_id')` emits `foreignUuid()` or `foreignId()`, and `MigrationHelper::morphColumns($table, 'notifiable')` emits `uuidMorphs()` or `morphs()`, all from `use_uuids`. Use them instead of a raw `$table->id()`, `$table->uuid('id')` or `$table->morphs()`, so the same migration builds matching keys in either mode.

Package migrations also follow these conventions: an anonymous class (`return new class extends Migration`), a `Schema::hasTable()` or `Schema::hasColumn()` guard so a rerun is harmless, explicit string lengths (`profile_photo_path` 2048, `phone_country` `char(2)`), and `$table->timestamps()` on every entity table.
