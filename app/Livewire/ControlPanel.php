<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Setting;
use App\Services\Traits\HasControlPanelAboutTab;
use App\Services\Traits\HasControlPanelChangelogsTab;
use App\Services\Traits\HasControlPanelSettingsTab;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Component;
use Throwable;

class ControlPanel extends Component implements HasActions, HasSchemas
{
    use HasControlPanelAboutTab;
    use HasControlPanelChangelogsTab;
    use HasControlPanelSettingsTab;
    use InteractsWithActions;
    use InteractsWithSchemas;

    private const CONTROL_PANEL_TAB_INDEX = 1;

    private const UPDATES_TAB_INDEX = 2;

    /**
     * @var array<string, bool|int|string>
     */
    public array $clientControlPanel = [];

    public int $controlPanelActiveTab = self::CONTROL_PANEL_TAB_INDEX;

    public function controlPanelAction(): Action
    {
        return Action::make('controlPanel')
            ->modal()
            ->label(arabic_text('لوحة التحكم'))
            ->modalDescription(arabic_text('بعض المعلومات والتفضيلات في كيفية عمل المنصة'))
            ->modalSubmitAction(false)
            ->extraModalWindowAttributes([
                'id' => 'control-panel-modal',
                'class' => 'muttasiq-modal-window quran-control-panel-modal-window',
            ])
            ->extraModalOverlayAttributes([
                'class' => 'muttasiq-modal-overlay quran-control-panel-modal-overlay',
            ])
            ->fillForm(fn (): array => $this->loadControlPanel())
            ->schema([
                Tabs::make('Tabs')
                    ->activeTab(fn (): int => $this->controlPanelActiveTab)
                    ->tabs([
                        $this->controlPanelSettingsTab(),
                        $this->controlPanelChangelogsTab(),
                        $this->controlPanelAboutTab(),
                    ]),
            ])
            ->action(function (array $data): void {
                $savedControlPanel = $this->filterControlPanel($data);
                $isMaintenancePulse = $this->isMountedControlPanelMaintenancePulse();

                $this->clientControlPanel = $savedControlPanel;
                $this->dispatch(
                    'control-panel-updated',
                    controlPanel: $savedControlPanel,
                    maintenancePulse: $isMaintenancePulse,
                    returnToGate: ! $isMaintenancePulse,
                );

                if (! $isMaintenancePulse) {
                    notify(
                        iconName: 'mdi.content-save-check',
                        title: arabic_text('تم حفظ الإعدادات بنجاح'),
                    );
                }
            });
    }

    public function saveControlPanelSetting(string $key, mixed $value): void
    {
        $defaults = self::controlPanelDefaults();

        if (! array_key_exists($key, $defaults)) {
            return;
        }

        $savedControlPanel = $this->filterControlPanel(
            array_replace($this->loadControlPanel(), [$key => $value]),
        );

        $this->clientControlPanel = $savedControlPanel;

        $this->dispatch(
            'control-panel-updated',
            controlPanel: $savedControlPanel,
            maintenancePulse: false,
            returnToGate: false,
        );

        notify(
            iconName: 'mdi.content-save-check',
            title: arabic_text('تم حفظ الإعدادات بنجاح'),
        );
    }

    public function supportUnlockAction(): Action
    {
        return Action::make('supportUnlock')
            ->modal()
            ->modalHeading(arabic_text('دعم المشروع'))
            ->modalDescription(arabic_text('قبل استخدام بعض الخصائص المميّزة في المنصة، نحتاج منك تأكيد دعم تطوير المشروع.'))
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalSubmitActionLabel(arabic_text('قمت بالدعم'))
            ->modalCancelAction(false)
            ->extraModalWindowAttributes([
                'id' => 'support-unlock-modal',
                'class' => 'muttasiq-modal-window quran-support-unlock-modal-window',
            ])
            ->extraModalOverlayAttributes([
                'class' => 'muttasiq-modal-overlay quran-support-unlock-modal-overlay',
            ])
            ->modalContent(fn (): HtmlString => $this->supportUnlockModalContent())
            ->extraModalFooterActions(fn (Action $action): array => [
                $action
                    ->makeModalSubmitAction('supportUnlockWeeklyBypass', arguments: ['mode' => 'weekly'])
                    ->label(arabic_text('أشهد الله أني لا أستطيع دعمكم الآن'))
                    ->color('gray'),
            ])
            ->action(function (array $data, array $arguments): void {
                $mode = ($arguments['mode'] ?? null) === 'weekly'
                    ? 'weekly'
                    : 'permanent';

                $this->dispatch('support-unlock-updated', mode: $mode);

                notify(
                    iconName: $mode === 'weekly'
                        ? 'heroicon-o-clock'
                        : 'heroicon-o-lock-open',
                    title: $mode === 'weekly'
                        ? arabic_text('تمت إتاحة الميّزات لأسبوع واحد')
                        : arabic_text('تمت إتاحة الميّزات بشكل دائم'),
                    body: $mode === 'weekly'
                        ? arabic_text('رزقك الله...')
                        : arabic_text('أحسن الله إليك...'),
                );
            });
    }

    public function triggerReaderMaintenancePulse(): void
    {
        $normalizedControlPanel = $this->filterControlPanel($this->loadControlPanel());

        $this->syncClientControlPanel($normalizedControlPanel);
        $this->dispatch(
            'control-panel-updated',
            controlPanel: $normalizedControlPanel,
            maintenancePulse: true,
            returnToGate: false,
        );

        $this->runSaveLikeControlPanelPulse();
        $this->dispatch(
            'control-panel-updated',
            controlPanel: $this->clientControlPanel,
            maintenancePulse: true,
            returnToGate: false,
        );

        $this->forceRender();
    }

    public function setControlPanelActiveTab(?string $tab = null): void
    {
        $this->controlPanelActiveTab = $tab === 'updates'
            ? self::UPDATES_TAB_INDEX
            : self::CONTROL_PANEL_TAB_INDEX;
    }

    /**
     * @param  array<string, mixed>  $controlPanel
     */
    public function openControlPanelModal(array $controlPanel = [], ?string $tab = null): void
    {
        $this->syncClientControlPanel($controlPanel);
        $this->setControlPanelActiveTab($tab);
        $this->mountAction('controlPanel');
    }

    /**
     * @param  array<string, mixed>  $controlPanel
     */
    public function syncClientControlPanel(array $controlPanel): void
    {
        $this->clientControlPanel = $this->filterControlPanel($controlPanel);
    }

    public function render(): View
    {
        return view('livewire.control-panel');
    }

    private function supportUnlockModalContent(): HtmlString
    {
        $html = Cache::rememberForever(
            'support-unlock-modal-content:v1',
            fn (): string => $this->buildSupportUnlockModalContent(),
        );

        return new HtmlString($html);
    }

    private function buildSupportUnlockModalContent(): string
    {
        $introBeforeStrong = arabic_text(
            'تطوير المزايا المتقدمة، وإتاحة المنصة على المخدّمات والمنصات بأجهزتها المختلفة، كل هذا يتطلب ',
        );
        $introStrong = arabic_text('وقتًا وجهدًا وتكلفة مستمرة');
        $introAfterStrong = arabic_text(
            '، بارك الله فيكم... ولذلك نودّ منكم على الأقلّ محاولة التبرع لتطوير منصة متسق باستخدام إحدى المنصات المتاحة لذلك، وجزاكم الله خيرا.',
        );
        $supportLinksCaption = arabic_text('روابط منصات الدعم:');

        return '<div class="space-y-4 text-right text-base! leading-7 text-gray-800 dark:text-gray-100">'
            .'<p class="text-center">'
            .e($introBeforeStrong)
            .'<strong>'.e($introStrong).'</strong>'
            .e($introAfterStrong)
            .'</p>'
            .'<div class="quran-support-unlock-links-panel rounded-xl p-3 text-sm">'
            .'<p class="mb-2 font-semibold text-gray-900 dark:text-gray-100">'.e($supportLinksCaption).'</p>'
            .'<div class="flex flex-wrap items-center justify-end gap-2">'
            .$this->supportUnlockLinkMarkup('Buy Me a Coffee', 'https://buymeacoffee.com/goodm4ven')
            .$this->supportUnlockLinkMarkup('Patreon', 'https://patreon.com/GoodM4ven')
            .$this->supportUnlockLinkMarkup('GitHub Sponsors', 'https://github.com/sponsors/GoodM4ven')
            .'</div>'
            .'</div>'
            .'</div>';
    }

    private function supportUnlockLinkMarkup(string $label, string $url): string
    {
        $openLinkNativeAware = htmlspecialchars(open_link_native_aware($url), ENT_QUOTES, 'UTF-8');
        $safeLabel = e($label);

        return '<button type="button" class="quran-support-unlock-link rounded-lg px-3 py-1.5 text-xs font-medium transition"'
            .' x-on:click.prevent="'.$openLinkNativeAware.'"'
            .' x-on:keydown.enter.prevent="'.$openLinkNativeAware.'"'
            .' x-on:keydown.space.prevent="'.$openLinkNativeAware.'">'
            .$safeLabel
            .'</button>';
    }

    /**
     * @return array<string, bool|int|string>
     */
    private function loadControlPanel(): array
    {
        $storedControlPanelValues = Setting::storedValues(array_keys(self::controlPanelDefaults()));

        $normalizedControlPanelValues = Setting::normalizeSettings(
            array_replace(self::controlPanelDefaults(), $storedControlPanelValues, $this->clientControlPanel),
        );

        return $normalizedControlPanelValues;
    }

    /**
     * @param  array<string, mixed>  $controlPanel
     * @return array<string, bool|int|string>
     */
    private function filterControlPanel(array $controlPanel): array
    {
        $defaults = self::controlPanelDefaults();
        $normalized = Setting::normalizeSettings(
            array_replace($defaults, array_intersect_key($controlPanel, $defaults)),
        );

        return array_intersect_key($normalized, $defaults);
    }

    private function runSaveLikeControlPanelPulse(): void
    {
        try {
            $this->mountAction('controlPanel', ['maintenancePulse' => true]);

            if (! $this->getMountedAction()) {
                return;
            }

            $this->callMountedAction();
        } catch (Throwable) {
            if (count($this->mountedActions ?? [])) {
                $this->unmountAction(canCancelParentActions: false);
            }
        }
    }

    private function isMountedControlPanelMaintenancePulse(): bool
    {
        $mountedActions = $this->mountedActions ?? [];

        if ($mountedActions === []) {
            return false;
        }

        $mountedAction = $mountedActions[array_key_last($mountedActions)] ?? null;

        if (! is_array($mountedAction)) {
            return false;
        }

        $arguments = $mountedAction['arguments'] ?? [];
        $value = $arguments['maintenancePulse'] ?? false;

        if (is_bool($value)) {
            return $value;
        }

        if ($value === 1 || $value === '1') {
            return true;
        }

        if ($value === 0 || $value === '0') {
            return false;
        }

        return false;
    }
}
