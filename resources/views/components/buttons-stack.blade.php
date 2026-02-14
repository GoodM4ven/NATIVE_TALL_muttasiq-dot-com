@props([
    'horizontal' => 'left',
    'vertical' => 'top',
    'inactiveGap' => 1.2,
    'activeGap' => 1.6,
    'activeNeighborGap' => 2.4,
])

<div
    {{ $attributes }}
    x-data="{
        horizontal: @js($horizontal),
        vertical: @js($vertical),
        inactiveGap: @js($inactiveGap),
        activeGap: @js($activeGap),
        activeNeighborGap: @js($activeNeighborGap),
        respectingStack: false,
        isQuickStackOpen: false,
        activeIndex: 0,
        items: [],
        observer: null,
        attributeObserver: null,
        stackTransitionMs: 200,
        shouldManageDisplay(item) {
            if (!item) {
                return false;
            }
    
            return !item.hasAttribute('x-show') && !item.hasAttribute('x-cloak');
        },
        init() {
            this.refreshItems();
            this.bindClickHandler();
            this.observeItems();
            this.observeRespecting();
            this.setRespectingStack();
            this.updateLayout();
        },
        destroy() {
            if (this.observer) {
                this.observer.disconnect();
            }
    
            if (this.attributeObserver) {
                this.attributeObserver.disconnect();
            }
    
            if (this.$refs.stack) {
                this.$refs.stack.removeEventListener('click', this.handleClick, true);
            }
        },
        setRespectingStack() {
            this.respectingStack = String(this.$el.dataset.respectingStack) === 'true';
    
            if (!this.respectingStack) {
                this.isQuickStackOpen = false;
                this.activeIndex = 0;
            }
        },
        observeRespecting() {
            this.attributeObserver = new MutationObserver(() => {
                this.setRespectingStack();
                this.updateLayout();
            });
    
            this.attributeObserver.observe(this.$el, {
                attributes: true,
                attributeFilter: ['data-respecting-stack'],
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
            this.observer = new MutationObserver(() => {
                this.refreshItems();
                this.updateLayout();
            });
    
            this.observer.observe(this.$refs.stack, {
                childList: true,
                subtree: true,
            });
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
    
                const index = Number(item.dataset.stackIndex ?? -1);
    
                if (index < 0) {
                    return;
                }
    
                if (!this.isQuickStackOpen) {
                    this.isQuickStackOpen = true;
                    this.activeIndex = index;
                    this.updateLayout();
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    return;
                }
    
                if (this.activeIndex !== index) {
                    this.activeIndex = index;
                    this.updateLayout();
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    return;
                }
    
                this.isQuickStackOpen = false;
                this.activeIndex = 0;
                this.updateLayout();
                setTimeout(() => this.resetStackItemState(item), 0);
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
                'fixed',
                'z-40',
                this.horizontal === 'left' ? 'end-10' : 'start-10',
                this.vertical === 'bottom' ? 'bottom-7' : 'top-7',
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
        offsetFromAnchor(index) {
            const lastIndex = this.items.length - 1;
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
        updateLayout() {
            if (!this.items.length) {
                return;
            }
    
            if (!this.respectingStack) {
                this.items.forEach((item) => this.resetItem(item));
                return;
            }
    
            const anchorSide = this.vertical === 'bottom' ? 'bottom' : 'top';
            const anchorOpposite = this.vertical === 'bottom' ? 'top' : 'bottom';
    
            this.items.forEach((item, index) => {
                const translateX = this.offsetFromAnchor(index).toFixed(2);
    
                item.style.position = 'absolute';
                item.style[anchorSide] = '0';
                item.style[anchorOpposite] = 'auto';
                item.style.left = '0';
                item.style.right = 'auto';
                item.style.transform = `translateX(${translateX}rem)`;
                item.style.transition = `transform ${this.stackTransitionMs}ms ease`;
                item.style.willChange = 'transform';
                item.style.zIndex = String(this.itemZIndex(index));
                if (this.shouldManageDisplay(item)) {
                    item.style.display = 'block';
                }
            });
        },
        resetItem(item) {
            item.style.position = '';
            item.style.top = '';
            item.style.bottom = '';
            item.style.left = '';
            item.style.right = '';
            item.style.transform = '';
            item.style.transition = '';
            item.style.willChange = '';
            item.style.zIndex = '';
            if (this.shouldManageDisplay(item)) {
                item.style.display = '';
            }
        },
    }"
    x-init="init();
    return () => destroy();"
    x-effect="if ((typeof isSettingsOpen !== 'undefined' && isSettingsOpen) || (typeof isAthkarManagerOpen !== 'undefined' && isAthkarManagerOpen)) { isQuickStackOpen = false; activeIndex = 0; updateLayout(); }"
    x-on:click.window="
        if (!respectingStack) return;
        if ($refs.stack && $refs.stack.contains($event.target)) return;
        isQuickStackOpen = false;
        activeIndex = 0;
        updateLayout();
    "
    x-on:click.outside="if (respectingStack) { isQuickStackOpen = false; activeIndex = 0; updateLayout(); }"
    x-bind:class="anchorClasses()"
>
    <div
        class="relative"
        x-ref="stack"
    >
        {{ $slot }}
    </div>
</div>
