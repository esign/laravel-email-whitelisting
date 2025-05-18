<?php

namespace Esign\EmailWhitelisting\Drivers;

use Esign\EmailWhitelisting\Contracts\EmailWhitelistingDriverContract;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Collection;

class ConfigurationDriver implements EmailWhitelistingDriverContract
{
    public function whitelistEmailAddresses(MessageSending $messageSendingEvent): Collection
    {
        return collect((array) config('email-whitelisting.mail_addresses'));
    }

    public function redirectEmailAddresses(MessageSending $messageSendingEvent): Collection
    {
        return collect((array) config('email-whitelisting.mail_addresses'));
    }
}
