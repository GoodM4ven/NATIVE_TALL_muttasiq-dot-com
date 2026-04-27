@assets
    <style>
        .quran-app-reader-stage {
            transform-origin: 50% 86%;
            will-change: transform, opacity;
        }

        [data-quran-app-shell].quran-app-shell--reader-entering .quran-app-reader-stage {
            animation: quran-reader-stage-enter 360ms cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        [data-quran-app-shell].quran-app-shell--reader-leaving .quran-app-reader-stage {
            animation: quran-reader-stage-leave 220ms cubic-bezier(0.4, 0, 1, 1) both;
        }

        @keyframes quran-reader-stage-enter {
            from {
                opacity: 0;
                transform: translate3d(0, 1.4rem, 0) scale(1.09);
            }

            to {
                opacity: 1;
                transform: translate3d(0, 0, 0) scale(1);
            }
        }

        @keyframes quran-reader-stage-leave {
            from {
                opacity: 1;
                transform: translate3d(0, 0, 0) scale(1);
            }

            to {
                opacity: 0;
                transform: translate3d(0, 1.1rem, 0) scale(0.93);
            }
        }
    </style>
@endassets

<div
    class="quran-app-reader-stage absolute inset-0 z-10 grid place-items-center px-4 py-5 sm:px-6 sm:py-8 xl:pb-6 xl:pt-5 2xl:pt-7 2xl:pb-8 3xl:py-8"
    x-cloak
    x-show="views['quran-app-tilawa'].isOpen || views['quran-app-hifth'].isOpen || views['quran-app-tadabbur'].isOpen"
    x-transition:enter="transition-[opacity,transform] ease-out duration-220"
    x-transition:enter-start="opacity-0! translate-y-4 scale-[1.03]"
    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
    x-transition:leave="transition-[opacity,transform] ease-in duration-160!"
    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
    x-transition:leave-end="opacity-0! translate-y-1 scale-[0.995]"
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
                class="w-68 h-full max-h-[78svh] rounded-2xl border border-sky-400/35 bg-sky-500/15 p-4 text-right text-sm text-sky-100 shadow-lg backdrop-blur-sm"
            >
                <h3 class="mb-2 text-base font-semibold">{{ arabic_text('لوحة التدبّر') }}</h3>
                <p class="leading-7">
                    {{ arabic_text('قريبًا: ستظهر هنا لوحة جانبية للتفسير والملاحظات أثناء القراءة.') }}
                </p>
            </aside>
        </div>
    </div>
</div>
