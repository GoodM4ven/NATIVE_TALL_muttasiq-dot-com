<?php

declare(strict_types=1);

namespace App\Filament\Resources\Thikrs\Pages;

use App\Filament\Resources\Thikrs\ThikrResource;
use App\Models\Thikr;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateThikr extends CreateRecord
{
    protected static string $resource = ThikrResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $targetOrder = max(1, (int) ($data['order'] ?? 1));
        unset($data['order']);

        $record = parent::handleRecordCreation($data);

        if ($record instanceof Thikr) {
            $record->moveToOrder($targetOrder);
        }

        return $record;
    }
}
