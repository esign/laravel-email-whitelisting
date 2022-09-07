<?php

namespace Esign\EmailWhitelisting\Drivers;

use Esign\EmailWhitelisting\Contracts\EmailWhitelistingDriverContract;
use Esign\EmailWhitelisting\Support\MessageSendingHelper;
use Illuminate\Mail\Events\MessageSending;

class ConfigurationDriver extends AbstractDriver implements EmailWhitelistingDriverContract
{
    public function whitelistEmailAddresses(MessageSending $messageSendingEvent): void
    {
        $whitelistedEmailAddresses = collect(config('email-whitelisting.mail_addresses'));

        MessageSendingHelper::getEmailAddressesGroupedBySendingType($messageSendingEvent)
            ->each(function (array $emailAddresses, string $sendingType) use ($messageSendingEvent, $whitelistedEmailAddresses) {
                $matchingWhitelistedEmailAddresses = $this->filterMatchingEmailAddressCollections(
                    collect($emailAddresses),
                    $whitelistedEmailAddresses,
                )->toArray();
                $messageSendingEvent->message->{strtolower($sendingType)}(...$matchingWhitelistedEmailAddresses);
            });
    }

    public function redirectEmailAddresses(MessageSending $messageSendingEvent): void
    {
        $emailsSendTo = config('email-whitelisting.mail_addresses');
        $messageSendingEvent->message->to(...$emailsSendTo);

        $messageSendingEvent->message->cc();
        $messageSendingEvent->message->bcc();
    }
}