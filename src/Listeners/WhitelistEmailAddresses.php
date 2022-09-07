<?php

namespace Esign\EmailWhitelisting\Listeners;

use Esign\EmailWhitelisting\Contracts\EmailWhitelistingDriverContract;
use Esign\EmailWhitelisting\Support\MessageSendingHelper;
use Illuminate\Mail\Events\MessageSending;


class WhitelistEmailAddresses
{
    public function handle(MessageSending $messageSendingEvent): bool
    {
        if (config('email-whitelisting.enabled')) {
            /** @var EmailWhitelistingDriverContract */
            $driver = app(EmailWhitelistingDriverContract::class);
            $this->appendOriginalEmailAddressesToSubject($messageSendingEvent);

            if (config('email-whitelisting.redirecting_enabled')) {
                $driver->redirectEmailAddresses($messageSendingEvent);
            } else {
                $driver->whitelistEmailAddresses($messageSendingEvent);
            }

            // Let's cancel the email when there's no one to receive it.
            if (count($messageSendingEvent->message->getTo()) == 0) {
                return false;
            }
        }

        return true;
    }

    protected function appendOriginalEmailAddressesToSubject(MessageSending $event): void
    {
        $originalFormattedEmailAddresses = MessageSendingHelper::getEmailAddressesGroupedBySendingType($event)
            ->filter()
            ->map(function (array $addressesOfSendingType, string $sendingType) {
                return "($sendingType: " . implode(', ', $addressesOfSendingType) . ")";
            })
            ->implode(' ');

        $event->message->subject(
            "{$event->message->getSubject()} {$originalFormattedEmailAddresses}"
        );
    }
}
