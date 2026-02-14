<?php

declare(strict_types=1);

namespace App\Services\Support\Traits;

trait HasTranslatedEnumLabels
{
    public function getLabel(): ?string
    {
        return static::labelFor($this);
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];

        foreach (static::cases() as $case) {
            $labels[$case->value] = static::translateLabel($case);
        }

        return $labels;
    }

    public static function labelFor(self|string|null $value): string
    {
        if ($value instanceof self) {
            return static::translateLabel($value);
        }

        if (is_string($value) && $value !== '') {
            foreach (static::cases() as $case) {
                if ($case->value === $value) {
                    return static::translateLabel($case);
                }
            }
        }

        return static::translateLabel(static::cases()[0]);
    }

    protected static function translateLabel(self $case): string
    {
        $translationKey = static::translationNamespace().'.'.$case->value;
        $translatedLabel = __($translationKey);

        if ($translatedLabel !== $translationKey) {
            return $translatedLabel;
        }

        return static::fallbackLabels()[$case->value] ?? $case->value;
    }

    abstract protected static function translationNamespace(): string;

    /**
     * @return array<string, string>
     */
    abstract protected static function fallbackLabels(): array;
}
