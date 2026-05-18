export const createOverflowOriginModule = (deps) => {
    const {
        athkarOverridesStorageKey,
        migrateSettingsOverrides,
        normalizeAthkarDefaults,
        normalizeAthkarOverrides,
        readAthkarOverridesFromStorage,
        readAthkarSettingsFromStorage,
        resolveAthkarWithOverrides,
        resolveEffectiveSettings,
        writeAthkarOverridesToStorage,
        writeAthkarSettingsToStorage,
        writeUserSettingOverride,
        createShimmerController,
        doesEnableVisualEnhancementsKey,
        skipGuidancePanelsSettingKey,
        progressStorageKey,
        supportUnlockStorageKey,
        supportUnlockModePermanent,
        supportUnlockModeWeekly,
        athkarCopyHoldDelayMs,
        athkarCopyHoldMoveThresholdPx,
        athkarCopyPopoverVisibleDurationMs,
        defaultProgressState,
        emptyProgressStats,
        resolveProgressStatsSafely,
        readProgressFromStorage,
    } = deps;

    return {
        get activeLabel() {
            return this.activeMode === 'sabah' ? 'أذكار الصباح' : 'أذكار المساء';
        },

        defaultType() {
            const [firstType] = Object.keys(this.typeLabels ?? {});

            return firstType ?? 'glorification';
        },

        typeLabelFor(type) {
            const normalizedType = String(type ?? this.defaultType());

            return (
                this.typeLabels?.[normalizedType] ?? this.typeLabels?.[this.defaultType()] ?? 'عام'
            );
        },

        activeTypeLabel(index) {
            return this.typeLabelFor(this.activeList?.[index]?.type);
        },

        scrollLayerKey(index, target, mode = this.activeMode) {
            if (!mode) {
                return null;
            }

            const normalizedIndex = Number(index ?? -1);

            if (!Number.isFinite(normalizedIndex) || normalizedIndex < 0) {
                return null;
            }

            const normalizedTarget = target === 'origin' ? 'origin' : 'text';
            const itemId = this.getModeList(mode)?.[normalizedIndex]?.id ?? normalizedIndex;

            return `${mode}:${itemId}:${normalizedTarget}`;
        },

        rememberScrollOffset(index, target, scrollTop) {
            const key = this.scrollLayerKey(index, target);

            if (!key) {
                return;
            }

            this.layerScrollOffsets[key] = Math.max(0, Number(scrollTop) || 0);
        },

        resolveRememberedScrollOffset(index, target) {
            const key = this.scrollLayerKey(index, target);

            if (!key) {
                return 0;
            }

            return Math.max(0, Number(this.layerScrollOffsets?.[key]) || 0);
        },

        resetLayerScrollOffsets({ syncActiveBox = true } = {}) {
            this.layerScrollOffsets = {};

            if (!syncActiveBox) {
                return;
            }

            const activeSlide = this.$el?.querySelector('[data-athkar-slide][data-active="true"]');
            const box = activeSlide?.querySelector('[data-athkar-text-box]');

            if (!box) {
                return;
            }

            box.scrollTop = 0;
            this.syncTextBoxEdgeFade(box);
        },

        rememberVisibleTextBoxScroll(index = this.activeIndex) {
            const activeSlide = this.$el?.querySelector('[data-athkar-slide][data-active="true"]');
            const box = activeSlide?.querySelector('[data-athkar-text-box]');

            if (!box) {
                return;
            }

            const target = box.dataset.athkarScrollTarget === 'origin' ? 'origin' : 'text';
            this.rememberScrollOffset(index, target, box.scrollTop);
        },

        resolveOverflowPaddingClasses(box) {
            if (!box) {
                return [];
            }

            const classes = new Set();
            const targets = box.querySelectorAll?.('[data-fitty-target]');

            targets?.forEach((node) => {
                const value = String(node?.dataset?.fittyOverflowPaddingClass ?? 'py-2').trim();

                if (value) {
                    classes.add(value);
                }
            });

            return Array.from(classes);
        },

        syncOverflowPaddingClass({ box, target, isOverflowing }) {
            if (!box) {
                return;
            }

            const paddingClasses = this.resolveOverflowPaddingClasses(box);

            if (!paddingClasses.length) {
                return;
            }

            if (!isOverflowing) {
                paddingClasses.forEach((className) => box.classList.remove(className));

                return;
            }

            const activeSlide = this.$el?.querySelector('[data-athkar-slide][data-active="true"]');
            const activeText = activeSlide?.querySelector(
                target === 'origin' ? '[data-athkar-origin-text]' : '[data-athkar-text]',
            );
            const activePaddingClass = String(
                activeText?.dataset?.fittyOverflowPaddingClass ?? 'py-2',
            ).trim();

            paddingClasses.forEach((className) => {
                box.classList.toggle(className, className === activePaddingClass);
            });
        },

        resolveTextBoxEdgeFadeSize(box) {
            if (!box) {
                return '1rem';
            }

            const computedSize = getComputedStyle(box)
                .getPropertyValue('--athkar-edge-fade-size')
                .trim();

            if (computedSize !== '') {
                return computedSize;
            }

            return '1rem';
        },

        syncTextBoxEdgeFade(box) {
            if (!box) {
                return;
            }

            const isTouchScrollEnabled =
                box.dataset.athkarTouchScroll === 'true' &&
                box.classList.contains('athkar-text-box--touch-scroll');

            if (!isTouchScrollEnabled) {
                box.style.setProperty('--athkar-edge-fade-top-size', '0px');
                box.style.setProperty('--athkar-edge-fade-bottom-size', '0px');
                return;
            }

            const maxScrollTop = Math.max(0, box.scrollHeight - box.clientHeight);
            const edgeTolerance = 1;

            if (maxScrollTop <= edgeTolerance) {
                box.style.setProperty('--athkar-edge-fade-top-size', '0px');
                box.style.setProperty('--athkar-edge-fade-bottom-size', '0px');
                return;
            }

            const fadeSize = this.resolveTextBoxEdgeFadeSize(box);
            const scrollTop = Math.max(0, Number(box.scrollTop) || 0);
            const isAtTop = scrollTop <= edgeTolerance;
            const isAtBottom = scrollTop >= maxScrollTop - edgeTolerance;

            box.style.setProperty('--athkar-edge-fade-top-size', isAtTop ? '0px' : fadeSize);
            box.style.setProperty('--athkar-edge-fade-bottom-size', isAtBottom ? '0px' : fadeSize);
        },

        syncTextBoxEdgeFadeFromEvent(event) {
            const box = event?.currentTarget;

            if (!(box instanceof HTMLElement)) {
                return;
            }

            const slide = box.closest?.('[data-athkar-slide]');
            const parsedIndex = Number(slide?.dataset?.index ?? this.activeIndex);
            const index = Number.isFinite(parsedIndex) ? Math.max(0, Math.trunc(parsedIndex)) : 0;
            const target = box.dataset.athkarScrollTarget === 'origin' ? 'origin' : 'text';
            const maxScrollTop = Math.max(0, box.scrollHeight - box.clientHeight);
            const edgeTolerance = 1;
            const normalizedScrollTop = Math.max(
                0,
                Math.min(maxScrollTop, Number(box.scrollTop) || 0),
            );
            const resolvedScrollTop =
                normalizedScrollTop <= edgeTolerance ? 0 : normalizedScrollTop;

            if (resolvedScrollTop !== box.scrollTop) {
                box.scrollTop = resolvedScrollTop;
            }

            if (this.copyHold.active && this.copyHold.source !== 'touch') {
                this.resetHoldCopyState();
            }

            this.rememberScrollOffset(index, target, resolvedScrollTop);
            this.syncTextBoxEdgeFade(box);
        },

        applyVisibleTextBoxScrollState({ box, index, target, isOverflowing }) {
            if (!box) {
                return;
            }

            if (!isOverflowing) {
                box.scrollTop = 0;
                this.rememberScrollOffset(index, target, 0);
                window.requestAnimationFrame(() => {
                    if (document.contains(box) && box.dataset.athkarTouchScroll !== 'true') {
                        box.scrollTop = 0;
                        this.syncTextBoxEdgeFade(box);
                    }
                });
                this.syncTextBoxEdgeFade(box);

                return;
            }

            const maxScrollTop = Math.max(0, box.scrollHeight - box.clientHeight);
            const remembered = this.resolveRememberedScrollOffset(index, target);
            const edgeTolerance = 1;
            const normalizedRemembered = Math.max(0, Math.min(maxScrollTop, remembered));
            const resolvedScrollTop =
                normalizedRemembered <= edgeTolerance ? 0 : normalizedRemembered;

            box.scrollTop = resolvedScrollTop;
            this.rememberScrollOffset(index, target, resolvedScrollTop);
            this.syncTextBoxEdgeFade(box);
        },

        hasOrigin(index) {
            const item = this.activeList?.[index];
            const normalizedOrigin = String(item?.origin ?? '').trim();

            return normalizedOrigin.length > 0 || Boolean(item?.is_original);
        },

        isOriginVisible(index) {
            return this.originToggle.mode === this.activeMode && this.originToggle.index === index;
        },

        isOriginOverflowVisible(index) {
            const hasExplicitOriginOverflow =
                this.originOverflowToggle.mode === this.activeMode &&
                this.originOverflowToggle.index === index;

            if (hasExplicitOriginOverflow) {
                return true;
            }

            if (
                this.originOverflowToggle.mode === null &&
                this.originOverflowToggle.index === null
            ) {
                return this.isOriginVisible(index);
            }

            return false;
        },

        clearOriginTransitionTimer() {
            if (this._originTransitionTimer !== null) {
                clearTimeout(this._originTransitionTimer);
                this._originTransitionTimer = null;
            }
        },

        isOriginTransitionFor(index) {
            return (
                this.originTransition.mode === this.activeMode &&
                this.originTransition.index === index &&
                this.originTransition.phase !== 'idle'
            );
        },

        shouldHideMainTextLayer(index) {
            if (this.isOriginTransitionFor(index)) {
                if (
                    this.originTransition.phase === 'out' ||
                    this.originTransition.phase === 'prep'
                ) {
                    return true;
                }

                if (this.originTransition.phase === 'in') {
                    return this.originTransition.toIsOrigin === true;
                }
            }

            return this.isOriginVisible(index);
        },

        shouldShowOriginTextLayer(index) {
            if (this.isOriginTransitionFor(index)) {
                if (
                    this.originTransition.phase === 'out' ||
                    this.originTransition.phase === 'prep'
                ) {
                    return false;
                }

                if (this.originTransition.phase === 'in') {
                    return this.originTransition.toIsOrigin === true;
                }
            }

            return this.isOriginVisible(index);
        },

        startOriginTransition(index, toIsOrigin) {
            const parsedIndex = Number(index ?? this.activeIndex ?? 0);
            const normalizedIndex = Number.isFinite(parsedIndex)
                ? Math.max(0, Math.trunc(parsedIndex))
                : 0;
            const fromIsOrigin = this.isOriginVisible(normalizedIndex);
            const fadeOutDuration = Math.max(0, Number(this.originFadeDurationMs) || 0);
            const prepDuration = Math.max(0, Number(this.originResyncDelayMs) || 0);

            this.clearOriginTransitionTimer();
            this.originTransition = {
                mode: this.activeMode,
                index: normalizedIndex,
                fromIsOrigin,
                toIsOrigin,
                phase: 'out',
            };

            const beginPreparePhase = () => {
                if (!this.isOriginTransitionFor(normalizedIndex)) {
                    return;
                }

                this.originTransition = {
                    ...this.originTransition,
                    phase: 'prep',
                };

                this.originToggle = toIsOrigin
                    ? {
                          mode: this.activeMode,
                          index: normalizedIndex,
                      }
                    : {
                          mode: null,
                          index: null,
                      };
                this.originOverflowToggle = toIsOrigin
                    ? {
                          mode: this.activeMode,
                          index: normalizedIndex,
                      }
                    : {
                          mode: null,
                          index: null,
                      };

                this.$nextTick(() => {
                    this.syncVisibleTextBoxState(normalizedIndex);
                    this.stopTextShimmer();
                    this.queueReaderTextFit();
                    this.setupTextShimmer(null, { immediate: true });
                });

                const beginFadeInPhase = () => {
                    if (!this.isOriginTransitionFor(normalizedIndex)) {
                        return;
                    }

                    this.originTransition = {
                        ...this.originTransition,
                        phase: 'in',
                    };

                    this.$nextTick(() => this.setupTextShimmer(null, { immediate: true }));
                    this._originTransitionTimer = window.setTimeout(() => {
                        this.clearOriginTransitionTimer();
                        this.originTransition = {
                            mode: null,
                            index: null,
                            fromIsOrigin: null,
                            toIsOrigin: null,
                            phase: 'idle',
                        };
                    }, fadeOutDuration);
                };

                this._originTransitionTimer = window.setTimeout(beginFadeInPhase, prepDuration);
            };

            this._originTransitionTimer = window.setTimeout(beginPreparePhase, fadeOutDuration);
        },

        toggleOrigin(index) {
            if (!this.hasOrigin(index)) {
                return;
            }

            this.rememberVisibleTextBoxScroll(index);

            this.startOriginTransition(index, !this.isOriginVisible(index));
        },

        syncVisibleTextBoxState(index = this.activeIndex) {
            const activeSlide = this.$el?.querySelector('[data-athkar-slide][data-active="true"]');
            const box = activeSlide?.querySelector('[data-athkar-text-box]');

            if (!box) {
                return;
            }

            const isOriginTarget = this.isOriginOverflowVisible(index);
            const target = isOriginTarget ? 'origin' : 'text';
            const isOverflowing = isOriginTarget
                ? box.dataset.athkarOriginOverflow === 'true'
                : box.dataset.athkarTextOverflow === 'true';

            box.dataset.athkarScrollTarget = target;
            box.dataset.athkarTouchScroll = isOverflowing ? 'true' : 'false';
            box.dataset.athkarTouchOverflow = isOverflowing ? 'true' : 'false';
            box.classList.toggle('athkar-text-box--touch-scroll', isOverflowing);
            box.classList.toggle('athkar-text-box--origin-scroll', isOverflowing && isOriginTarget);
            this.syncOverflowPaddingClass({ box, target, isOverflowing });

            this.applyVisibleTextBoxScrollState({ box, index, target, isOverflowing });
            this.syncTextBoxEdgeFade(box);
        },

        hideOrigin() {
            this.clearOriginTransitionTimer();

            this.originToggle = {
                mode: null,
                index: null,
            };
            this.originOverflowToggle = {
                mode: null,
                index: null,
            };
            this.originTransition = {
                mode: null,
                index: null,
                fromIsOrigin: null,
                toIsOrigin: null,
                phase: 'idle',
            };
        },

        requestSingleThikrCompletion(index) {
            if (!this.activeMode) {
                return;
            }

            const normalizedIndex = Number(index ?? -1);

            if (!Number.isFinite(normalizedIndex) || normalizedIndex < 0) {
                return;
            }

            window.dispatchEvent(
                new CustomEvent('athkar-open-single-completion', {
                    detail: { index: normalizedIndex },
                }),
            );
        },

        isOriginalThikr(index) {
            return this.hasOrigin(index);
        },
    };
};
