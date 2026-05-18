import { createLineLayoutRenderCoreModule } from './line-layout-render-core';
import { createLineLayoutSearchDirectoryAndCaptionModule } from './line-layout-search-directory-and-caption';
import { createLineLayoutModalInputSyncModule } from './line-layout-modal-input-sync';

const mergeModuleDescriptors = (target, ...modules) => {
    modules.forEach((moduleEntries) => {
        Object.defineProperties(target, Object.getOwnPropertyDescriptors(moduleEntries));
    });

    return target;
};

export const createLineLayoutModule = (deps) => {
    return mergeModuleDescriptors(
        {},
        createLineLayoutRenderCoreModule(deps),
        createLineLayoutSearchDirectoryAndCaptionModule(deps),
        createLineLayoutModalInputSyncModule(deps),
    );
};
