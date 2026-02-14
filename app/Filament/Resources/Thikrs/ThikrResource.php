<?php

declare(strict_types=1);

namespace App\Filament\Resources\Thikrs;

use App\Filament\Resources\Thikrs\Pages\CreateThikr;
use App\Filament\Resources\Thikrs\Pages\EditThikr;
use App\Filament\Resources\Thikrs\Pages\ListThikrs;
use App\Filament\Resources\Thikrs\Schemas\ThikrForm;
use App\Filament\Resources\Thikrs\Tables\ThikrsTable;
use App\Models\Thikr;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ThikrResource extends Resource
{
    protected static ?string $model = Thikr::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = 'الأذكار';

    protected static ?string $pluralModelLabel = 'أذكار';

    protected static ?string $modelLabel = 'ذكر';

    protected static ?string $slug = 'athkar';

    public static function form(Schema $schema): Schema
    {
        return ThikrForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ThikrsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<string, \Filament\Resources\Pages\PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListThikrs::route('/'),
            'create' => CreateThikr::route('/create'),
            'edit' => EditThikr::route('/{record}/edit'),
        ];
    }
}
