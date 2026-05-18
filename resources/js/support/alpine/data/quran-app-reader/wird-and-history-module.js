import { createWirdAndHistoryProgressAndModeModule } from './wird-and-history-progress-and-mode';
import { createWirdAndHistoryNavigationAndManagerSyncModule } from './wird-and-history-navigation-and-manager-sync';
import { createWirdAndHistoryBookmarksAndEffectsModule } from './wird-and-history-bookmarks-and-effects';
import { createWirdAndHistoryBookmarkPressAndQuickOpenModule } from './wird-and-history-bookmark-press-and-quick-open';

const mergeModuleDescriptors = (target, ...modules) => {
    modules.forEach((moduleEntries) => {
        Object.defineProperties(target, Object.getOwnPropertyDescriptors(moduleEntries));
    });

    return target;
};

export const createWirdAndHistoryModule = (deps) => {
    return mergeModuleDescriptors(
        {},
        createWirdAndHistoryProgressAndModeModule(deps),
        createWirdAndHistoryNavigationAndManagerSyncModule(deps),
        createWirdAndHistoryBookmarksAndEffectsModule(deps),
        createWirdAndHistoryBookmarkPressAndQuickOpenModule(deps),
    );
};
