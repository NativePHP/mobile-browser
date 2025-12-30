## nativephp/mobile-browser

Open URLs in system browser, in-app browser, and OAuth authentication sessions.

### Installation

```bash
composer require nativephp/mobile-browser

php artisan vendor:publish --tag=nativephp-plugins-provider
php artisan native:plugin:register nativephp/mobile-browser
php artisan native:plugin:list
```

### PHP Usage (Livewire/Blade)

Use the `Browser` facade:

@verbatim
<code-snippet name="Using Browser Facade" lang="php">
use NativePHP\Browser\Facades\Browser;

// Open URL in system browser (Safari/Chrome)
Browser::open('https://example.com');

// Open URL in in-app browser (SFSafariViewController/Chrome Custom Tabs)
Browser::inApp('https://example.com');

// Open URL for OAuth authentication
Browser::auth('https://oauth.example.com/authorize?...');
</code-snippet>
@endverbatim

### JavaScript Usage (Vue/React/Inertia)

@verbatim
<code-snippet name="Using Browser in JavaScript" lang="javascript">
import { browser } from '@nativephp/browser';

// Open in system browser
await browser.open('https://example.com');

// Open in in-app browser
await browser.inApp('https://example.com');

// Open for OAuth authentication
await browser.auth('https://oauth.example.com/authorize?...');
</code-snippet>
@endverbatim

### Available Methods

- `Browser::open(string $url)` / `browser.open(url)` - Open in system's default browser
- `Browser::inApp(string $url)` / `browser.inApp(url)` - Open in in-app browser overlay
- `Browser::auth(string $url)` / `browser.auth(url)` - Open for OAuth authentication flow

### Platform Behavior

**iOS:**
- `open()` - Opens Safari
- `inApp()` - Uses SFSafariViewController
- `auth()` - Uses ASWebAuthenticationSession with automatic callback handling

**Android:**
- `open()` - Opens default browser app
- `inApp()` - Uses Chrome Custom Tabs (falls back to system browser)
- `auth()` - Uses Chrome Custom Tabs with deep link callback

### OAuth Authentication

For OAuth flows, configure your callback scheme in `.env`:

```env
NATIVEPHP_DEEPLINK_SCHEME=myapp
```

The `auth()` method will automatically use this scheme for the callback URL.