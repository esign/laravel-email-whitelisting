<?php

namespace Esign\EmailWhitelisting\Listeners;

use Esign\EmailWhitelisting\Models\WhitelistedEmailAddress;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Address;

class WhitelistEmailAddresses
{
    public function handle(MessageSending $event): bool
    {
        if ($this->shouldWhitelistMailAddresses()) {
            $this->addOriginalEmailAddressesInSubject($event);

            if (config('email-whitelisting.redirect_mails')) {
                $this->redirectMail($event);
            } else {
                $this->whitelistMailAddresses($event);
            }

            if (count($event->message->getTo()) == 0) {
                // cancel mail when no send to addresses
                return false;
            }
        }

        return true;
    }

    protected function shouldWhitelistMailAddresses(): bool
    {
        return ! app()->isProduction() && config('email-whitelisting.enabled');
    }

    protected function addOriginalEmailAddressesInSubject(MessageSending $event): void
    {
        $originalFormattedEmailAddresses = collect([
            'To' => $event->message->getTo(),
            'Cc' => $event->message->getCc(),
            'Bcc' => $event->message->getBcc(),
        ])
            // Map the array of address objects to an array of actual email addresses
            ->map(function (array $addressesOfSendingType) {
                return array_map(
                    fn (Address $address) => $address->getAddress(),
                    $addressesOfSendingType
                );
            })
            // Let's filter out any empty arrays
            ->filter()
            // Format the sending type with the corresponding email addresses
            ->map(function (array $addressesOfSendingType, string $sendingType) {
                return "($sendingType: " . implode(', ', $addressesOfSendingType) . ")";
            })
            // Add a space between the sending types for some better readability
            ->implode(' ');

        $event->message->subject(
            "{$event->message->getSubject()} {$originalFormattedEmailAddresses}"
        );
    }

    protected function whitelistMailAddresses(MessageSending $event): void
    {
        foreach (['To', 'Cc', 'Bcc'] as $type) {
            if ($originalAddresses = $event->message->{'get' . $type}()) {
                $typeAddresses = collect($originalAddresses)->map(function (Address $item) {
                    return $item->getAddress();
                });

                if (config('email-whitelisting.driver') == 'config') {
                    $emailsSendTo = $this->whitelistEmailsFromConfig($typeAddresses);
                    $event->message->{strtolower($type)}(...$emailsSendTo);
                } elseif (config('email-whitelisting.driver') == 'database') {
                    $emailsSendTo = $this->whitelistEmailsFromDatabase($typeAddresses);
                    $event->message->{strtolower($type)}(...$emailsSendTo);
                }
            }
        }
    }

    protected function whitelistEmailsFromConfig(Collection $emailAddresses): array
    {
        $whitelistedEmailAddresses = collect(config('email-whitelisting.mail_addresses'));

        return $emailAddresses->filter(function (string $emailAddress) use ($whitelistedEmailAddresses) {
            return $whitelistedEmailAddresses->contains(function (string $whiteListedEmailAddress) use ($emailAddress) {
                return Str::is($whiteListedEmailAddress, $emailAddress);
            });
        })->toArray();
    }

    protected function whitelistEmailsFromDatabase(Collection $typeAddresses): array
    {
        $whitelistedEmailAddresses = WhitelistedEmailAddress::whereIn('email', $typeAddresses)->pluck('email');
        $wildcards = WhitelistedEmailAddress::where('email', 'like', '*%')->pluck('email')->map(function (string $wildcard) {
            return Str::after($wildcard, '*');
        })->toArray();

        $addressesFromWildCards = $typeAddresses->where(function (string $typeAddress) use ($wildcards) {
            return Str::endsWith($typeAddress, $wildcards);
        });

        return array_unique([...$whitelistedEmailAddresses, ...$addressesFromWildCards]);
    }

    protected function redirectMail(MessageSending $event): void
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
