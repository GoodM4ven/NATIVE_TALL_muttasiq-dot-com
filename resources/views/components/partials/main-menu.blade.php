<div
    class="3xl:zoom-[1.15] 4xl:zoom-[1.25] -top-40! sm:top-0! zoom-[0.625] sm:zoom-[0.85] md:zoom-[0.85] lg:zoom-[1] xl:zoom-[0.8] 2xl:zoom-[1] absolute inset-0 flex items-center justify-center"
    x-cloak
    x-show="views['main-menu'].isOpen"
    x-transition:enter="transition-all ease-out duration-650"
    x-transition:enter-start="opacity-0 blur-[2px]"
    x-transition:enter-end="opacity-100 blur-0"
    x-transition:leave="transition-all ease-in duration-150"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-60"
>
    <x-main-menu
        style="zoom: var(--ui-scale, 1);"
        x-bind:data-main-menu-exiting="views['main-menu'].isOpen ? 'false' : 'true'"
    >
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
            :iconName="'fontawesome.search'"
            :iconClasses="'scale-[0.85] stroke-2'"
            :caption="arabic_text('الآثار')"
        />
        <x-main-menu.item
            :iconName="'fontawesome.feather'"
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
