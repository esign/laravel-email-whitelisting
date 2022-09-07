<?php

namespace Esign\EmailWhitelisting\Contracts;

use Illuminate\Mail\Events\MessageSending;

interface EmailWhitelistingDriverContract
{
    public function redirectEmailAddresses(MessageSending $messageSendingEvent): void;
    public function whitelistEmailAddresses(MessageSending $messageSendingEvent): void;
}