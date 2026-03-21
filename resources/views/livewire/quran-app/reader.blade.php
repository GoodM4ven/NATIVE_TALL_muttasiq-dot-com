@assets
    <style>
        @font-face {
            font-family: 'MadinaQuran';
            src: url('/vendor/arabicable/madina.woff2') format('woff2');
            font-display: swap;
        }

        .quran-reader {
            --quran-panel-bg: color-mix(in srgb, var(--background) 92%, transparent);
            --quran-panel-border: color-mix(in srgb, var(--primary-500) 42%, transparent);
            --quran-panel-shadow: 0 22px 40px color-mix(in srgb, var(--gray-900) 18%, transparent);
            --quran-panel-text: var(--primary-950);
            --quran-ink: color-mix(in srgb, var(--primary-950) 94%, var(--gray-900));
            --quran-subtle: color-mix(in srgb, var(--primary-700) 68%, var(--gray-500));
            --quran-chip-bg: color-mix(in srgb, var(--background-dark) 66%, transparent);
            --quran-chip-border: color-mix(in srgb, var(--primary-500) 35%, transparent);
            --quran-chip-hover: color-mix(in srgb, var(--primary-500) 16%, transparent);
            --quran-active-bg: color-mix(in srgb, var(--success-500) 24%, transparent);
            --quran-active-text: color-mix(in srgb, var(--success-600) 82%, var(--primary-900));
            --quran-page-surface: color-mix(in srgb, var(--background) 86%, transparent);
            --quran-page-border: color-mix(in srgb, var(--gray-300) 58%, transparent);
        }

        .dark .quran-reader {
            --quran-panel-bg: color-mix(in srgb, var(--primary-200) 24%, transparent);
            --quran-panel-border: color-mix(in srgb, var(--primary-300) 42%, transparent);
            --quran-panel-shadow: 0 24px 44px color-mix(in srgb, var(--gray-950) 62%, transparent);
            --quran-panel-text: var(--primary-50);
            --quran-ink: color-mix(in srgb, var(--primary-50) 94%, white);
            --quran-subtle: color-mix(in srgb, var(--primary-100) 72%, var(--gray-300));
            --quran-chip-bg: color-mix(in srgb, var(--gray-950) 44%, transparent);
            --quran-chip-border: color-mix(in srgb, var(--primary-200) 42%, transparent);
            --quran-chip-hover: color-mix(in srgb, var(--primary-300) 25%, transparent);
            --quran-active-bg: color-mix(in srgb, var(--success-400) 28%, transparent);
            --quran-active-text: color-mix(in srgb, var(--success-200) 82%, white);
            --quran-page-surface: color-mix(in srgb, var(--gray-950) 44%, transparent);
            --quran-page-border: color-mix(in srgb, var(--gray-700) 65%, transparent);
        }

        .quran-reader-panel {
            color: var(--quran-panel-text);
            background: var(--quran-panel-bg);
            border-color: var(--quran-panel-border);
            box-shadow: var(--quran-panel-shadow);
        }

        .quran-page-surface {
            background: var(--quran-page-surface);
            border-color: var(--quran-page-border);
        }

        .font-quran {
            font-family: 'MadinaQuran', 'Amiri', 'Traditional Arabic', serif;
        }

        .quran-ayah-line-run {
            display: flex;
            width: 100%;
            direction: rtl;
            align-items: baseline;
            gap: 0;
            white-space: nowrap;
        }

        .quran-ayah-line-run-rect {
            justify-content: flex-start;
        }

        .quran-ayah-line-run-centered {
            justify-content: center;
        }

        .quran-word-button {
            display: inline-flex;
            align-items: baseline;
            white-space: nowrap;
            line-height: 1;
        }

        .quran-ayah-marker {
            font-family: 'IBM Plex Sans Arabic', 'Manrope', ui-sans-serif, system-ui, sans-serif;
            line-height: 1;
        }
    </style>
@endassets

<div
    class="quran-reader relative grid h-full w-full place-items-center"
    dir="rtl"
>
    @if (!$ready)
        <section
            class="quran-reader-panel relative flex h-[68svh] min-h-[26rem] w-full max-w-[34rem] flex-col items-center justify-center gap-4 rounded-[1.75rem] border px-6 py-7 text-center"
        >
            <h2 class="font-quran text-3xl leading-[1.9]">قارئ القرآن</h2>
            <p class="text-sm leading-7 opacity-85">
                بيانات المصحف غير متاحة بعد. تأكد من تجهيز جداول القرآن وبياناتها، ثم أعد فتح قسم الكتاب.
            </p>
        </section>
    @else
        <section
            class="quran-reader-panel relative flex h-[76svh] min-h-[31rem] w-full max-w-[35rem] flex-col overflow-hidden rounded-[1.75rem] border"
        >
            <header class="grid grid-cols-[auto_1fr_auto] items-center gap-2 px-3 py-3 sm:px-4 sm:py-4">
                <div class="flex items-center gap-2">
                    <button
                        class="rounded-xl border px-3 py-2 text-xs font-semibold transition sm:px-4"
                        type="button"
                        style="border-color: var(--quran-chip-border); background: var(--quran-chip-bg);"
                        wire:click="nextPage"
                    >
                        التالي
                    </button>
                </div>

                <div class="grid place-items-center gap-1 text-center">
                    <p
                        class="text-xs font-semibold"
                        style="color: var(--quran-subtle);"
                    >الصفحة</p>
                    <div class="flex items-center justify-center gap-2">
                        <input
                            class="w-[4.5rem] rounded-lg border bg-transparent px-2 py-1 text-center text-sm tabular-nums outline-none transition focus:ring-2"
                            type="number"
                            style="border-color: var(--quran-chip-border);"
                            max="{{ max(1, $maxPage) }}"
                            min="1"
                            wire:model.live.debounce.350ms="pageNumber"
                        >
                        <span
                            class="text-xs tabular-nums"
                            style="color: var(--quran-subtle);"
                        >/{{ max(1, $maxPage) }}</span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button
                        class="rounded-xl border px-3 py-2 text-xs font-semibold transition sm:px-4"
                        type="button"
                        style="border-color: var(--quran-chip-border); background: var(--quran-chip-bg);"
                        wire:click="previousPage"
                    >
                        السابق
                    </button>
                    <button
                        class="rounded-xl border px-3 py-2 text-xs font-semibold transition sm:px-4"
                        type="button"
                        style="border-color: var(--quran-chip-border); background: var(--quran-chip-bg);"
                        x-data
                        x-on:click="$viewNav('quran-app-gate')"
                    >
                        القائمة
                    </button>
                </div>
            </header>

            <div class="min-h-0 flex-1 overflow-y-auto px-3 pb-4 sm:px-4 sm:pb-5">
                <div class="quran-page-surface rounded-2xl border px-3 py-4 sm:px-4 sm:py-5">
                    @if ($qpcPageFontFamily !== null && $qpcPageFontUrl !== null && $qpcPageFontFormat !== null)
                        <style>
                            @font-face {
                                font-family: '{{ $qpcPageFontFamily }}';
                                src: url('{{ $qpcPageFontUrl }}') format('{{ $qpcPageFontFormat }}');
                                font-display: block;
                            }
                        </style>
                    @endif

                    <div
                        class="{{ !$useCenteredAyahLayout ? 'mx-auto w-[32rem] max-w-full space-y-7' : 'mx-auto max-w-[920px] space-y-7' }}">
                        @foreach ($mushafLines as $line)
                            @php
                                $isRectangularAyahLine = $line['line_type'] === 'ayah' && !$useCenteredAyahLayout;
                                $lineFontStyle =
                                    $qpcPageFontFamily !== null
                                        ? "font-family: '{$qpcPageFontFamily}', 'MadinaQuran', 'Amiri', 'Traditional Arabic', serif;"
                                        : null;
                            @endphp
                            <div
                                class="{{ $isRectangularAyahLine ? 'text-right' : ($line['is_centered'] ? 'text-center' : '') }}"
                                wire:key="quran-line-{{ $pageNumber }}-{{ $line['line_number'] }}-{{ $line['line_type'] }}"
                            >
                                @if ($line['line_type'] === 'ayah' && $line['words'] !== [])
                                    <div
                                        class="{{ $isRectangularAyahLine ? 'quran-ayah-line quran-ayah-line-run quran-ayah-line-run-rect font-quran text-[1.95rem] leading-[1.54] sm:text-[2.08rem]' : 'quran-ayah-line quran-ayah-line-run quran-ayah-line-run-centered font-quran text-[1.72rem] leading-[2.12] sm:text-[2.02rem]' }}"
                                        @if ($lineFontStyle !== null) style="{{ $lineFontStyle }}; color: var(--quran-ink);"
                                        @else
                                            style="color: var(--quran-ink);" @endif
                                    >
                                        @foreach ($line['words'] as $word)
                                            @php
                                                $wordAyahIndex = (int) ($word['ayah_index'] ?? 0);
                                            @endphp
                                            <button
                                                class="{{ $wordAyahIndex > 0 && $wordAyahIndex === $activeAyahIndex ? 'quran-segment-active' : '' }} quran-word-button rounded-sm px-0 transition"
                                                type="button"
                                                @if ($wordAyahIndex > 0 && $wordAyahIndex === $activeAyahIndex) style="background: var(--quran-active-bg); color: var(--quran-active-text);" @endif
                                                wire:key="quran-word-{{ $pageNumber }}-{{ $line['line_number'] }}-{{ $word['word_index'] ?? $loop->index }}"
                                                @if ($wordAyahIndex > 0) wire:click="selectAyah({{ $wordAyahIndex }})"
                                                @else
                                                    disabled @endif
                                            >
                                                {{ $word['text'] }}
                                            </button>
                                            @if (($word['ends_ayah'] ?? false) && !($word['is_glyph'] ?? false))
                                                <span
                                                    class="quran-ayah-marker mr-0.5 text-[0.92rem]"
                                                    style="color: var(--quran-subtle);"
                                                >۝{{ $word['ayah_number'] }}</span>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <div
                                        class="font-quran text-2xl leading-[2.1] sm:text-3xl"
                                        style="color: var(--quran-ink);"
                                    >
                                        {{ $line['text'] }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>
