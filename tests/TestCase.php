<?php

namespace :vendor_namespace\Tests;

use :vendor_namespace\:studly_package_nameServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [:studly_package_nameServiceProvider::class];
    }
} 