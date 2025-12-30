/**
 * Browser Plugin for NativePHP Mobile
 *
 * @example
 * import { browser } from '@nativephp/browser';
 *
 * // Open in system browser
 * await browser.open('https://example.com');
 *
 * // Open in in-app browser
 * await browser.inApp('https://example.com');
 *
 * // Open for OAuth authentication
 * await browser.auth('https://oauth.example.com/authorize?...');
 */

const baseUrl = '/_native/api/call';

async function bridgeCall(method, params = {}) {
    const response = await fetch(baseUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        },
        body: JSON.stringify({ method, params })
    });

    const result = await response.json();

    if (result.status === 'error') {
        throw new Error(result.message || 'Native call failed');
    }

    return result.data;
}

/**
 * Open a URL in the system's default browser (Safari/Chrome)
 * @param {string} url - The URL to open
 * @returns {Promise<boolean>} True if successfully opened
 */
export async function open(url) {
    const result = await bridgeCall('Browser.Open', { url });
    return result?.success === true;
}

/**
 * Open a URL in an in-app browser (SFSafariViewController/Chrome Custom Tabs)
 * @param {string} url - The URL to open
 * @returns {Promise<boolean>} True if successfully opened
 */
export async function inApp(url) {
    const result = await bridgeCall('Browser.OpenInApp', { url });
    return result?.success === true;
}

/**
 * Open a URL for OAuth authentication
 * Uses ASWebAuthenticationSession on iOS, Chrome Custom Tabs on Android
 * @param {string} url - The authentication URL
 * @returns {Promise<boolean>} True if session started successfully
 */
export async function auth(url) {
    const result = await bridgeCall('Browser.OpenAuth', { url });
    return result?.success === true;
}

export const Browser = {
    open,
    inApp,
    auth
};

export default Browser;
