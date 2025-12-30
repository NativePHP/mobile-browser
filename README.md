# Browser Plugin for NativePHP Mobile

Open URLs in system browser, in-app browser (SFSafariViewController/Chrome Custom Tabs), and OAuth authentication sessions.

## Installation

```bash
composer require nativephp/mobile-browser
```

## PHP Usage (Livewire/Blade)

```php
use NativePHP\Browser\Facades\Browser;

// Open URL in system browser (Safari/Chrome)
Browser::open('https://example.com');

// Open URL in in-app browser (SFSafariViewController on iOS, Chrome Custom Tabs on Android)
Browser::inApp('https://example.com');

// Open URL for OAuth authentication (ASWebAuthenticationSession on iOS)
Browser::auth('https://oauth.example.com/authorize?...');
```

## JavaScript Usage (Vue/React/Inertia)

```javascript
import { browser } from '@nativephp/browser';

// Open in system browser
await browser.open('https://example.com');

// Open in in-app browser
await browser.inApp('https://example.com');

// Open for OAuth authentication
await browser.auth('https://oauth.example.com/authorize?...');
```

## Methods

### `Browser::open(string $url): bool`

Opens the URL in the device's default browser app (Safari on iOS, default browser on Android).

### `Browser::inApp(string $url): bool`

Opens the URL in an in-app browser overlay:
- **iOS**: Uses `SFSafariViewController` for a seamless in-app experience
- **Android**: Uses Chrome Custom Tabs with fallback to system browser

### `Browser::auth(string $url): bool`

Opens the URL in an authentication session optimized for OAuth flows:
- **iOS**: Uses `ASWebAuthenticationSession` which handles the callback automatically
- **Android**: Uses Chrome Custom Tabs with deep link callback handling

The callback URL scheme is read from your `NATIVEPHP_DEEPLINK_SCHEME` environment variable.

## License

MIT