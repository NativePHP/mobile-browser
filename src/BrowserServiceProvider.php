<?php

namespace Native\Mobile\Providers;

use Illuminate\Support\ServiceProvider;
use Native\Mobile\Browser;
use Native\Mobile\Providers\Testing\BrowserMacros;
use Native\Mobile\Testing\FakeBridge;

class BrowserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Browser::class, function () {
            return new Browser;
        });

        // Test sugar (assertBrowsed() etc.) — only under a test runner, and
        // only on a core whose FakeBridge is macroable (the method_exists
        // guard keeps older v4 and v3 cores fatal-free).
        if ($this->app->runningUnitTests()
            && class_exists(FakeBridge::class)
            && method_exists(FakeBridge::class, 'macro')) {
            BrowserMacros::register();
        }
    }
}