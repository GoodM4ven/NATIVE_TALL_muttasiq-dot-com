<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\WebAthkarGateActivityChart;
use App\Filament\Widgets\WebHomeActivityChart;
use App\Filament\Widgets\WebQuranGateActivityChart;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\Widget;

class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'إحصائيات';

    protected static ?string $title = 'إحصائيات';

    /**
     * @return array<class-string<Widget>>
     */
    public function getWidgets(): array
    {
        return [
            WebHomeActivityChart::class,
            WebAthkarGateActivityChart::class,
            WebQuranGateActivityChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
