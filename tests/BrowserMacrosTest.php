<?php

/**
 * The browser test vocabulary this plugin registers on the FakeBridge
 * (assertBrowsed / assertOpenedInApp / assertOpenedAuth /
 * assertNothingBrowsed) — the sugar app developers use instead of raw
 * bridge method strings.
 *
 * Skipped on cores whose FakeBridge predates macro support.
 */

use Native\Mobile\Browser;
use Native\Mobile\Testing\FakeBridge;
use Native\Mobile\Testing\Native;
use PHPUnit\Framework\AssertionFailedError;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    if (! method_exists(FakeBridge::class, 'macro')) {
        $this->markTestSkipped('This core\'s FakeBridge does not support macros.');
    }

    $this->bridge = Native::fakeBridge();
});

describe('assertBrowsed()', function () {
    it('passes when the system browser was opened', function () {
        $this->bridge->respondTo('Browser.Open', ['success' => true]);

        (new Browser)->open('https://nativephp.com');

        $this->bridge->assertBrowsed();
    });

    it('passes when the in-app browser was opened', function () {
        $this->bridge->respondTo('Browser.OpenInApp', ['success' => true]);

        (new Browser)->inApp('https://nativephp.com');

        $this->bridge->assertBrowsed();
    });

    it('passes when an auth session was opened', function () {
        $this->bridge->respondTo('Browser.OpenAuth', ['success' => true]);

        (new Browser)->auth('https://accounts.example.com/oauth/authorize');

        $this->bridge->assertBrowsed();
    });

    it('matches the exact opened url across any of the three methods', function () {
        $this->bridge->respondTo('Browser.OpenInApp', ['success' => true]);

        (new Browser)->inApp('https://nativephp.com/docs');

        $this->bridge->assertBrowsed('https://nativephp.com/docs');
    });

    it('fails when nothing was opened', function () {
        expect(fn () => $this->bridge->assertBrowsed())
            ->toThrow(AssertionFailedError::class);
    });

    it('fails when a different url was opened, naming what was', function () {
        $this->bridge->respondTo('Browser.Open', ['success' => true]);

        (new Browser)->open('https://actual.example.com');

        expect(fn () => $this->bridge->assertBrowsed('https://expected.example.com'))
            ->toThrow(AssertionFailedError::class, 'actual.example.com');
    });
});

describe('assertOpenedInApp()', function () {
    it('passes when the in-app browser was opened', function () {
        $this->bridge->respondTo('Browser.OpenInApp', ['success' => true]);

        (new Browser)->inApp('https://nativephp.com');

        $this->bridge->assertOpenedInApp();
    });

    it('matches the exact url', function () {
        $this->bridge->respondTo('Browser.OpenInApp', ['success' => true]);

        (new Browser)->inApp('https://nativephp.com/docs');

        $this->bridge->assertOpenedInApp('https://nativephp.com/docs');
    });

    it('fails when nothing was opened in-app', function () {
        expect(fn () => $this->bridge->assertOpenedInApp())
            ->toThrow(AssertionFailedError::class);
    });

    it('does not pass for a system browser open', function () {
        $this->bridge->respondTo('Browser.Open', ['success' => true]);

        (new Browser)->open('https://nativephp.com');

        expect(fn () => $this->bridge->assertOpenedInApp())
            ->toThrow(AssertionFailedError::class);
    });

    it('fails when a different url was opened in-app, naming what was', function () {
        $this->bridge->respondTo('Browser.OpenInApp', ['success' => true]);

        (new Browser)->inApp('https://actual.example.com');

        expect(fn () => $this->bridge->assertOpenedInApp('https://expected.example.com'))
            ->toThrow(AssertionFailedError::class, 'actual.example.com');
    });
});

describe('assertOpenedAuth()', function () {
    it('passes when an auth session was opened', function () {
        $this->bridge->respondTo('Browser.OpenAuth', ['success' => true]);

        (new Browser)->auth('https://accounts.example.com/oauth/authorize');

        $this->bridge->assertOpenedAuth();
    });

    it('matches the exact url', function () {
        $this->bridge->respondTo('Browser.OpenAuth', ['success' => true]);

        (new Browser)->auth('https://accounts.example.com/oauth/authorize?client_id=abc');

        $this->bridge->assertOpenedAuth('https://accounts.example.com/oauth/authorize?client_id=abc');
    });

    it('fails when nothing was opened for auth', function () {
        expect(fn () => $this->bridge->assertOpenedAuth())
            ->toThrow(AssertionFailedError::class);
    });

    it('does not pass for an in-app open', function () {
        $this->bridge->respondTo('Browser.OpenInApp', ['success' => true]);

        (new Browser)->inApp('https://nativephp.com');

        expect(fn () => $this->bridge->assertOpenedAuth())
            ->toThrow(AssertionFailedError::class);
    });

    it('fails when a different url was opened for auth, naming what was', function () {
        $this->bridge->respondTo('Browser.OpenAuth', ['success' => true]);

        (new Browser)->auth('https://actual.example.com/oauth');

        expect(fn () => $this->bridge->assertOpenedAuth('https://expected.example.com/oauth'))
            ->toThrow(AssertionFailedError::class, 'actual.example.com');
    });
});

describe('assertNothingBrowsed()', function () {
    it('passes when nothing was opened', function () {
        $this->bridge->assertNothingBrowsed();
    });

    it('fails after the system browser was opened', function () {
        $this->bridge->respondTo('Browser.Open', ['success' => true]);

        (new Browser)->open('https://nativephp.com');

        expect(fn () => $this->bridge->assertNothingBrowsed())
            ->toThrow(AssertionFailedError::class);
    });

    it('fails after the in-app browser was opened', function () {
        $this->bridge->respondTo('Browser.OpenInApp', ['success' => true]);

        (new Browser)->inApp('https://nativephp.com');

        expect(fn () => $this->bridge->assertNothingBrowsed())
            ->toThrow(AssertionFailedError::class);
    });

    it('fails after an auth session was opened', function () {
        $this->bridge->respondTo('Browser.OpenAuth', ['success' => true]);

        (new Browser)->auth('https://accounts.example.com/oauth/authorize');

        expect(fn () => $this->bridge->assertNothingBrowsed())
            ->toThrow(AssertionFailedError::class);
    });
});
