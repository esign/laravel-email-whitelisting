<?php

namespace Esign\EmailWhitelisting\Support;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Collection;
use Symfony\Component\Mime\Address;

class MessageSendingHelper
{
    public static function getEmailAddressesGroupedBySendingType(MessageSending $messageSendingEvent): Collection
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

    public static function getAllEmailAddresses(MessageSending $messageSendingEvent): Collection
    {
        return self::getEmailAddressesGroupedBySendingType($messageSendingEvent)
            ->flatten()
            ->unique();
    }
}
