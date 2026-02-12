<?php

declare(strict_types=1);

namespace App\Filament\Resources\Thikrs\Schemas;

use App\Filament\Resources\Thikrs\ThikrResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ThikrForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Grid::make()
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('time')
                            ->label('الوقت')
                            ->options(ThikrResource::timeOptions())
                            ->required()
                            ->native(false)
                            ->columnSpan(1),

                        TextInput::make('count')
                            ->label('العدد')
                            ->required()
                            ->integer()
                            ->minValue(1)
                            ->columnSpan(1),
                    ]),

                Toggle::make('is_aayah')
                    ->label('آيات')
                    ->default(false),

                Textarea::make('text')
                    ->label('النص')
                    ->required()
                    ->rows(8)
                    ->columnSpanFull(),
            ]);
    }
}
