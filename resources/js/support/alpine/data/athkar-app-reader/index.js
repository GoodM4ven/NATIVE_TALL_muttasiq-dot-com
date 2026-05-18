import { createInitialState } from './initial-state';
import { createLifecycleModule } from './lifecycle-module';
import { createModeFlowModule } from './mode-flow-module';
import { createOverflowOriginModule } from './overflow-origin-module';
import { createMetricsModule } from './metrics-module';
import { createNavigationModule } from './navigation-module';
import { createCompletionModule } from './completion-module';
import { createTextInteractionModule } from './text-interaction-module';
import * as shared from './shared';

const mergeModuleDescriptors = (target, ...modules) => {
    modules.forEach((moduleEntries) => {
        Object.defineProperties(target, Object.getOwnPropertyDescriptors(moduleEntries));
    });

    return target;
};

document.addEventListener('alpine:init', () => {
    window.Alpine.data('athkarAppReader', (config) => {
        const reader = createInitialState(config, shared);

        mergeModuleDescriptors(
            reader,
            createLifecycleModule(shared),
            createModeFlowModule(shared),
            createOverflowOriginModule(shared),
            createMetricsModule(shared),
            createNavigationModule(shared),
            createCompletionModule(shared),
            createTextInteractionModule(shared),
        );

        return reader;
    });
});
