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
