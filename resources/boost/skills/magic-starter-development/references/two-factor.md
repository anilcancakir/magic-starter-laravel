# Two-Factor Authentication

## Where to Find It

- Actions: `src/Actions/EnableTwoFactorAuthentication.php`, `ConfirmTwoFactorAuthentication.php`, `DisableTwoFactorAuthentication.php`, `GenerateNewRecoveryCodes.php`
- Contracts: `src/Contracts/EnablesTwoFactorAuthentication.php`, `ConfirmsTwoFactorAuthentication.php`, `DisablesTwoFactorAuthentication.php`, `GeneratesNewRecoveryCodes.php`
- Trait: `src/Traits/TwoFactorAuthenticatable.php`, on the user model
- Provider: `src/Support/TwoFactorAuthenticationProvider.php`, over PragmaRX Google2FA
- Controllers: `src/Http/Controllers/TwoFactorAuthenticationController.php`, `TwoFactorChallengeController.php`, `TwoFactorRecoveryCodeController.php`
- Config: `config/magic-starter.php`, the `two_factor` block

## What to Watch For

### Enable generates a secret but does not activate two-factor

`EnablesTwoFactorAuthentication::enable(mixed $user): array` generates a base32 TOTP secret with `TwoFactorAuthenticationProvider::generateSecretKey()`, makes `two_factor.recovery_codes_count` (default `8`) recovery codes shaped `random(10)-random(10)`, stores both encrypted, and clears `two_factor_confirmed_at`. It returns `secret`, `qr_url` (an otpauth URI) and `recovery_codes`; the enrollment screen needs all three. A null `two_factor_confirmed_at` means enrollment is pending.

### Confirm checks a TOTP code and sets the activation timestamp

`ConfirmsTwoFactorAuthentication::confirm(mixed $user, string $code): void` decrypts the secret through `$user->twoFactorSecret()`, verifies the code through the provider (a window of one step either side for clock drift) and sets `two_factor_confirmed_at`. It throws a `ValidationException` when there is no secret or the code is wrong. `hasEnabledTwoFactorAuthentication()` returns true only after this.

### Disable clears all three columns without a check of its own

`DisablesTwoFactorAuthentication::disable(mixed $user): void` nulls `two_factor_secret`, `two_factor_recovery_codes` and `two_factor_confirmed_at`. The action verifies nothing, so the caller is responsible for authenticating the user first.

### Recovery codes

`GeneratesNewRecoveryCodes::generate(mixed $user): array` replaces the whole set with the same count, encrypts it, saves and returns the plaintext codes. A code spent at the challenge goes through `redeemRecoveryCode()`, which, under a lock, swaps that one code for a fresh random one the user never sees, so the spent code stops working and the count stays the same. The column holds `encrypt(json_encode($codes))`: decrypt, then JSON-decode, or call `recoveryCodes()`.

### The challenge uses a short-lived encrypted token

When the user has confirmed two-factor, a password login, a social sign-in or a sign-in handoff returns `{two_factor: true, two_factor_token}` instead of a token. `POST auth/otp/verify` is the exception: it issues a token without the challenge. The `two_factor_token` encrypts `user_id`, `expires_at` (now plus `two_factor.challenge_token_ttl` minutes, default `5`) and `remember` (the sign-in's `remember_me`). `POST auth/two-factor-challenge` decrypts it, checks the expiry, resolves the user and accepts either a TOTP `code` (through `verifyOnce()`, so an observed code cannot sign in twice) or a `recovery_code`. On success it calls `createAuthToken()` with `remember` (a token without the key reads as false) and returns the usual authenticated response.

### Config and storage

- `two_factor_confirmed_at` is the gate: a secret with a null timestamp is an unfinished enrollment.
- The secret and the recovery codes are stored with `encrypt()`; read them through `twoFactorSecret()` and `recoveryCodes()`, never from the raw columns.
- `two_factor.company_name` defaults to `APP_NAME` and is the issuer label in authenticator apps.
- `two_factor.geoip_db_path` (default `null`) points at a MaxMind database that `SessionLocation` reads to show a location on the sessions list; null turns that off and nothing else breaks.
- Keep `challenge_token_ttl` long enough for the user to switch to the authenticator app and short enough to limit exposure.
