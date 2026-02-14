<?php

declare(strict_types=1);

namespace App\Filament\Resources\Thikrs\Schemas;

use App\Services\Enums\ThikrTime;
use App\Services\Enums\ThikrType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component as SchemaComponent;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ThikrForm
{
    /**
     * @return array<int, SchemaComponent>
     */
    public static function components(bool $withOrder = false, bool $fromManager = false): array
    {
        $baseComponents = [
            self::orderInput()->columnSpanFull(),

            Grid::make()
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    Select::make('time')
                        ->label('الوقت')
                        ->options(ThikrTime::class)
                        ->required()
                        ->native(false)
                        ->columnSpan(1),

                    Select::make('type')
                        ->label('النوع')
                        ->options(ThikrType::class)
                        ->required()
                        ->native(false)
                        ->afterLabel('احرص على الترتيب')
                        ->default(ThikrType::Glorification->value)
                        ->columnSpan(1),

                    self::countInput()->columnSpan(1),
                ]),

            Toggle::make('is_aayah')
                ->label('آيات')
                ->live()
                ->hint(fn (Get $get): ?string => $get('is_aayah') ? '' : null)
                ->helperText('هذا الخيار يضيف أقواس البداية والنهاية تلقائيًا حول النص. استخدم هذا الرمز ۝ للفصل بين الآيات.')
                ->columnSpanFull()
                ->default(false),

            Textarea::make('text')
                ->label('النص')
                ->required()
                ->rows($fromManager ? 4 : 8)
                ->extraInputAttributes([
                    'style' => 'font-family: var(--font-arabic-serif); font-size: 1.25rem; line-height: 2;',
                ])
                ->columnSpan([
                    'default' => 2,
                    'xl' => 1,
                ]),

            Textarea::make('origin')
                ->label('الأصل')
                ->afterLabel('الأثر الوارد في السنة الذي يتضمن النص')
                ->rows($fromManager ? 4 : 8)
                ->extraInputAttributes([
                    'style' => 'font-family: var(--font-arabic-serif); font-size: 1.25rem; line-height: 2;',
                ])
                ->columnSpan([
                    'default' => 2,
                    'xl' => 1,
                ]),
        ];

        return $baseComponents;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components(self::components());
    }

    private static function orderInput(): TextInput
    {
        return TextInput::make('order')
            ->label('الترتيب')
            ->required()
            ->type('number')
            ->inputMode('numeric')
            ->step(1)
            ->integer()
            ->minValue(1)
            ->rules(['required', 'integer', 'min:1']);
    }

    private static function countInput(): TextInput
    {
        return TextInput::make('count')
            ->label('العدد')
            ->required()
            ->type('number')
            ->inputMode('numeric')
            ->step(1)
            ->integer()
            ->minValue(1)
            ->rules(['required', 'integer', 'min:1']);
    }
}
