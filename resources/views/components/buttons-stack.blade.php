@props([
    'horizontal' => 'left',
    'vertical' => 'top',
    'inactiveGap' => 1.2,
    'activeGap' => 1.6,
    'activeNeighborGap' => 2.4,
    'stackTopOffset' => null,
])

@assets
    {{-- Scale only the stacked buttons, never their inline Filament modals (siblings of the stack items, so excluded). --}}
    {{-- Use `scale` (a transform) not `zoom`: WebKit/iOS won't re-layout a dynamic `zoom` var over already-zoomed children, and `scale` is a separate longhand from the stack JS `transform`. --}}
    <style>
        [data-stack-item] {
            scale: var(--ui-scale, 1);
            /* Fade show/hide instead of snapping; stacked mode overrides this inline. */
            transition: opacity 220ms ease;
        }
    </style>
@endassets

<div
    {{ $attributes }}
    x-data="{
        horizontal: @js($horizontal),
        vertical: @js($vertical),
        inactiveGap: @js($inactiveGap),
        activeGap: @js($activeGap),
        activeNeighborGap: @js($activeNeighborGap),
        stackTopOffset: @js($stackTopOffset),
        respectingStack: false,
        isQuickStackOpen: false,
        activeIndex: 0,
        items: [],
        observer: null,
        attributeObserver: null,
        modalObserver: null,
        layoutFrameId: null,
        isLayoutQueued: false,
        pendingLayoutPasses: 0,
        shouldWaitForNextTick: false,
        interactionUnlockId: null,
        actionStateRecoveryId: null,
        isInteractionLocked: false,
        actionOpenState: false,
        isQuranManagerModalOpen: false,
        stackTransitionMs: 200,
        modalHideGraceMs: 800,
        wasHidingStackItems: false,
        hideStackItemsGraceUntil: 0,
        showStackItemsTimerId: null,
        shouldManageDisplay(item) {
            if (!item) {
                return false;
            }
    
            return !item.hasAttribute('x-show') && !item.hasAttribute('x-cloak');
        },
        isItemVisible(item) {
            if (!item || !item.isConnected) {
                return false;
            }
    
            if (item.hidden || item.hasAttribute('x-cloak')) {
                return false;
            }
    
            const styles = window.getComputedStyle(item);
    
            return styles.display !== 'none' && styles.visibility !== 'hidden';
        },
        visibleItems() {
            return this.items.filter((item) => this.isItemVisible(item));
        },
        init() {
            this.refreshItems();
            this.bindClickHandler();
            this.observeItems();
            this.observeRespecting();
            this.observeModalVisibility();
            this.setRespectingStack();
            this.syncQuranManagerModalStateFromDom();
            this.scheduleLayout(3);
        },
        destroy() {
            if (this.observer) {
                this.observer.disconnect();
            }
    
            if (this.attributeObserver) {
                this.attributeObserver.disconnect();
            }
    
            if (this.modalObserver) {
                this.modalObserver.disconnect();
            }
    
            if (this.$refs.stack) {
                this.$refs.stack.removeEventListener('click', this.handleClick, true);
            }
    
            if (this.layoutFrameId !== null) {
                window.cancelAnimationFrame(this.layoutFrameId);
            }
    
            if (this.actionStateRecoveryId !== null) {
                window.clearTimeout(this.actionStateRecoveryId);
                this.actionStateRecoveryId = null;
            }
    
            if (this.showStackItemsTimerId !== null) {
                window.clearTimeout(this.showStackItemsTimerId);
                this.showStackItemsTimerId = null;
            }
    
            this.releaseInteractionLock();
        },
        setRespectingStack() {
            const nextState = String(this.$el.dataset.respectingStack) === 'true';
    
            if (this.respectingStack === nextState) {
                return;
            }
    
            this.respectingStack = nextState;
    
            if (!this.respectingStack) {
                this.closeQuickStack();
                this.releaseInteractionLock();
            }
        },
        observeRespecting() {
            this.attributeObserver = new MutationObserver(() => {
                this.setRespectingStack();
                this.scheduleLayout(3);
            });
    
            this.attributeObserver.observe(this.$el, {
                attributes: true,
                attributeFilter: ['data-respecting-stack'],
            });
        },
        observeModalVisibility() {
            if (typeof MutationObserver === 'undefined' || !(document.body instanceof Element)) {
                return;
            }
    
            this.modalObserver = new MutationObserver((mutations) => {
                const hasModalMutation = mutations.some((mutation) => {
                    if (!(mutation.target instanceof Element)) {
                        return false;
                    }
    
                    if (
                        mutation.target.classList.contains('fi-modal') ||
                        mutation.target.classList.contains('fi-modal-window')
                    ) {
                        return true;
                    }
    
                    return mutation.target.closest('.fi-modal') !== null;
                });
    
                if (!hasModalMutation) {
                    return;
                }
    
                this.scheduleQuranManagerModalStateSync();
            });
    
            this.modalObserver.observe(document.body, {
                subtree: true,
                childList: true,
                attributes: true,
                attributeFilter: ['class', 'style'],
            });
        },
        refreshItems() {
            const flagged = Array.from(
                this.$refs.stack.querySelectorAll('[data-stack-item]'),
            );
    
            this.items = flagged.length ?
                flagged :
                Array.from(this.$refs.stack.children);
    
            this.items.forEach((el, index) => {
                el.dataset.stackIndex = index;
                el.dataset.stackItem = '';
            });
        },
        observeItems() {
            this.observer = new MutationObserver((mutations) => {
                if (!this.hasRelevantMutation(mutations)) {
                    return;
                }
    
                this.refreshItems();
    
                // Correct an external style clobber synchronously, before paint.
                const styleClobber = mutations.some(
                    (m) => m.type === 'attributes' && m.attributeName === 'style',
                );
    
                if (styleClobber && this.respectingStack) {
                    this.updateLayout();
                }
    
                this.scheduleLayout(2, { afterDom: false });
            });
    
            this.observer.observe(this.$refs.stack, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['style', 'hidden', 'x-cloak'],
            });
        },
        hasRelevantMutation(mutations) {
            return mutations.some((mutation) => this.isRelevantMutation(mutation));
        },
        isRelevantMutation(mutation) {
            if (mutation.type === 'attributes') {
                return this.isRelevantAttributeMutation(mutation);
            }
    
            if (mutation.type !== 'childList') {
                return false;
            }
    
            return this.containsStackItem(mutation.target) ||
                Array.from(mutation.addedNodes).some((node) => this.containsStackItem(node)) ||
                Array.from(mutation.removedNodes).some((node) => this.containsStackItem(node));
        },
        isStackItemElement(node) {
            return node instanceof Element && node.matches('[data-stack-item]');
        },
        containsStackItem(node) {
            if (!(node instanceof Element)) {
                return false;
            }
    
            return this.isStackItemElement(node) || node.querySelector('[data-stack-item]') !== null;
        },
        isRelevantAttributeMutation(mutation) {
            if (!this.isStackItemElement(mutation.target)) {
                return false;
            }
    
            if (mutation.attributeName !== 'style') {
                return true;
            }
    
            return mutation.target.hasAttribute('x-show');
        },
        scheduleLayout(passCount = 1, { afterDom = true } = {}) {
            this.pendingLayoutPasses = Math.max(this.pendingLayoutPasses, passCount);
            this.shouldWaitForNextTick = this.shouldWaitForNextTick || afterDom;
    
            if (this.isLayoutQueued) {
                return;
            }
    
            this.isLayoutQueued = true;
    
            const queueFrame = () => {
                this.layoutFrameId = window.requestAnimationFrame(() => {
                    this.layoutFrameId = null;
                    this.isLayoutQueued = false;
    
                    const remainingPasses = this.pendingLayoutPasses;
    
                    this.pendingLayoutPasses = 0;
                    this.shouldWaitForNextTick = false;
    
                    this.refreshItems();
                    this.updateLayout();
    
                    if (remainingPasses > 1) {
                        this.scheduleLayout(remainingPasses - 1, { afterDom: false });
                    }
                });
            };
    
            if (this.shouldWaitForNextTick && typeof this.$nextTick === 'function') {
                this.$nextTick(() => {
                    if (!this.isLayoutQueued || this.layoutFrameId !== null) {
                        return;
                    }
    
                    queueFrame();
                });
    
                return;
            }
    
            queueFrame();
        },
        closeQuickStack() {
            this.isQuickStackOpen = false;
            this.activeIndex = 0;
        },
        resolveInteractionCooldownMs() {
            return Math.max(this.stackTransitionMs + 120, 320);
        },
        lockInteractions() {
            this.releaseInteractionLock();
    
            this.isInteractionLocked = true;
            this.interactionUnlockId = window.setTimeout(() => {
                this.isInteractionLocked = false;
                this.interactionUnlockId = null;
            }, this.resolveInteractionCooldownMs());
        },
        releaseInteractionLock() {
            if (this.interactionUnlockId !== null) {
                window.clearTimeout(this.interactionUnlockId);
                this.interactionUnlockId = null;
            }
    
            this.isInteractionLocked = false;
        },
        scheduleActionStateRecovery() {
            if (this.actionStateRecoveryId !== null) {
                window.clearTimeout(this.actionStateRecoveryId);
            }
    
            this.actionStateRecoveryId = window.setTimeout(() => {
                this.actionStateRecoveryId = null;
    
                if (!this.actionOpenState || this.hasAnyOpenFilamentModal()) {
                    return;
                }
    
                this.actionOpenState = false;
                this.scheduleLayout(2);
            }, 220);
        },
        syncActionState(isActionOpen) {
            const nextState = isActionOpen === true;
    
            if (this.actionOpenState === nextState) {
                if (nextState) {
                    this.scheduleActionStateRecovery();
                }
    
                return;
            }
    
            this.actionOpenState = nextState;
    
            if (this.actionOpenState) {
                this.closeQuickStack();
                this.releaseInteractionLock();
                this.scheduleActionStateRecovery();
            } else if (this.actionStateRecoveryId !== null) {
                window.clearTimeout(this.actionStateRecoveryId);
                this.actionStateRecoveryId = null;
            }
    
            this.scheduleLayout(3);
        },
        syncQuranManagerModalState(isOpen) {
            const nextState = isOpen === true;
    
            if (this.isQuranManagerModalOpen === nextState) {
                return;
            }
    
            this.isQuranManagerModalOpen = nextState;
    
            if (this.isQuranManagerModalOpen) {
                this.closeQuickStack();
                this.releaseInteractionLock();
            }
    
            this.scheduleLayout(2);
        },
        shouldHideStackItems() {
            return this.isQuranManagerModalOpen || (this.actionOpenState && this.hasAnyOpenFilamentModal());
        },
        // Hide instantly, but delay UN-hiding by a grace window measured from the
        // moment a modal CLOSES, so Filament's close-A-then-open-B nested-modal
        // handoff (both directions) never flashes the buttons in the gap.
        effectiveShouldHideStackItems() {
            if (this.shouldHideStackItems()) {
                this.wasHidingStackItems = true;
    
                if (this.showStackItemsTimerId !== null) {
                    window.clearTimeout(this.showStackItemsTimerId);
                    this.showStackItemsTimerId = null;
                }
    
                return true;
            }
    
            if (this.wasHidingStackItems) {
                this.wasHidingStackItems = false;
                this.hideStackItemsGraceUntil = Date.now() + this.modalHideGraceMs;
            }
    
            const remaining = this.hideStackItemsGraceUntil - Date.now();
    
            if (remaining > 0) {
                if (this.showStackItemsTimerId === null) {
                    this.showStackItemsTimerId = window.setTimeout(() => {
                        this.showStackItemsTimerId = null;
                        this.items.forEach((item) => this.applyStackItemVisibility(item));
                    }, remaining + 20);
                }
    
                return true;
            }
    
            return false;
        },
        applyStackItemVisibility(item) {
            if (!(item instanceof Element)) {
                return;
            }
    
            const shouldHide = this.effectiveShouldHideStackItems();
    
            this.setItemStyle(item, 'opacity', shouldHide ? '0' : '1');
            this.setItemStyle(item, 'pointerEvents', shouldHide ? 'none' : '');
        },
        hasAnyOpenFilamentModal() {
            const modal = document.querySelector('.fi-modal.fi-modal-open');
            if (!modal) return false;
            // Measure the inner window: some modals leave the outer .fi-modal at 0x0.
            const target = modal.querySelector('.fi-modal-window') || modal;
            const rect = target.getBoundingClientRect();
            if (rect.width === 0 && rect.height === 0) return false;
            const styles = window.getComputedStyle(target);
            return styles.display !== 'none' && styles.visibility !== 'hidden';
        },
        scheduleQuranManagerModalStateSync() {
            [0, 24, 64, 140].forEach((delayMs) => {
                window.setTimeout(() => {
                    this.syncQuranManagerModalStateFromDom();
                }, delayMs);
            });
        },
        isQuranManagerModalWindowVisible(modalWindowId) {
            const normalizedId = String(modalWindowId ?? '').trim();
    
            if (normalizedId === '') {
                return false;
            }
    
            const resolveModalWindowFromElement = (element) => {
                if (!(element instanceof Element)) {
                    return null;
                }
    
                if (element.classList.contains('fi-modal-window')) {
                    return element;
                }
    
                const nestedModalWindow = element.querySelector('.fi-modal-window');
    
                return nestedModalWindow instanceof Element ? nestedModalWindow : null;
            };
            const directElement = document.getElementById(normalizedId);
            const directModalWindow = resolveModalWindowFromElement(directElement);
            const escapedId = window.CSS?.escape ? window.CSS.escape(normalizedId) : normalizedId;
            const modalByDataId = document.querySelector(`[data-fi-modal-id='${escapedId}']`);
            const modalWindowFromDataId = resolveModalWindowFromElement(modalByDataId);
            const modalWindowElement = directModalWindow ?? modalWindowFromDataId ?? null;
    
            if (!(modalWindowElement instanceof Element)) {
                return false;
            }
    
            const modalElement = modalWindowElement.closest('.fi-modal');
    
            if (modalElement && !modalElement.classList.contains('fi-modal-open')) {
                return false;
            }
    
            const styles = window.getComputedStyle(modalWindowElement);
    
            return styles.display !== 'none' && styles.visibility !== 'hidden';
        },
        isJumpPageInputVisible() {
            const inputElement = document.getElementById('quran-reader-page-counter-input');
    
            if (!(inputElement instanceof HTMLInputElement) || !inputElement.isConnected) {
                return false;
            }
    
            const modalElement = inputElement.closest('.fi-modal');
    
            if (modalElement && !modalElement.classList.contains('fi-modal-open')) {
                return false;
            }
    
            const styles = window.getComputedStyle(inputElement);
    
            return (
                inputElement.clientHeight > 8 &&
                inputElement.clientWidth > 8 &&
                styles.display !== 'none' &&
                styles.visibility !== 'hidden'
            );
        },
        syncQuranManagerModalStateFromDom() {
            const isOpen =
                this.hasAnyOpenFilamentModal() ||
                this.isQuranManagerModalWindowVisible('quran-reader-search-modal') ||
                this.isQuranManagerModalWindowVisible('quran-reader-history-modal') ||
                this.isQuranManagerModalWindowVisible('quran-reader-bookmarks-modal') ||
                this.isQuranManagerModalWindowVisible('quran-reader-jump-page-modal') ||
                this.isJumpPageInputVisible();
    
            this.syncQuranManagerModalState(isOpen);
        },
        rootClasses() {
            return this.anchorClasses();
        },
        stackStyle() {
            const normalizedOffset = String(this.stackTopOffset ?? '').trim();
    
            if (normalizedOffset === '') {
                return '';
            }
    
            return `top: ${normalizedOffset};`;
        },
        bindClickHandler() {
            this.handleClick = (event) => {
                if (!this.respectingStack) {
                    return;
                }
    
                const item = event.target.closest('[data-stack-item]');
    
                if (!item || !this.$refs.stack.contains(item)) {
                    return;
                }
    
                const index = this.visibleItems().indexOf(item);
    
                if (index < 0) {
                    return;
                }
    
                if (!this.isQuickStackOpen) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    this.isQuickStackOpen = true;
                    this.activeIndex = index;
                    this.scheduleLayout(2);
                    return;
                }
    
                if (this.activeIndex !== index) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    this.activeIndex = index;
                    this.scheduleLayout(2);
                    return;
                }
    
                if (this.isInteractionLocked) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    return;
                }
    
                this.lockInteractions();
                this.scheduleLayout(1, { afterDom: false });
                window.setTimeout(() => this.resetStackItemState(item), 0);
            };
    
            this.$refs.stack.addEventListener('click', this.handleClick, true);
        },
        resetStackItemState(item) {
            const button = item?.querySelector?.('button');
    
            if (button) {
                button.blur();
                const data = window.Alpine?.$data ?
                    window.Alpine.$data(button) :
                    (button.__x?.$data ?? null);
                if (data && typeof data === 'object' && 'hovered' in data) {
                    data.hovered = false;
                }
            }
        },
        anchorClasses() {
            if (!this.respectingStack) {
                return '';
            }
    
            return [
                'fixed app-action-buttons-stack',
                'z-40',
                this.horizontal === 'left' ? 'end-10 sm:end-10' : 'start-10 sm:start-10',
                this.vertical === 'bottom' ? 'bottom-7' : 'top-5',
            ].join(' ');
        },
        direction() {
            return this.horizontal === 'right' ? -1 : 1;
        },
        gapBetween(index) {
            if (!this.isQuickStackOpen) {
                return this.inactiveGap;
            }
    
            if (index === this.activeIndex || index + 1 === this.activeIndex) {
                return this.activeNeighborGap;
            }
    
            return this.activeGap;
        },
        offsetFromAnchor(index, visibleCount) {
            const lastIndex = visibleCount - 1;
            let total = 0;
    
            for (let i = index; i < lastIndex; i += 1) {
                total += this.gapBetween(i);
            }
    
            return total * this.direction();
        },
        itemZIndex(index) {
            if (this.isQuickStackOpen && this.activeIndex === index) {
                return 80;
            }
    
            return 70 - index;
        },
        setItemStyle(item, prop, value) {
            if (item.style[prop] !== value) {
                item.style[prop] = value;
            }
        },
        updateLayout() {
            if (!this.items.length) {
                return;
            }
    
            if (!this.respectingStack) {
                this.items.forEach((item) => {
                    this.resetItem(item);
                    this.applyStackItemVisibility(item);
                });
                return;
            }
    
            const visibleItems = this.visibleItems();
    
            if (!visibleItems.length) {
                this.items.forEach((item) => {
                    this.resetItem(item);
                    this.applyStackItemVisibility(item);
                });
                return;
            }
    
            if (this.activeIndex > visibleItems.length - 1) {
                this.activeIndex = Math.max(visibleItems.length - 1, 0);
            }
    
            const visibleCount = visibleItems.length;
    
            const anchorSide = this.vertical === 'bottom' ? 'bottom' : 'top';
            const anchorOpposite = this.vertical === 'bottom' ? 'top' : 'bottom';
    
            const visibleSet = new Set(visibleItems);
            this.items.forEach((item) => {
                if (!visibleSet.has(item)) {
                    this.resetItem(item);
                }
            });
    
            visibleItems.forEach((item, index) => {
                const translateX = this.offsetFromAnchor(index, visibleCount).toFixed(2);
                const positionTransition = this.isQuickStackOpen ? `transform ${this.stackTransitionMs}ms ease, ` : '';
    
                this.setItemStyle(item, 'position', 'absolute');
                this.setItemStyle(item, anchorSide, '0px');
                this.setItemStyle(item, anchorOpposite, 'auto');
                this.setItemStyle(item, 'left', '0px');
                this.setItemStyle(item, 'right', 'auto');
                this.setItemStyle(item, 'transform', `translateX(${translateX}rem)`);
                this.setItemStyle(item, 'transition', `${positionTransition}opacity ${this.stackTransitionMs}ms ease`);
                this.setItemStyle(item, 'willChange', 'transform');
                this.setItemStyle(item, 'zIndex', String(this.itemZIndex(index)));
                if (this.shouldManageDisplay(item)) {
                    this.setItemStyle(item, 'display', 'block');
                }
                this.applyStackItemVisibility(item);
            });
        },
        resetItem(item) {
            this.setItemStyle(item, 'position', '');
            this.setItemStyle(item, 'top', '');
            this.setItemStyle(item, 'bottom', '');
            this.setItemStyle(item, 'left', '');
            this.setItemStyle(item, 'right', '');
            this.setItemStyle(item, 'transform', '');
            this.setItemStyle(item, 'transition', '');
            this.setItemStyle(item, 'willChange', '');
            this.setItemStyle(item, 'zIndex', '');
            if (this.shouldManageDisplay(item)) {
                this.setItemStyle(item, 'display', '');
            }
        },
    }"
    x-init="init();
    return () => destroy();"
    x-effect="syncActionState($store?.layoutManager?.isActionOpen === true)"
    x-on:switch-view.window="closeQuickStack(); releaseInteractionLock(); syncQuranManagerModalState(false); scheduleLayout(3)"
    x-on:hashchange.window="scheduleLayout(3)"
    x-on:resize.window="scheduleLayout(2, { afterDom: false })"
    x-on:orientationchange.window="scheduleLayout(2, { afterDom: false })"
    x-on:open-modal.window="closeQuickStack(); releaseInteractionLock(); scheduleLayout(3); scheduleQuranManagerModalStateSync()"
    x-on:x-modal-opened.window="scheduleLayout(3); scheduleQuranManagerModalStateSync()"
    x-on:opened-form-component-action-modal.window="scheduleLayout(3); scheduleQuranManagerModalStateSync()"
    x-on:close-modal.window="scheduleLayout(4); scheduleQuranManagerModalStateSync()"
    x-on:close-modal-quietly.window="scheduleLayout(4); scheduleQuranManagerModalStateSync()"
    x-on:x-modal-closed.window="scheduleLayout(4); scheduleQuranManagerModalStateSync()"
    x-on:closing-form-component-action-modal.window="scheduleLayout(4); scheduleQuranManagerModalStateSync()"
    x-on:closed-form-component-action-modal.window="scheduleLayout(4); scheduleQuranManagerModalStateSync()"
    x-on:quran-manager-modals-visibility.window="syncQuranManagerModalState($event.detail?.open === true)"
    x-on:click.window="
        if (!respectingStack) return;
        if ($refs.stack && $refs.stack.contains($event.target)) return;
        if (!isQuickStackOpen && !isInteractionLocked) return;
        closeQuickStack();
        releaseInteractionLock();
        scheduleLayout(2, { afterDom: false });
    "
    x-on:click.outside="
        if (!respectingStack) return;
        if (!isQuickStackOpen && !isInteractionLocked) return;
        closeQuickStack();
        releaseInteractionLock();
        scheduleLayout(2, { afterDom: false });
    "
    x-bind:class="rootClasses()"
>
    <div
        class="relative"
        x-ref="stack"
        x-bind:style="stackStyle()"
    >
        {{ $slot }}
    </div>
</div>
