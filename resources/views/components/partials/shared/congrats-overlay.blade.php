@props([
    'show' => 'false',
    'title' => '',
    'subtitle' => null,
    'decorated' => false,
    'arabesque' => false,
    'patternVariant' => 'default',
    'withBackdrop' => false,
    'backdropClass' => 'bg-white/94 dark:bg-slate-950/90',
    'fixedLayer' => false,
    'maxWidthClass' => 'max-w-3xl',
    'transitionEnter' => 'transition-all ease-out duration-700 delay-500',
    'transitionEnterStart' => 'opacity-0! translate-y-6 blur-[2px]',
    'transitionEnterEnd' => 'opacity-100 translate-y-0 blur-0',
    'transitionLeave' => 'transition-all ease-in duration-300',
    'transitionLeaveStart' => 'opacity-100 translate-y-0 blur-0',
    'transitionLeaveEnd' => 'opacity-0! translate-y-6 blur-[2px]',
])

@php
    $containerPositionClass = $fixedLayer ? 'fixed' : 'absolute';
@endphp

@once
    @assets
        <style>
            .quran-congrats-style-one {
                background-color: #16a085;
                background-image:
                    linear-gradient(67.5deg, #16a085 10%, transparent 10%),
                    linear-gradient(157.5deg, #16a085 10%, transparent 10%),
                    linear-gradient(67.5deg, transparent 90%, #16a085 90%),
                    linear-gradient(157.5deg, transparent 90%, #16a085 90%),
                    linear-gradient(22.5deg, #16a085 10%, transparent 10%),
                    linear-gradient(112.5deg, #16a085 10%, transparent 10%),
                    linear-gradient(22.5deg, transparent 90%, #16a085 90%),
                    linear-gradient(112.5deg, transparent 90%, #16a085 90%),
                    linear-gradient(22.5deg,
                        transparent 33%,
                        #d5d8dc 33%,
                        #d5d8dc 36%,
                        transparent 36%,
                        transparent 64%,
                        #d5d8dc 64%,
                        #d5d8dc 67%,
                        transparent 67%),
                    linear-gradient(-22.5deg,
                        transparent 33%,
                        #d5d8dc 33%,
                        #d5d8dc 36%,
                        transparent 36%,
                        transparent 64%,
                        #d5d8dc 64%,
                        #d5d8dc 67%,
                        transparent 67%),
                    linear-gradient(112.5deg,
                        transparent 33%,
                        #d5d8dc 33%,
                        #d5d8dc 36%,
                        transparent 36%,
                        transparent 64%,
                        #d5d8dc 64%,
                        #d5d8dc 67%,
                        transparent 67%),
                    linear-gradient(-112.5deg,
                        transparent 33%,
                        #d5d8dc 33%,
                        #d5d8dc 36%,
                        transparent 36%,
                        transparent 64%,
                        #d5d8dc 64%,
                        #d5d8dc 67%,
                        transparent 67%);
                background-size: 250px 250px;
                background-position:
                    -100px 150px,
                    -150px 150px,
                    -150px 100px,
                    -100px 100px,
                    -150px 100px,
                    -100px 100px,
                    -100px 150px,
                    -150px 150px,
                    0 0,
                    0 0,
                    0 0,
                    0 0;
            }
        </style>
    @endassets
@endonce

<div
    class="{{ $containerPositionClass }} inset-0 z-40 flex items-center justify-center px-6 py-12"
    x-cloak
    x-show="{{ $show }}"
    x-transition:enter="{{ $transitionEnter }}"
    x-transition:enter-start="{{ $transitionEnterStart }}"
    x-transition:enter-end="{{ $transitionEnterEnd }}"
    x-transition:leave="{{ $transitionLeave }}"
    x-transition:leave-start="{{ $transitionLeaveStart }}"
    x-transition:leave-end="{{ $transitionLeaveEnd }}"
>
    @if ($withBackdrop)
        <div
            class="{{ $backdropClass }} backdrop-blur-xs absolute inset-0 z-0"
            aria-hidden="true"
        ></div>
    @endif

    <div class="{{ $maxWidthClass }} relative z-10 flex w-full flex-col items-center gap-6 text-center">
        @if ($decorated && $arabesque && $patternVariant === 'connected')
            <div
                class="pointer-events-none absolute inset-0 z-0 flex items-center justify-center"
                aria-hidden="true"
            >
                <span class="quran-congrats-style-one absolute inset-0 rounded-3xl opacity-30"></span>
            </div>
        @elseif ($decorated && $arabesque)
            <div
                class="pointer-events-none absolute inset-0 z-0 flex items-center justify-center"
                aria-hidden="true"
            >
                <span
                    class="h-152 w-152 absolute rounded-full border border-emerald-300/35 shadow-[0_0_0_1px_rgba(16,185,129,0.08)]"
                ></span>
                <span class="h-136 w-136 absolute rounded-full border border-amber-300/35"></span>
                <span class="h-120 w-120 absolute rounded-full border border-sky-300/30"></span>
                <span class="border-primary-300/35 h-104 w-104 absolute rounded-full border border-dashed"></span>
                <span class="h-88 w-88 absolute rounded-full border border-dotted border-emerald-400/40"></span>
                <span class="absolute h-72 w-[18rem] rounded-full border border-amber-500/40"></span>
                <span class="absolute h-56 w-56 rounded-full border border-sky-400/40"></span>

                <span
                    class="w-68 bg-linear-to-r absolute h-[2px] from-transparent via-emerald-500/55 to-transparent"></span>
                <span
                    class="h-68 bg-linear-to-b absolute w-[2px] from-transparent via-amber-500/55 to-transparent"></span>
                <span
                    class="rotate-30 bg-linear-to-r absolute h-[2px] w-56 from-transparent via-sky-500/45 to-transparent"
                ></span>
                <span
                    class="-rotate-30 via-primary-500/45 bg-linear-to-r absolute h-[2px] w-56 from-transparent to-transparent"
                ></span>
                <span
                    class="rotate-60 bg-linear-to-r absolute h-[2px] w-56 from-transparent via-emerald-500/40 to-transparent"
                ></span>
                <span
                    class="-rotate-60 bg-linear-to-r absolute h-[2px] w-56 from-transparent via-amber-500/40 to-transparent"
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
            aria-hidden="true"
            @class([
                'absolute! top-1/2! z-10! flex! h-40! w-40! text-[4rem]!' =>
                    $decorated && $arabesque,
                'relative z-10 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-700 shadow-sm dark:bg-emerald-400/15 dark:text-emerald-200',
            ])
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
