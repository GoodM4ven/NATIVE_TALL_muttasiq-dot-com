<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Enums\ThikrTime;
use App\Services\Enums\ThikrType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;

/**
 * @property int $id
 * @property ThikrTime $time
 * @property ThikrType $type
 * @property string $text
 * @property string|null $origin
 * @property bool $is_aayah
 * @property int $count
 * @property int $order
 * @property-read bool $is_original
 */
class Thikr extends Model implements Sortable
{
    use HasFactory;
    use SortableTrait {
        setNewOrder as private setSortableNewOrder;
    }

    public const DEFAULT_CACHE_KEY = 'athkar.defaults.v2';

    public const DEFAULT_CACHE_TTL_SECONDS = 604800; // ? 1 week

    public const AAYAH_OPENING_MARK = '﴿';

    public const AAYAH_CLOSING_MARK = '﴾';

    protected static function booted(): void
    {
        static::saving(function (self $thikr): void {
            $thikr->text = self::normalizeAayahText($thikr->text, (bool) $thikr->is_aayah);
        });

        static::saved(function (self $thikr): void {
            self::clearDefaultCache();
        });

        static::deleted(function (self $thikr): void {
            self::clearDefaultCache();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'time' => ThikrTime::class,
            'type' => ThikrType::class,
            'is_aayah' => 'boolean',
            'count' => 'integer',
            'order' => 'integer',
        ];
    }

    /**
     * @return array<int, array{id: int, time: string, type: string, text: string, origin: string|null, is_aayah: bool, is_original: bool, count: int, order: int}>
     */
    public static function cachedDefaults(): array
    {
        return Cache::remember(
            self::DEFAULT_CACHE_KEY,
            now()->addSeconds(self::DEFAULT_CACHE_TTL_SECONDS),
            fn (): array => self::defaultsPayload(),
        );
    }

    public static function clearDefaultCache(): void
    {
        Cache::forget(self::DEFAULT_CACHE_KEY);
    }

    public static function normalizeAayahText(string $text, bool $isAayah): string
    {
        $trimmedText = self::stripAayahWrapper($text);

        if (! $isAayah) {
            return $trimmedText;
        }

        return self::AAYAH_OPENING_MARK.$trimmedText.self::AAYAH_CLOSING_MARK;
    }

    public static function setNewOrder(
        $ids,
        int $startOrder = 1,
        ?string $primaryKeyColumn = null,
        ?callable $modifyQuery = null
    ): void {
        self::setSortableNewOrder($ids, $startOrder, $primaryKeyColumn, $modifyQuery);

        self::clearDefaultCache();
    }

    public function moveToOrder(int $targetOrder): void
    {
        $orderedIds = self::query()
            ->ordered()
            ->pluck($this->getKeyName())
            ->map(static fn (int|string $id): int => (int) $id)
            ->all();

        $currentRecordId = (int) $this->getKey();
        $currentIndex = array_search($currentRecordId, $orderedIds, true);

        if ($currentIndex === false) {
            return;
        }

        $maxIndex = max(0, count($orderedIds) - 1);
        $targetIndex = min(max(0, $targetOrder - 1), $maxIndex);

        if ($targetIndex === $currentIndex) {
            return;
        }

        array_splice($orderedIds, $currentIndex, 1);
        array_splice($orderedIds, $targetIndex, 0, [$currentRecordId]);

        self::setNewOrder($orderedIds);

        $this->refresh();
    }

    public static function stripAayahWrapper(string $text): string
    {
        $trimmedText = trim($text);

        if (! self::isWrappedAsAayah($trimmedText)) {
            return $trimmedText;
        }

        $wrappedTextLength = mb_strlen($trimmedText);
        $openingMarkLength = mb_strlen(self::AAYAH_OPENING_MARK);
        $closingMarkLength = mb_strlen(self::AAYAH_CLOSING_MARK);
        $contentLength = max(0, $wrappedTextLength - $openingMarkLength - $closingMarkLength);

        return trim((string) mb_substr($trimmedText, $openingMarkLength, $contentLength));
    }

    public static function isWrappedAsAayah(string $text): bool
    {
        return str_starts_with($text, self::AAYAH_OPENING_MARK)
            && str_ends_with($text, self::AAYAH_CLOSING_MARK);
    }

    /**
     * @return array<int, array{id: int, time: string, type: string, text: string, origin: string|null, is_aayah: bool, is_original: bool, count: int, order: int}>
     */
    public static function defaultsPayload(): array
    {
        return self::query()
            ->ordered()
            ->get(['id', 'time', 'type', 'text', 'origin', 'is_aayah', 'count', 'order'])
            ->map(fn (self $thikr): array => $thikr->toAthkarArray())
            ->all();
    }

    /**
     * @return array{id: int, time: string, type: string, text: string, origin: string|null, is_aayah: bool, is_original: bool, count: int, order: int}
     */
    public function toAthkarArray(): array
    {
        $type = $this->type instanceof ThikrType
            ? $this->type->value
            : ((string) $this->type ?: ThikrType::Glorification->value);
        $origin = is_string($this->origin) ? trim($this->origin) : null;
        $normalizedOrigin = $origin === '' ? null : $origin;

        return [
            'id' => $this->id,
            'time' => $this->time->value,
            'type' => $type,
            'text' => $this->text,
            'origin' => $normalizedOrigin,
            'is_aayah' => (bool) $this->is_aayah,
            'is_original' => $this->isOriginal(),
            'count' => $this->count,
            'order' => $this->order,
        ];
    }

    public function isOriginal(): bool
    {
        return is_string($this->origin) && trim($this->origin) !== '';
    }

    public function getIsOriginalAttribute(): bool
    {
        return $this->isOriginal();
    }
}
