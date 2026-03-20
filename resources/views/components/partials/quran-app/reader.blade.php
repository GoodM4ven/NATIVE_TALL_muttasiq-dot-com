<div
    class="absolute inset-0 z-10 grid place-items-center px-4 py-5 sm:px-6 sm:py-8"
    x-cloak
    x-show="views['quran-reader'].isOpen"
    x-transition:enter="transition-all ease-out duration-750 delay-400"
    x-transition:enter-start="opacity-0! translate-y-5 blur-[2px]"
    x-transition:enter-end="opacity-100 translate-y-0 blur-0"
    x-transition:leave="transition-all ease-in duration-350!"
    x-transition:leave-start="opacity-100 translate-y-0 blur-0"
    x-transition:leave-end="opacity-0! blur-[2px]"
>
    <livewire:quran-reader />
</div>
