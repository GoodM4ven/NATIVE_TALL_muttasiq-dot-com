@props([
    'show' => 'false',
    'title' => '',
    'subtitle' => null,
    'decorated' => false,
    'arabesque' => false,
    'withBackdrop' => false,
    'backdropClass' => 'bg-white/94 dark:bg-slate-950/90',
    'fixedLayer' => false,
    'maxWidthClass' => 'max-w-3xl',
])

@php
    $containerPositionClass = $fixedLayer ? 'fixed' : 'absolute';
@endphp

<div
    class="{{ $containerPositionClass }} inset-0 z-40 flex items-center justify-center px-6 py-12"
    x-cloak
    x-show="{{ $show }}"
    x-transition:enter="transition-all ease-out duration-700 delay-500"
    x-transition:enter-start="opacity-0! translate-y-6 blur-[2px]"
    x-transition:enter-end="opacity-100 translate-y-0 blur-0"
    x-transition:leave="transition-all ease-in duration-300"
    x-transition:leave-start="opacity-100 translate-y-0 blur-0"
    x-transition:leave-end="opacity-0! translate-y-6 blur-[2px]"
>
    @if ($withBackdrop)
        <div
            class="{{ $backdropClass }} backdrop-blur-xs absolute inset-0 z-0"
            aria-hidden="true"
        ></div>
    @endif

    <div class="{{ $maxWidthClass }} relative z-10 flex w-full flex-col items-center gap-6 text-center">
        @if ($decorated && $arabesque)
            <div
                class="pointer-events-none absolute inset-0 z-0 flex items-center justify-center"
                aria-hidden="true"
            >
                <span
                    class="absolute h-[38rem] w-[38rem] rounded-full border border-emerald-300/35 shadow-[0_0_0_1px_rgba(16,185,129,0.08)]"
                ></span>
                <span class="absolute h-[34rem] w-[34rem] rounded-full border border-amber-300/35"></span>
                <span class="absolute h-[30rem] w-[30rem] rounded-full border border-sky-300/30"></span>
                <span
                    class="border-primary-300/35 absolute h-[26rem] w-[26rem] rounded-full border border-dashed"></span>
                <span
                    class="absolute h-[22rem] w-[22rem] rounded-full border border-dotted border-emerald-400/40"></span>
                <span class="absolute h-[18rem] w-[18rem] rounded-full border border-amber-500/40"></span>
                <span class="absolute h-[14rem] w-[14rem] rounded-full border border-sky-400/40"></span>

                <span
                    class="absolute h-[2px] w-[17rem] bg-gradient-to-r from-transparent via-emerald-500/55 to-transparent"
                ></span>
                <span
                    class="absolute h-[17rem] w-[2px] bg-gradient-to-b from-transparent via-amber-500/55 to-transparent"
                ></span>
                <span
                    class="rotate-30 absolute h-[2px] w-[14rem] bg-gradient-to-r from-transparent via-sky-500/45 to-transparent"
                ></span>
                <span
                    class="-rotate-30 via-primary-500/45 absolute h-[2px] w-[14rem] bg-gradient-to-r from-transparent to-transparent"
                ></span>
                <span
                    class="rotate-60 absolute h-[2px] w-[14rem] bg-gradient-to-r from-transparent via-emerald-500/40 to-transparent"
                ></span>
                <span
                    class="-rotate-60 absolute h-[2px] w-[14rem] bg-gradient-to-r from-transparent via-amber-500/40 to-transparent"
                ></span>

                @for ($index = 0; $index < 12; $index++)
                    <span
                        class="border-primary-400/45 absolute h-3.5 w-3.5 rounded-full border bg-white shadow-sm"
                        style="transform: rotate({{ $index * 30 }}deg) translateY(-14.2rem);"
                    ></span>
                @endfor

                @for ($index = 0; $index < 8; $index++)
                    <span
                        class="absolute text-xl text-emerald-600/70"
                        style="transform: rotate({{ $index * 45 }}deg) translateY(-9.2rem);"
                    >۞</span>
                @endfor
            </div>
        @elseif ($decorated)
            <div
                class="pointer-events-none absolute inset-0 z-0 flex items-center justify-center"
                aria-hidden="true"
            >
                <span class="absolute h-96 w-96 rounded-full border border-amber-400/35"></span>
                <span class="absolute h-80 w-80 rounded-full border border-emerald-400/25"></span>
                <span class="border-primary-400/28 absolute h-64 w-64 rounded-full border"></span>
                <span class="absolute h-48 w-48 rounded-full border border-amber-500/30"></span>
            </div>
        @endif

        <div
            class="relative z-10 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-700 shadow-sm dark:bg-emerald-400/15 dark:text-emerald-200"
            aria-hidden="true"
        >
            {{ $icon ?? '✓' }}
        </div>

        <p
            class="relative z-10 rounded-full bg-white/85 px-5 py-2.5 text-3xl text-slate-900 shadow-sm dark:bg-slate-900/80 dark:text-white">
            {{ $title }}
        </p>

        @if (filled($subtitle))
            <p
                class="relative z-10 rounded-full bg-white/80 px-4 py-1.5 text-xs text-slate-600 shadow-sm dark:bg-slate-900/75 dark:text-slate-300">
                {{ $subtitle }}
            </p>
        @endif
    </div>
</div>
