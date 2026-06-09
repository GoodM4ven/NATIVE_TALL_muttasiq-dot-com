@props([
    'embedUrl' => '',
    'videoUrl' => '',
])

@php
    $resolvedEmbedUrl = trim((string) $embedUrl);
    $resolvedVideoUrl = trim((string) $videoUrl);
@endphp

<div class="space-y-4">
    <div class="overflow-hidden rounded-2xl border border-gray-200/70 bg-black shadow-lg dark:border-white/10">
        @if ($resolvedEmbedUrl !== '')
            <div class="relative aspect-video w-full">
                <iframe
                    class="absolute inset-0 h-full w-full"
                    src="{{ $resolvedEmbedUrl }}"
                    title="{{ arabic_text('ما هو متسق؟') }}"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen
                    loading="lazy"
                    referrerpolicy="strict-origin-when-cross-origin"
                ></iframe>
            </div>
        @else
            <div class="flex min-h-56 items-center justify-center px-6 py-8 text-center text-sm text-white/80">
                {{ arabic_text('تعذر تجهيز رابط الفيديو الآن.') }}
            </div>
        @endif
    </div>

    @if ($resolvedVideoUrl !== '')
        <div class="flex flex-wrap items-center justify-between gap-3 text-xs sm:text-sm">
            <p class="text-gray-600 dark:text-gray-300">
                {{ arabic_text('يمكنك تكبير العرض وملء الشاشة من داخل المشغّل باستخدام الزرّ المخصص لذلك.') }}
            </p>

            <a
                class="text-primary-700 dark:text-primary-300 font-semibold transition hover:underline"
                href="{{ $resolvedVideoUrl }}"
                rel="noopener noreferrer"
                target="_blank"
            >
                {{ arabic_text('استعراض في يوتيوب') }}
            </a>
        </div>
    @endif
</div>
