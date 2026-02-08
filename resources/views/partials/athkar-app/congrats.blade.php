<div
    class="absolute inset-0 z-40 flex items-center justify-center px-6 py-12"
    x-cloak
    x-show="isCompletionVisible && !shouldSkipNoticePanels()"
    x-transition:enter="transition-all ease-out duration-700 delay-500"
    x-transition:enter-start="opacity-0! translate-y-6 blur-[2px]"
    x-transition:enter-end="opacity-100 translate-y-0 blur-0"
    x-transition:leave="transition-all ease-in duration-300"
    x-transition:leave-start="opacity-100 translate-y-0 blur-0"
    x-transition:leave-end="opacity-0! translate-y-6 blur-[2px]"
>
    <div class="athkar-reader flex w-full max-w-3xl flex-col items-center gap-6 text-center">
        <div
            class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-700 shadow-sm dark:bg-emerald-400/15 dark:text-emerald-200"
            aria-hidden="true"
        >
            ✓
        </div>
        <p class="text-3xl text-slate-900 dark:text-white">
            والحمد لله رب العالمين
        </p>
        <p class="text-xs text-slate-500 dark:text-slate-400">
            تم حفظ الإنجاز حتى بداية اليوم التالي.
        </p>
    </div>
</div>
