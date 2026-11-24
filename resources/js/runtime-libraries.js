// Register loaders only; dependencies stay in lazy chunks.
function cachedLoader(importLibrary, globalName) {
    let promise;
    return () => promise ??= importLibrary().then(module => {
        window[globalName] = module.default;
        return module.default;
    }).catch(error => {
        promise = undefined;
        throw error;
    });
}

window.loadChart = cachedLoader(() => import('chart.js/auto'), 'Chart');
window.loadLeaflet = cachedLoader(() => import('./runtime/leaflet'), 'L');
window.loadCropper = cachedLoader(() => import('./runtime/cropper'), 'Cropper');

let adminMapModule;
window.initAdminProductMap = (element, options) => {
    adminMapModule ??= import('./admin-product-map').catch(error => {
        adminMapModule = undefined;
        throw error;
    });
    return adminMapModule.then(module => module.initAdminProductMap(element, options));
};
