<?php

/**
 * Contract tests for the Browser facade (which ships in nativephp/mobile)
 * against THIS plugin's bridge functions, driven through the NativePHP
 * FakeBridge. They pin the PHP → native contract: calling the facade fires
 * the right bridge function with the right payload and decodes native
 * responses as callers expect. Requires nativephp/mobile, so this file is
 * bound to the Testbench TestCase.
 *
 * Run with: ./test-plugins.sh --element browser
 */

use Native\Mobile\Browser;
use Native\Mobile\Testing\Native;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->bridge = Native::fakeBridge();
});

describe('open()', function () {
    it('fires Browser.Open with the url payload', function () {
        $this->bridge->respondTo('Browser.Open', ['success' => true]);

        (new Browser)->open('https://nativephp.com');

        $this->bridge->assertCalled('Browser.Open', function (array $p) {
            expect($p)->toBe(['url' => 'https://nativephp.com']);

            return true;
        });
    });

    it('returns true when the bridge reports success', function () {
        $this->bridge->respondTo('Browser.Open', ['success' => true]);

        expect((new Browser)->open('https://nativephp.com'))->toBeTrue();
    });

    it('returns false when the bridge reports success as false', function () {
        $this->bridge->respondTo('Browser.Open', ['success' => false]);

        expect((new Browser)->open('https://nativephp.com'))->toBeFalse();
    });

    it('returns false when the response has no success key', function () {
        $this->bridge->respondTo('Browser.Open', ['error' => 'failed to open']);

        expect((new Browser)->open('https://nativephp.com'))->toBeFalse();
    });

    it('returns false when nothing is scripted for the bridge call', function () {
        expect((new Browser)->open('https://nativephp.com'))->toBeFalse();

        $this->bridge->assertCalled('Browser.Open');
    });

    it('passes a url with query strings and a fragment verbatim', function () {
        $this->bridge->respondTo('Browser.Open', ['success' => true]);

        $url = 'https://nativephp.com/search?q=native+php&sort=asc#results';
        (new Browser)->open($url);

        $this->bridge->assertCalled('Browser.Open', function (array $p) use ($url) {
            expect($p['url'])->toBe($url);

            return true;
        });
    });

    it('passes a url with special and encoded characters verbatim', function () {
        $this->bridge->respondTo('Browser.Open', ['success' => true]);

        $url = 'https://example.com/path with spaces/?name=Jos%C3%A9&emoji=%F0%9F%9A%80';
        (new Browser)->open($url);

        $this->bridge->assertCalled('Browser.Open', function (array $p) use ($url) {
            expect($p['url'])->toBe($url);

            return true;
        });
    });

    it('does not validate the url before calling the bridge', function () {
        $this->bridge->respondTo('Browser.Open', ['success' => true]);

        (new Browser)->open('not-a-valid-url');

        $this->bridge->assertCalled('Browser.Open', function (array $p) {
            expect($p['url'])->toBe('not-a-valid-url');

            return true;
        });
    });

    it('calls the bridge exactly once per invocation', function () {
        $this->bridge->respondTo('Browser.Open', ['success' => true]);

        (new Browser)->open('https://nativephp.com');

        $this->bridge->assertCalledTimes('Browser.Open', 1);
    });
});

describe('inApp()', function () {
    it('fires Browser.OpenInApp with the url payload', function () {
        $this->bridge->respondTo('Browser.OpenInApp', ['success' => true]);

        (new Browser)->inApp('https://nativephp.com');

        $this->bridge->assertCalled('Browser.OpenInApp', function (array $p) {
            expect($p)->toBe(['url' => 'https://nativephp.com']);

            return true;
        });
    });

    it('returns true when the bridge reports success', function () {
        $this->bridge->respondTo('Browser.OpenInApp', ['success' => true]);

        expect((new Browser)->inApp('https://nativephp.com'))->toBeTrue();
    });

    it('returns false when the bridge reports success as false', function () {
        $this->bridge->respondTo('Browser.OpenInApp', ['success' => false]);

        expect((new Browser)->inApp('https://nativephp.com'))->toBeFalse();
    });

    it('returns false when the response has no success key', function () {
        $this->bridge->respondTo('Browser.OpenInApp', ['error' => 'failed to open']);

        expect((new Browser)->inApp('https://nativephp.com'))->toBeFalse();
    });

    it('returns false when nothing is scripted for the bridge call', function () {
        expect((new Browser)->inApp('https://nativephp.com'))->toBeFalse();

        $this->bridge->assertCalled('Browser.OpenInApp');
    });

    it('passes a url with query strings and a fragment verbatim', function () {
        $this->bridge->respondTo('Browser.OpenInApp', ['success' => true]);

        $url = 'https://nativephp.com/docs?version=1.0&lang=en#installation';
        (new Browser)->inApp($url);

        $this->bridge->assertCalled('Browser.OpenInApp', function (array $p) use ($url) {
            expect($p['url'])->toBe($url);

            return true;
        });
    });

    it('throws an InvalidArgumentException for an invalid url', function () {
        expect(fn () => (new Browser)->inApp('not-a-valid-url'))
            ->toThrow(InvalidArgumentException::class, 'Invalid URL provided');
    });

    it('does not call the bridge when the url is invalid', function () {
        try {
            (new Browser)->inApp('not-a-valid-url');
        } catch (InvalidArgumentException) {
            // expected
        }

        $this->bridge->assertNotCalled('Browser.OpenInApp');
    });

    it('throws for an empty string url', function () {
        expect(fn () => (new Browser)->inApp(''))
            ->toThrow(InvalidArgumentException::class);
    });

    it('accepts a valid url containing a port and userinfo', function () {
        $this->bridge->respondTo('Browser.OpenInApp', ['success' => true]);

        $url = 'https://user:pass@example.com:8443/secure';
        (new Browser)->inApp($url);

        $this->bridge->assertCalled('Browser.OpenInApp', function (array $p) use ($url) {
            expect($p['url'])->toBe($url);

            return true;
        });
    });

    it('calls the bridge exactly once per invocation', function () {
        $this->bridge->respondTo('Browser.OpenInApp', ['success' => true]);

        (new Browser)->inApp('https://nativephp.com');

        $this->bridge->assertCalledTimes('Browser.OpenInApp', 1);
    });
});

describe('auth()', function () {
    it('fires Browser.OpenAuth with the url payload', function () {
        $this->bridge->respondTo('Browser.OpenAuth', ['success' => true]);

        (new Browser)->auth('https://accounts.example.com/oauth/authorize?client_id=abc');

        $this->bridge->assertCalled('Browser.OpenAuth', function (array $p) {
            expect($p)->toBe(['url' => 'https://accounts.example.com/oauth/authorize?client_id=abc']);

            return true;
        });
    });

    it('returns true when the bridge reports success', function () {
        $this->bridge->respondTo('Browser.OpenAuth', ['success' => true]);

        expect((new Browser)->auth('https://accounts.example.com/oauth/authorize'))->toBeTrue();
    });

    it('returns false when the bridge reports success as false', function () {
        $this->bridge->respondTo('Browser.OpenAuth', ['success' => false]);

        expect((new Browser)->auth('https://accounts.example.com/oauth/authorize'))->toBeFalse();
    });

    it('returns false when the response has no success key', function () {
        $this->bridge->respondTo('Browser.OpenAuth', ['error' => 'failed to start session']);

        expect((new Browser)->auth('https://accounts.example.com/oauth/authorize'))->toBeFalse();
    });

    it('returns false when nothing is scripted for the bridge call', function () {
        expect((new Browser)->auth('https://accounts.example.com/oauth/authorize'))->toBeFalse();

        $this->bridge->assertCalled('Browser.OpenAuth');
    });

    it('passes an OAuth url with query strings and a callback redirect verbatim', function () {
        $this->bridge->respondTo('Browser.OpenAuth', ['success' => true]);

        $url = 'https://accounts.example.com/oauth/authorize?client_id=abc123&redirect_uri=native%3A%2F%2Fcallback&scope=profile+email&state=xyz';
        (new Browser)->auth($url);

        $this->bridge->assertCalled('Browser.OpenAuth', function (array $p) use ($url) {
            expect($p['url'])->toBe($url);

            return true;
        });
    });

    it('does not validate the url before calling the bridge', function () {
        $this->bridge->respondTo('Browser.OpenAuth', ['success' => true]);

        (new Browser)->auth('not-a-valid-url');

        $this->bridge->assertCalled('Browser.OpenAuth', function (array $p) {
            expect($p['url'])->toBe('not-a-valid-url');

            return true;
        });
    });

    it('calls the bridge exactly once per invocation', function () {
        $this->bridge->respondTo('Browser.OpenAuth', ['success' => true]);

        (new Browser)->auth('https://accounts.example.com/oauth/authorize');

        $this->bridge->assertCalledTimes('Browser.OpenAuth', 1);
    });
});

describe('cross-method isolation', function () {
    it('does not cross-call other bridge functions', function () {
        $this->bridge->respondTo('Browser.Open', ['success' => true]);

        (new Browser)->open('https://nativephp.com');

        $this->bridge
            ->assertNotCalled('Browser.OpenInApp')
            ->assertNotCalled('Browser.OpenAuth');
    });

    it('records calls to different methods independently and in order', function () {
        $this->bridge->respondTo('Browser.Open', ['success' => true]);
        $this->bridge->respondTo('Browser.OpenInApp', ['success' => true]);
        $this->bridge->respondTo('Browser.OpenAuth', ['success' => true]);

        (new Browser)->open('https://nativephp.com');
        (new Browser)->inApp('https://nativephp.com/docs');
        (new Browser)->auth('https://accounts.example.com/oauth/authorize');

        $this->bridge->assertCallOrder([
            'Browser.Open',
            'Browser.OpenInApp',
            'Browser.OpenAuth',
        ]);
    });
});
