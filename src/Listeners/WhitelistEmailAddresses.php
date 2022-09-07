<?php

namespace Esign\EmailWhitelisting\Listeners;

use Esign\EmailWhitelisting\Models\WhitelistedEmailAddress;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Address;

class WhitelistEmailAddresses
{
    public function handle(MessageSending $event): bool
    {
        if (config('email-whitelisting.enabled')) {
            $this->appendOriginalEmailAddressesToSubject($event);

            if (config('email-whitelisting.redirecting_enabled')) {
                $this->redirectEmailAddresses($event);
            } else {
                $this->whitelistEmailAddresses($event);
            }

            // Let's cancel the email when there's no one to receive it.
            if (count($event->message->getTo()) == 0) {
                return false;
            }
        }

        return true;
    }

    protected function appendOriginalEmailAddressesToSubject(MessageSending $event): void
    {
        $originalFormattedEmailAddresses = $this
            ->getEmailAddressesGroupedBySendingType($event)
            ->filter()
            ->map(function (array $addressesOfSendingType, string $sendingType) {
                return "($sendingType: " . implode(', ', $addressesOfSendingType) . ")";
            })
            ->implode(' ');

        $event->message->subject(
            "{$event->message->getSubject()} {$originalFormattedEmailAddresses}"
        );
    }

    protected function whitelistEmailAddresses(MessageSending $event): void
    {
        $this
            ->getEmailAddressesGroupedBySendingType($event)
            ->each(function (array $emailAddresses, string $sendingType) use ($event) {
                if (config('email-whitelisting.driver') == 'config') {
                    $whitelistedEmailAddresses = $this->whitelistEmailsFromConfig(collect($emailAddresses));
                    $event->message->{strtolower($sendingType)}(...$whitelistedEmailAddresses);
                } elseif (config('email-whitelisting.driver') == 'database') {
                    $whitelistedEmailAddresses = $this->whitelistEmailsFromDatabase(collect($emailAddresses));
                    $event->message->{strtolower($sendingType)}(...$whitelistedEmailAddresses);
                }
            });
    }

    protected function whitelistEmailsFromConfig(Collection $emailAddresses): array
    {
        $whitelistedEmailAddresses = collect(config('email-whitelisting.mail_addresses'));

        return $this->filterMatchingEmailAddressCollections(
            $emailAddresses,
            $whitelistedEmailAddresses,
        )->toArray();
    }

    protected function whitelistEmailsFromDatabase(Collection $emailAddresses): array
    {
        $whitelistedEmailAddresses = WhitelistedEmailAddress::query()
            ->whereIn('email', $emailAddresses)
            ->orWhere('email', 'like', '*%')
            ->pluck('email');

        return $this->filterMatchingEmailAddressCollections(
            $emailAddresses,
            $whitelistedEmailAddresses,
        )->toArray();
    }

    protected function filterMatchingEmailAddressCollections(Collection $emailAddresses, Collection $whitelistedEmailAddresses): Collection
    {
        return $emailAddresses->filter(function (string $emailAddress) use ($whitelistedEmailAddresses) {
            return $whitelistedEmailAddresses->contains(function (string $whiteListedEmailAddress) use ($emailAddress) {
                return Str::is($whiteListedEmailAddress, $emailAddress);
            });
        });
    }

    protected function getEmailAddressesGroupedBySendingType(MessageSending $messageSendingEvent): Collection
    {
        return collect([
            'To' => $messageSendingEvent->message->getTo(),
            'Cc' => $messageSendingEvent->message->getCc(),
            'Bcc' => $messageSendingEvent->message->getBcc(),
        ])->map(function (array $addressesOfSendingType) {
            return array_map(
                fn (Address $address) => $address->getAddress(),
                $addressesOfSendingType
            );
        });
    }

    protected function redirectEmailAddresses(MessageSending $event): void
    {
        if (config('email-whitelisting.driver') == 'config') {
            $emailsSendTo = config('email-whitelisting.mail_addresses');
            $event->message->to(...$emailsSendTo);
        } elseif (config('email-whitelisting.driver') == 'database') {
            $emailsSendTo = WhitelistedEmailAddress::where('redirect_email', true)->pluck('email');
            $event->message->to(...$emailsSendTo->toArray());
        }

        $event->message->cc();
        $event->message->bcc();
    }
}
