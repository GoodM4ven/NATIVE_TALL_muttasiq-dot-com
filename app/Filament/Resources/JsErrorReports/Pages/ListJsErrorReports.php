<?php

declare(strict_types=1);

namespace App\Filament\Resources\JsErrorReports\Pages;

use App\Filament\Resources\JsErrorReports\JsErrorReportResource;
use App\Models\JsErrorReport;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListJsErrorReports extends ListRecords
{
    protected static string $resource = JsErrorReportResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'unresolved' => Tab::make('غير المعالجة')
                ->badge(fn (): int => JsErrorReport::query()->whereNull('resolved_at')->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNull('resolved_at'))
                ->excludeQueryWhenResolvingRecord(),

            'resolved' => Tab::make('المعالجة')
                ->badge(fn (): int => JsErrorReport::query()->whereNotNull('resolved_at')->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotNull('resolved_at'))
                ->excludeQueryWhenResolvingRecord(),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'unresolved';
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
