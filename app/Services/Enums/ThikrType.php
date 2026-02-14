<?php

declare(strict_types=1);

namespace App\Services\Enums;

use App\Services\Support\Traits\HasTranslatedEnumLabels;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ThikrType: string implements HasColor, HasLabel
{
    use HasTranslatedEnumLabels;

    case Glorification = 'glorification';
    case Gratitude = 'gratitude';
    case Repentance = 'repentance';
    case Supplication = 'supplication';
    case Protection = 'protection';

    public function getColor(): string|array|null
    {
        return 'info';
    }

    protected static function translationNamespace(): string
    {
        return 'custom.thikr.type';
    }

    /**
     * @return array<string, string>
     */
    protected static function fallbackLabels(): array
    {
        return [
            self::Glorification->value => 'ثناء',
            self::Gratitude->value => 'حمد',
            self::Repentance->value => 'استغفار',
            self::Supplication->value => 'دعاء',
            self::Protection->value => 'تحصين',
        ];
    }
}
