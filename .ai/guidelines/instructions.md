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
- Size primary texts with Fitty [resources/js/packages/fitty.js] using `[data-fitty-target]` / `[data-fitty-box]` and `fitty-refit` refresh flow.
- Use the Alpine breakpoint `bp` helpers in [resources/js/support/alpine/storage/breakpointer.js], including `current`, `is()`, `isTouch()`, `isTablet()`, `shouldUseSortHandles()`.
- For heavy front-end assets, we have lazy asset strategy for CSS through `@lazyCss(...)` from [`app/Providers/LazyCssServiceProvider.php`] and for JS bundle scheduling in [resources/js/app.js] and idle [resources/js/app-lazy.js] imports.
- Reuse CSS variable helpers instead of custom parsing: JS helpers are in [resources/js/support/css-variables.js], and PHP theme helpers are in [app/Services/Functions/theme.php].
- The whole application is an SPA-like shell with one main route ([routes/web.php]: `/`) and where client-side nested view transitions are in [resources/views/home.blade.php].
- Use hash navigation via `x-hash-actions` (from [`resources/js/packages/alpine/hash-actions.js`]) and `switch-view` events for native/web navigation consistency.
- Use `$livewireLock` (from [resources/js/support/alpine/magic/livewire-lock.js]) for action locking where repeated taps/clicks could cause duplicate requests.
- Use Filament as the primary UI engine for notifications, modals, slideovers, forms, tables, admin panels, etc.
- Keep the "control panel" as a Filament tabbed action, where settings, changelogs, and about tabs are built.
- For settings behavior, keep server/API defaults authoritative for untouched keys while persisting only explicit user overrides (see [resources/js/support/alpine/athkar-app-overrides.js]); any new setting must join this merge path.
- Place reusable cross-feature utilities in `Support`/`support` namespaces and folders, put inside their standradized main folders first of course.
- The layout manager [resources/js/support/alpine/data/layout-manager.js] tracks action/modal events (`open-modal`, `close-modal`, etc.) and should stay in sync with Filament modal behavior.

## Preferences
- Do not ever consider using reduced-motion CSS feature.
- Do not restore reduced-motion suppression for Livewire (disabled in [resources/js/overrides/livewire-transition-consistency.js]).
- We manually decide what animations/effects to disable when `enable_visual_enhancements` setting is diabled.

## Documentation
- When looking for docs, check first which versions are in `composer.json` and `package.json`.
- When implementing a new development feature, and when it's very essential and uncommon (such as the existance of this instructions file), make sure it's documented in README.

## Development
- The native apps need some modifications on the NativePHP engine. These are done via `muttasiq-patches` NativePHP plugin. It's supposedly located in [~/Code/LaravelPackages/NATIVE_PLUGIN_muttasiq-patches] directory. Update its own README if you touch it.
  - The patching is build-time only and externalized to `goodm4ven/nativephp-muttasiq-patches`, enabled by [app/Providers/NativeServiceProvider.php], and ran as Android pre-complile hook.
  - Toggle local development of that plugin using [.scripts/composer-local-plugins-switch.sh], which targets [~/Code/LaravelPackages/NATIVE_PLUGIN_muttasiq-patches] by default.
- Preferred container workflow is [`lara-stacker`](https://github.com/GoodM4ven/CLI_LARAVEL_lara-stacker), expected to be located at [~/Code/Scripts/CLI_LARAVEL_lara-stacker/], and including scripts to import this project and to setup the local development environment.
- You can check out what Laravel setup requires for this application to work in [composer.json]'s `setup` script.

## Testing
- Do not write tests unless explicitely told to. But when you're implementing a new main feature, do create a new test then.
- When told to write tests, try to find first a related feature test and try to add to it, if it was suitable and simple enough to do.
- Feature tests must be put inside either App or Browser folders, where Browser is for PestPHP browser testing.
- PestPHP browser testing is buggy currently, and our setup is in a docker container, so make sure you're using [.scripts/testing] scripts that account for the setup.

## Debugging
- For investigating AlpineJS transition failiures, try using [resources/js/support/debugging/alpine-transition-debugger.js].
- If the front-end behavior doesn't sync with the back-end. Debug it using Playright browser skill.
  - When Playright is unavailable to the AI agent, warn the user about it.

## Quran Reader Fitting and Cache (Critical)
- Main implementation is in [resources/js/support/alpine/data/quran-app-reader.js].
- Rendering visibility is controlled by `pageFitState()`:
  - `fading-out` when `isTransitioningOutPage` is true.
  - `fitting` when `isFittingPage` is true (this state intentionally hides `.quran-page-lines`).
  - `ready` otherwise.
- Layout lifecycle:
  - `scheduleLayout()` queues `layoutPageGuaranteed()`.
  - `layoutPageGuaranteed()` retries `layoutPage()` until fit run count increases for the current page.
  - `layoutPage()` runs font readiness + text stabilization + `fitPageToViewport()` then `queuePageReveal()`.
  - `queuePageReveal()` is the final gate that must flip `isFittingPage` to false.
- Fit cache strategy:
  - Cache map is `_fitResultByContext`, persisted in localStorage key `quran-reader-fit-cache-v3`.
  - Cache key includes page number, breakpoint, viewport buckets, modal state, layout mode, line count, font families, and fit profile bounds.
  - Cache is bypassed during modal lifecycle (`_bypassNextFitCache`, modal settling, or open modals) and during suppressed persistence windows (`_suppressFitCacheWriteUntil`).
  - Fit sanity check (`scheduleFitSanityCheck`) can invalidate suspicious fit entries and force a re-layout.
- Modal and navigation guards that can keep page hidden:
  - `_isModalLifecycleSettling` + `_activeModalIds` + `openModalCount()`.
  - `_pendingNavigationRequest` + `_navigationRevealLocked`.
  - If these guards stay stale while `isFittingPage` is true, UI can appear as a permanent white/hidden page.
- Current anti-stuck rules:
  - `clearStaleRevealGuards()` clears stale modal/navigation guards when they no longer match real DOM/navigation state.
  - `queuePageReveal()` has a blocked-duration fallback so reveal cannot remain hidden indefinitely once blockers are stale.
  - `ensureWirdEntryPageVisible()` must be used after `wird` enter/slider/swipe navigation to guarantee visibility recovery.
- Wird-specific race handling:
  - `_wirdNavigationRequestSerial` is last-write-wins for rapid interactions.
  - `queueWirdEntryRevealRecovery()` must be serial-guarded so old timers cannot re-hide a newer page.
  - Any in-wird navigation (`stepWird`, `navigateWirdToStep`) should clear old wird-entry recovery timers first.
  - Reader navigation source profiling is centralized in `navigationSourceProfile()`. `keyboard`/`swipe` must resolve to the same profile as `chevron` in both normal mode and wird mode so cache/fit heuristics stay aligned instead of taking a slower divergent branch.
  - `wirdNavigationSourceProfile()` should remain a thin wrapper over the shared reader navigation profile unless wird mode truly needs unique behavior.
  - Slider commits must prefer a fresh last `input` step (`_wirdSliderPendingCommitStep`/`_wirdSliderLastInputStep`) over `change` payload, but only when input is recent; otherwise use direct `change` step.
  - Keep slider focus hygiene: release/commit must blur `.quran-page-slider` so keyboard arrows always route through `onGlobalArrowNavigate()` instead of getting trapped by range-input native handling.
  - Keep page-event source fidelity in `handleRequestedNavigation()`: preserve incoming `detail.source` (especially `page-jump` and `page-slider-commit`) so navigation heuristics and history attribution do not diverge.
- When touching this area, always run the focused browser regression:
  - `lands on the final wird slider page and keeps the re-entered completed page visible`
  - This path covers support unlock modal, fast slider scrub to final wird step, completion exit, and re-entry visibility.

## Finishing
- When have modified CSS or JS files, use `npm run format:prettier` to format them.
- When have modified Blade-PHP files, use `npm run format:blade` to format them.
- When have modified PHP files, ensure `php artisan pint` was ran to format them.
- When have modified PHP files, run static analysis using `vendor/bin/phpstan analyse`.
