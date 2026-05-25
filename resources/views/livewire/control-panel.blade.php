<div>
    <style>
        .fi-modal:has(.quran-control-panel-modal-window),
        .fi-modal:has(.quran-support-unlock-modal-window) {
            position: fixed !important;
            inset: 0 !important;
            z-index: 2147482000 !important;
        }

        .fi-modal .quran-control-panel-modal-window,
        .fi-modal .quran-support-unlock-modal-window {
            z-index: 2147482002 !important;
            position: relative;
        }

        .fi-modal:has(.quran-control-panel-modal-window)>.fi-modal-window-ctn,
        .fi-modal:has(.quran-support-unlock-modal-window)>.fi-modal-window-ctn {
            position: fixed !important;
            inset: 0 !important;
            z-index: 2147482001 !important;
            width: 100vw !important;
            height: 100dvh !important;
            transform: none !important;
            isolation: isolate;
        }

        .quran-control-panel-modal-overlay,
        .quran-support-unlock-modal-overlay {
            background: color-mix(in srgb, var(--gray-950) 72%, transparent) !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            z-index: 2147482001 !important;
        }
    </style>

    <div
        class="inset-e-10 sm:inset-e-6.5 md:inset-e-6.5 lg:inset-e-6.5 xl:inset-e-8 2xl:inset-e-10 xl:top-6.5 fixed top-5 z-30 sm:top-5 md:top-5 lg:top-5 2xl:top-8"
        data-stack-item
        x-transition
        x-show="!isControlPanelOpen && !isAthkarManagerOpen"
        x-data="{
            controlPanelModalId: @js('fi-' . $this->getId() . '-action-0'),
            isReaderMaintenanceInFlight: false,
            hasQueuedReaderMaintenance: false,
            westernNumeralChars: ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            arabicIndicNumeralChars: ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            arabicHarakatPattern: /[\u0610-\u061A\u064B-\u065F\u0670\u06D6-\u06ED\u08D4-\u08FF]/g,
            quranCopyHintDesktop: @js(arabic_text('عند التعطيل: الضغط المطوّل لمدة 0.75 ثانية يستهدف الكلمة بدل الآية.')),
            quranCopyHintMobile: @js(arabic_text('عند التعطيل في الجوال: النقر المزدوج ينسخ الهدف الافتراضي، والنقر المزدوج مع التثبيت في النقرة الثانية يستهدف الهدف العكسي.')),
            sliderNumeralsObserver: null,
            sliderNumeralsSyncTimer: null,
            fieldTextOriginalValues: new WeakMap(),
            resolveControlPanelModalWindow() {
                const escapedId = window.CSS?.escape ?
                    window.CSS.escape(this.controlPanelModalId) :
                    this.controlPanelModalId;
                const directModal = document.getElementById(this.controlPanelModalId);
                const dataModal = document.querySelector(`[data-fi-modal-id='${escapedId}']`);
                const modalElement = directModal ?? dataModal;
        
                if (!modalElement) {
                    return null;
                }
        
                if (modalElement.classList?.contains('fi-modal-window')) {
                    return modalElement;
                }
        
                return modalElement.querySelector('.fi-modal-window');
            },
            boostControlPanelModalLayer() {
                const modalWindow = this.resolveControlPanelModalWindow();
        
                if (!(modalWindow instanceof HTMLElement)) {
                    return;
                }
        
                const modalRoot = modalWindow.closest('.fi-modal');
                const modalWindowContainer = modalRoot?.querySelector('.fi-modal-window-ctn');
                const modalOverlay = modalRoot?.querySelector('.quran-control-panel-modal-overlay,.quran-support-unlock-modal-overlay');
        
                if (modalRoot instanceof HTMLElement) {
                    modalRoot.style.zIndex = '2147482000';
                }
        
                if (modalWindowContainer instanceof HTMLElement) {
                    modalWindowContainer.style.zIndex = '2147482001';
                }
        
                if (modalOverlay instanceof HTMLElement) {
                    modalOverlay.style.zIndex = '2147482001';
                }
        
                modalWindow.style.zIndex = '2147482002';
            },
            isControlPanelModalCurrentlyOpen() {
                const modalWindow = this.resolveControlPanelModalWindow();
        
                if (!(modalWindow instanceof HTMLElement)) {
                    return false;
                }
        
                const modalRoot = modalWindow.closest('.fi-modal');
        
                return Boolean(
                    modalRoot?.classList?.contains?.('fi-modal-open') ||
                    modalWindow.getAttribute('aria-hidden') === 'false' ||
                    modalRoot?.getAttribute?.('aria-hidden') === 'false',
                );
            },
            isControlPanelModalEvent(detail = {}) {
                const detailId = String(detail?.id ?? '').trim();
        
                if (detailId !== '' && detailId === this.controlPanelModalId) {
                    return true;
                }
        
                return this.isControlPanelModalCurrentlyOpen();
            },
            resolveControlPanelWesternNumeralState() {
                const modalWindow = this.resolveControlPanelModalWindow();
                const checkbox = modalWindow?.querySelector?.(`input[type='checkbox'][name*='does_use_western_numerals'],input[type='checkbox'][wire\\:model*='does_use_western_numerals']`);
        
                if (checkbox instanceof HTMLInputElement) {
                    return checkbox.checked;
                }
        
                const storedSettings = typeof window.getAthkarSettingsFromStorage === 'function' ?
                    window.getAthkarSettingsFromStorage() : {};
                const normalizedSetting = storedSettings?.does_use_western_numerals;
        
                return normalizedSetting !== false && normalizedSetting !== 0 && normalizedSetting !== '0';
            },
            resolveControlPanelPreserveHarakatState() {
                const modalWindow = this.resolveControlPanelModalWindow();
                const checkbox = modalWindow?.querySelector?.(
                    `input[type='checkbox'][name*='does_preserve_harakat_in_display'],input[type='checkbox'][wire\\:model*='does_preserve_harakat_in_display']`);
        
                if (checkbox instanceof HTMLInputElement) {
                    return checkbox.checked;
                }
        
                const storedSettings = typeof window.getAthkarSettingsFromStorage === 'function' ?
                    window.getAthkarSettingsFromStorage() : {};
                const normalizedSetting = storedSettings?.does_preserve_harakat_in_display;
        
                return normalizedSetting !== false && normalizedSetting !== 0 && normalizedSetting !== '0';
            },
            stripHarakatForDisplay(value) {
                return String(value ?? '').replace(this.arabicHarakatPattern, '');
            },
            convertControlPanelDisplayText(
                value, {
                    useWesternNumerals = true,
                    preserveHarakat = true,
                    preserveFixedSamples = false,
                } = {},
            ) {
                const source = String(value ?? '');
                const normalizedText = preserveHarakat ?
                    source :
                    this.stripHarakatForDisplay(source);
                const convertDigit = (digit) => {
                    const westernIndex = this.westernNumeralChars.indexOf(digit);
        
                    if (westernIndex >= 0) {
                        return useWesternNumerals ?
                            this.westernNumeralChars[westernIndex] :
                            this.arabicIndicNumeralChars[westernIndex];
                    }
        
                    const arabicIndicIndex = this.arabicIndicNumeralChars.indexOf(digit);
        
                    if (arabicIndicIndex >= 0) {
                        return useWesternNumerals ?
                            this.westernNumeralChars[arabicIndicIndex] :
                            this.arabicIndicNumeralChars[arabicIndicIndex];
                    }
        
                    return digit;
                };
                const shouldPreserveSample = (digitChunk, offset) => {
                    if (!preserveFixedSamples) {
                        return false;
                    }
        
                    const before = normalizedText[offset - 1] ?? '';
                    const after = normalizedText[offset + digitChunk.length] ?? '';
        
                    if (before !== '(' || after !== ')') {
                        return false;
                    }
        
                    const normalizedDigits = String(digitChunk ?? '').replace(/[٠-٩]/g, (digit) => {
                        const index = this.arabicIndicNumeralChars.indexOf(digit);
        
                        return index >= 0 ? this.westernNumeralChars[index] : digit;
                    });
        
                    return normalizedDigits === '123';
                };
        
                return normalizedText.replace(/[0-9٠-٩]+/g, (digitChunk, offset) => {
                    if (shouldPreserveSample(digitChunk, offset)) {
                        return digitChunk;
                    }
        
                    return String(digitChunk ?? '')
                        .split('')
                        .map((digit) => convertDigit(digit))
                        .join('');
                });
            },
            syncControlPanelSliderNumerals() {
                const modalWindow = this.resolveControlPanelModalWindow();
        
                if (!(modalWindow instanceof Element)) {
                    return;
                }
        
                const useWesternNumerals = this.resolveControlPanelWesternNumeralState();
                const preserveHarakat = this.resolveControlPanelPreserveHarakatState();
                const sliderTargets = modalWindow.querySelectorAll(`[data-control-panel-main-text-size-slider] .noUi-value,[data-control-panel-main-text-size-slider] .noUi-tooltip,[data-control-panel-main-text-size-slider] [class*='slider'][class*='value'],[data-control-panel-main-text-size-slider] [aria-valuetext]`);
        
                sliderTargets.forEach((element) => {
                    if (!(element instanceof Element)) {
                        return;
                    }
        
                    if (element.hasAttribute('aria-valuetext')) {
                        const ariaValueText = String(element.getAttribute('aria-valuetext') ?? '');
                        const convertedAriaValueText = this.convertControlPanelDisplayText(ariaValueText, {
                            useWesternNumerals,
                            preserveHarakat,
                        });
        
                        if (convertedAriaValueText !== ariaValueText) {
                            element.setAttribute('aria-valuetext', convertedAriaValueText);
                        }
                    }
        
                    const originalText = String(element.textContent ?? '');
        
                    if (!/[0-9٠-٩]/.test(originalText)) {
                        return;
                    }
        
                    const convertedText = this.convertControlPanelDisplayText(originalText, {
                        useWesternNumerals,
                        preserveHarakat,
                        preserveFixedSamples: true,
                    });
        
                    if (convertedText !== originalText) {
                        element.textContent = convertedText;
                    }
                });
            },
            syncControlPanelFieldNumerals() {
                const modalWindow = this.resolveControlPanelModalWindow();
        
                if (
                    !(modalWindow instanceof Element) ||
                    typeof document === 'undefined' ||
                    typeof window.NodeFilter === 'undefined'
                ) {
                    return;
                }
        
                const useWesternNumerals = this.resolveControlPanelWesternNumeralState();
                const preserveHarakat = this.resolveControlPanelPreserveHarakatState();
                const textNodes = [];
                const walker = document.createTreeWalker(
                    modalWindow,
                    window.NodeFilter.SHOW_TEXT, {
                        acceptNode: (node) => {
                            const parent = node?.parentElement;
        
                            if (!(parent instanceof Element)) {
                                return window.NodeFilter.FILTER_REJECT;
                            }
        
                            if (
                                parent.closest(
                                    '[data-control-panel-main-text-size-slider] .noUi-value, [data-control-panel-main-text-size-slider] .noUi-tooltip, [data-control-panel-main-text-size-slider] [class*=\'slider\'][class*=\'value\']',
                                )
                            ) {
                                return window.NodeFilter.FILTER_REJECT;
                            }
        
                            if (parent.closest('script, style')) {
                                return window.NodeFilter.FILTER_REJECT;
                            }
        
                            if (parent.closest('[data-control-panel-ordinal-managed=\'true\']')) {
                                return window.NodeFilter.FILTER_REJECT;
                            }
        
                            const rawText = String(node.nodeValue ?? '');
        
                            if (rawText.trim() === '') {
                                return window.NodeFilter.FILTER_REJECT;
                            }
        
                            return window.NodeFilter.FILTER_ACCEPT;
                        },
                    },
                );
        
                let currentNode = walker.nextNode();
        
                while (currentNode) {
                    textNodes.push(currentNode);
                    currentNode = walker.nextNode();
                }
        
                textNodes.forEach((node) => {
                    const capturedOriginalText = this.fieldTextOriginalValues.has(node) ?
                        String(this.fieldTextOriginalValues.get(node) ?? '') :
                        String(node.nodeValue ?? '');
        
                    if (!this.fieldTextOriginalValues.has(node)) {
                        this.fieldTextOriginalValues.set(node, capturedOriginalText);
                    }
        
                    const convertedText = this.convertControlPanelDisplayText(capturedOriginalText, {
                        useWesternNumerals,
                        preserveHarakat,
                        preserveFixedSamples: true,
                    });
                    const currentText = String(node.nodeValue ?? '');
        
                    if (convertedText !== currentText) {
                        node.nodeValue = convertedText;
                    }
                });
            },
            syncControlPanelNumerals() {
                this.syncControlPanelSliderNumerals();
                this.syncControlPanelFieldNumerals();
                this.syncQuranCopyInteractionHint();
                this.syncQuranWirdSettingOrdinalLabel();
            },
            syncQuranCopyInteractionHint() {
                const modalWindow = this.resolveControlPanelModalWindow();
        
                if (!(modalWindow instanceof Element)) {
                    return;
                }
        
                const copyTargetToggle = modalWindow.querySelector(
                    `input[type='checkbox'][name*='does_quran_target_words_by_default'],input[type='checkbox'][wire\\:model*='does_quran_target_words_by_default']`,
                );
        
                if (!(copyTargetToggle instanceof Element)) {
                    return;
                }
        
                const fieldWrapper = copyTargetToggle.closest(
                    '.fi-fo-field-wrp, .fi-field-wrp, [data-field-wrapper]',
                );
        
                if (!(fieldWrapper instanceof Element)) {
                    return;
                }
        
                const helperElement = fieldWrapper.querySelector(
                    '.fi-fo-field-wrp-helper-text, .fi-fo-field-wrp-hint',
                );
        
                if (!(helperElement instanceof HTMLElement)) {
                    return;
                }
        
                const isBaseBreakpoint = Boolean(this.$store?.bp?.is?.('base'));
                helperElement.textContent = isBaseBreakpoint ?
                    this.quranCopyHintMobile :
                    this.quranCopyHintDesktop;
            },
            syncQuranWirdSettingOrdinalLabel() {
                const modalWindow = this.resolveControlPanelModalWindow();
        
                if (!(modalWindow instanceof Element)) {
                    return;
                }
        
                const managedWirdWrapper = modalWindow.querySelector('.quran-wird-group-field');
        
                if (!(managedWirdWrapper instanceof Element)) {
                    return;
                }
        
                const labelCandidates = Array.from(
                    managedWirdWrapper.querySelectorAll(
                        '.fi-fo-field-wrp-label, .fi-field-wrp-label, label, legend',
                    ),
                );
                const labelElement =
                    labelCandidates.find((candidate) => {
                        if (!(candidate instanceof HTMLElement)) {
                            return false;
                        }
        
                        return (
                            !candidate.closest('.quran-wird-frequency-field') &&
                            !candidate.closest('.quran-wird-khatmat-field')
                        );
                    }) ?? null;
        
                if (!(labelElement instanceof HTMLElement)) {
                    return;
                }
        
                const resolveLeadingLabelTextNode = (element) => {
                    if (!(element instanceof Element)) {
                        return null;
                    }
        
                    const directTextNode = Array.from(element.childNodes).find((node) =>
                        node?.nodeType === window.Node.TEXT_NODE &&
                        String(node.nodeValue ?? '').trim() !== ''
                    );
        
                    if (directTextNode) {
                        return directTextNode;
                    }
        
                    return Array.from(element.querySelectorAll('*'))
                        .map((candidate) => Array.from(candidate.childNodes))
                        .flat()
                        .find((node) =>
                            node?.nodeType === window.Node.TEXT_NODE &&
                            String(node.nodeValue ?? '').trim() !== ''
                        ) ?? null;
                };
        
                const leadingLabelTextNode = resolveLeadingLabelTextNode(labelElement);
        
                if (!(leadingLabelTextNode instanceof window.Text)) {
                    return;
                }
        
                const savedBaseLabel = String(labelElement.dataset.quranWirdBaseLabel ?? '').trim();
                const computedBaseLabel = savedBaseLabel !== '' ?
                    savedBaseLabel :
                    String(leadingLabelTextNode.nodeValue ?? '')
                    .replace(/^\s*[0-9٠-٩]+\.\s*/u, '')
                    .trim();
        
                if (computedBaseLabel === '') {
                    return;
                }
        
                const useWesternNumerals = this.resolveControlPanelWesternNumeralState();
                const preserveHarakat = this.resolveControlPanelPreserveHarakatState();
                const immersiveCaptionSettingToggle = modalWindow.querySelector(
                    `input[type='checkbox'][name*='does_quran_show_immersive_mobile_edge_captions'],input[type='checkbox'][wire\\:model*='does_quran_show_immersive_mobile_edge_captions']`,
                );
                const immersiveCaptionSettingWrapper =
                    immersiveCaptionSettingToggle?.closest(
                        '.fi-fo-field-wrp, .fi-field-wrp, [data-field-wrapper]',
                    ) ?? null;
        
                labelElement.dataset.quranWirdBaseLabel = computedBaseLabel;
                labelElement.dataset.controlPanelOrdinalManaged = 'true';
                const resolvedOrdinal = '5.';
                const nextLabel = `${resolvedOrdinal} ${computedBaseLabel}`;
                const nextLabelVisual = this.convertControlPanelDisplayText(nextLabel, {
                    useWesternNumerals,
                    preserveHarakat,
                    preserveFixedSamples: true,
                });
        
                if (String(leadingLabelTextNode.nodeValue ?? '').trim() !== nextLabelVisual) {
                    leadingLabelTextNode.nodeValue = ` ${nextLabelVisual} `;
                }
        
                if (!(immersiveCaptionSettingWrapper instanceof Element)) {
                    return;
                }
        
                const immersiveLabelElement = immersiveCaptionSettingWrapper.querySelector(
                    '.fi-fo-field-wrp-label, .fi-field-wrp-label, label, legend',
                );
        
                if (!(immersiveLabelElement instanceof HTMLElement)) {
                    return;
                }
        
                const immersiveLeadingTextNode = resolveLeadingLabelTextNode(immersiveLabelElement);
        
                if (!(immersiveLeadingTextNode instanceof window.Text)) {
                    return;
                }
        
                const savedImmersiveBaseLabel = String(
                    immersiveLabelElement.dataset.immersiveBaseLabel ?? '',
                ).trim();
                const computedImmersiveBaseLabel = savedImmersiveBaseLabel !== '' ?
                    savedImmersiveBaseLabel :
                    String(immersiveLeadingTextNode.nodeValue ?? '')
                    .replace(/^\s*[0-9٠-٩]+\.\s*/u, '')
                    .trim();
        
                if (computedImmersiveBaseLabel === '') {
                    return;
                }
        
                immersiveLabelElement.dataset.immersiveBaseLabel = computedImmersiveBaseLabel;
        
                if (immersiveCaptionSettingWrapper.offsetParent === null) {
                    return;
                }
        
                const immersiveLabelVisual = this.convertControlPanelDisplayText(
                    `6. ${computedImmersiveBaseLabel}`, {
                        useWesternNumerals,
                        preserveHarakat,
                        preserveFixedSamples: true,
                    },
                );
        
                if (String(immersiveLeadingTextNode.nodeValue ?? '').trim() !== immersiveLabelVisual) {
                    immersiveLeadingTextNode.nodeValue = ` ${immersiveLabelVisual} `;
                }
            },
            teardownControlPanelSliderNumeralsObserver() {
                if (this.sliderNumeralsObserver) {
                    this.sliderNumeralsObserver.disconnect();
                    this.sliderNumeralsObserver = null;
                }
            },
            setupControlPanelSliderNumeralsObserver() {
                this.teardownControlPanelSliderNumeralsObserver();
        
                const modalWindow = this.resolveControlPanelModalWindow();
        
                if (!(modalWindow instanceof Element) || typeof MutationObserver === 'undefined') {
                    return;
                }
        
                this.sliderNumeralsObserver = new MutationObserver(() => {
                    this.queueControlPanelSliderNumeralsSync(10);
                });
        
                this.sliderNumeralsObserver.observe(modalWindow, {
                    childList: true,
                    subtree: true,
                    characterData: true,
                    attributes: true,
                    attributeFilter: ['aria-valuetext'],
                });
            },
            queueControlPanelSliderNumeralsSync(delayMs = 0) {
                if (this.sliderNumeralsSyncTimer !== null) {
                    clearTimeout(this.sliderNumeralsSyncTimer);
                    this.sliderNumeralsSyncTimer = null;
                }
        
                this.sliderNumeralsSyncTimer = window.setTimeout(() => {
                    this.sliderNumeralsSyncTimer = null;
                    this.syncControlPanelNumerals();
                }, Math.max(0, Number(delayMs) || 0));
            },
            closeVisibleFilamentModals({ exceptIds = [] } = {}) {
                if (typeof document === 'undefined') {
                    return;
                }
        
                const excludedModalIds = new Set(
                    (Array.isArray(exceptIds) ? exceptIds : [])
                    .map((id) => String(id ?? '').trim())
                    .filter((id) => id !== ''),
                );
                const modalElements = Array.from(
                    document.querySelectorAll('[data-fi-modal-id], .fi-modal-window[id]'),
                );
                const openModalIds = new Set();
        
                modalElements.forEach((modalElement) => {
                    if (!(modalElement instanceof Element)) {
                        return;
                    }
        
                    const modalId = String(
                        modalElement.getAttribute('data-fi-modal-id') ??
                        modalElement.getAttribute('id') ??
                        '',
                    ).trim();
        
                    if (modalId === '' || excludedModalIds.has(modalId)) {
                        return;
                    }
        
                    const modalWindow = modalElement.querySelector('.fi-modal-window');
                    const modalRoot = modalElement.classList.contains('fi-modal') ?
                        modalElement :
                        modalElement.closest('.fi-modal');
                    const isOpen =
                        modalElement.classList.contains('fi-modal-open') ||
                        modalRoot?.classList.contains('fi-modal-open') ||
                        modalElement.getAttribute('aria-hidden') === 'false' ||
                        modalWindow?.getAttribute('aria-hidden') === 'false';
        
                    if (!isOpen) {
                        return;
                    }
        
                    openModalIds.add(modalId);
                });
        
                openModalIds.forEach((modalId) => {
                    const closePayload = { id: modalId };
                    window.dispatchEvent(new CustomEvent('close-modal-quietly', { detail: closePayload }));
                    window.dispatchEvent(new CustomEvent('close-modal', { detail: closePayload }));
                });
            },
            isControlPanelEventReceiverVisible() {
                if (!(this.$el instanceof HTMLElement)) {
                    return false;
                }
        
                const elementStyles = window.getComputedStyle(this.$el);
        
                if (
                    elementStyles.display === 'none' ||
                    elementStyles.visibility === 'hidden'
                ) {
                    return false;
                }
        
                return this.$el.getClientRects().length > 0;
            },
            openControlPanelModalFromEvent(detail = {}) {
                $wire.openControlPanelModal(
                    window.getAthkarSettingsFromStorage?.() ?? {},
                    detail?.tab ?? null,
                );
        
                window.setTimeout(() => {
                    if (this.isControlPanelModalCurrentlyOpen()) {
                        isControlPanelOpen = true;
                        this.boostControlPanelModalLayer();
                        this.setupControlPanelSliderNumeralsObserver();
                        this.queueControlPanelSliderNumeralsSync(40);
                    }
                }, 120);
            },
            openSupportUnlockModalFromEvent() {
                this.openSupportUnlockModal();
            },
            async openSupportUnlockModal() {
                this.closeVisibleFilamentModals();
        
                const closePayload = { id: this.controlPanelModalId };
                window.dispatchEvent(new CustomEvent('close-modal-quietly', { detail: closePayload }));
                window.dispatchEvent(new CustomEvent('close-modal', { detail: closePayload }));
        
                try {
                    await $wire.unmountAction(false);
                } catch (_) {
                    //
                }
        
                await new Promise((resolve) => window.setTimeout(resolve, 220));
                await $wire.mountAction('supportUnlock');
            },
            async runReaderMaintenancePulse() {
                if (this.isReaderMaintenanceInFlight) {
                    this.hasQueuedReaderMaintenance = true;
        
                    return;
                }
        
                if (
                    isControlPanelOpen ||
                    isAthkarManagerOpen ||
                    this.$store?.layoutManager?.isActionOpen
                ) {
                    return;
                }
        
                this.isReaderMaintenanceInFlight = true;
        
                try {
                    await $wire.triggerReaderMaintenancePulse();
                } finally {
                    this.isReaderMaintenanceInFlight = false;
        
                    if (this.hasQueuedReaderMaintenance) {
                        this.hasQueuedReaderMaintenance = false;
        
                        queueMicrotask(() => this.runReaderMaintenancePulse());
                    }
                }
            },
        }"
        x-init="queueControlPanelSliderNumeralsSync(10)"
        x-on:open-control-panel-modal.window="openControlPanelModalFromEvent($event.detail ?? {})"
        x-on:open-support-unlock-modal.window="openSupportUnlockModalFromEvent()"
        x-on:athkar-reader-maintenance.window="runReaderMaintenancePulse()"
        x-on:control-panel-updated.window="queueControlPanelSliderNumeralsSync(0)"
        x-on:resize.window="if (isControlPanelOpen) { queueControlPanelSliderNumeralsSync(0); }"
        x-on:change.window="if (resolveControlPanelModalWindow()?.contains($event.target)) { queueControlPanelSliderNumeralsSync(0); }"
        x-on:input.window="if (resolveControlPanelModalWindow()?.contains($event.target)) { queueControlPanelSliderNumeralsSync(0); }"
        x-on:x-modal-opened.window="if (isControlPanelModalEvent($event.detail ?? {})) { isControlPanelOpen = true; boostControlPanelModalLayer(); setupControlPanelSliderNumeralsObserver(); queueControlPanelSliderNumeralsSync(40); }"
        x-on:opened-form-component-action-modal.window="if (isControlPanelModalEvent($event.detail ?? {})) { isControlPanelOpen = true; boostControlPanelModalLayer(); setupControlPanelSliderNumeralsObserver(); queueControlPanelSliderNumeralsSync(40); }"
        x-on:close-modal.window="if (String($event.detail?.id ?? '') === controlPanelModalId || !isControlPanelModalCurrentlyOpen()) { isControlPanelOpen = false; teardownControlPanelSliderNumeralsObserver(); }"
        x-on:close-modal-quietly.window="if (String($event.detail?.id ?? '') === controlPanelModalId || !isControlPanelModalCurrentlyOpen()) { isControlPanelOpen = false; teardownControlPanelSliderNumeralsObserver(); }"
        x-on:closed-form-component-action-modal.window="if (String($event.detail?.id ?? '') === controlPanelModalId || !isControlPanelModalCurrentlyOpen()) { isControlPanelOpen = false; teardownControlPanelSliderNumeralsObserver(); }"
    >
        <x-action-button
            data-testid="control-panel-button"
            :useInvertedStyle="true"
            :iconName="'heroicon-s-adjustments-horizontal'"
            x-on:click="window.dispatchEvent(new CustomEvent('request-open-control-panel-modal'))"
        />
    </div>

    <x-filament-actions::modals />
</div>
