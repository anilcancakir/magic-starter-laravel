# Notification Preferences

## Where to Find It

- `src/NotificationPreferenceRegistry.php`: type registration, channel aliases, slug resolution
- `src/Traits/HasNotifications.php`: `prefers()`, `notificationPreferenceMatrix()`, OneSignal routing
- `src/Listeners/GateNotificationChannels.php`: the `NotificationSending` listener that gates channels
- `src/Models/NotificationSetting.php`: a notifiable's per-type, per-channel override
- `config/magic-starter.php`: the `notifications` feature flag

## What to Watch For

### The registry defines types and channels

- Register types from a service provider's `boot()`: `NotificationPreferenceRegistry::register([OrderShipped::class => ['label' => '...', 'channels' => ['email', 'push'], 'default' => ['email'], 'locked' => []]])`. The key is the notification class.
- `label` goes through `__()` when the matrix is built, so a multi-language app registers a translation key rather than a sentence. Register the key, not `__('...')` itself: `boot()` runs before any locale middleware, and under Octane once per worker, so a `__()` there freezes the first request's language for every later one. A finished sentence still works, since `__()` returns a string it has no line for unchanged.
- `NotificationPreferenceRegistry::channelAliases(['push' => OneSignalChannel::class])` maps a logical name to a driver channel.
- A slug is derived from the class basename with the `Notification` suffix stripped, in snake case; a `slug` key in the registration overrides it.

### `GateNotificationChannels` runs on every `NotificationSending`

- An unregistered notification class is delivered.
- A notifiable without `prefers()` is delivered.
- `resolveLogicalChannel()` maps the driver channel back to its logical name, so an aliased channel is gated under its alias. A `broadcast` the type does not register answers to its `database` preference, since it is the live copy of that row.
- A channel the type does not list in `channels` is delivered.
- A locked channel is always delivered, whatever the user chose.
- A notification implementing `Contracts\BypassesNotificationPreferences` is asked `bypassesPreference($notifiable, $logicalChannel)` after the locked check and before `prefers()`; true delivers despite a disabled preference.
- Otherwise the listener returns `prefers()`, and false cancels that channel.

### `prefers()` falls back in three steps

1. A type that is not registered returns true.
2. A `NotificationSetting` row for the type and channel returns its `is_enabled`.
3. Otherwise the channel is enabled when it is in the registered `default` list.

### `notificationPreferenceMatrix()` builds the settings payload

- It returns type slug to `label` plus `channels`, each channel carrying `enabled` and `locked`.
- `enabled` follows a stored override and falls back to the registry default.
- A locked channel cannot be toggled, so the client shows it disabled; `NotificationPreferenceRegistry::locked()` lists them per type.

### OneSignal routing targets an alias (SDK v5)

- `routeNotificationForOneSignal()` returns `['external_id' => [MagicStarter::onesignalExternalIdPrefix() . $this->getKey()]]`.
- The prefix (`magic-starter.onesignal.external_id_prefix`, default `user_`) exists because OneSignal rejects a bare numeric external id, and it has to match the client's `Notify.initializePush(prefix + user.id)`; a mismatch is accepted and delivered to nobody.
- The driver is `AnilcanCakir\MagicStarter\Notifications\Channels\OneSignalChannel`, which passes the aliases to `\onesignal\client\model\Notification::setIncludeAliases()`.

### Consumer pattern

Write ordinary Laravel notifications, register each type with its channels in the registry, and let `GateNotificationChannels` filter delivery. The settings screen reads `notificationPreferenceMatrix()` through `GET notification-preferences` and writes through `PUT notification-preferences`.
