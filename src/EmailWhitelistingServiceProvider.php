<?php

namespace Esign\EmailWhitelisting;

use Esign\EmailWhitelisting\Contracts\EmailWhitelistingDriverContract;
use Esign\EmailWhitelisting\Providers\EventServiceProvider;
use Illuminate\Support\ServiceProvider;

class EmailWhitelistingServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([$this->configPath() => config_path('email-whitelisting.php')], 'config');

            $this->publishes([
                $this->migrationPath() => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_whitelist_email_addresses_table.php'),
            ], 'migrations');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom($this->configPath(), 'email-whitelisting');
        $this->app->register(EventServiceProvider::class);
        $this->app->bind(EmailWhitelistingDriverContract::class, config('email-whitelisting.driver'));
    }

    protected function configPath(): string
    {
        return __DIR__ . '/../config/email-whitelisting.php';
    }

    protected function migrationPath(): string
    {
        return __DIR__ . '/../database/migrations/create_whitelist_email_addresses_table.php.stub';
    }
}
