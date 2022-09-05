<?php

namespace Esign\EmailWhitelisting\Listeners;

use Esign\EmailWhitelisting\Models\WhitelistedEmailAddress;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mime\Address;

class WhitelistNotificationEmailAddresses
{

    public function handle(NotificationSending $event): bool
    {
        if ($this->shouldWhitelistMailAddresses($event->channel)) {

            if (config('email-whitelisting.redirect_mails')) {
                $this->redirectNotification($event);
            } else {
                return $this->whitelistMailAddresses($event);
            }
        }

        return true;
    }

    protected function shouldWhitelistMailAddresses(string $notificationChannel): bool
    {
        return !app()->isProduction() &&
            config('email-whitelisting.whitelist_mails') &&
            $notificationChannel == 'mail';
    }

    protected function whitelistMailAddresses(NotificationSending $event): bool
    {
        $toEmail = $event->notifiable->email;

        return WhitelistedEmailAddress::where('email', $toEmail)->exists();
    }

    protected function redirectNotification(NotificationSending $event)
    {
        // $emailsSendTo = WhitelistedEmailAddress::where('redirect_email', true)->pluck('email');


    }
}
