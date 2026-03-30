<div
    class="absolute inset-0 z-10 grid place-items-center px-4 py-5 sm:px-6 sm:py-8"
    x-cloak
    x-show="views['quran-app-tilawa'].isOpen || views['quran-app-hifth'].isOpen || views['quran-app-tadabbur'].isOpen"
    x-transition:enter="transition-all ease-out duration-750 delay-400"
    x-transition:enter-start="opacity-0! translate-y-5 blur-[2px]"
    x-transition:enter-end="opacity-100 translate-y-0 blur-0"
    x-transition:leave="transition-all ease-in duration-350!"
    x-transition:leave-start="opacity-100 translate-y-0 blur-0"
    x-transition:leave-end="opacity-0! blur-[2px]"
>
    <div class="relative grid h-full w-full place-items-center">
        <livewire:quran-app.reader />

        <div
            class="pointer-events-none absolute inset-0 z-30 grid place-items-center px-6"
            x-cloak
            x-show="views['quran-app-hifth'].isOpen"
        >
            <div
                class="rounded-2xl border border-amber-400/45 bg-amber-500/20 px-6 py-4 text-center text-sm font-semibold text-amber-100 shadow-lg backdrop-blur-sm sm:text-base">
                {{ arabic_text('وضع الحفظ قريبًا بإذن الله') }}
            </div>
        </div>

        <div
            class="pointer-events-none absolute inset-0 z-30 hidden justify-end p-4 sm:flex"
            x-cloak
            x-show="views['quran-app-tadabbur'].isOpen"
        >
            <aside
                class="h-full max-h-[78svh] w-68 rounded-2xl border border-sky-400/35 bg-sky-500/15 p-4 text-right text-sm text-sky-100 shadow-lg backdrop-blur-sm"
            >
                <h3 class="mb-2 text-base font-semibold">{{ arabic_text('لوحة التدبّر') }}</h3>
                <p class="leading-7">
                    {{ arabic_text('قريبًا: ستظهر هنا لوحة جانبية للتفسير والملاحظات أثناء القراءة.') }}
                </p>
            </aside>
        </div>
    </div>
</div>
