<?php

namespace :vendor_namespace;

use Illuminate\Support\ServiceProvider;

class :studly_package_nameServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([$this->configPath() => config_path(':package_name.php')], 'config');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom($this->configPath(), ':package_name');

        $this->app->singleton(':package_name', function () {
            return new :studly_package_name;
        });
    }

    protected function configPath(): string
    {
        return __DIR__ . '/../config/:package_name.php';
    }
}
