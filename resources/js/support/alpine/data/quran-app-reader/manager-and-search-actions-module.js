import { createManagerAndSearchActionsUiAndLocalIndexModule } from './manager-and-search-actions-ui-and-local-index';
import { createManagerAndSearchActionsWarmAndNavigateModule } from './manager-and-search-actions-warm-and-navigate';

const mergeModuleDescriptors = (target, ...modules) => {
    modules.forEach((moduleEntries) => {
        Object.defineProperties(target, Object.getOwnPropertyDescriptors(moduleEntries));
    });

    return target;
};

export const createManagerAndSearchActionsModule = (deps) => {
    return mergeModuleDescriptors(
        {},
        createManagerAndSearchActionsUiAndLocalIndexModule(deps),
        createManagerAndSearchActionsWarmAndNavigateModule(deps),
    );
};
