<div>
    <button
        class="bg-success-500/90 absolute -top-3 right-2 z-10 flex h-7 w-7 items-center justify-center rounded-full text-white opacity-0 shadow-lg transition-opacity duration-300"
        type="button"
        aria-label="إتمام جميع الأذكار"
        x-bind:class="completionHack.isVisible && 'opacity-100!'"
        x-on:click.stop="$wire.mountAction('completion')"
    >
        <x-icon
            class="h-4 w-4"
            name="remix.check-double-fill"
        />
    </button>

    <x-filament-actions::modals />
</div>
