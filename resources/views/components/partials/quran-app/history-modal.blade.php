<div
    class="quran-manager-shell"
    data-no-swipe
    dir="rtl"
    x-data
    x-init="$nextTick(() => window.dispatchEvent(new CustomEvent('quran-history-manager-request-sync')))"
>
    <livewire:quran-app.history-manager-table />
</div>
