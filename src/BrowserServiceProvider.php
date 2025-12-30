<?php

namespace Native\Mobile\Providers;

use Illuminate\Support\ServiceProvider;
use Native\Mobile\Browser;

class BrowserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Browser::class, function () {
            return new Browser;
        });
    }
}