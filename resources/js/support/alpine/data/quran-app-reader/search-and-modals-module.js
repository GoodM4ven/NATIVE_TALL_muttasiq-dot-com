import { createSearchAndModalsStreamAndResultsModule } from './search-and-modals-stream-and-results';
import { createSearchAndModalsLifecycleAndStateModule } from './search-and-modals-lifecycle-and-state';

const mergeModuleDescriptors = (target, ...modules) => {
    modules.forEach((moduleEntries) => {
        Object.defineProperties(target, Object.getOwnPropertyDescriptors(moduleEntries));
    });

    return target;
};

export const createSearchAndModalsModule = (deps) => {
    return mergeModuleDescriptors(
        {},
        createSearchAndModalsStreamAndResultsModule(deps),
        createSearchAndModalsLifecycleAndStateModule(deps),
    );
};
