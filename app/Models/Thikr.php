<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Enums\ThikrTime;
use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

/**
 * @property int $id
 * @property ThikrTime $time
 * @property string $text
 * @property int $count
 */
class Thikr extends Model implements Sortable
{
    use SortableTrait;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'time' => ThikrTime::class,
            'count' => 'integer',
        ];
    }
}
