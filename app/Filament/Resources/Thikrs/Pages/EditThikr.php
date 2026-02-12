<?php

declare(strict_types=1);

namespace App\Filament\Resources\Thikrs\Pages;

use App\Filament\Resources\Thikrs\ThikrResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditThikr extends EditRecord
{
    protected static string $resource = ThikrResource::class;

    /**
     * @return array<int, \Filament\Actions\DeleteAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
