<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class Setting extends Model
{
    public const APP_VERSION = 'app_version';

    public const YOUTUBE_VIDEO_URL = 'youtube_video_url';

    public const DEFAULT_YOUTUBE_VIDEO_URL = 'https://www.youtube.com/watch?v=3gVbuDSm-uk';

    public const GROUP_GENERAL = 'general';

    public const GROUP_ATHKAR = 'athkar';

    public const GROUP_QURAN = 'quran';

    public const DOES_AUTOMATICALLY_SWITCH_COMPLETED_ATHKAR = 'does_automatically_switch_completed_athkar';

    public const DOES_CLICKING_SWITCH_ATHKAR_TOO = 'does_clicking_switch_athkar_too';

    public const DOES_PREVENT_SWITCHING_ATHKAR_UNTIL_COMPLETION = 'does_prevent_switching_athkar_until_completion';

    public const DOES_ENABLE_VISUAL_ENHANCEMENTS = 'enable_visual_enhancements';

    public const DOES_SKIP_GUIDANCE_PANELS = 'does_skip_notice_panels';

    public const DOES_USE_WESTERN_NUMERALS = 'does_use_western_numerals';

    public const DOES_PRESERVE_HARAKAT_IN_DISPLAY = 'does_preserve_harakat_in_display';

    public const DOES_QURAN_TARGET_WORDS_BY_DEFAULT = 'does_quran_target_words_by_default';

    public const DOES_QURAN_PRESERVE_HARAKAT_ON_COPY = 'does_quran_preserve_harakat_on_copy';

    public const DOES_QURAN_APPEND_SURAH_AFFIX_ON_MULTI_COPY = 'does_quran_append_surah_affix_on_multi_copy';

    public const DOES_QURAN_APPEND_SURAH_AFFIX_ALWAYS_ON_COPY = 'does_quran_append_surah_affix_always_on_copy';

    public const DOES_QURAN_SHOW_IMMERSIVE_MOBILE_EDGE_CAPTIONS = 'does_quran_show_immersive_mobile_edge_captions';

    public const DOES_QURAN_USE_VOLUME_BUTTONS_NAVIGATION = 'does_quran_use_volume_buttons_navigation';

    public const QURAN_WIRD_FREQUENCY_MODE = 'quran_wird_frequency_mode';

    public const QURAN_WIRD_KHATMAT_TARGET = 'quran_wird_khatmat_target';

    public const QURAN_WIRD_FREQUENCY_MONTHLY = 0;

    public const QURAN_WIRD_FREQUENCY_DAILY = 1;

    public const QURAN_WIRD_KHATMAT_MIN = 1;

    public const QURAN_WIRD_KHATMAT_MAX = 4;

    public const QURAN_WIRD_KHATMAT_MONTHLY_MAX = 20;

    public const MINIMUM_MAIN_TEXT_SIZE = 'minimum_main_text_size';

    public const MAXIMUM_MAIN_TEXT_SIZE = 'maximum_main_text_size';

    public const MIN_MAIN_TEXT_SIZE_MIN = 14;

    public const MIN_MAIN_TEXT_SIZE_MAX = 28;

    public const MIN_MAIN_TEXT_SIZE_DEFAULT = 24;

    public const MAX_MAIN_TEXT_SIZE_MIN = 14;

    public const MAX_MAIN_TEXT_SIZE_MAX = 28;

    public const MAX_MAIN_TEXT_SIZE_DEFAULT = 25;

    /**
     * @return array<string, array{default: bool|int|string, label: string, group: string, type: 'boolean'|'integer'|'string', help?: string, min?: int, max?: int, maxLength?: int, format?: 'url'}>
     */
    public static function definitions(): array
    {
        $definitions = [
            self::DOES_AUTOMATICALLY_SWITCH_COMPLETED_ATHKAR => [
                'default' => true,
                'label' => '1. الانتقال التلقائي عند اكتمال عدد الذكر.',
                'group' => self::GROUP_ATHKAR,
                'type' => 'boolean',
            ],
            self::DOES_CLICKING_SWITCH_ATHKAR_TOO => [
                'default' => true,
                'label' => '2. الضغط والنقر يقوم بالانتقال للذكر التالي، وليس مجرد السحب.',
                'help' => 'ولكن إن قمت بالعودة للأذكار التامة، أو كان خيار الأذكار (1) معطلًا، فالضغط يقوم بزيادة العدّ.',
                'group' => self::GROUP_ATHKAR,
                'type' => 'boolean',
            ],
            self::DOES_PREVENT_SWITCHING_ATHKAR_UNTIL_COMPLETION => [
                'default' => true,
                'label' => '3. المنع من الانتقال بين الأذكار حتى إنهائها أولًا.',
                'help' => 'عند التعطيل: يمكن إعادة استعراض أذكار الصباح والمساء حتى بعد إتمامها.',
                'group' => self::GROUP_ATHKAR,
                'type' => 'boolean',
            ],
            self::MINIMUM_MAIN_TEXT_SIZE => [
                'default' => self::MIN_MAIN_TEXT_SIZE_DEFAULT,
                'label' => '1. الحد الأدنى لحجم النصوص المحورية.',
                'group' => self::GROUP_GENERAL,
                'type' => 'integer',
                'min' => self::MIN_MAIN_TEXT_SIZE_MIN,
                'max' => self::MIN_MAIN_TEXT_SIZE_MAX,
            ],
            self::MAXIMUM_MAIN_TEXT_SIZE => [
                'default' => self::MAX_MAIN_TEXT_SIZE_DEFAULT,
                'label' => '2. الحد الأقصى لحجم النصوص المحورية.',
                'group' => self::GROUP_GENERAL,
                'type' => 'integer',
                'min' => self::MAX_MAIN_TEXT_SIZE_MIN,
                'max' => self::MAX_MAIN_TEXT_SIZE_MAX,
            ],
            self::DOES_ENABLE_VISUAL_ENHANCEMENTS => [
                'default' => false,
                'label' => '1. تحسين التأثيرات البصرية وتجميل النصوص المحورية.',
                'group' => self::GROUP_GENERAL,
                'type' => 'boolean',
            ],
            self::DOES_SKIP_GUIDANCE_PANELS => [
                'default' => false,
                'label' => '2. تجاوز رسائل التعريف والتهنئة والتلميحات المساعدة.',
                'group' => self::GROUP_GENERAL,
                'type' => 'boolean',
            ],
            self::DOES_USE_WESTERN_NUMERALS => [
                'default' => true,
                'label' => '3. استخدام الأرقام العربية الغربية (__WESTERN_NUMERALS_SAMPLE__) بدل العربية الشرقية (__ARABIC_NUMERALS_SAMPLE__) في العرض.',
                'group' => self::GROUP_GENERAL,
                'type' => 'boolean',
            ],
            self::DOES_PRESERVE_HARAKAT_IN_DISPLAY => [
                'default' => true,
                'label' => '4. إظهار الحركات في النصوص العربية المعروضة.',
                'group' => self::GROUP_GENERAL,
                'type' => 'boolean',
            ],
            self::DOES_QURAN_TARGET_WORDS_BY_DEFAULT => [
                'default' => false,
                'label' => '1. توجيه التحديد والضغط المباشر في قارئ القرآن إلى الكلمات بدل الآيات.',
                'help' => 'عند التعطيل: في الشاشات الواسعة الضغط المطوّل لمدة 0.75 ثانية يستهدف الكلمة بدل الآية، وفي الشاشات الصغيرة النقر المزدوج ينسخ الهدف الافتراضي والنقر المزدوج مع التثبيت في النقرة الثانية يستهدف الهدف العكسي.',
                'group' => self::GROUP_QURAN,
                'type' => 'boolean',
            ],
            self::DOES_QURAN_PRESERVE_HARAKAT_ON_COPY => [
                'default' => true,
                'label' => '2. الحفاظ على الحركات والزخارف عند نسخ نص الآيات.',
                'group' => self::GROUP_QURAN,
                'type' => 'boolean',
            ],
            self::DOES_QURAN_APPEND_SURAH_AFFIX_ON_MULTI_COPY => [
                'default' => true,
                'label' => '3. إضافة اسم السورة عند النسخ المتعدد بين الآيات.',
                'help' => 'عند التفعيل: يُضاف اسم السورة لكل سورة مشاركة إذا امتد النسخ بالسحب عبر أكثر من آية.',
                'group' => self::GROUP_QURAN,
                'type' => 'boolean',
            ],
            self::DOES_QURAN_APPEND_SURAH_AFFIX_ALWAYS_ON_COPY => [
                'default' => false,
                'label' => '4. إضافة اسم السورة دائمًا عند النسخ.',
                'help' => 'عند التفعيل: يُضاف اسم السورة حتى عند نسخ كلمات من آية واحدة فقط.',
                'group' => self::GROUP_QURAN,
                'type' => 'boolean',
            ],
            self::DOES_QURAN_SHOW_IMMERSIVE_MOBILE_EDGE_CAPTIONS => [
                'default' => true,
                'label' => '6. إظهار اسم السورة ورقم الصفحة عند أطراف القارئ.',
                'group' => self::GROUP_QURAN,
                'type' => 'boolean',
            ],
            self::QURAN_WIRD_FREQUENCY_MODE => [
                'default' => self::QURAN_WIRD_FREQUENCY_MONTHLY,
                'label' => '5. إعداد الوِرد اليومي.',
                'group' => self::GROUP_QURAN,
                'type' => 'integer',
                'min' => self::QURAN_WIRD_FREQUENCY_MONTHLY,
                'max' => self::QURAN_WIRD_FREQUENCY_DAILY,
            ],
            self::QURAN_WIRD_KHATMAT_TARGET => [
                'default' => 1,
                'label' => 'هدف عدد الختمات المستهدفة للوِرد.',
                'help' => 'في الوضع اليومي: الحد الأقصى 4 ختمات/يوم. في الوضع الشهري: الحد الأقصى 20 ختمة.',
                'group' => self::GROUP_QURAN,
                'type' => 'integer',
                'min' => self::QURAN_WIRD_KHATMAT_MIN,
                'max' => self::QURAN_WIRD_KHATMAT_MONTHLY_MAX,
            ],
            self::DOES_QURAN_USE_VOLUME_BUTTONS_NAVIGATION => [
                'default' => false,
                'label' => '5. استخدام أزرار رفع وخفض الصوت للتنقل بين الأذكار وصفحات القرآن.',
                'help' => 'عند التفعيل: زر رفع الصوت يعود للذكر/الصفحة السابقة، وزر خفض الصوت ينتقل للذكر/الصفحة التالية.',
                'group' => self::GROUP_GENERAL,
                'type' => 'boolean',
            ],
        ];

        foreach ($definitions as $name => $definition) {
            $definitions[$name]['label'] = arabic_text($definition['label']);

            if (array_key_exists('help', $definition)) {
                $definitions[$name]['help'] = arabic_text((string) $definition['help']);
            }
        }

        $definitions[self::DOES_USE_WESTERN_NUMERALS]['label'] = str_replace(
            ['__WESTERN_NUMERALS_SAMPLE__', '__ARABIC_NUMERALS_SAMPLE__'],
            ['123', '١٢٣'],
            $definitions[self::DOES_USE_WESTERN_NUMERALS]['label'],
        );

        return $definitions;
    }

    /**
     * @return array<int, string>
     */
    public static function quranWirdFrequencyModeOptions(): array
    {
        return [
            self::QURAN_WIRD_FREQUENCY_MONTHLY => arabic_text('شهري (توزيع الختمات على أيام الشهر)'),
            self::QURAN_WIRD_FREQUENCY_DAILY => arabic_text('يومي (ختمات كاملة يوميًا)'),
        ];
    }

    public static function quranWirdKhatmatMaxForFrequency(int $frequencyMode): int
    {
        $normalizedFrequencyMode = max(
            self::QURAN_WIRD_FREQUENCY_MONTHLY,
            min(self::QURAN_WIRD_FREQUENCY_DAILY, $frequencyMode),
        );

        if ($normalizedFrequencyMode === self::QURAN_WIRD_FREQUENCY_DAILY) {
            return self::QURAN_WIRD_KHATMAT_MAX;
        }

        return self::QURAN_WIRD_KHATMAT_MONTHLY_MAX;
    }

    /**
     * @return array<int, string>
     */
    public static function quranWirdKhatmatOptionsForFrequency(int $frequencyMode): array
    {
        $maximum = self::quranWirdKhatmatMaxForFrequency($frequencyMode);
        $options = [];

        for ($count = self::QURAN_WIRD_KHATMAT_MIN; $count <= $maximum; $count++) {
            $options[$count] = self::quranWirdKhatmaLabel($count);
        }

        return $options;
    }

    /**
     * @return array<string, array{default: bool|int|string, label: string, group: string, type: 'boolean'|'integer'|'string', help?: string, min?: int, max?: int, maxLength?: int, format?: 'url'}>
     */
    public static function definitionsForGroup(string $group): array
    {
        return array_filter(
            self::definitions(),
            static fn (array $definition): bool => $definition['group'] === $group,
        );
    }

    /**
     * @param  array<int, string>  $names
     * @return array<string, bool|int|string>
     */
    public static function storedValues(array $names): array
    {
        if ($names === []) {
            return [];
        }

        try {
            $definitions = self::definitions();

            /** @var array<string, bool|int|string> $storedValues */
            $storedValues = self::query()
                ->whereIn('name', $names)
                ->get(['name', 'value', 'value_text'])
                ->mapWithKeys(function (self $setting) use ($definitions): array {
                    $definition = $definitions[$setting->name] ?? null;

                    if (is_array($definition) && $definition['type'] === 'string') {
                        return [
                            $setting->name => is_string($setting->value_text) && trim($setting->value_text) !== ''
                                ? trim($setting->value_text)
                                : '',
                        ];
                    }

                    return [
                        $setting->name => (int) $setting->value,
                    ];
                })
                ->all();

            return $storedValues;
        } catch (Throwable $exception) {
            Log::warning('Failed to resolve stored settings from the database.', [
                'message' => $exception->getMessage(),
                'names' => $names,
            ]);

            return [];
        }
    }

    /**
     * @return array<string, bool|int|string>
     */
    public static function defaults(): array
    {
        $defaults = [];

        foreach (self::definitions() as $key => $definition) {
            $defaults[$key] = $definition['default'];
        }

        return $defaults;
    }

    /**
     * @return array{
     *     minimum_main_text_size: array{min: int, max: int, default: int},
     *     maximum_main_text_size: array{min: int, max: int, default: int}
     * }
     */
    public static function mainTextSizeLimits(): array
    {
        $definitions = self::definitions();
        $minimumDefinition = $definitions[self::MINIMUM_MAIN_TEXT_SIZE] ?? [];
        $maximumDefinition = $definitions[self::MAXIMUM_MAIN_TEXT_SIZE] ?? [];

        return [
            self::MINIMUM_MAIN_TEXT_SIZE => [
                'min' => (int) ($minimumDefinition['min'] ?? self::MIN_MAIN_TEXT_SIZE_MIN),
                'max' => (int) ($minimumDefinition['max'] ?? self::MIN_MAIN_TEXT_SIZE_MAX),
                'default' => (int) ($minimumDefinition['default'] ?? self::MIN_MAIN_TEXT_SIZE_DEFAULT),
            ],
            self::MAXIMUM_MAIN_TEXT_SIZE => [
                'min' => (int) ($maximumDefinition['min'] ?? self::MAX_MAIN_TEXT_SIZE_MIN),
                'max' => (int) ($maximumDefinition['max'] ?? self::MAX_MAIN_TEXT_SIZE_MAX),
                'default' => (int) ($maximumDefinition['default'] ?? self::MAX_MAIN_TEXT_SIZE_DEFAULT),
            ],
        ];
    }

    public static function normalizeValue(string $name, mixed $value): bool|int|string
    {
        $definition = self::definitions()[$name] ?? null;

        if (! is_array($definition)) {
            if (is_bool($value)) {
                return $value;
            }

            if (is_numeric($value)) {
                return (int) $value;
            }

            return false;
        }

        if ($definition['type'] === 'integer') {
            $numericValue = is_numeric($value) ? (int) $value : (int) $definition['default'];
            $minimum = (int) ($definition['min'] ?? $definition['default']);
            $maximum = (int) ($definition['max'] ?? $definition['default']);

            return max($minimum, min($maximum, $numericValue));
        }

        if ($definition['type'] === 'string') {
            $normalizedValue = trim((string) $value);

            if ($normalizedValue === '') {
                return (string) $definition['default'];
            }

            $maxLength = (int) ($definition['maxLength'] ?? 255);
            $normalizedValue = Str::of($normalizedValue)
                ->limit($maxLength, '')
                ->toString();

            if (($definition['format'] ?? null) === 'url' && ! filter_var($normalizedValue, FILTER_VALIDATE_URL)) {
                return (string) $definition['default'];
            }

            return $normalizedValue;
        }

        if (is_bool($value)) {
            return $value;
        }

        if ($value === 1 || $value === '1') {
            return true;
        }

        if ($value === 0 || $value === '0') {
            return false;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($normalized !== null) {
            return $normalized;
        }

        return (bool) $definition['default'];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, bool|int|string>
     */
    public static function normalizeSettings(array $settings): array
    {
        $normalized = [];

        foreach (self::definitions() as $name => $definition) {
            $value = array_key_exists($name, $settings) ? $settings[$name] : $definition['default'];
            $normalized[$name] = self::normalizeValue($name, $value);
        }

        $minimumMainTextSize = (int) ($normalized[self::MINIMUM_MAIN_TEXT_SIZE] ?? self::MIN_MAIN_TEXT_SIZE_DEFAULT);
        $maximumMainTextSize = (int) ($normalized[self::MAXIMUM_MAIN_TEXT_SIZE] ?? self::MAX_MAIN_TEXT_SIZE_DEFAULT);

        if ($minimumMainTextSize > $maximumMainTextSize) {
            $minimumMainTextSize = $maximumMainTextSize;
        }

        $normalized[self::MINIMUM_MAIN_TEXT_SIZE] = $minimumMainTextSize;
        $normalized[self::MAXIMUM_MAIN_TEXT_SIZE] = max($maximumMainTextSize, $minimumMainTextSize);

        $wirdFrequencyMode = (int) ($normalized[self::QURAN_WIRD_FREQUENCY_MODE] ?? self::QURAN_WIRD_FREQUENCY_MONTHLY);
        $wirdKhatmatTarget = (int) ($normalized[self::QURAN_WIRD_KHATMAT_TARGET] ?? self::QURAN_WIRD_KHATMAT_MIN);
        $wirdKhatmatMax = self::quranWirdKhatmatMaxForFrequency($wirdFrequencyMode);
        $normalized[self::QURAN_WIRD_KHATMAT_TARGET] = max(
            self::QURAN_WIRD_KHATMAT_MIN,
            min($wirdKhatmatMax, $wirdKhatmatTarget),
        );

        return $normalized;
    }

    private static function quranWirdKhatmaLabel(int $count): string
    {
        $normalizedCount = max(self::QURAN_WIRD_KHATMAT_MIN, $count);

        if ($normalizedCount === 1) {
            return arabic_text('1 ختمة');
        }

        if ($normalizedCount === 2) {
            return arabic_text('2 ختمتان');
        }

        if ($normalizedCount <= 10) {
            return arabic_text(sprintf('%d ختمات', $normalizedCount));
        }

        return arabic_text(sprintf('%d ختمة', $normalizedCount));
    }

    public static function appVersion(): string
    {
        try {
            $stored = self::resolveTextValue(self::APP_VERSION);

            if (is_string($stored) && trim($stored) !== '') {
                return trim($stored);
            }
        } catch (Throwable $exception) {
            Log::warning('Failed to resolve the stored app version from the database.', [
                'message' => $exception->getMessage(),
            ]);
        }

        return self::configuredAppVersion();
    }

    public static function configuredAppVersion(): string
    {
        $fallback = (string) config('app.custom.app_version', '');

        if (trim($fallback) !== '') {
            return trim($fallback);
        }

        return trim((string) config('nativephp.version', ''));
    }

    public static function setAppVersion(?string $version): void
    {
        try {
            self::storeTextValue(self::APP_VERSION, $version, 32);
        } catch (Throwable $exception) {
            Log::warning('Failed to store the app version in the database.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public static function youtubeVideoUrl(): string
    {
        try {
            $stored = self::resolveTextValue(self::YOUTUBE_VIDEO_URL);

            if (is_string($stored) && trim($stored) !== '') {
                if (self::parseYoutubeVideoId($stored) !== null) {
                    return $stored;
                }
            }
        } catch (Throwable $exception) {
            Log::warning('Failed to resolve the stored YouTube video URL from the database.', [
                'message' => $exception->getMessage(),
            ]);
        }

        return self::DEFAULT_YOUTUBE_VIDEO_URL;
    }

    public static function youtubeVideoEmbedUrl(): string
    {
        $url = self::youtubeVideoUrl();
        $parsed = self::parseYoutubeVideoId($url);

        if ($parsed === null) {
            return $url;
        }

        return sprintf(
            'https://www.youtube.com/embed/%s?rel=0&modestbranding=1&playsinline=1',
            $parsed,
        );
    }

    public static function setYoutubeVideoUrl(?string $url): void
    {
        $normalized = is_string($url) ? trim($url) : '';

        if ($normalized === '') {
            $normalized = self::DEFAULT_YOUTUBE_VIDEO_URL;
        }

        if (self::parseYoutubeVideoId($normalized) === null) {
            $normalized = self::DEFAULT_YOUTUBE_VIDEO_URL;
        }

        self::storeTextValue(self::YOUTUBE_VIDEO_URL, $normalized, 255);
    }

    private static function resolveTextValue(string $name): ?string
    {
        $stored = self::query()
            ->where('name', $name)
            ->value('value_text');

        if (! is_string($stored)) {
            return null;
        }

        $normalized = trim($stored);

        return $normalized === '' ? null : $normalized;
    }

    private static function storeTextValue(string $name, ?string $value, int $maxLength): void
    {
        $normalized = is_string($value) ? trim($value) : '';
        $normalized = $normalized !== '' ? Str::of($normalized)->limit($maxLength, '')->toString() : '';
        $valueText = $normalized === '' ? null : $normalized;

        self::query()->updateOrCreate(
            ['name' => $name],
            [
                'value' => 0,
                'value_text' => $valueText,
            ],
        );
    }

    private static function parseYoutubeVideoId(string $url): ?string
    {
        $trimmed = trim($url);

        if ($trimmed === '') {
            return null;
        }

        $parsedUrl = parse_url($trimmed);

        if (! is_array($parsedUrl)) {
            return null;
        }

        $host = strtolower((string) ($parsedUrl['host'] ?? ''));
        $path = trim((string) ($parsedUrl['path'] ?? ''), '/');

        if ($host === 'youtu.be') {
            $videoId = trim((string) explode('/', $path)[0]);

            return $videoId !== '' ? $videoId : null;
        }

        if (str_contains($host, 'youtube.com')) {
            if (str_starts_with($path, 'embed/')) {
                $videoId = trim((string) explode('/', substr($path, 6))[0]);

                return $videoId !== '' ? $videoId : null;
            }

            if (str_starts_with($path, 'shorts/')) {
                $videoId = trim((string) explode('/', substr($path, 7))[0]);

                return $videoId !== '' ? $videoId : null;
            }

            if (str_starts_with($path, 'live/')) {
                $videoId = trim((string) explode('/', substr($path, 5))[0]);

                return $videoId !== '' ? $videoId : null;
            }

            if ($path === 'watch') {
                $query = [];
                parse_str((string) ($parsedUrl['query'] ?? ''), $query);

                $videoId = trim((string) ($query['v'] ?? ''));

                return $videoId !== '' ? $videoId : null;
            }
        }

        return null;
    }
}
