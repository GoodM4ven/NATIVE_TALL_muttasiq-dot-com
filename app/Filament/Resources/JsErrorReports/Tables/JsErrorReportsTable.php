<?php

declare(strict_types=1);

namespace App\Filament\Resources\JsErrorReports\Tables;

use App\Models\JsErrorReport;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class JsErrorReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (JsErrorReport $record): string => $record->isResolved() ? 'معالج' : 'قيد المراجعة')
                    ->colors([
                        'success' => fn (JsErrorReport $record): bool => $record->isResolved(),
                        'warning' => fn (JsErrorReport $record): bool => ! $record->isResolved(),
                    ]),

                TextColumn::make('user_note')
                    ->label('وصف المستخدم')
                    ->limit(85)
                    ->searchable(),

                TextColumn::make('first_error_message')
                    ->label('أول خطأ تقني')
                    ->limit(85)
                    ->searchable(),

                TextColumn::make('error_count')
                    ->label('عدد الأخطاء')
                    ->badge()
                    ->sortable(),

                TextColumn::make('runtime_platform')
                    ->label('المنصة')
                    ->badge()
                    ->placeholder('غير محدد'),

                TextColumn::make('screen_breakpoint')
                    ->label('نقطة التوقف')
                    ->badge()
                    ->placeholder('غير محدد'),

                TextColumn::make('created_at')
                    ->label('وقت البلاغ')
                    ->since()
                    ->sortable(),

                TextColumn::make('resolved_at')
                    ->label('وقت المعالجة')
                    ->since()
                    ->placeholder('غير معالج')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->recordActions([
                Action::make('markResolved')
                    ->label('تعيين كمعالج')
                    ->icon('heroicon-s-check-circle')
                    ->color('success')
                    ->visible(fn (JsErrorReport $record): bool => ! $record->isResolved())
                    ->requiresConfirmation()
                    ->action(function (JsErrorReport $record): void {
                        $record->update([
                            'resolved_at' => now(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('تم تحديث البلاغ إلى معالج')
                            ->send();
                    }),

                Action::make('markUnresolved')
                    ->label('إرجاع كغير معالج')
                    ->icon('heroicon-s-arrow-uturn-right')
                    ->color('warning')
                    ->visible(fn (JsErrorReport $record): bool => $record->isResolved())
                    ->requiresConfirmation()
                    ->action(function (JsErrorReport $record): void {
                        $record->update([
                            'resolved_at' => null,
                        ]);

                        Notification::make()
                            ->warning()
                            ->title('أُعيد البلاغ إلى قائمة غير المعالجة')
                            ->send();
                    }),

                Action::make('showTechnicalDetails')
                    ->label('تفاصيل')
                    ->icon('heroicon-s-code-bracket')
                    ->color('gray')
                    ->modalHeading('تفاصيل البلاغ التقنية')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق')
                    ->modalContent(fn (JsErrorReport $record): View => view(
                        'filament.resources.js-error-reports.technical-details',
                        ['record' => $record],
                    )),

                DeleteAction::make()
                    ->label('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markResolvedBulk')
                        ->label('تعيين المحدد كمعالج')
                        ->icon('heroicon-s-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records
                                ->filter(fn (JsErrorReport $record): bool => ! $record->isResolved())
                                ->each(fn (JsErrorReport $record): bool => $record->update(['resolved_at' => now()]));
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('markUnresolvedBulk')
                        ->label('إرجاع المحدد كغير معالج')
                        ->icon('heroicon-s-arrow-uturn-right')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $records
                                ->filter(fn (JsErrorReport $record): bool => $record->isResolved())
                                ->each(fn (JsErrorReport $record): bool => $record->update(['resolved_at' => null]));
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
