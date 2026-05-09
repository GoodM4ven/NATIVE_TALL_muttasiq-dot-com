<div
    class="3xl:[zoom:1.15] 4xl:[zoom:1.25] -top-40! sm:top-0! absolute inset-0 flex items-center justify-center [zoom:0.625] sm:[zoom:0.85] md:[zoom:0.85] lg:[zoom:1] xl:[zoom:0.8] 2xl:[zoom:1]"
    x-cloak
    x-show="views['main-menu'].isOpen"
    x-transition:enter="transition-all ease-out duration-1000 delay-400"
    x-transition:enter-start="opacity-0 blur-[2px]"
    x-transition:enter-end="opacity-100 blur-0"
    x-transition:leave="transition-all ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-60"
>
    <x-main-menu x-bind:data-main-menu-exiting="views['main-menu'].isOpen ? 'false' : 'true'">
        <x-main-menu.item
            :iconName="'zondicon.chat-bubble-dots'"
            :caption="arabic_text('الأذكار')"
            :onClickCallback="'() => ($viewNav(`athkar-app-gate`))'"
        />
        <x-main-menu.item
            :iconName="'fontawesome.solid-hand-holding'"
            :iconClasses="'scale-[1.15]'"
            :caption="arabic_text('الأدعية')"
        />
        <x-main-menu.item
            :iconName="'teeny.plant'"
            :caption="arabic_text('المعروف')"
        />
        <x-main-menu.item
            :iconName="'unicons.check-square'"
            :caption="arabic_text('السنن')"
        />
        <x-main-menu.item
            :iconName="'entypo.book'"
            :iconClasses="'scale-[1.05]'"
            :caption="arabic_text('الكتاب')"
            :onClickCallback="'() => openQuranEntry()'"
        />
        <x-main-menu.item
            :iconName="'vaadin.search'"
            :iconClasses="'scale-[0.85]'"
            :caption="arabic_text('الآثار')"
        />
        <x-main-menu.item
            :iconName="'bootstrap.compass-fill'"
            :caption="arabic_text('التعلم')"
        />
        <x-main-menu.item
            :iconName="'fontawesome.solid-bottle-droplet'"
            :caption="arabic_text('الدواء')"
        />
        <x-main-menu.item
            :iconName="'entypo.bookmark'"
            :iconClasses="'scale-[1.15]'"
            :caption="arabic_text('المحفوظات')"
        />
    </x-main-menu>

    {{-- <div class="z-100 fixed bottom-4 left-4 flex flex-col gap-2">
        <button
            class="bg-white/92 rounded-lg border border-emerald-500/45 px-3 py-1.5 text-xs font-semibold text-emerald-800 shadow-sm backdrop-blur-sm"
            type="button"
            aria-label="{{ arabic_text('محاكاة يوم جديد للوِرد') }}"
            x-on:click.prevent="window.dispatchEvent(new CustomEvent('quran-wird-simulate-day', { detail: { days: 1 } }))"
        >
            {{ arabic_text('اختبار: يوم جديد') }}
        </button>

        <button
            class="bg-white/92 rounded-lg border border-sky-500/45 px-3 py-1.5 text-xs font-semibold text-sky-800 shadow-sm backdrop-blur-sm"
            type="button"
            aria-label="{{ arabic_text('معاينة تهنئة إتمام الوِرد') }}"
            x-on:click.prevent="
                $viewNav('quran-app-tilawa');
                window.dispatchEvent(new CustomEvent('quran-wird-congrats-preview', { detail: { mode: 'open' } }));
            "
        >
            {{ arabic_text('اختبار: تهنئة الوِرد') }}
        </button>

        <button
            class="bg-white/92 rounded-lg border border-slate-500/45 px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur-sm"
            type="button"
            aria-label="{{ arabic_text('إغلاق معاينة تهنئة إتمام الوِرد') }}"
            x-on:click.prevent="window.dispatchEvent(new CustomEvent('quran-wird-congrats-preview', { detail: { mode: 'close' } }))"
        >
            {{ arabic_text('إغلاق المعاينة') }}
        </button>
    </div> --}}
</div>
