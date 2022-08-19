<?php

namespace Esign\EmailWhitelisting\Facades;

use Illuminate\Support\Facades\Facade;

class EmailWhitelistingFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'email-whitelisting';
    }
}
