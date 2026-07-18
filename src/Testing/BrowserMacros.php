<?php

namespace Native\Mobile\Providers\Testing;

use Native\Mobile\Testing\FakeBridge;
use PHPUnit\Framework\Assert;

/**
 * Browser test vocabulary for the NativePHP testing suite, registered as
 * FakeBridge macros so app tests read in browser terms instead of raw
 * bridge method strings:
 *
 *     Native::test(LoginScreen::class)
 *         ->tap('Continue with Google')
 *         ->assertOpenedAuth('https://accounts.google.com/oauth/authorize');
 *
 * open()/inApp()/auth() are one-shot fire-and-forget calls — nothing to
 * script a response for beyond the built-in
 * respondTo('Browser.Open', ['success' => true]) etc. — so this vocabulary
 * is assert* only.
 *
 * Registered by BrowserServiceProvider when the app is running unit tests
 * on a core whose FakeBridge supports macros.
 */
class BrowserMacros
{
    /** Bridge methods behind every URL open, regardless of mode. */
    public const OPEN_METHODS = [
        'Browser.Open',
        'Browser.OpenInApp',
        'Browser.OpenAuth',
    ];

    public static function register(): void
    {
        /**
         * Assert a URL was opened — system browser (open()), in-app
         * browser (inApp()), or auth session (auth()) — any of the three,
         * or exactly $url when given.
         */
        FakeBridge::macro('assertBrowsed', function (?string $url = null) {
            return BrowserMacros::assertUrlOpened(
                $this,
                BrowserMacros::OPEN_METHODS,
                $url,
                'a URL to be opened'
            );
        });

        /** Assert a URL was opened in the in-app browser (inApp()). */
        FakeBridge::macro('assertOpenedInApp', function (?string $url = null) {
            return BrowserMacros::assertUrlOpened(
                $this,
                ['Browser.OpenInApp'],
                $url,
                'a URL to be opened in the in-app browser'
            );
        });

        /** Assert a URL was opened in an authentication session (auth()). */
        FakeBridge::macro('assertOpenedAuth', function (?string $url = null) {
            return BrowserMacros::assertUrlOpened(
                $this,
                ['Browser.OpenAuth'],
                $url,
                'a URL to be opened in an authentication session'
            );
        });

        /** Assert nothing was opened — open(), inApp(), nor auth(). */
        FakeBridge::macro('assertNothingBrowsed', function () {
            foreach (BrowserMacros::OPEN_METHODS as $method) {
                $this->assertNotCalled($method);
            }

            return $this;
        });
    }

    /**
     * Shared assertion behind the assert*() macros above: collect the
     * urls passed to $methods and assert one was opened (any, or exactly
     * $url when given). Public because macro closures are rebound to
     * FakeBridge's scope, not this class's — a protected helper wouldn't
     * be reachable from inside them.
     */
    public static function assertUrlOpened(FakeBridge $bridge, array $methods, ?string $url, string $expectation): FakeBridge
    {
        $opened = array_map(
            fn (array $call) => $call['params']['url'] ?? '',
            array_merge(...array_map(fn (string $method) => $bridge->callsTo($method), $methods))
        );

        if ($url === null) {
            Assert::assertNotEmpty($opened, "Expected {$expectation}, but none was.");

            return $bridge;
        }

        Assert::assertContains(
            $url,
            $opened,
            "Expected [{$url}] to be opened. Opened: "
                .($opened === [] ? '(none)' : '['.implode('], [', $opened).']')
        );

        return $bridge;
    }
}
