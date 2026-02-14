<?php

declare(strict_types=1);

namespace App\Services\Enums;

use App\Services\Support\Traits\HasTranslatedEnumLabels;
use Filament\Support\Contracts\HasLabel;

enum ThikrTime: string implements HasLabel
{
    use HasTranslatedEnumLabels;

    case Shared = 'shared';
    case Sabah = 'sabah';
    case Masaa = 'masaa';

    protected static function translationNamespace(): string
    {
        return 'custom.thikr.time';
    }

    /**
     * @return array<string, string>
     */
    protected static function fallbackLabels(): array
    {
        return [
            self::Shared->value => 'مشترك',
            self::Sabah->value => 'الصباح',
            self::Masaa->value => 'المساء',
        ];
    }
}
