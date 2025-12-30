package com.nativephp.browser

import android.content.Intent
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
                activity.startActivity(intent)

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
                val builder = CustomTabsIntent.Builder()
                val customTabsIntent = builder.build()
                customTabsIntent.launchUrl(activity, Uri.parse(url))

                Log.d("Browser.OpenInApp", "Successfully opened URL in in-app browser")
                mapOf("success" to true)
            } catch (e: Exception) {
                Log.e("Browser.OpenInApp", "Error opening URL: ${e.message}", e)
                // Fallback to system browser if Custom Tabs fail
                return try {
                    val intent = Intent(Intent.ACTION_VIEW, Uri.parse(url))
                    activity.startActivity(intent)
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
     * On Android, this uses Custom Tabs with the configured callback scheme
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
                // Use Custom Tabs for auth flow - the callback will be handled by deep links
                val builder = CustomTabsIntent.Builder()
                val customTabsIntent = builder.build()
                customTabsIntent.launchUrl(activity, Uri.parse(url))

                Log.d("Browser.OpenAuth", "Successfully opened auth URL")
                mapOf("success" to true)
            } catch (e: Exception) {
                Log.e("Browser.OpenAuth", "Error opening auth URL: ${e.message}", e)
                throw BridgeError.ExecutionFailed("Failed to open auth URL: ${e.message}")
            }
        }
    }
}