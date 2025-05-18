<?php

namespace Esign\EmailWhitelisting\Listeners;

use Esign\EmailWhitelisting\Contracts\EmailWhitelistingDriverContract;
use Esign\EmailWhitelisting\Events\EmailAddressesSkipped;
use Esign\EmailWhitelisting\Support\MessageSendingHelper;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WhitelistEmailAddresses
{
    public function __construct(
        protected EmailWhitelistingDriverContract $emailWhitelistingDriver,
    ) {
    }

    public function handle(MessageSending $messageSendingEvent): bool
    {
        if (config('email-whitelisting.enabled')) {
            $this->appendOriginalEmailAddressesToSubject($messageSendingEvent);

            if (config('email-whitelisting.redirecting_enabled')) {
                $this->redirectEmailAddresses($messageSendingEvent);
            } else {
                $this->whitelistEmailAddresses($messageSendingEvent);
            }

            // Let's cancel the email when there's no one to receive it.
            if (count($messageSendingEvent->message->getTo()) == 0) {
                return false;
            }
        }

        return true;
    }

    protected function redirectEmailAddresses(MessageSending $messageSendingEvent): void
    {
        $redirectEmailAddresses = $this->emailWhitelistingDriver->redirectEmailAddresses($messageSendingEvent);
        $messageSendingEvent->message->to(...$redirectEmailAddresses);

        $messageSendingEvent->message->cc();
        $messageSendingEvent->message->bcc();
    }

    protected function whitelistEmailAddresses(MessageSending $messageSendingEvent): void
    {
        $whitelistedEmailAddresses = $this->emailWhitelistingDriver->whitelistEmailAddresses($messageSendingEvent);

        MessageSendingHelper::getEmailAddressesGroupedBySendingType($messageSendingEvent)
            ->each(function (array $emailAddresses, string $sendingType) use ($messageSendingEvent, $whitelistedEmailAddresses) {
                $originalEmailAddresses = collect($emailAddresses);
                $matchingWhitelistedEmailAddresses = $this->filterWhitelistedEmailAddresses($originalEmailAddresses, $whitelistedEmailAddresses);
                $skippedEmailAddresses = $originalEmailAddresses->diff($matchingWhitelistedEmailAddresses);

                if ($skippedEmailAddresses->isNotEmpty()) {
                    event(new EmailAddressesSkipped($skippedEmailAddresses, $originalEmailAddresses, $messageSendingEvent, $sendingType));
                }

                $messageSendingEvent->message->{strtolower($sendingType)}(...$matchingWhitelistedEmailAddresses->toArray());
            });
    }

    protected function filterWhitelistedEmailAddresses(
        Collection $originalEmailAddresses,
        Collection $whitelistedEmailAddresses,
    ): Collection {
        return $originalEmailAddresses->filter(function (string $emailAddress) use ($whitelistedEmailAddresses) {
            return $whitelistedEmailAddresses->contains(function (string $whiteListedEmailAddress) use ($emailAddress) {
                return Str::is($whiteListedEmailAddress, $emailAddress);
            });
        });
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
