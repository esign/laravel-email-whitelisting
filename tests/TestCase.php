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

    protected function getEnvironmentSetUp($app)
    {
        $migration = include __DIR__ . '/../database/migrations/create_whitelist_email_addresses_table.php.stub';
        $migration->up();
    }
} 