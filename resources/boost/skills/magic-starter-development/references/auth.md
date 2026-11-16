# Authentication

## Where to Find It

- `src/Actions/CreateUser.php`: registration, newsletter consent, the verification mail
- `src/Actions/CreateGuestUser.php`: guest sign-in keyed by `device_id`
- `src/Actions/CreateUserFromProvider.php`: social sign-up
- `src/Actions/LogOtpProvider.php`: the default OTP sender (it only logs; replace it in production)
- `src/Actions/CacheOtpVerifier.php`: the OTP verifier, a one-time cache pull
- `src/Http/Controllers/Concerns/AuthenticatesUsers.php`: token creation and the response shape
- `src/Http/Requests/LoginRequest.php`, `RegisterRequest.php`: rules that follow the identity strategy
- `src/Features.php`: `emailIdentity()`, `phoneIdentity()` and every feature check

## What to Watch For

### The identity strategy is config-driven (email, phone, or both)

- `Features::emailIdentity()` reads `magic-starter.auth.email` (default `true`); `Features::phoneIdentity()` reads `magic-starter.auth.phone` (default `false`).
- `LoginRequest` and `RegisterRequest` call both when they build their rules, so config, not code, decides what a request must carry.
- With both on, `email` is `required_without:phone` and `phone` is `required_without:email`: the user supplies at least one.
- A phone value always passes the `E164Phone` rule.

### Guest users have a null email and a null password

- `CreateGuestUser` writes `email: null`, `password: null` and `is_guest: true`.
- It looks for a user that is still a guest with that `device_id` and returns it, so repeating the call does not create a duplicate. With no such guest it frees the `device_id` from any promoted account that still holds it and creates a new guest, because the device id is an anonymous key that must stop opening an account once it has credentials.
- A guest becomes a registered account through `UpdatesUserProfiles` or `UpdatesUserPasswords` once it has an email or a phone plus a password (see `profile.md`), not through `CreatesUsers`. Guard every email and password read with a null check.
- `POST auth/guest/claim` (`ClaimsGuestAccounts`) moves a guest session's rows into an existing account the caller signs in to.

### One verification mail per registration, guarded by object identity

- `MustVerifyEmail::sendEmailVerificationNotification()` sends at most once per model instance; a registration reaches it twice, once from `CreateUser` and once from the framework listener on `Registered`.
- The guard is an instance property, not a static keyed by id: a static would survive between requests under Octane and mute a customer's later resend.
- That is also its limit. Binding your own `CreatesUsers` and then firing `event(new Registered($user->fresh()))`, or reloading the model between the action and the event, hands the listener a different object and sends both mails again, with no warning. Pass the same instance through.
- `POST email/verification-notification` is unaffected: its own request, its own instance.
- With the `email-verification` feature off, the trait sends nothing.

### Social sign-up and a pre-verified email

- Social sign-up goes through `CreatesUsersFromProvider`, not `CreatesUsers`. It force-creates a passwordless user with `email_verified_at` set to now when the provider vouched for the address, fires `Registered`, and fires `Verified` in that case.
- `CreateUser` accepts an optional `email_verified_at` when `extended-profile` is on and passes it to `create()`, so it is mass-assigned. The published `User` stub does not list `email_verified_at` in `$fillable`, so with the stub the value is dropped and the verification mail still goes out. A non-null value skips `sendEmailVerificationNotification()` only when the app's user model lists `email_verified_at` in `$fillable`, because the send requires `email_verified_at === null`.

### The OTP flow uses two contracts

- `SendsOtpCodes` is bound to `LogOtpProvider`, which writes the code with `Log::info()` and does nothing else; bind your own for real SMS or push delivery.
- `VerifiesOtpCodes` is bound to `CacheOtpVerifier`, which calls `Cache::pull('otp_' . $phone)`: the first read consumes the code, so a code works once.
- The verifier sets no TTL; the code lives as long as whoever cached it decided.

### The auth response (`AuthenticatesUsers`)

- `createAuthToken()` calls `$user->createToken('auth_token')`. When `token_expiration_minutes` (or `remember_me_expiration_minutes` for a `remember_me` sign-in) is a positive number, the token gets that expiry; then `ip_address` and `user_agent` are force-filled onto the package's `PersonalAccessToken`, which carries those columns.
- `authenticatedResponse()` sets the user resolver on both the injected `$request` and `app('request')`, so a nested resource (`TeamResource`, for example) can call `$request->user()` before Sanctum's middleware has run.
- The envelope is `{data: {user, token}, message}`, plus `data.deletion_cancelled: true` when the sign-in cancelled a scheduled account deletion. On a password login, a social sign-in or a sign-in handoff, a user with confirmed two-factor gets `{two_factor: true, two_factor_token}` instead (see `two-factor.md`); registration, guest sign-in and `POST auth/otp/verify` issue the token directly.
