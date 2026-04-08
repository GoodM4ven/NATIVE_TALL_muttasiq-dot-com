<div
    class="quran-manager-shell"
    data-no-swipe
    dir="rtl"
    x-data
    x-init="$nextTick(() => window.dispatchEvent(new CustomEvent('quran-bookmarks-manager-request-sync')))"
>
    <livewire:quran-app.bookmarks-manager-table />
</div>
