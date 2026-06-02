<?php

declare(strict_types=1);

test('manual js error reporting mode exposes breakpoint and hides technical snapshot field', function () {
    $componentSource = file_get_contents(app_path('Livewire/JsErrorReporter.php'));

    expect($componentSource)->not->toBeFalse()
        ->and($componentSource)->toContain('public bool $isManualReport = false;')
        ->and($componentSource)->toContain("TextInput::make('screen_breakpoint')")
        ->and($componentSource)->toContain("->label(arabic_text('المقاس الحالي للجهاز'))")
        ->and($componentSource)->toContain('private function resolveScreenBreakpointDisplayLabel(): string')
        ->and($componentSource)->toContain("'base' => arabic_text('صغير جدًّا')")
        ->and($componentSource)->toContain("'sm' => arabic_text('صغير')")
        ->and($componentSource)->toContain("'md' => arabic_text('متوسط')")
        ->and($componentSource)->toContain("'lg' => arabic_text('كبير')")
        ->and($componentSource)->toContain("'xl' => arabic_text('كبير جدًّا 1')")
        ->and($componentSource)->toContain("'2xl' => arabic_text('كبير جدًّا 2')")
        ->and($componentSource)->not->toContain('(Breakpoint)')
        ->and($componentSource)->toContain('->hidden(fn (): bool => $this->isManualReport || $this->capturedErrors === [])');
});

test('about tab report errors action dispatches manual js error report modal event', function () {
    $aboutTabSource = file_get_contents(app_path('Services/Traits/HasControlPanelAboutTab.php'));

    expect($aboutTabSource)->not->toBeFalse()
        ->and($aboutTabSource)->toContain('private function reportErrorsAction(): Action')
        ->and($aboutTabSource)->toContain("window.dispatchEvent(new CustomEvent('trigger-js-error-report-modal'))");
});

test('manual js error report trigger avoids automatic reload on close', function () {
    $scriptSource = file_get_contents(
        resource_path('views/components/partials/scripts/mobile-js-errors-handler.blade.php'),
    );

    expect($scriptSource)->not->toBeFalse()
        ->and($scriptSource)->toContain("window.addEventListener('trigger-js-error-report-modal', () => {")
        ->and($scriptSource)->toContain("mode: 'manual'")
        ->and($scriptSource)->toContain("if (lastModalMode === 'manual') {")
        ->and($scriptSource)->toContain('sessionStorage.removeItem(successfulSubmissionFlag);')
        ->and($scriptSource)->toContain("window.Livewire.dispatchTo('js-error-reporter', 'show-submitted-toast');")
        ->and($scriptSource)->toContain("sessionStorage.setItem(successfulSubmissionFlag, '1');")
        ->and($scriptSource)->toContain('const resolveBreakpointLabel = () => {')
        ->and($scriptSource)->toContain('return resolveBreakpointFromViewportWidth() ?? normalizedBreakpoint;')
        ->and($scriptSource)->not->toContain('breakpoint: isNativeRuntime ? null : trimTo(readBreakpoint(), 8),');
});
