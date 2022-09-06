<?php

namespace Esign\EmailWhitelisting\Listeners;

use Esign\EmailWhitelisting\Models\WhitelistedEmailAddress;
use Illuminate\Mail\Events\MessageSending;
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

                $emailsSendTo = WhitelistedEmailAddress::whereIn('email', $typeAddresses)->pluck('email');
                $event->message->{strtolower($type)}(...$emailsSendTo->toArray());
            }
        }
    }

    protected function redirectMail(MessageSending $event): void
    {
        $emailsSendTo = WhitelistedEmailAddress::where('redirect_email', true)->pluck('email');

        $event->message->to(...$emailsSendTo->toArray());
        $event->message->cc();
        $event->message->bcc();
    }
}
