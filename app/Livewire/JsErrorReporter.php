<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\JsErrorReports\JsErrorReportRecorder;
use App\Services\Support\Enums\NotificationType;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;
use Livewire\Component;

class JsErrorReporter extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    /**
     * @var array<int, array{
     *     type: string,
     *     time: string|null,
     *     message: string,
     *     source: string|null,
     *     line: int|null,
     *     column: int|null,
     *     stack: string|null
     * }>
     */
    public array $capturedErrors = [];

    /**
     * @var array{
     *     url: string|null,
     *     user_agent: string|null,
     *     language: string|null,
     *     platform: string|null,
     *     breakpoint: string|null
     * }
     */
    public array $clientContext = [
        'url' => null,
        'user_agent' => null,
        'language' => null,
        'platform' => null,
        'breakpoint' => null,
    ];

    public function openReportModal(array $payload = []): void
    {
        $errors = $this->normalizeErrors($payload['errors'] ?? []);

        if ($errors === []) {
            return;
        }

        $this->capturedErrors = $errors;
        $this->clientContext = $this->normalizeContext($payload['context'] ?? []);

        $this->mountAction('reportJsError');
    }

    public function reportJsErrorAction(): Action
    {
        return Action::make('reportJsError')
            ->modalHeading('حدث خلل غير متوقع في التطبيق')
            ->modalDescription('من فضلك اكتب وصفًا لما حصل قبل المشكلة لنتمكن من تتبع السبب بشكل أسرع...')
            ->modalAutofocus(false)
            ->modalWidth(Width::ThreeExtraLarge)
            ->modalSubmitActionLabel('إرسال البلاغ')
            ->modalCancelActionLabel('إغلاق')
            ->registerModalActions([
                $this->openGithubIssueAction(),
            ])
            ->modalContentFooter(
                fn (Action $action): HtmlString => new HtmlString(
                    Blade::render('<x-partials.js-error-reporter.modal-footer :action="$action" />', [
                        'action' => $action,
                    ]),
                ),
            )
            ->fillForm(fn (): array => [
                'user_note' => '',
                'technical_snapshot' => $this->formatErrorsForDisplay(),
            ])
            ->schema([
                \Filament\Forms\Components\Textarea::make('user_note')
                    ->label('ماذا كنت تفعل قبل ظهور المشكلة؟')
                    ->required()
                    ->minLength(8)
                    ->maxLength(1500)
                    ->rows(4)
                    ->trim()
                    ->helperText('الوصف يفيدنا أكثر من التفاصيل التقنية المرفقة تلقائيا'),

                \Filament\Forms\Components\Textarea::make('technical_snapshot')
                    ->label('تفاصيل تقنية مرفقة')
                    ->rows(5)
                    ->disabled()
                    ->dehydrated(false)
                    ->extraInputAttributes([
                        'dir' => 'ltr',
                        'class' => 'font-mono text-xs leading-6',
                    ]),
            ])
            ->action(function (array $data, JsErrorReportRecorder $recorder): void {
                try {
                    $report = $recorder->store([
                        'user_note' => (string) ($data['user_note'] ?? ''),
                        'errors' => $this->capturedErrors,
                        'context' => $this->clientContext,
                    ], request());

                    $this->dispatch('js-error-report-submitted', reportId: $report->id);
                    $this->resetCapturedData();
                } catch (\Throwable $exception) {
                    report($exception);

                    notify(
                        iconName: 'heroicon-o-exclamation-triangle',
                        title: 'تعذر إرسال البلاغ الآن',
                        body: 'يمكنك المحاولة لاحقًا أو استخدام رابط GitHub الموجود أسفل النافذة.',
                        type: NotificationType::Danger,
                    );
                }
            });
    }

    public function openGithubIssueAction(): Action
    {
        return Action::make('openGithubIssue')
            ->label('أم تفضل كتابة بلاغ تقني على منصة جيتهب')
            ->icon('heroicon-s-arrow-top-right-on-square')
            ->link()
            ->color('gray')
            ->actionJs(open_link_native_aware($this->githubIssueUrl()));
    }

    public function render(): View
    {
        return view('livewire.js-error-reporter');
    }

    #[On('show-submitted-toast')]
    public function showSubmittedToast(): void
    {
        notify(
            iconName: 'heroicon-o-check-circle',
            title: 'تم إرسال البلاغ بنجاح',
            body: 'جزاك الله خيرا...',
        );
    }

    private function resetCapturedData(): void
    {
        $this->capturedErrors = [];
        $this->clientContext = [
            'url' => null,
            'user_agent' => null,
            'language' => null,
            'platform' => null,
            'breakpoint' => null,
        ];
    }

    /**
     * @return array{url: string|null, user_agent: string|null, language: string|null, platform: string|null, breakpoint: string|null}
     */
    private function normalizeContext(mixed $context): array
    {
        if (! is_array($context)) {
            return [
                'url' => null,
                'user_agent' => null,
                'language' => null,
                'platform' => null,
                'breakpoint' => null,
            ];
        }

        return [
            'url' => $this->trimToLength($context['url'] ?? null, 2048),
            'user_agent' => $this->trimToLength($context['user_agent'] ?? null, 1000),
            'language' => $this->trimToLength($context['language'] ?? null, 32),
            'platform' => $this->trimToLength($context['platform'] ?? null, 32),
            'breakpoint' => $this->trimToLength($context['breakpoint'] ?? null, 8),
        ];
    }

    /**
     * @return array<int, array{type: string, time: string|null, message: string, source: string|null, line: int|null, column: int|null, stack: string|null}>
     */
    private function normalizeErrors(mixed $errors): array
    {
        if (! is_array($errors)) {
            return [];
        }

        return collect($errors)
            ->filter(fn (mixed $entry): bool => is_array($entry))
            ->take(15)
            ->map(fn (array $entry): array => [
                'type' => $this->trimToLength($entry['type'] ?? null, 20) ?: 'error',
                'time' => $this->trimToLength($entry['time'] ?? null, 50),
                'message' => $this->trimToLength($entry['message'] ?? null, 1000) ?: 'Unknown error',
                'source' => $this->trimToLength($entry['source'] ?? null, 2048),
                'line' => is_numeric($entry['line'] ?? null) ? max(0, (int) $entry['line']) : null,
                'column' => is_numeric($entry['column'] ?? null) ? max(0, (int) $entry['column']) : null,
                'stack' => $this->clipToLength($entry['stack'] ?? null, 20000),
            ])
            ->values()
            ->all();
    }

    private function formatErrorsForDisplay(): string
    {
        return collect($this->capturedErrors)
            ->map(function (array $entry): string {
                $parts = [
                    '['.$entry['type'].']',
                    $entry['message'],
                ];

                if ($entry['source']) {
                    $parts[] = '('.$entry['source'].':'.($entry['line'] ?? 0).':'.($entry['column'] ?? 0).')';
                }

                if ($entry['time']) {
                    $parts[] = '@ '.$entry['time'];
                }

                $summary = implode(' ', $parts);

                if (! $entry['stack']) {
                    return $summary;
                }

                return $summary."\n".$entry['stack'];
            })
            ->implode("\n\n");
    }

    private function trimToLength(mixed $value, int $length): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim(strip_tags($value));

        if ($trimmed === '') {
            return null;
        }

        return mb_substr($trimmed, 0, $length);
    }

    private function clipToLength(mixed $value, int $length, int $tailLength = 2000): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim(strip_tags($value));

        if ($trimmed === '') {
            return null;
        }

        if (mb_strlen($trimmed) <= $length) {
            return $trimmed;
        }

        $normalizedTailLength = min(max($tailLength, 0), intdiv($length, 2));
        $headLength = max($length - $normalizedTailLength - 5, 0);

        return mb_substr($trimmed, 0, $headLength).' ... '.mb_substr($trimmed, -$normalizedTailLength);
    }

    private function githubIssueUrl(): string
    {
        return 'https://github.com/GoodM4ven/NATIVE_TALL_muttasiq-dot-com/issues/new';
    }
}
