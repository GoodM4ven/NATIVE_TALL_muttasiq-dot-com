import { createInitialState } from './initial-state';
import { createLifecycleBootstrapModule } from './lifecycle-bootstrap-module';
import { createWirdAndHistoryModule } from './wird-and-history-module';
import { createReaderNavigationFitModule } from './reader-navigation-fit-module';
import { createSelectionCopyModule } from './selection-copy-module';
import { createLineLayoutModule } from './line-layout-module';
import { createSearchAndModalsModule } from './search-and-modals-module';
import { createManagerAndSearchActionsModule } from './manager-and-search-actions-module';
import * as shared from './shared';

const mergeModuleDescriptors = (target, ...modules) => {
    modules.forEach((moduleEntries) => {
        Object.defineProperties(target, Object.getOwnPropertyDescriptors(moduleEntries));
    });

    return target;
};

document.addEventListener('alpine:init', () => {
    window.Alpine.data('quranAppReader', (config = {}) => {
        const reader = createInitialState(config, shared);

        mergeModuleDescriptors(
            reader,
            createLifecycleBootstrapModule(shared),
            createWirdAndHistoryModule(shared),
            createReaderNavigationFitModule(shared),
            createSelectionCopyModule(shared),
            createLineLayoutModule(shared),
            createSearchAndModalsModule(shared),
            createManagerAndSearchActionsModule(shared),
        );

        return reader;
    });
});
