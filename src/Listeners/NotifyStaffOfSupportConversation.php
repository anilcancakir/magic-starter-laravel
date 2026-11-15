<?php

namespace AnilcanCakir\MagicStarter\Listeners;

use AnilcanCakir\MagicStarter\Events\SupportConversationNeedsStaff;
use AnilcanCakir\MagicStarter\Notifications\SupportConversationNeedsStaffNotification;
use AnilcanCakir\MagicStarter\Support\SupportStaffRecipients;
use Illuminate\Support\Facades\Notification;

/**
 * Alerts every support recipient that a conversation is waiting on staff.
 */
class NotifyStaffOfSupportConversation
{
    public function __construct(
        private readonly SupportStaffRecipients $recipients,
    ) {}

    public function handle(SupportConversationNeedsStaff $event): void
    {
        $recipients = $this->recipients->all();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new SupportConversationNeedsStaffNotification($event->conversation, $event->reason),
        );
    }
}
