## nativephp/browser

Open URLs in system browser, in-app browser, and OAuth authentication sessions.

### PHP Usage (Livewire/Blade)

Use the `Browser` facade:

@verbatim
<code-snippet name="Using Browser Facade" lang="php">
use Native\Mobile\Facades\Browser;

// Open in in-app browser (keeps users in your app)
Browser::inApp('https://nativephp.com/mobile');

// Open in system browser (leaves your app)
Browser::open('https://nativephp.com/mobile');

// OAuth authentication with automatic redirect handling
Browser::auth('https://provider.com/oauth/authorize?client_id=123&redirect_uri=nativephp://127.0.0.1/auth/callback');
</code-snippet>
@endverbatim

### JavaScript Usage (Vue/React/Inertia)

@verbatim
<code-snippet name="Using Browser in JavaScript" lang="javascript">
import { browser } from '#nativephp';

// Open in in-app browser
await browser.inApp('https://nativephp.com/mobile');

// Open in system browser
await browser.open('https://nativephp.com/mobile');

// OAuth authentication
await browser.auth('https://provider.com/oauth/authorize?client_id=123&redirect_uri=nativephp://127.0.0.1/auth/callback');
</code-snippet>
@endverbatim

### Methods

- `Browser::inApp(string $url)` - Opens in embedded browser (SFSafariViewController/Chrome Custom Tabs)
- `Browser::open(string $url)` - Opens in device's default browser
- `Browser::auth(string $url)` - Opens OAuth authentication browser with automatic redirect handling

### When to Use Each Method

- **`inApp()`**: Keep users within your app for documentation, help pages, or related content
- **`open()`**: Use when full browser features are required for complex web applications
- **`auth()`**: Implement OAuth authentication flows with secure, automatic redirect handling
