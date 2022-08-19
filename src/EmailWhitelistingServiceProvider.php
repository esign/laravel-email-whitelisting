<?php

namespace Esign\EmailWhitelisting;

use Illuminate\Support\ServiceProvider;

class EmailWhitelistingServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([$this->configPath() => config_path('email-whitelisting.php')], 'config');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom($this->configPath(), 'email-whitelisting');

        $this->app->singleton('email-whitelisting', function () {
            return new EmailWhitelisting;
        });
    }

    protected function configPath(): string
    {
        return __DIR__ . '/../config/email-whitelisting.php';
    }
}
