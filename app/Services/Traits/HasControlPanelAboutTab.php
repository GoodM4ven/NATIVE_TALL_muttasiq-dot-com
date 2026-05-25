<?php

declare(strict_types=1);

namespace App\Services\Traits;

use Filament\Actions\Action;
use Filament\Schemas\Components\Image;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Support\Enums\TextSize;

trait HasControlPanelAboutTab
{
    protected function controlPanelAboutTab(): Tab
    {
        return Tab::make('about')
            ->label(arabic_text('حولنا'))
            ->key('about')
            ->icon('phosphor.warning-diamond-fill')
            ->schema([
                Text::make('منصة متسق')
                    ->size(TextSize::Large)
                    ->color('black')
                    ->extraAttributes(['class' => 'block w-full text-center -mb-5']),

                Text::make(config('app.custom.app_description'))
                    ->size(TextSize::Small)
                    ->color('gray')
                    ->extraAttributes(['class' => 'block w-full text-center mt-2']),

                Image::make(
                    url: fn () => asset(is_dark_mode_on() ? 'icon-dark.png' : 'icon.png'),
                    alt: 'Muttasiq application icono',
                )
                    ->imageSize('10rem')
                    ->alignCenter()
                    ->extraAttributes([
                        'class' => 'mx-auto cursor-pointer border-0 -mt-5',
                        'role' => 'button',
                        'tabindex' => '0',
                        'x-on:click' => open_link_native_aware('https://github.com/GoodM4ven/NATIVE_TALL_muttasiq-dot-com'),
                        'x-on:keydown.enter.prevent' => open_link_native_aware('https://github.com/GoodM4ven/NATIVE_TALL_muttasiq-dot-com'),
                        'x-on:keydown.space.prevent' => open_link_native_aware('https://github.com/GoodM4ven/NATIVE_TALL_muttasiq-dot-com'),
                    ]),

                Text::make('روابط سريعة:')
                    ->size(TextSize::Medium)
                    ->color('black')
                    ->extraAttributes(['class' => 'block w-full text-center -mt-3']),

                $this->developmentLinkAction(
                    name: 'open_source_code',
                    label: 'الترميز مفتوح المصدر',
                    url: 'https://github.com/GoodM4ven/NATIVE_TALL_muttasiq-dot-com',
                    icon: 'heroicon-s-code-bracket',
                ),

                $this->developmentLinkAction(
                    name: 'open_main_missions',
                    label: 'المهام الرئيسية للمنصة',
                    url: 'https://github.com/GoodM4ven/NATIVE_TALL_muttasiq-dot-com/?tab=readme-ov-file#%D8%A7%D9%84%D9%85%D9%87%D8%A7%D9%85',
                    icon: 'heroicon-s-clipboard-document-list',
                ),

                $this->developmentLinkAction(
                    name: 'open_discussions',
                    label: 'الاقتراحات ونقاشها',
                    url: 'https://github.com/GoodM4ven/NATIVE_TALL_muttasiq-dot-com/discussions',
                    icon: 'heroicon-s-chat-bubble-left-right',
                ),

                $this->developmentLinkAction(
                    name: 'open_bug_reports',
                    label: 'التبليغ عن الأخطاء',
                    url: 'https://github.com/GoodM4ven/NATIVE_TALL_muttasiq-dot-com/issues',
                    icon: 'bootstrap.x-circle-fill',
                ),

                $this->developmentLinkAction(
                    name: 'open_current_development',
                    label: 'التطوير التفصيلي الحالي',
                    url: 'https://github.com/users/GoodM4ven/projects/5/views/1',
                    icon: 'ant-design.code',
                ),

                $this->developmentLinkAction(
                    name: 'open_project_support',
                    label: 'دعم المشروع',
                    url: 'https://github.com/GoodM4ven/NATIVE_TALL_muttasiq-dot-com?tab=readme-ov-file#%D8%A7%D9%84%D8%AF%D8%B9%D9%85',
                    icon: 'bootstrap.lightning-charge-fill',
                ),
            ]);
    }

    private function developmentLinkAction(string $name, string $label, string $url, string $icon): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->link()
            ->extraAttributes(['class' => 'flex w-fit mx-auto text-[0.8rem]! items-center gap-1.5 whitespace-nowrap text-center -mt-3'])
            ->actionJs(open_link_native_aware(url: $url));
    }
}
