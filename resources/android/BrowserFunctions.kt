package com.nativephp.browser

import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.util.Log
import androidx.browser.customtabs.CustomTabsIntent
import androidx.fragment.app.FragmentActivity
import com.nativephp.mobile.bridge.BridgeError
import com.nativephp.mobile.bridge.BridgeFunction

/**
 * Functions related to opening URLs in browsers
 * Namespace: "Browser.*"
 */
object BrowserFunctions {

    /**
     * Open a URL in the system's default browser
     * Parameters:
     *   - url: string - The URL to open
     * Returns:
     *   - success: boolean - True if successfully opened
     */
    class Open(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val url = parameters["url"] as? String
                ?: throw BridgeError.InvalidParameters("url is required")

            Log.d("Browser.Open", "Opening URL in system browser: $url")

            return try {
                val intent = Intent(Intent.ACTION_VIEW, Uri.parse(url))
                intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                activity.startActivity(activity.keepOutOfThisApp(intent))

                Log.d("Browser.Open", "Successfully opened URL in system browser")
                mapOf("success" to true)
            } catch (e: Exception) {
                Log.e("Browser.Open", "Error opening URL: ${e.message}", e)
                throw BridgeError.ExecutionFailed("Failed to open URL: ${e.message}")
            }
        }
    }

    /**
     * Open a URL in an in-app browser (Chrome Custom Tabs)
     * Parameters:
     *   - url: string - The URL to open
     * Returns:
     *   - success: boolean - True if successfully opened
     */
    class OpenInApp(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val url = parameters["url"] as? String
                ?: throw BridgeError.InvalidParameters("url is required")

            Log.d("Browser.OpenInApp", "Opening URL in in-app browser: $url")

            return try {
                val uri = Uri.parse(url)
                val builder = CustomTabsIntent.Builder()
                val customTabsIntent = builder.build()
                customTabsIntent.intent.data = uri
                activity.keepOutOfThisApp(customTabsIntent.intent)
                customTabsIntent.launchUrl(activity, uri)

                Log.d("Browser.OpenInApp", "Successfully opened URL in in-app browser")
                mapOf("success" to true)
            } catch (e: Exception) {
                Log.e("Browser.OpenInApp", "Error opening URL: ${e.message}", e)
                // Fallback to system browser if Custom Tabs fail
                return try {
                    val intent = Intent(Intent.ACTION_VIEW, Uri.parse(url))
                    activity.startActivity(activity.keepOutOfThisApp(intent))
                    Log.d("Browser.OpenInApp", "Opened URL in system browser (fallback)")
                    mapOf("success" to true)
                } catch (fallbackError: Exception) {
                    throw BridgeError.ExecutionFailed("Failed to open URL: ${fallbackError.message}")
                }
            }
        }
    }

    /**
     * Open a URL in an authentication session
     * On Android, this uses a Custom Tab in a task of its own (see AuthTab), and the
     * callback reaches the app as a deep link
     * Parameters:
     *   - url: string - The URL to open for authentication
     * Returns:
     *   - success: boolean - True if successfully opened
     */
    class OpenAuth(private val activity: FragmentActivity) : BridgeFunction {
        override fun execute(parameters: Map<String, Any>): Map<String, Any> {
            val url = parameters["url"] as? String
                ?: throw BridgeError.InvalidParameters("url is required")

            Log.d("Browser.OpenAuth", "Opening URL for authentication: $url")

            return try {
                AuthTab.start(activity, url)

                Log.d("Browser.OpenAuth", "Successfully opened auth URL")
                mapOf("success" to true)
            } catch (e: Exception) {
                Log.e("Browser.OpenAuth", "Error opening auth URL: ${e.message}", e)
                throw BridgeError.ExecutionFailed("Failed to open auth URL: ${e.message}")
            }
        }
    }
}

/**
 * Make sure [intent], a VIEW intent for a URL, opens in a browser if Android would otherwise
 * hand it back to this app.
 *
 * For a link the app holds an App Link for, a plain VIEW intent resolves straight to the app's
 * own MainActivity, so the browser never opens and the link arrives as a deep link instead.
 * Before Android 12 an unverified link can also put the app in the chooser next to the browsers.
 *
 * In those cases the intent gets a selector: the same request for the scheme alone, with no
 * host. Android picks the activity from the selector and still delivers the real URL, and only a
 * browser handles a web URL with no host, since an app's link filter always names one. So the
 * user's default browser opens the URL (or, with no default set, the system asks which
 * browser). Every other URL is left alone, so links to other apps (YouTube, Maps) still open in
 * those apps.
 */
@Suppress("DEPRECATION")
internal fun Context.keepOutOfThisApp(intent: Intent): Intent {
    val scheme = intent.data?.scheme?.lowercase()

    if (scheme != "http" && scheme != "https") {
        return intent
    }

    // The app's own package is always visible to it, so no <queries> entry is needed.
    val candidates = packageManager.queryIntentActivities(intent, PackageManager.MATCH_DEFAULT_ONLY)

    if (candidates.none { it.activityInfo.packageName == packageName }) {
        return intent
    }

    Log.d("Browser", "${intent.data} would open in this app, sending it to a browser instead")

    return intent.apply {
        addCategory(Intent.CATEGORY_BROWSABLE)
        selector = Intent(Intent.ACTION_VIEW, Uri.parse("$scheme://"))
            .addCategory(Intent.CATEGORY_BROWSABLE)
    }
}
