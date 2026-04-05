<?php

declare(strict_types=1);

namespace App\Filament\Resources\ThikrOverrideSubmissions\Pages;

use App\Filament\Resources\ThikrOverrideSubmissions\ThikrOverrideSubmissionResource;
use App\Models\ThikrOverrideSubmission;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageThikrOverrideSubmissions extends ManageRecords
{
    protected static string $resource = ThikrOverrideSubmissionResource::class;

    /**
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'pending' => Tab::make(arabic_text('بانتظار المراجعة'))
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where(
                        'status',
                        ThikrOverrideSubmission::STATUS_PENDING,
                    ),
                ),
            'approved' => Tab::make(arabic_text('المعتمدة'))
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where(
                        'status',
                        ThikrOverrideSubmission::STATUS_APPROVED,
                    ),
                ),
            'rejected' => Tab::make(arabic_text('المرفوضة'))
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->where(
                        'status',
                        ThikrOverrideSubmission::STATUS_REJECTED,
                    ),
                ),
            'all' => Tab::make(arabic_text('الكل')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'pending';
    }
}
