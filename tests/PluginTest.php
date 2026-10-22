<?php

/**
 * Plugin validation tests for Browser.
 *
 * Run with: ./vendor/bin/pest
 */
beforeEach(function () {
    // PLUGIN_PATH lets CI run this suite from an external Pest harness
    // (the plugin's own composer deps aren't resolvable on public
    // runners); locally the suite sits inside the plugin as usual.
    $this->pluginPath = getenv('PLUGIN_PATH') ?: dirname(__DIR__);
    $this->manifestPath = $this->pluginPath.'/nativephp.json';
});

describe('Plugin Manifest', function () {
    it('has a valid nativephp.json file', function () {
        expect(file_exists($this->manifestPath))->toBeTrue();

        json_decode(file_get_contents($this->manifestPath), true);

        expect(json_last_error())->toBe(JSON_ERROR_NONE);
    });

    it('has required fields', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        expect($manifest)->toHaveKeys(['namespace', 'bridge_functions']);
        expect($manifest['namespace'])->toBe('Browser');
    });

    it('declares every bridge function for both platforms', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        expect($manifest['bridge_functions'])->toBeArray()->not->toBeEmpty();

        $names = array_column($manifest['bridge_functions'], 'name');
        expect($names)->toContain(
            'Browser.Open',
            'Browser.OpenInApp',
            'Browser.OpenAuth',
        );

        foreach ($manifest['bridge_functions'] as $function) {
            expect($function)->toHaveKeys(['name']);
            expect(isset($function['android']) || isset($function['ios']))->toBeTrue();
        }
    });

    it('declares no events', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        expect($manifest['events'])->toBeArray()->toBeEmpty();
    });

    it('requests no permissions', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        expect($manifest['android']['permissions'])->toBeArray()->toBeEmpty();
        expect($manifest['ios']['info_plist'])->toBeArray()->toBeEmpty();
    });
});

describe('Native Code', function () {
    it('has matching bridge function classes in native code', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        $kotlinContent = implode('', array_map('file_get_contents', glob($this->pluginPath.'/resources/android/*.kt')));
        $swiftContent = implode('', array_map('file_get_contents', glob($this->pluginPath.'/resources/ios/*.swift')));

        foreach ($manifest['bridge_functions'] as $function) {
            if (isset($function['android'])) {
                $parts = explode('.', $function['android']);
                expect($kotlinContent)->toContain('class '.end($parts));
            }

            if (isset($function['ios'])) {
                $parts = explode('.', $function['ios']);
                expect($swiftContent)->toContain('class '.end($parts));
            }
        }
    });

    it('declares the activity and init function that keep the Android auth tab in its own task', function () {
        $manifest = json_decode(file_get_contents($this->manifestPath), true);

        $kotlinContent = implode('', array_map('file_get_contents', glob($this->pluginPath.'/resources/android/*.kt')));

        expect($manifest['android']['activities'])->toContain([
            'name' => '.AuthTabActivity',
            'exported' => false,
            'theme' => '@android:style/Theme.Translucent.NoTitleBar',
        ]);
        expect($kotlinContent)->toContain('class AuthTabActivity');

        $parts = explode('.', $manifest['android']['init_function']);
        expect($kotlinContent)->toContain('fun '.end($parts).'(context: Context)');
    });

    it('sends the app\'s own links to a browser on Android instead of back into the app', function () {
        $functions = file_get_contents($this->pluginPath.'/resources/android/BrowserFunctions.kt');
        $authTab = file_get_contents($this->pluginPath.'/resources/android/AuthTab.kt');

        expect($functions)->toContain('internal fun Context.keepOutOfThisApp(intent: Intent): Intent');
        expect($functions)->toContain('selector = Intent(Intent.ACTION_VIEW, Uri.parse("$scheme://"))');

        // Open, OpenInApp (and its plain fallback) and OpenAuth's tab all launch through it.
        expect($functions)->toContain('activity.startActivity(activity.keepOutOfThisApp(intent))');
        expect($functions)->toContain('activity.keepOutOfThisApp(customTabsIntent.intent)');
        expect($authTab)->toContain('keepOutOfThisApp(tab.intent)');
    });
});

describe('Composer Configuration', function () {
    it('has valid composer.json', function () {
        $composer = json_decode(file_get_contents($this->pluginPath.'/composer.json'), true);

        expect(json_last_error())->toBe(JSON_ERROR_NONE);
        expect($composer['type'])->toBe('nativephp-plugin');
        expect($composer['require'])->toHaveKey('php');
        expect($composer['require']['php'])->not->toBeEmpty();
        expect($composer['require'])->toHaveKey('nativephp/mobile');
    });

    it('registers a provider that maps to an existing file', function () {
        $composer = json_decode(file_get_contents($this->pluginPath.'/composer.json'), true);

        $providers = $composer['extra']['laravel']['providers'] ?? [];
        expect($providers)->not->toBeEmpty();

        foreach ($providers as $provider) {
            $matched = false;

            foreach ($composer['autoload']['psr-4'] as $prefix => $path) {
                if (! str_starts_with($provider, $prefix)) {
                    continue;
                }

                $relative = str_replace('\\', '/', substr($provider, strlen($prefix)));
                $file = $this->pluginPath.'/'.rtrim($path, '/').'/'.$relative.'.php';

                if (file_exists($file)) {
                    $matched = true;
                    break;
                }
            }

            expect($matched)->toBeTrue("Provider {$provider} does not map to a file");
        }
    });
});
