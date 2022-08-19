<?php

namespace Esign\EmailWhitelisting\Tests;

use Esign\EmailWhitelisting\EmailWhitelistingServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [EmailWhitelistingServiceProvider::class];
    }
} 