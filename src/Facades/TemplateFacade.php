<?php

namespace :vendor_namespace\Facades;

use Illuminate\Support\Facades\Facade;

class :studly_package_nameFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ':package_name';
    }
}
