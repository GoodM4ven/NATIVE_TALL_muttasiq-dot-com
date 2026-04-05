<?php

declare(strict_types=1);

namespace App\Filament\Resources\ThikrOverrideSubmissions;

use App\Filament\Resources\ThikrOverrideSubmissions\Pages\ManageThikrOverrideSubmissions;
use App\Models\Thikr;
use App\Models\ThikrOverrideSubmission;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ThikrOverrideSubmissionResource extends Resource
{
    protected static ?string $model = ThikrOverrideSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'athkar-override-submissions';

    protected static ?string $navigationLabel = 'مراجعات تعديلات الأذكار';

    protected static ?string $pluralModelLabel = 'طلبات مراجعة تعديلات الأذكار';

    protected static ?string $modelLabel = 'طلب مراجعة تعديل';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('status')
                    ->label(arabic_text('الحالة'))
                    ->badge()
                    ->formatStateUsing(static fn (string $state): string => self::statusLabel($state))
                    ->color(static fn (string $state): string => self::statusColor($state)),

                TextColumn::make('thikr_id')
                    ->label(arabic_text('رقم الذكر'))
                    ->badge(),

                TextColumn::make('thikr.text')
                    ->label(arabic_text('النص الأصلي'))
                    ->formatStateUsing(static fn (?string $state): string => Str::limit((string) $state, 100))
                    ->wrap(),

                TextColumn::make('override_payload')
                    ->label(arabic_text('النص المقترح'))
                    ->state(static function (ThikrOverrideSubmission $record): string {
                        $payload = self::normalizeOverridePayload($record->override_payload);
                        $proposedText = Arr::get($payload, 'proposed.text');

                        return Str::limit((string) $proposedText, 100);
                    })
                    ->wrap(),

                TextColumn::make('override_payload')
                    ->label(arabic_text('الحقول المتغيرة'))
                    ->state(static function (ThikrOverrideSubmission $record): string {
                        $payload = self::normalizeOverridePayload($record->override_payload);
                        $changedKeys = Arr::get($payload, 'changed_keys');

                        if (! is_array($changedKeys) || $changedKeys === []) {
                            return '-';
                        }

                        return implode(
                            ', ',
                            array_map(
                                static fn (mixed $key): string => self::changedKeyLabel((string) $key),
                                $changedKeys,
                            ),
                        );
                    })
                    ->wrap(),

                TextColumn::make('submitted_at')
                    ->label(arabic_text('تاريخ الإرسال'))
                    ->since()
                    ->sortable(),

                TextColumn::make('reviewed_at')
                    ->label(arabic_text('تاريخ المراجعة'))
                    ->since()
                    ->placeholder(arabic_text('—'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('submitted_from_ip')
                    ->label('IP')
                    ->placeholder(arabic_text('—'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reviewer.name')
                    ->label(arabic_text('المراجع'))
                    ->placeholder(arabic_text('—'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(arabic_text('الحالة'))
                    ->options([
                        ThikrOverrideSubmission::STATUS_PENDING => arabic_text('بانتظار المراجعة'),
                        ThikrOverrideSubmission::STATUS_APPROVED => arabic_text('مقبول'),
                        ThikrOverrideSubmission::STATUS_REJECTED => arabic_text('مرفوض'),
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(arabic_text('اعتماد'))
                    ->icon('heroicon-s-check-circle')
                    ->color('success')
                    ->visible(static fn (ThikrOverrideSubmission $record): bool => $record->isPending())
                    ->modalHeading(arabic_text('اعتماد التعديل'))
                    ->modalDescription(arabic_text('سيتم تطبيق التعديل المقترح على سجل الذكر الأصلي.'))
                    ->schema([
                        Textarea::make('reviewed_note')
                            ->label(arabic_text('ملاحظة الإدارة'))
                            ->rows(3)
                            ->maxLength(2000),
                    ])
                    ->action(function (ThikrOverrideSubmission $record, array $data): void {
                        if (! self::applyApprovedOverride($record)) {
                            Notification::make()
                                ->danger()
                                ->title(arabic_text('تعذر اعتماد التعديل'))
                                ->body(arabic_text('تعذر العثور على السجل الأصلي أو بيانات التعديل غير صالحة.'))
                                ->send();

                            return;
                        }

                        $record->update([
                            'status' => ThikrOverrideSubmission::STATUS_APPROVED,
                            'reviewed_at' => now(),
                            'reviewed_by_user_id' => auth()->id(),
                            'reviewed_note' => Arr::get($data, 'reviewed_note'),
                        ]);

                        Notification::make()
                            ->success()
                            ->title(arabic_text('تم اعتماد التعديل'))
                            ->send();
                    }),

                Action::make('reject')
                    ->label(arabic_text('رفض'))
                    ->icon('heroicon-s-x-circle')
                    ->color('danger')
                    ->visible(static fn (ThikrOverrideSubmission $record): bool => $record->isPending())
                    ->modalHeading(arabic_text('رفض التعديل'))
                    ->modalDescription(arabic_text('سيتغير وضع هذا الطلب إلى مرفوض مع حفظ ملاحظة الإدارة.'))
                    ->schema([
                        Textarea::make('reviewed_note')
                            ->label(arabic_text('سبب الرفض'))
                            ->required()
                            ->rows(3)
                            ->maxLength(2000),
                    ])
                    ->action(function (ThikrOverrideSubmission $record, array $data): void {
                        $record->update([
                            'status' => ThikrOverrideSubmission::STATUS_REJECTED,
                            'reviewed_at' => now(),
                            'reviewed_by_user_id' => auth()->id(),
                            'reviewed_note' => Arr::get($data, 'reviewed_note'),
                        ]);

                        Notification::make()
                            ->warning()
                            ->title(arabic_text('تم رفض التعديل'))
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageThikrOverrideSubmissions::route('/'),
        ];
    }

    private static function applyApprovedOverride(ThikrOverrideSubmission $submission): bool
    {
        $thikr = $submission->thikr()->first();

        if (! $thikr instanceof Thikr) {
            return false;
        }

        $payload = self::normalizeOverridePayload($submission->override_payload);
        $proposed = Arr::get($payload, 'proposed');

        if (! is_array($proposed) || $proposed === []) {
            return false;
        }

        $nextIsAayah = array_key_exists('is_aayah', $proposed)
            ? (bool) $proposed['is_aayah']
            : (bool) $thikr->is_aayah;
        $updateData = [];

        if (array_key_exists('time', $proposed)) {
            $updateData['time'] = (string) $proposed['time'];
        }

        if (array_key_exists('type', $proposed)) {
            $updateData['type'] = (string) $proposed['type'];
        }

        if (array_key_exists('origin', $proposed)) {
            $origin = trim((string) ($proposed['origin'] ?? ''));
            $updateData['origin'] = $origin === '' ? null : $origin;
        }

        if (array_key_exists('count', $proposed)) {
            $updateData['count'] = max(1, (int) $proposed['count']);
        }

        if (array_key_exists('is_aayah', $proposed)) {
            $updateData['is_aayah'] = $nextIsAayah;
        }

        if (array_key_exists('text', $proposed)) {
            $updateData['text'] = Thikr::normalizeAayahText((string) $proposed['text'], $nextIsAayah);
        }

        if ($updateData !== []) {
            $thikr->fill($updateData);
            $thikr->save();
        }

        if (array_key_exists('order', $proposed)) {
            $thikr->moveToOrder(max(1, (int) $proposed['order']));
        }

        return true;
    }

    private static function statusLabel(string $status): string
    {
        return match ($status) {
            ThikrOverrideSubmission::STATUS_APPROVED => arabic_text('مقبول'),
            ThikrOverrideSubmission::STATUS_REJECTED => arabic_text('مرفوض'),
            default => arabic_text('بانتظار المراجعة'),
        };
    }

    private static function statusColor(string $status): string
    {
        return match ($status) {
            ThikrOverrideSubmission::STATUS_APPROVED => 'success',
            ThikrOverrideSubmission::STATUS_REJECTED => 'danger',
            default => 'warning',
        };
    }

    private static function changedKeyLabel(string $key): string
    {
        return match ($key) {
            'order' => arabic_text('الترتيب'),
            'time' => arabic_text('الوقت'),
            'type' => arabic_text('النوع'),
            'text' => arabic_text('النص'),
            'origin' => arabic_text('المصدر'),
            'count' => arabic_text('العدد'),
            'is_aayah' => arabic_text('نص آية'),
            default => $key,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function normalizeOverridePayload(mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }

        if (! is_string($payload) || trim($payload) === '') {
            return [];
        }

        $decodedPayload = json_decode($payload, true);

        return is_array($decodedPayload) ? $decodedPayload : [];
    }
}
