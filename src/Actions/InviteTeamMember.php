<?php

namespace AnilcanCakir\MagicStarter\Actions;

use AnilcanCakir\MagicStarter\Contracts\InvitesTeamMembers;
use AnilcanCakir\MagicStarter\Notifications\TeamInvitationNotification;
use AnilcanCakir\MagicStarter\Support\MailLocale;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Default action for inviting a member to a team via email.
 */
class InviteTeamMember implements InvitesTeamMembers
{
    /**
     * Invite a new team member to the given team.
     *
     * @param  Authenticatable  $user  The user performing the invitation.
     * @param  Model  $team  The team to invite the member to.
     * @param  string  $email  The email address to invite.
     * @param  string  $role  The role to assign on acceptance.
     * @return Model The created invitation.
     */
    public function invite(Authenticatable $user, Model $team, string $email, string $role): Model
    {
        // Stored lower-case so the accept check and the already-sent check compare like with like.
        $email = Str::lower($email);

        $invitation = $team->invitations()->create([
            'email' => $email,
            'role' => $role,
            'token' => Str::random(32),
            'expires_at' => now()->addDays((int) config('magic-starter.invitation_expiry_days', 7)),
        ]);

        // The recipient is an anonymous notifiable with no locale of its own, so the
        // mail is pinned to the inviter's language at dispatch.
        Notification::route('mail', $email)->notify(
            (new TeamInvitationNotification($invitation))->locale(MailLocale::for($user)),
        );

        return $invitation;
    }
}
