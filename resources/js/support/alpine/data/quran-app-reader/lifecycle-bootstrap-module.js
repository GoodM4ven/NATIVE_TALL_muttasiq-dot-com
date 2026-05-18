import { createLifecycleBootstrapEnvironmentAndCacheModule } from './lifecycle-bootstrap-environment-and-cache';
import { createLifecycleBootstrapSupportAndWirdStateModule } from './lifecycle-bootstrap-support-and-wird-state';
import { createLifecycleBootstrapWirdRecordReconcileModule } from './lifecycle-bootstrap-wird-record-reconcile';

const mergeModuleDescriptors = (target, ...modules) => {
    modules.forEach((moduleEntries) => {
        Object.defineProperties(target, Object.getOwnPropertyDescriptors(moduleEntries));
    });

    return target;
};

export const createLifecycleBootstrapModule = (deps) => {
    return mergeModuleDescriptors(
        {},
        createLifecycleBootstrapEnvironmentAndCacheModule(deps),
        createLifecycleBootstrapSupportAndWirdStateModule(deps),
        createLifecycleBootstrapWirdRecordReconcileModule(deps),
    );
};
