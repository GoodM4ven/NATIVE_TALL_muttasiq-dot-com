<div
    class="absolute inset-0 flex items-center justify-center"
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
            :caption="'الأذكار'"
            :onClickCallback="'() => ($viewNav(`athkar-app-gate`))'"
        />
        <x-main-menu.item
            :iconName="'fontawesome.solid-hand-holding'"
            :iconClasses="'scale-[1.15]'"
            :caption="'الأدعية'"
        />
        <x-main-menu.item
            :iconName="'teeny.plant'"
            :caption="'المعروف'"
        />
        <x-main-menu.item
            :iconName="'unicons.check-square'"
            :caption="'السنن'"
        />
        <x-main-menu.item
            :iconName="'entypo.book'"
            :iconClasses="'scale-[1.05]'"
            :caption="'الكتاب'"
        />
        <x-main-menu.item
            :iconName="'vaadin.search'"
            :iconClasses="'scale-[0.85]'"
            :caption="'الآثار'"
        />
        <x-main-menu.item
            :iconName="'bootstrap.compass-fill'"
            :caption="'التعلم'"
        />
        <x-main-menu.item
            :iconName="'fontawesome.solid-bottle-droplet'"
            :caption="'الدواء'"
        />
        <x-main-menu.item
            :iconName="'entypo.bookmark'"
            :iconClasses="'scale-[1.15]'"
            :caption="'المحفوظات'"
        />
    </x-main-menu>
</div>
