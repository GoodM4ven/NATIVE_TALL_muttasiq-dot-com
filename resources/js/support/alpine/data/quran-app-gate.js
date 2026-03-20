document.addEventListener('alpine:init', () => {
    window.Alpine.data('quranAppGate', () => ({
        projectedMode: null,
        pinnedMode: null,
        isPointerInside: false,
        isModePinned: false,
        puckX: 50,
        puckY: 50,
        init() {
            this.$nextTick(() => {
                this.positionPuckAtDefault();
            });
        },
        normalizeAngle(angle) {
            const fullTurn = Math.PI * 2;

            return ((angle % fullTurn) + fullTurn) % fullTurn;
        },
        isAngleWithinArc(angle, arcStart, arcEnd) {
            const fullTurn = Math.PI * 2;
            const normalizedAngle = this.normalizeAngle(angle);
            const normalizedStart = this.normalizeAngle(arcStart);
            const normalizedEnd = this.normalizeAngle(arcEnd);
            const span = (normalizedEnd - normalizedStart + fullTurn) % fullTurn;
            const progress = (normalizedAngle - normalizedStart + fullTurn) % fullTurn;

            return progress <= span;
        },
        resolveModeFromAngle(angle, centerX, centerY, shellRect) {
            if (!shellRect) {
                return null;
            }

            const topLeftAngle = Math.atan2(shellRect.top - centerY, shellRect.left - centerX);
            const topRightAngle = Math.atan2(shellRect.top - centerY, shellRect.right - centerX);
            const bottomCenterAngle = Math.atan2(
                shellRect.bottom - centerY,
                shellRect.left + shellRect.width / 2 - centerX,
            );

            if (this.isAngleWithinArc(angle, topLeftAngle, topRightAngle)) {
                return 'tilawa';
            }

            if (this.isAngleWithinArc(angle, topRightAngle, bottomCenterAngle)) {
                return 'hifth';
            }

            return 'tadabbur';
        },
        isModeActive(mode) {
            return (this.pinnedMode ?? this.projectedMode) === mode;
        },
        pinMode(mode) {
            this.pinnedMode = mode;
            this.isModePinned = true;
        },
        unpinMode(mode) {
            if (this.pinnedMode !== mode) {
                return;
            }

            this.pinnedMode = null;
            this.isModePinned = false;
        },
        handlePointerEnter() {
            this.isPointerInside = true;
        },
        handlePointerLeave() {
            this.isPointerInside = false;
            this.projectedMode = null;

            if (!this.isModePinned) {
                this.positionPuckAtDefault();
            }
        },
        positionPuckAtDefault() {
            const shellElement = this.$refs?.shell;
            const anchorCircle = this.$refs?.anchorCircle;

            if (!shellElement || !anchorCircle) {
                return;
            }

            const shellRect = shellElement.getBoundingClientRect();
            const anchorRect = anchorCircle.getBoundingClientRect();
            const anchorCenterX = anchorRect.left + anchorRect.width / 2;
            const anchorCenterY = anchorRect.top + anchorRect.height / 2;
            const radius = anchorRect.width / 2;
            const defaultX = anchorCenterX;
            const defaultY = anchorCenterY - radius;

            this.puckX = ((defaultX - shellRect.left) / shellRect.width) * 100;
            this.puckY = ((defaultY - shellRect.top) / shellRect.height) * 100;
        },
        handlePointerMove(event) {
            if (event.pointerType === 'touch') {
                return;
            }

            const shellElement = this.$refs?.shell;
            const anchorCircle = this.$refs?.anchorCircle;

            if (!shellElement || !anchorCircle) {
                return;
            }

            this.isPointerInside = true;

            const shellRect = shellElement.getBoundingClientRect();
            const anchorRect = anchorCircle.getBoundingClientRect();
            const anchorCenterX = anchorRect.left + anchorRect.width / 2;
            const anchorCenterY = anchorRect.top + anchorRect.height / 2;
            const radius = anchorRect.width / 2;
            const deltaX = event.clientX - anchorCenterX;
            const deltaY = event.clientY - anchorCenterY;
            const distance = Math.hypot(deltaX, deltaY);

            if (distance <= 0.001) {
                this.positionPuckAtDefault();
                this.projectedMode = 'tilawa';

                return;
            }

            const projectedX = anchorCenterX + (deltaX / distance) * radius;
            const projectedY = anchorCenterY + (deltaY / distance) * radius;
            const projectedAngle = Math.atan2(
                projectedY - anchorCenterY,
                projectedX - anchorCenterX,
            );

            this.puckX = ((projectedX - shellRect.left) / shellRect.width) * 100;
            this.puckY = ((projectedY - shellRect.top) / shellRect.height) * 100;
            this.projectedMode = this.resolveModeFromAngle(
                projectedAngle,
                anchorCenterX,
                anchorCenterY,
                shellRect,
            );
        },
        openMode(mode) {
            const modeViewMap = {
                tilawa: 'quran-app-tilawa',
                hifth: 'quran-app-hifth',
                tadabbur: 'quran-app-tadabbur',
            };
            const targetView = modeViewMap[mode] ?? 'quran-app-gate';

            this.$viewNav(targetView);
        },
    }));
});
