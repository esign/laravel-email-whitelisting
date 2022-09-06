<?php

namespace Esign\EmailWhitelisting\Providers;

use Esign\EmailWhitelisting\Listeners\WhitelistEmailAddresses;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSending;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        MessageSending::class => [
            WhitelistEmailAddresses::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
