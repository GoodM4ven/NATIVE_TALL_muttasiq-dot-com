document.addEventListener('alpine:init', () => {
    window.Alpine.store('locator', {
        fragment: window.location.hash,
        init() {
            ['hashchange', 'popstate'].forEach((event) => {
                window.addEventListener(event, () => {
                    this.fragment = window.location.hash;
                });
            });
        },
    });
});
