# Profile Management

## Where to Find It

- `UpdateUserProfile`: name, email, phone, timezone and locale updates; guest conversion
- `UpdateUserPassword`: the password change, with a current-password check
- `ScheduleUserDeletion`, `DeleteUser`: the scheduled account deletion and the purge
- `HasProfilePhoto`: the `profile_photo_url` accessor
- `SessionAgent`: browser, platform and mobile detection from a user agent

## What to Watch For

### Profile updates and email changes

`UpdateUserProfile` captures the old email before it saves. When the `email-verification` feature is on and the email changed, it force-fills `email_verified_at` to null and sends a new verification mail. A guest may set a password in the same call; a registered user changes it through `PUT user/password`.

### Guest conversion

A guest becomes a registered account when it has an email or a phone and a password. `UpdateUserProfile` and `UpdateUserPassword` both check this after they save and promote the user (`is_guest` false, `device_id` released) and dispatch `GuestConverted`. `UpdateUserPassword` skips the `current_password` check for a guest that has no password yet.

### Profile photos

`HasProfilePhoto` provides `profile_photo_url`: the disk URL when `profile_photo_path` is set, null otherwise, and the client draws its own initials. The disk is `magic-starter.profile_photo_disk` (default `public`), falling back to `filesystems.default` when that is null.

### Sessions and device detection

A session is a Sanctum token carrying `ip_address` and `user_agent`. With the `sessions` feature on, `GET sessions` lists them with `SessionAgent::parse()` results (browser, platform, mobile flag), `DELETE sessions/other` revokes every other token and `DELETE sessions/{token}` revokes one.

### Password changes

A registered user sends `current_password`, `password` and `password_confirmation`. The action hashes the new password before saving, and `UpdateUserProfile` strips `password_confirmation` before it updates the row.

### Account deletion

`POST` or `DELETE user` calls `SchedulesUserDeletion` and answers 202: every token is revoked now and the account is purged after `magic-starter.account_deletion.grace_days` (default `30`); with `immediately` the purge is queued at once. Signing in again during the grace period cancels a deletion the user asked for. `magic-starter:purge-deleted-users` deletes due accounts through `DeletesUsers`, which, in one transaction, refuses an account it cannot delete safely (shared teams, a live subscription), deletes the owned teams through `DeletesTeams`, detaches the other memberships, removes social accounts and notification data, deletes the tokens and then the user. Only after the commit does it revoke Apple sign-in, delete the OneSignal user and remove the photo and support files, since none of those can be rolled back.
