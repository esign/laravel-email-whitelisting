<?php

namespace Esign\EmailWhitelisting\Events;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Collection;

class EmailAddressesSkipped
{
    public function __construct(
        public Collection $skippedEmailAddresses,
        public Collection $originalEmailAddresses,
        public MessageSending $messageSendingEvent,
        public string $sendingType,
    ) {}
}
