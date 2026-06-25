<div
    class="absolute inset-0"
    x-cloak
    x-show="views['main-menu'].isOpen"
    x-transition.opacity.duration.300ms
>
    <div
        class="duration-400 absolute inset-0 z-0 overflow-hidden opacity-30 transition-opacity dark:opacity-40"
        aria-hidden="true"
    >
        <div
            class="absolute inset-0 transition-opacity delay-300 duration-500 will-change-[opacity]"
            x-cloak
            x-show="!$store.colorScheme.isDarkModeOn"
        >
            <x-goodmaven::blurred-image
                class="h-full w-full scale-110 object-cover opacity-60"
                alt="Morning background"
                :imagePath="asset('images/background/main-morning-blurred.webp')"
                :thumbnailImagePath="asset('images/background/main-morning-blurred-blur-thumbnail.webp')"
                isDisplayEnforcedJs="() => window.location.hash === '#main-menu'"
            />
        </div>

        <div
            class="absolute inset-0 transition-opacity delay-300 duration-500 will-change-[opacity]"
            x-cloak
            x-show="$store.colorScheme.isDarkModeOn"
        >
            <x-goodmaven::blurred-image
                class="h-full w-full scale-110 object-cover"
                alt="Night background"
                :imagePath="asset('images/background/main-night-blurred.webp')"
                :thumbnailImagePath="asset('images/background/main-night-blurred-blur-thumbnail.webp')"
                isDisplayEnforcedJs="() => window.location.hash === '#main-menu'"
            />
        </div>

        <div class="absolute inset-0 bg-black/0 dark:bg-black/60"></div>
    </div>

    <div
        class="3xl:scale-[1.15] 4xl:scale-[1.25] -top-40! sm:top-0! absolute inset-0 z-10 flex scale-[0.625] items-center justify-center sm:scale-[0.85] md:scale-[0.85] lg:scale-[1] xl:scale-[0.8] 2xl:scale-[1]">
        <div
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
                style="scale: var(--ui-scale, 1);"
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
                    :onClickCallback="'() => ($viewNav(`sunna-gate`))'"
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
    </div>
</div>
