<?php

declare(strict_types=1);

namespace App\Filament\Resources\Thikrs\Pages;

use App\Filament\Resources\Thikrs\ThikrResource;
use App\Models\Thikr;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

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

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $targetOrder = max(1, (int) ($data['order'] ?? ($record->order ?? 1)));
        unset($data['order']);

        $updatedRecord = parent::handleRecordUpdate($record, $data);

        if ($updatedRecord instanceof Thikr) {
            $updatedRecord->moveToOrder($targetOrder);
        }

        return $updatedRecord;
    }
}
