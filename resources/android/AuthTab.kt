package com.nativephp.browser

import android.app.Activity
import android.app.ActivityManager
import android.content.ActivityNotFoundException
import android.content.Context
import android.content.Intent
import android.net.Uri
import android.os.Bundle
import android.util.Log
import androidx.browser.customtabs.CustomTabsIntent
import com.nativephp.mobile.lifecycle.NativePHPLifecycle

/**
 * Plugin init (nativephp.json android.init_function), run each time MainActivity is created.
 */
fun browserPluginInit(context: Context) {
    AuthTab.init(context)
}

/**
 * Keeps the Custom Tab that Browser.OpenAuth opens in a task of its own, for as long as the
 * sign-in lasts.
 *
 * A Custom Tab opened straight from MainActivity joins the app's task. If the app's activities
 * are removed while it's open (an app update, a force-stop), the tab is left as the only
 * activity in that task. The launcher keeps bringing that task back, so the user sees a stale
 * sign-in page and the app never starts.
 *
 * So the tab is opened from [AuthTabActivity], started as a separate document task that the
 * launcher never resumes. To behave like iOS's ASWebAuthenticationSession sheet, coming back to
 * the app while the sign-in is pending brings the tab back in front of it, and the tab's task is
 * removed once a deep link (the redirect) reaches the app, the user closes the tab, or a new
 * sign-in starts.
 */
object AuthTab {
    private const val TAG = "Browser.AuthTab"

    @Volatile
    private var pending = false

    private var subscribed = false

    @Synchronized
    fun init(context: Context) {
        // Nothing is pending in a fresh process, so any tab left over belongs to a sign-in
        // the app has already forgotten.
        if (!pending) {
            removeTasks(context)
        }

        if (subscribed) {
            return
        }

        subscribed = true
        val appContext = context.applicationContext

        NativePHPLifecycle.on(NativePHPLifecycle.Events.ON_NEW_INTENT) {
            if (pending) {
                Log.d(TAG, "Deep link reached the app, closing the auth tab")
            }

            pending = false
            removeTasks(appContext)
        }

        NativePHPLifecycle.on(NativePHPLifecycle.Events.ON_RESUME) {
            if (pending) {
                bringToFront(appContext)
            }
        }
    }

    fun start(activity: Activity, url: String) {
        // Also subscribes on cores whose plugin compiler doesn't call init functions (before 3.2).
        init(activity)
        removeTasks(activity)
        pending = true

        activity.startActivity(
            Intent(activity, AuthTabActivity::class.java)
                .putExtra(AuthTabActivity.EXTRA_URL, url)
                .addFlags(Intent.FLAG_ACTIVITY_NEW_DOCUMENT or Intent.FLAG_ACTIVITY_MULTIPLE_TASK)
        )
    }

    /**
     * The tab closed without a redirect reaching the app: the user cancelled. Show the app
     * again rather than whatever task happens to be behind this one.
     */
    fun closed(activity: AuthTabActivity) {
        pending = false
        appTasks(activity).firstOrNull { !it.isAuthTab() }?.let { runCatching { it.moveToFront() } }
        activity.finishAndRemoveTask()
    }

    private fun bringToFront(context: Context) {
        val task = appTasks(context).firstOrNull { it.isAuthTab() }

        if (task == null) {
            // Swiped away from Recents.
            pending = false

            return
        }

        runCatching { task.moveToFront() }
    }

    private fun removeTasks(context: Context) {
        appTasks(context).filter { it.isAuthTab() }.forEach { runCatching { it.finishAndRemoveTask() } }
    }

    private fun appTasks(context: Context): List<ActivityManager.AppTask> =
        context.getSystemService(ActivityManager::class.java)?.appTasks ?: emptyList()

    private fun ActivityManager.AppTask.isAuthTab(): Boolean =
        runCatching { taskInfo.baseIntent.component?.className == AuthTabActivity::class.java.name }
            .getOrDefault(false)
}

/**
 * The invisible root of the auth tab's task. It opens the Custom Tab on top of itself, and
 * being resumed again means the tab has closed.
 */
class AuthTabActivity : Activity() {
    private var tabOpened = false

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        tabOpened = savedInstanceState?.getBoolean(STATE_TAB_OPENED) ?: false
    }

    override fun onResume() {
        super.onResume()

        if (tabOpened) {
            AuthTab.closed(this)

            return
        }

        tabOpened = true

        try {
            CustomTabsIntent.Builder().build().launchUrl(this, Uri.parse(intent.getStringExtra(EXTRA_URL)))
        } catch (e: ActivityNotFoundException) {
            Log.e("Browser.OpenAuth", "No browser to open the auth URL", e)
            AuthTab.closed(this)
        }
    }

    override fun onSaveInstanceState(outState: Bundle) {
        super.onSaveInstanceState(outState)
        outState.putBoolean(STATE_TAB_OPENED, tabOpened)
    }

    companion object {
        const val EXTRA_URL = "com.nativephp.browser.AUTH_URL"
        private const val STATE_TAB_OPENED = "tab_opened"
    }
}
