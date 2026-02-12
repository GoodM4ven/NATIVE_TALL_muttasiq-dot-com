<?php

declare(strict_types=1);

namespace App\Filament\Resources\Thikrs\Pages;

use App\Filament\Resources\Thikrs\ThikrResource;
use Filament\Resources\Pages\CreateRecord;

class CreateThikr extends CreateRecord
{
    protected static string $resource = ThikrResource::class;
}
