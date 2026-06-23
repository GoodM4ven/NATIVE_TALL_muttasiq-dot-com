<div
    class="-top-12! sm:top-0! absolute inset-0 flex items-center justify-center"
    data-sunna-app-root
    x-cloak
    x-show="views['sunna-gate'].isOpen || views['sunna-istiham-app'].isOpen"
    x-transition:enter="transition-all ease-out duration-750 delay-400"
    x-transition:enter-start="opacity-0! translate-y-5 blur-[2px]"
    x-transition:enter-end="opacity-100 translate-y-0 blur-0"
    x-transition:leave="transition-all ease-in duration-350!"
    x-transition:leave-start="opacity-100 translate-y-0 blur-0"
    x-transition:leave-end="opacity-0! blur-[2px]"
>
    <div class="relative flex h-full w-full items-center justify-center">
        <x-partials.sunna-app.gate />
        <x-partials.sunna-app.istiham />
    </div>
</div>
