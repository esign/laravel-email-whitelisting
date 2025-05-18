<?php

namespace Esign\EmailWhitelisting\Drivers;

use Esign\EmailWhitelisting\Contracts\EmailWhitelistingDriverContract;
use Esign\EmailWhitelisting\Models\WhitelistedEmailAddress;
use Esign\EmailWhitelisting\Support\MessageSendingHelper;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Collection;

class DatabaseDriver implements EmailWhitelistingDriverContract
{
    public function whitelistEmailAddresses(MessageSending $messageSendingEvent): Collection
    {
        $emailAddresses = MessageSendingHelper::getAllEmailAddresses($messageSendingEvent);

        return WhitelistedEmailAddress::query()
            ->whereIn('email', $emailAddresses)
            ->orWhere('email', 'like', '*%')
            ->pluck('email');
    }

    public function redirectEmailAddresses(MessageSending $messageSendingEvent): Collection
    {
        return WhitelistedEmailAddress::where('redirect_email', true)->pluck('email');
    }
}
