<div x-data="{ tip: null }" x-on:athkar-open-single-completion.window="$wire.mountAction('singleThikrCompletion', { index: Number($event.detail?.index ?? -1) })">
    <button
        class="bg-success-500/90 absolute -top-3 right-2 z-10 flex h-7 w-7 items-center justify-center rounded-full text-white opacity-0 shadow-lg transition-opacity duration-300"
        type="button"
        aria-label="إتمام جميع الأذكار"
        x-bind:class="completionHack.isVisible && 'opacity-100!'"
        x-bind:style="completionHack.isVisible ? 'pointer-events: auto;' : 'pointer-events: none;'"
        x-on:mouseenter="tip = $tippy('إتمام كل الأذكار', 'bottom')"
        x-on:mouseleave="tip?._clearHideTimer?.(); tip?.hide()"
        x-on:focus="tip = $tippy('إتمام كل الأذكار', 'bottom')"
        x-on:blur="tip?._clearHideTimer?.(); tip?.hide()"
        x-on:click.stop="
            tip?._clearHideTimer?.();
            tip?.hide();
            if (!completionHack.isVisible) { showCompletionHack({ pinned: true, armed: true }); return; }
            if (!completionHack.canHover && !completionHack.isArmed) { completionHack.isArmed = true; return; }
            $wire.mountAction('completion');
        "
    >
        <x-icon
            class="h-4 w-4"
            name="remix.check-double-fill"
        />
    </button>

    <x-filament-actions::modals />
</div>
