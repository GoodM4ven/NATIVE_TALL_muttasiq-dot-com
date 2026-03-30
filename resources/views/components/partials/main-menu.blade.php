<div
    class="absolute inset-0 flex items-center justify-center xl:[zoom:1.25]"
    x-cloak
    x-show="views['main-menu'].isOpen"
    x-transition:enter="transition-[opacity,filter] ease-out duration-1000 delay-400"
    x-transition:enter-start="opacity-0! blur-[2px]"
    x-transition:enter-end="opacity-100 blur-0"
    x-transition:leave="transition-[opacity,filter] ease-in duration-350"
    x-transition:leave-start="opacity-100 blur-0"
    x-transition:leave-end="opacity-0! blur-[2px]"
>
    <x-main-menu>
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
            :onClickCallback="'() => ($viewNav(`quran-app-gate`))'"
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
</div>
