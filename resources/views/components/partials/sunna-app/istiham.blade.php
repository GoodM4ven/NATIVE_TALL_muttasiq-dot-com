{{-- ponytail: blank placeholder, designed later in this thread. --}}
<div
    class="absolute inset-0 flex items-center justify-center"
    x-cloak
    x-show="views['sunna-istiham-app'].isOpen"
    x-transition:enter="transition-all ease-out duration-500 delay-200"
    x-transition:enter-start="opacity-0! scale-[0.985]"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition-opacity ease-in duration-180!"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0!"
>
    <p class="font-arabic-serif text-primary-900/70 dark:text-primary-100/70 text-2xl">
        {{ arabic_text('الاستهام') }}
    </p>
</div>
