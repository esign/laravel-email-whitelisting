<?php

namespace Esign\EmailWhitelisting\Listeners;

use Esign\EmailWhitelisting\Models\WhitelistedEmailAddress;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Address;

class WhitelistEmailAddresses
{
    public function handle(MessageSending $event): bool
    {
        if ($this->shouldWhitelistMailAddresses()) {
            $this->addOriginalToAddressesInSubject($event);

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
        return ! app()->isProduction() && config('email-whitelisting.whitelist_mails');
    }

    protected function addOriginalToAddressesInSubject(MessageSending $event): void
    {
        $subject = $event->message->getSubject() . ' (';

        foreach (['To', 'Cc', 'Bcc'] as $type) {
            if ($originalAddresses = $event->message->{'get' . $type}()) {
                $typeAddresses = collect($originalAddresses)->map(function (Address $item) {
                    return $item->getAddress();
                });

                $subject .= $type . ': ' . $typeAddresses->implode(', ') . ', ';
            }
        }

        $subject .= ')';

        $event->message->subject($subject);
    }

    protected function whitelistMailAddresses(MessageSending $event): void
    {
        foreach (['To', 'Cc', 'Bcc'] as $type) {
            if ($originalAddresses = $event->message->{'get' . $type}()) {
                $typeAddresses = collect($originalAddresses)->map(function (Address $item) {
                    return $item->getAddress();
                });

                if (config('email-whitelisting.driver') == 'config') {
                    $whitelistedEmailAddresses = Arr::where(config('email-whitelisting.mail_addresses'), function (string $email) {
                        return ! Str::startsWith($email, '*');
                    });
                    $wildcards = collect(config('email-whitelisting.mail_addresses'))->where(function (string $email) {
                        return Str::startsWith($email, '*');
                    })->map(function (string $wildcard) {
                        return Str::after($wildcard, '*');
                    })->toArray();

                    $addressesFromWildCards = $typeAddresses->where(function (string $typeAddress) use ($wildcards) {
                        return Str::endsWith($typeAddress, $wildcards);
                    });

                    $emailsSendTo = array_unique([...$whitelistedEmailAddresses, ...$addressesFromWildCards]);
                    $event->message->{strtolower($type)}(...$emailsSendTo);
                } elseif (config('email-whitelisting.driver') == 'database') {
                    $whitelistedEmailAddresses = WhitelistedEmailAddress::whereIn('email', $typeAddresses)->pluck('email');
                    $wildcards = WhitelistedEmailAddress::where('email', 'like', '*%')->pluck('email')->map(function (string $wildcard) {
                        return Str::after($wildcard, '*');
                    })->toArray();
                    $addressesFromWildCards = Arr::where($typeAddresses->toArray(), function (string $typeAddress) use ($wildcards) {
                        return Str::endsWith($typeAddress, $wildcards);
                    });

                    $emailsSendTo = array_unique([...$whitelistedEmailAddresses, ...$addressesFromWildCards]);
                    $event->message->{strtolower($type)}(...$emailsSendTo);
                }
            }
        }
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
