# Muttasiq TALL and Native App

This shared source code base is representing the web version primarily, the one that serves the administration panel and the API. There are special changes via NativePHP for multiple platforms; distinguished between using `is_platform` global function helper. And since it's a `WebView`, performance is our primary objective.

## Terminology
- Answer in English always.
- When saying "mobile" broadly, then it's both `base` breakpoint in web small mobile screens and the native Android and IOS apps; unless explicitly told otherwise.
- When saying a "click" broadly, keep in mind that it would mean a "tap" for mobile and tablet devices; unless explicitly told otherwise. And usually that "touch" is doing the same thing a "hover" does in web big screens.

## Architecture
- Since we develop with PHP strict typing, we don't have to do too much defensive programming in place.
- Add partials as components under [resources/views/components/partials], instead of adding plain `@include`s, in order to gain Livewire Blaze speed.
- Know that app design colors are specified in [resources/css/app.css] (`@theme`) and [config/app.php] files. Those are even used by Filament in [app/Providers/FilamentServiceProvider.php].
- Use the Alpine breakpoint `bp` helpers in [resources/js/support/alpine/storage/breakpointer.js], including `current`, `is()`, `isTouch()`, `isTablet()`, `shouldUseSortHandles()`.
- For heavy front-end assets, we have lazy asset strategy for CSS through `@lazyCss(...)` from [`app/Providers/LazyCssServiceProvider.php`] and for JS bundle scheduling in [resources/js/app.js] and idle [resources/js/app-lazy.js] imports.
- Reuse CSS variable helpers instead of custom parsing: JS helpers are in [resources/js/support/css-variables.js], and PHP theme helpers are in [app/Services/Functions/theme.php].
- The whole application is an SPA-like shell with one main route ([routes/web.php]: `/`) and where client-side nested view transitions are in [resources/views/home.blade.php].
- Use hash navigation via `x-hash-actions` (from [`resources/js/packages/alpine/hash-actions.js`]) and `switch-view` events for native/web navigation consistency.
- Use `$livewireLock` (from [resources/js/support/alpine/magic/livewire-lock.js]) for action locking where repeated taps/clicks could cause duplicate requests.
- Use Filament as the primary UI engine for notifications, modals, slideovers, forms, tables, admin panels, etc.
- Keep the "control panel" as a Filament tabbed action, where settings, changelogs, and about tabs are built.
- For settings behavior, keep server/API defaults authoritative for untouched keys while persisting only explicit user overrides (see [resources/js/support/alpine/athkar-app-overrides.js]); any new setting must join this merge path.
- For app notices that should auto-skip after first acknowledged display, persist a per-notice browser flag with both `hasDisplayed` and `hasBypassedOnce` states, and gate future display on that persisted pair instead of transient runtime state.
- Use `arabic_text(...)` from [app/Services/Functions/custom.php] for UI-facing Arabic strings (labels, headings, captions, button texts, notices). This helper is the single source for display-time Arabic processing:
  - Harakat: keeps or strips based on `does_preserve_harakat_in_display`.
  - Numerals: renders Western (`123`) or Arabic-Indic (`١٢٣`) based on `does_use_western_numerals`.
  - Prefer passing full final strings through this helper instead of ad-hoc numeral/harakat conversions in Blade, Livewire actions, or model label builders.
- Place reusable cross-feature utilities in `Support`/`support` namespaces and folders, put inside their standradized main folders first of course.
- The layout manager [resources/js/support/alpine/data/layout-manager.js] tracks action/modal events (`open-modal`, `close-modal`, etc.) and should stay in sync with Filament modal behavior.
- When designing front-end code, and writing pure CSS at the top in the Blade file, never use the following properties unresponsively: font sizing (font-size, line-height, etc.), element sizing (width, height, padding, etc.), and positioning (top, bottom, inset-inline-start, etc.).
  - These properties must be added as inlined TailwindCSS classes on their HTML elements and having counterparts for all the breakpoints in defined in [resources/css/support/breakpointer.css].

## Preferences
- Do not ever consider using reduced-motion CSS feature.
- Do not restore reduced-motion suppression for Livewire (disabled in [resources/js/overrides/livewire-transition-consistency.js]).
- We manually decide what animations/effects to disable when `enable_visual_enhancements` setting is diabled.

## Documentation
- When looking for docs, check first which versions are in `composer.json` and `package.json`.
- When implementing a new development feature, and when it's very essential and uncommon (such as the existance of this instructions file), make sure it's documented in README.

## Development
- The native apps need some modifications on the NativePHP engine. These are done via `muttasiq-patches` NativePHP plugin. It's supposedly located in [~/Code/LaravelPackages/NATIVE_PLUGIN_muttasiq-patches] directory. Update its own README if you touch it.
  - The patching is build-time only and externalized to `goodm4ven/nativephp-muttasiq-patches`, enabled by [app/Providers/NativeServiceProvider.php], and ran as mobile pre-complile hook.
  - Toggle local development of that plugin using [.scripts/composer-local-plugins-switch.sh], which targets [~/Code/LaravelPackages/NATIVE_PLUGIN_muttasiq-patches] by default.
  - For NativePHP user-generated or user-imported files that must be publicly renderable and survive app updates, use `Storage::disk('mobile_public')` instead of plain `public`/`local` paths. Do not set `FILESYSTEM_DISK=mobile_public` as a global app default for web/admin; only use that env override in native-build-specific environments when the whole file flow is intended to target the native persistent public storage.
- Preferred container workflow is [`lara-stacker`](https://github.com/GoodM4ven/CLI_LARAVEL_lara-stacker), expected to be located at [~/Code/Scripts/CLI_LARAVEL_lara-stacker/], and including scripts to import this project and to setup the local development environment.
- You can check out what Laravel setup requires for this application to work in [composer.json]'s `setup` script.

## Testing
- Do not write tests unless explicitely told to, or when shipping a new main **feature**.
  - When you do add tests, prefer extending an existing related test, and keep feature tests under `tests/Feature/App` or `tests/Feature/Browser`.
- For fast checks, start with the smallest relevant non-browser run, usually `php artisan test --compact <file-or-filter>`, then scale to the project wrappers `.scripts/testing/test.sh` or `.scripts/testing/paral.sh` and their Composer entrypoints such as `composer run test:raw` or `composer run testparal:raw`.
- Browser tests are special in this Docker setup, so do not call Pest browser tests directly; use `.scripts/testing/browser.sh` or `composer run testbrowser:raw`, and treat `composer green` as the full pre-commit flow because it runs the verification checks, linting, and the raw parallel non-browser plus browser suites.
  - Browser test wrapper defaults to fail-fast (`--stop-on-failure`) unless an explicit stop option is already passed. Set `BROWSER_TEST_STOP_ON_FAILURE=0` only when you intentionally want full-suite browser output.
  - Browser timeout has a hard minimum floor of `10m` in the wrapper to avoid false halts from short `timeout` values.
  - Browser wrapper auto-syncs Playwright runtime install when runtime version or install locations drift; do not bump Playwright package versions by default just because tests fail.
  - Do not run multiple individual browser tests simultaneously.

## Debugging
- For investigating AlpineJS transition failiures, try using [resources/js/support/debugging/alpine-transition-debugger.js].
- If the front-end behavior doesn't sync with the back-end. Debug it using Playright browser skill.
  - When Playright is unavailable to the AI agent, warn the user about it.
- When you fail for the 2nd time in fixing a hairy bug, immediately improvise a logging mechanism in place and provide instructions on how to log back to IF you cannot do the logging and testing automatically until the fix is implemented. 

## Finishing
- When have modified CSS or JS files, use `pnpm run format:prettier` to format them.
- When have modified Blade-PHP files, use `pnpm run format:blade` to format them.
- When have modified PHP files, ensure `php artisan pint` was ran to format them.
- When have modified PHP files, run static analysis using `vendor/bin/phpstan analyse`.
