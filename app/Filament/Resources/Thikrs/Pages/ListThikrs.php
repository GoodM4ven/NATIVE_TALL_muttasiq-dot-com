<?php

declare(strict_types=1);

namespace App\Filament\Resources\Thikrs\Pages;

use App\Filament\Resources\Thikrs\ThikrResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListThikrs extends ListRecords
{
    protected static string $resource = ThikrResource::class;

    /**
     * @return array<int, \Filament\Actions\CreateAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
