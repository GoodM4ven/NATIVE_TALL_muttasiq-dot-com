<?php

declare(strict_types=1);

namespace App\Filament\Resources\Thikrs\Tables;

use App\Filament\Resources\Thikrs\ThikrResource;
use App\Models\Thikr;
use App\Services\Enums\ThikrTime;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ThikrsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('order')
            ->reorderable('order')
            ->afterReordering(function (array $order): void {
                Thikr::clearDefaultCache();
            })
            ->columns([
                TextInputColumn::make('order')
                    ->width(50)
                    ->label('الترتيب')
                    ->type('number')
                    ->inputMode('numeric')
                    ->step(1)
                    ->rules(['required', 'integer', 'min:1'])
                    ->updateStateUsing(function (Thikr $record, mixed $state): int {
                        $record->moveToOrder((int) $state);

                        return $record->order;
                    })
                    ->sortable(),
                TextColumn::make('time')
                    ->label('الوقت')
                    ->badge()
                    ->formatStateUsing(fn (ThikrTime|string $state): string => ThikrResource::timeLabel($state))
                    ->sortable(),
                IconColumn::make('is_aayah')
                    ->label('آيات')
                    ->boolean(),
                TextColumn::make('text')
                    ->label('النص')
                    ->limit(120)
                    ->searchable(),
                TextColumn::make('count')
                    ->label('العدد')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('تاريخ التعديل')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('time')
                    ->label('الوقت')
                    ->options(ThikrResource::timeOptions()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
