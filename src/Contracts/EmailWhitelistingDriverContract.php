<?php

namespace Esign\EmailWhitelisting\Contracts;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Collection;

interface EmailWhitelistingDriverContract
{
    public function redirectEmailAddresses(MessageSending $messageSendingEvent): Collection;

    public function whitelistEmailAddresses(MessageSending $messageSendingEvent): Collection;
}
