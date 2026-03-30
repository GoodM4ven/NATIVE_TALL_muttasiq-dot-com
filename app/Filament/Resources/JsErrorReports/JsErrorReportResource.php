<?php

declare(strict_types=1);

namespace App\Filament\Resources\JsErrorReports;

use App\Filament\Resources\JsErrorReports\Pages\ListJsErrorReports;
use App\Filament\Resources\JsErrorReports\Tables\JsErrorReportsTable;
use App\Models\JsErrorReport;
use BackedEnum;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class JsErrorReportResource extends Resource
{
    protected static ?string $model = JsErrorReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'balaghat-akhtaa';

    protected static ?string $navigationLabel = 'بلاغات الأخطاء';

    protected static ?string $pluralModelLabel = 'بلاغات أخطاء الواجهة';

    protected static ?string $modelLabel = 'بلاغ خطأ';

    public static function table(Table $table): Table
    {
        return JsErrorReportsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListJsErrorReports::route('/'),
        ];
    }
}
