<div class="space-y-4">
    <div class="space-y-1">
        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ app_arabic_text('وصف المستخدم') }}</p>
        <p class="rounded-lg bg-gray-50 p-3 text-sm leading-7 text-gray-800 dark:bg-white/5 dark:text-gray-200">
            {{ $record->user_note }}
        </p>
    </div>

    <div class="grid gap-3 text-sm sm:grid-cols-2">
        <div>
            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ app_arabic_text('المنصة') }}</p>
            <p class="text-gray-700 dark:text-gray-300">{{ $record->runtime_platform ?: app_arabic_text('غير محدد') }}
            </p>
        </div>
        <div>
            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ app_arabic_text('نقطة التوقف') }}</p>
            <p class="text-gray-700 dark:text-gray-300">{{ $record->screen_breakpoint ?: app_arabic_text('غير محدد') }}
            </p>
        </div>
        <div>
            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ app_arabic_text('وقت البلاغ') }}</p>
            <p class="text-gray-700 dark:text-gray-300">{{ $record->created_at?->toDateTimeString() }}</p>
        </div>
        <div class="sm:col-span-2">
            <p class="font-semibold text-gray-900 dark:text-gray-100">{{ app_arabic_text('الرابط') }}</p>
            <p class="break-all text-gray-700 dark:text-gray-300">{{ $record->page_url ?: app_arabic_text('غير محدد') }}
            </p>
        </div>
    </div>

    <div class="space-y-1">
        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ app_arabic_text('البيانات التقنية') }}</p>
        <pre
            class="max-h-[22rem] overflow-auto rounded-lg bg-gray-950/95 p-3 text-xs leading-6 text-gray-100"
            dir="ltr"
        >{{ json_encode($record->errors, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
    </div>
</div>
