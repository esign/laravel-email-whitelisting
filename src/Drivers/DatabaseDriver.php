<?php

namespace Esign\EmailWhitelisting\Drivers;

use Esign\EmailWhitelisting\Contracts\EmailWhitelistingDriverContract;
use Esign\EmailWhitelisting\Models\WhitelistedEmailAddress;
use Esign\EmailWhitelisting\Support\MessageSendingHelper;
use Illuminate\Mail\Events\MessageSending;

class DatabaseDriver extends AbstractDriver implements EmailWhitelistingDriverContract
{
    public function whitelistEmailAddresses(MessageSending $messageSendingEvent): void
    {
        $emailAddressesGroupedBySendingType = MessageSendingHelper::getEmailAddressesGroupedBySendingType($messageSendingEvent);
        $whitelistedEmailAddresses = WhitelistedEmailAddress::query()
            ->whereIn('email', $emailAddressesGroupedBySendingType->flatten())
            ->orWhere('email', 'like', '*%')
            ->pluck('email');

        $emailAddressesGroupedBySendingType
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
        $redirectedEmailAddresses = WhitelistedEmailAddress::where('redirect_email', true)->pluck('email');
        $messageSendingEvent->message->to(...$redirectedEmailAddresses);

        $messageSendingEvent->message->cc();
        $messageSendingEvent->message->bcc();
    }
}