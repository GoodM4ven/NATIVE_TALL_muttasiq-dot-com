import { createSelectionCopySettingsAndDragStateModule } from './selection-copy-settings-and-drag-state';
import { createSelectionCopyComposeAndPointerModule } from './selection-copy-compose-and-pointer';

const mergeModuleDescriptors = (target, ...modules) => {
    modules.forEach((moduleEntries) => {
        Object.defineProperties(target, Object.getOwnPropertyDescriptors(moduleEntries));
    });

    return target;
};

export const createSelectionCopyModule = (deps) => {
    return mergeModuleDescriptors(
        {},
        createSelectionCopySettingsAndDragStateModule(deps),
        createSelectionCopyComposeAndPointerModule(deps),
    );
};
