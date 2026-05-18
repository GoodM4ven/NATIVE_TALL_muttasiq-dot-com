import { createReaderNavigationFitSurahQuickNavAndBurstModule } from './reader-navigation-fit-surah-quick-nav-and-burst';
import { createReaderNavigationFitPageNavAndLayoutSchedulingModule } from './reader-navigation-fit-page-nav-and-layout-scheduling';
import { createReaderNavigationFitRevealGuardsAndSolverModule } from './reader-navigation-fit-reveal-guards-and-solver';
import { createReaderNavigationFitIdleWarmupAndScaleControlsModule } from './reader-navigation-fit-idle-warmup-and-scale-controls';
import { createReaderNavigationFitPageAdjustAndChromeModule } from './reader-navigation-fit-page-adjust-and-chrome';

const mergeModuleDescriptors = (target, ...modules) => {
    modules.forEach((moduleEntries) => {
        Object.defineProperties(target, Object.getOwnPropertyDescriptors(moduleEntries));
    });

    return target;
};

export const createReaderNavigationFitModule = (deps) => {
    return mergeModuleDescriptors(
        {},
        createReaderNavigationFitSurahQuickNavAndBurstModule(deps),
        createReaderNavigationFitPageNavAndLayoutSchedulingModule(deps),
        createReaderNavigationFitRevealGuardsAndSolverModule(deps),
        createReaderNavigationFitIdleWarmupAndScaleControlsModule(deps),
        createReaderNavigationFitPageAdjustAndChromeModule(deps),
    );
};
