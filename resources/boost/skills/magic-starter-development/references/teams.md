# Teams

## Where to Find It

- Actions: `src/Actions/CreateTeam.php`, `AddTeamMember.php`, `InviteTeamMember.php`, `AcceptTeamInvitation.php`, `RemoveTeamMember.php`, `UpdateTeamMemberRole.php`, `DeleteTeam.php`, `SubscriptionGuardedDeleteTeam.php`
- Listener: `src/Listeners/CreatePersonalTeamListener.php`
- Enum: `src/Enums/Role.php`
- Model: `src/Models/Team.php`
- Contracts: `src/Contracts/`, one interface per action

## What to Watch For

### Personal teams

`CreatePersonalTeamListener` creates a personal team on Laravel's `Registered` event while the `teams` feature is on. Its idempotency check is a database query rather than the cached relation, so a second dispatch of the event finds the team and stops. The name comes from `magic-starter::teams.personal_team_name`, in the user's `locale` or the app locale. The `personal_team` column marks it: `DELETE teams/{team}` refuses a personal team, and its owner cannot leave it (`personal_team_cannot_leave`).

### Team creation

`CreateTeam::create()` validates the name, creates the team through `$user->ownedTeams()->create()` with `personal_team = false`, attaches the creator on the pivot as `Role::OWNER`, clears the cached `ownedTeams` and `teams` relations with `unsetRelation()`, then force-fills the user's `current_team_id`. The relation reset is there so a Gate check right after sees the new team.

### Membership and the pivot model

Every member relation goes through the pivot: `Team::users()` chains `->using(MagicStarter::membershipModel())->withPivot('role')`. Use `membershipModel()` rather than `TeamUser` or a raw `team_user` query, because an app can replace the pivot model. Role changes use `updateExistingPivot()` and removal uses `detach()`. Owner detection compares `(string) $team->user_id === (string) $member->id`; the string cast keeps it right for both UUID and integer keys.

### Roles

`Role` is a backed enum with `OWNER`, `ADMIN`, `EDITOR` and `MEMBER`. `Role::assignable()` returns only `ADMIN`, `EDITOR` and `MEMBER`, because ownership follows `teams.user_id` rather than a pivot assignment, and it moves only through `TransfersTeamOwnership`. Build `in:` rules with `Role::assignableForValidation()`, and do not accept `owner` as a role from a request.

### Invitations

`InviteTeamMember` lower-cases the email, creates a `TeamInvitation` with a 32-character random token and sends `TeamInvitationNotification` to the address through an anonymous mail route, in the inviter's locale. Lookup is by email. A new invitation has no `expires_at`, so it does not expire; `ResendTeamInvitation` sets `expires_at` to now plus `magic-starter.invitation_expiry_days` (default `7`). `AcceptTeamInvitation` deletes an expired invitation and refuses it.

### Deletion

`DeletesTeams` is bound to `SubscriptionGuardedDeleteTeam`, which refuses while the team is billed and then runs `DeleteTeam::delete()`: delete the team photo from the team photo disk, detach every member and delete the invitations, delete the team (force-delete on a model that soft deletes), and dispatch `TeamDeleted`. The controller checks `personal_team` before calling it and moves the owner's `current_team_id` off a deleted team. With the `restorable-teams` feature on, `DELETE teams/{team}` trashes through `TrashesTeams` instead.
