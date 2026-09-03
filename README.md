# Browser Plugin for NativePHP Mobile

Open URLs in system browser, in-app browser (SFSafariViewController/Chrome Custom Tabs), and OAuth authentication sessions.

## Overview

The Browser API provides three methods for opening URLs, each designed for specific use cases: in-app browsing, system browser navigation, and web authentication flows.

## Installation

```bash
composer require nativephp/mobile-browser
```

## Usage

### PHP (Livewire/Blade)

```php
use Native\Mobile\Facades\Browser;

// Open in in-app browser
Browser::inApp('https://nativephp.com/mobile');

// Open in system browser
Browser::open('https://nativephp.com/mobile');

// OAuth authentication
Browser::auth('https://provider.com/oauth/authorize?client_id=123&redirect_uri=nativephp://127.0.0.1/auth/callback');
```

### JavaScript (Vue/React/Inertia)

```js
import { Browser } from '#nativephp';

// Open in in-app browser
await Browser.inApp('https://nativephp.com/mobile');

// Open in system browser
await Browser.open('https://nativephp.com/mobile');

// OAuth authentication
await Browser.auth('https://provider.com/oauth/authorize?client_id=123&redirect_uri=nativephp://127.0.0.1/auth/callback');
```

## Methods

### `inApp()`

Opens a URL in an embedded browser within your app using Custom Tabs (Android) or SFSafariViewController (iOS).

### `open()`

Opens a URL in the device's default browser app, leaving your application entirely.

Also handles custom schemes — `open('myapp://path')` hands the URL to whichever
app registered `myapp`, if one is installed. The returned `success` is the real
answer: `false` means the system found nothing to open it with, which is how you
detect that the target app is not installed.

### `auth()`

Opens a URL in a specialized authentication browser designed for OAuth flows with automatic `nativephp://` redirect handling.

## Use Cases

### When to Use Each Method

**`inApp()`** - Keep users within your app experience:
- Documentation, help pages, terms of service
- External content that relates to your app
- When you want users to easily return to your app

**`open()`** - Full browser experience needed:
- Complex web applications
- Content requiring specific browser features
- When users need bookmarking or sharing capabilities

**`auth()`** - OAuth authentication flows:
- Login with WorkOS, Auth0, Google, Facebook, etc.
- Secure authentication with automatic redirects
- Isolated browser session for security

## Testing

The plugin extends the NativePHP testing suite with browser-specific helpers, so your app tests can assert what was opened without knowing any bridge internals:

```php
use Native\Mobile\Testing\Native;

it('opens the docs in the in-app browser', function () {
    Native::fakeBridge()->respondTo('Browser.OpenInApp', ['success' => true]);

    Native::test(HelpScreen::class)
        ->tap('View documentation')
        ->assertOpenedInApp('https://nativephp.com/docs');
});

it('starts the oauth flow', function () {
    Native::fakeBridge()->respondTo('Browser.OpenAuth', ['success' => true]);

    Native::test(LoginScreen::class)
        ->tap('Continue with Google')
        ->assertOpenedAuth();
});

it('does nothing until the button is tapped', function () {
    Native::test(LoginScreen::class)
        ->assertNothingBrowsed();
});
```

### Helpers

- `assertBrowsed(?string $url = null)` — assert a URL was opened, via `open()`, `inApp()`, or `auth()` — any of the three, or exactly `$url` when given.
- `assertOpenedInApp(?string $url = null)` — assert a URL was opened in the in-app browser (`inApp()`), or exactly `$url` when given.
- `assertOpenedAuth(?string $url = null)` — assert a URL was opened in an authentication session (`auth()`), or exactly `$url` when given.
- `assertNothingBrowsed()` — assert nothing was opened by any of the three methods.

`open()`, `inApp()`, and `auth()` are fire-and-forget calls with nothing to read back beyond success/failure, so there's no `with*()` helper to fake a response — script it directly when a test needs `open()`/`inApp()`/`auth()` to return `true`:

```php
Native::fakeBridge()->respondTo('Browser.Open', ['success' => true]);
```

The helpers are available on `Native::fakeBridge()` and chain directly off `Native::test(...)`. They register automatically while running tests (requires a core with a macroable FakeBridge; on older cores they simply don't register).
