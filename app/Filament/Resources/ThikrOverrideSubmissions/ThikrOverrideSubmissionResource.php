<?php

declare(strict_types=1);

namespace App\Filament\Resources\ThikrOverrideSubmissions;

use App\Filament\Resources\ThikrOverrideSubmissions\Pages\ManageThikrOverrideSubmissions;
use App\Models\Thikr;
use App\Models\ThikrOverrideSubmission;
use App\Services\Enums\ThikrTime;
use App\Services\Enums\ThikrType;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ThikrOverrideSubmissionResource extends Resource
{
    /**
     * @var array<int, string>
     */
    private const OVERRIDE_COMPARISON_KEYS = [
        'order',
        'time',
        'type',
        'text',
        'origin',
        'count',
        'is_aayah',
    ];

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
                Action::make('viewReview')
                    ->label(arabic_text('عرض المقارنة'))
                    ->icon('heroicon-s-eye')
                    ->color('gray')
                    ->modalHeading(arabic_text('مقارنة التعديل المقترح'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(arabic_text('إغلاق'))
                    ->modalWidth(Width::SevenExtraLarge)
                    ->modalContent(
                        static fn (ThikrOverrideSubmission $record): HtmlString => self::overrideReviewModalContent($record),
                    ),

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

    private static function overrideReviewModalContent(ThikrOverrideSubmission $record): HtmlString
    {
        $payload = self::normalizeOverridePayload($record->override_payload);
        $thikrRelationRecord = $record->thikr;
        $thikr = $thikrRelationRecord instanceof Thikr ? $thikrRelationRecord : null;
        $baselineCurrent = [
            'order' => $thikr instanceof Thikr ? max(1, (int) $thikr->order) : 1,
            'time' => $thikr instanceof Thikr ? $thikr->time->value : '',
            'type' => $thikr instanceof Thikr ? $thikr->type->value : '',
            'text' => $thikr instanceof Thikr ? (string) $thikr->text : '',
            'origin' => $thikr instanceof Thikr ? $thikr->origin : null,
            'count' => $thikr instanceof Thikr ? max(1, (int) $thikr->count) : 1,
            'is_aayah' => $thikr instanceof Thikr && (bool) $thikr->is_aayah,
        ];
        $currentSnapshot = Arr::get($payload, 'current');
        $proposedSnapshot = Arr::get($payload, 'proposed');
        $current = array_replace($baselineCurrent, is_array($currentSnapshot) ? $currentSnapshot : []);
        $proposed = array_replace($current, is_array($proposedSnapshot) ? $proposedSnapshot : []);
        $changedKeys = Arr::get($payload, 'changed_keys');
        $normalizedChangedKeys = collect(is_array($changedKeys) ? $changedKeys : [])
            ->map(static fn (mixed $key): string => trim((string) $key))
            ->filter(
                static fn (string $key): bool => $key !== '' && in_array($key, self::OVERRIDE_COMPARISON_KEYS, true),
            )
            ->values()
            ->all();

        if ($normalizedChangedKeys === []) {
            $normalizedChangedKeys = collect(self::OVERRIDE_COMPARISON_KEYS)
                ->filter(
                    static fn (string $key): bool => Arr::get($proposed, $key) !== Arr::get($current, $key),
                )
                ->values()
                ->all();
        }

        $renderSnapshotRows = static function (array $snapshot): string {
            return collect(self::OVERRIDE_COMPARISON_KEYS)
                ->map(function (string $key) use ($snapshot): string {
                    $label = e(self::changedKeyLabel($key));
                    $value = e(self::comparisonValueLabel($key, Arr::get($snapshot, $key)));

                    return '<div class="grid grid-cols-[6.4rem_1fr] items-start gap-2 rounded-lg border border-slate-200/70 bg-white/70 px-3 py-2 dark:border-slate-700/70 dark:bg-slate-900/45">'
                        .'<span class="text-[0.72rem] font-semibold text-slate-500 dark:text-slate-300">'.$label.'</span>'
                        .'<span class="text-sm leading-7 whitespace-pre-wrap break-words text-slate-900 dark:text-slate-50">'.$value.'</span>'
                        .'</div>';
                })
                ->implode('');
        };
        $comparisonRows = collect($normalizedChangedKeys)
            ->map(function (string $key) use ($current, $proposed): string {
                $label = e(self::changedKeyLabel($key));
                $currentValue = e(self::comparisonValueLabel($key, Arr::get($current, $key)));
                $proposedValue = e(self::comparisonValueLabel($key, Arr::get($proposed, $key)));

                return '<tr class="border-b border-slate-200/70 dark:border-slate-700/70">'
                    .'<td class="px-3 py-2 text-xs font-semibold text-slate-600 dark:text-slate-300">'.$label.'</td>'
                    .'<td class="px-3 py-2 text-sm leading-7 whitespace-pre-wrap break-words text-slate-900 dark:text-slate-100">'.$currentValue.'</td>'
                    .'<td class="px-3 py-2 text-sm leading-7 whitespace-pre-wrap break-words text-primary-700 dark:text-primary-200">'.$proposedValue.'</td>'
                    .'</tr>';
            })
            ->implode('');

        if ($comparisonRows === '') {
            $comparisonRows = '<tr>'
                .'<td colspan="3" class="px-3 py-4 text-sm text-slate-500 dark:text-slate-300">'
                .e(arabic_text('لا توجد فروقات محفوظة لهذا الطلب.'))
                .'</td>'
                .'</tr>';
        }

        return new HtmlString(
            '<div class="space-y-4 text-right" dir="rtl">'
                .'<div class="grid gap-4 lg:grid-cols-2">'
                .'<section class="rounded-xl border border-slate-200/80 bg-white/80 p-4 shadow-xs dark:border-slate-700/70 dark:bg-slate-900/60">'
                .'<h3 class="mb-3 text-sm font-semibold text-slate-900 dark:text-slate-100">'.e(arabic_text('السجل الأصلي الكامل')).'</h3>'
                .'<div class="space-y-2">'.$renderSnapshotRows($current).'</div>'
                .'</section>'
                .'<section class="rounded-xl border border-primary-200/80 bg-primary-50/70 p-4 shadow-xs dark:border-primary-700/50 dark:bg-primary-900/20">'
                .'<h3 class="mb-3 text-sm font-semibold text-primary-900 dark:text-primary-100">'.e(arabic_text('السجل المقترح بعد التعديل')).'</h3>'
                .'<div class="space-y-2">'.$renderSnapshotRows($proposed).'</div>'
                .'</section>'
                .'</div>'
                .'<section class="rounded-xl border border-slate-200/80 bg-white/80 p-4 shadow-xs dark:border-slate-700/70 dark:bg-slate-900/60">'
                .'<h3 class="mb-3 text-sm font-semibold text-slate-900 dark:text-slate-100">'.e(arabic_text('صفوف الفروقات (الأصلي مقابل المقترح)')).'</h3>'
                .'<div class="overflow-x-auto rounded-lg border border-slate-200/70 dark:border-slate-700/70">'
                .'<table class="min-w-full border-collapse">'
                .'<thead class="bg-slate-100/90 dark:bg-slate-800/80">'
                .'<tr>'
                .'<th class="px-3 py-2 text-right text-[0.7rem] font-semibold text-slate-600 dark:text-slate-300">'.e(arabic_text('الحقل')).'</th>'
                .'<th class="px-3 py-2 text-right text-[0.7rem] font-semibold text-slate-600 dark:text-slate-300">'.e(arabic_text('الأصلي')).'</th>'
                .'<th class="px-3 py-2 text-right text-[0.7rem] font-semibold text-primary-700 dark:text-primary-300">'.e(arabic_text('المقترح')).'</th>'
                .'</tr>'
                .'</thead>'
                .'<tbody>'.$comparisonRows.'</tbody>'
                .'</table>'
                .'</div>'
                .'</section>'
                .'</div>',
        );
    }

    private static function comparisonValueLabel(string $key, mixed $value): string
    {
        return match ($key) {
            'time' => ThikrTime::labelFor((string) $value),
            'type' => ThikrType::labelFor(is_string($value) ? $value : null),
            'is_aayah' => (bool) $value ? arabic_text('نعم') : arabic_text('لا'),
            'order', 'count' => (string) max(1, (int) $value),
            default => self::normalizedTextValue($value),
        };
    }

    private static function normalizedTextValue(mixed $value): string
    {
        $normalizedValue = trim((string) ($value ?? ''));

        return $normalizedValue === '' ? arabic_text('—') : $normalizedValue;
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
