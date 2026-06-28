## General
- Answer in English.
- Use PHP strict typing throughout new code.
- Do not implement or restore support for the `prefers-reduced-motion` CSS media feature.
- After modifying CSS or JS files, run `pnpm run format:prettier`.
- After modifying Blade files, run `pnpm run format:blade`.
- After modifying PHP files, run `php artisan pint`.
- After modifying PHP files, run `vendor/bin/phpstan analyse`.

## Documentation
- Check package versions in `composer.json` and `package.json` before consulting documentation.
- Document significant architectural or development features in the project's README.

## Architecture
- Refer to the `setup` Composer script to understand the required Laravel project setup.
- Prefer Blade components under `resources/views/components/partials` over plain `@include`s.
- Reuse the application's existing theme colors instead of introducing new color systems.
- Reuse the existing Alpine `bp` breakpoint helpers for responsive behavior.
- Follow the existing lazy-loading strategy for CSS and JavaScript assets instead of introducing new loading mechanisms.
- Reuse the existing CSS variable and theme helpers instead of implementing custom parsing.
- Reuse the existing SPA navigation, hash navigation, and view transition mechanisms instead of introducing new routing patterns.
- Use `$livewireLock` for actions that could otherwise trigger duplicate Livewire requests.
- Use `arabic_text(...)` for all UI-facing Arabic strings.
- Place reusable cross-feature utilities under the existing `Support` namespaces instead of feature-specific directories.
- Keep the custom layout manager synchronized with Filament's modal and action lifecycle.
- Keep custom CSS responsive. Prefer responsive Tailwind utility classes for sizing, spacing, typography, and positioning instead of fixed CSS values.
- New animations and visual effects should respect the `enable_visual_enhancements` setting.
- Do not restore Livewire's reduced-motion suppression.

## Cross-Platform
- When modifying NativePHP behavior, update the `muttasiq-patches` plugin and its README if applicable.
- Use the `lara-stacker` development environment and helper scripts for local development whenever possible.

## Testing
- Do not write tests unless explicitly requested or implementing a new primary feature.
- When adding tests, prefer extending existing related tests.
- Use the project's testing wrapper scripts instead of invoking browser tests directly.
- Do not run multiple browser tests simultaneously.

## Debugging
- When frontend behavior becomes inconsistent with backend behavior, debug it using the Playwright browser skill when available.
- If two meaningful fix attempts fail, add temporary logging before making further changes. If you cannot execute the logging yourself, provide clear instructions for collecting and sharing the logs.