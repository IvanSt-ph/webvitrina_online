let runtime;
const pending = new Map();
const submitting = new WeakSet();

function loadRuntime() {
    return runtime ??= import('./phone-input-runtime').catch(error => {
        runtime = undefined;
        throw error;
    });
}

window.loadIntlTelInput = () => loadRuntime().then(() => window.intlTelInput);

// Manual and automatic initializers share a per-input lock, including async loads.
window.initializePhoneInput = (input, initialize) => {
    if (pending.has(input)) return pending.get(input);
    if (input.dataset.intlInitialized) return Promise.resolve();
    const task = loadRuntime().then(() => {
        if (!input.isConnected) return;
        const instance = initialize();
        input.dataset.intlInitialized = 'true';
        return instance;
    }).finally(() => pending.delete(input));
    pending.set(input, task);
    return task;
};

function initializeWithin(root) {
    if (!root.matches?.('input[type="tel"]') && !root.querySelector?.('input[type="tel"]')) return Promise.resolve();
    return loadRuntime().then(module => module.initializeAutomaticPhoneInputs(root));
}

function reportError() {
    window.showSiteToast?.('Не удалось загрузить поле телефона. Повторите попытку.', 'error');
}

function start() {
    void initializeWithin(document).catch(reportError);
    new MutationObserver(records => {
        for (const record of records) for (const node of record.addedNodes) {
            if (node.nodeType === 1) void initializeWithin(node).catch(reportError);
        }
    }).observe(document.body, { childList: true, subtree: true });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
else start();

// A fast submit must wait until the number-formatting submit handlers are bound.
document.addEventListener('submit', event => {
    const form = event.target;
    const inputs = [...form.querySelectorAll('input[type="tel"]')];
    if (!inputs.some(input => pending.has(input) || !input.dataset.intlInitialized)) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    if (submitting.has(form)) return;
    submitting.add(form);
    const submitter = event.submitter;
    initializeWithin(form)
        .then(() => Promise.all(inputs.map(input => pending.get(input))))
        .then(() => {
            if (inputs.some(input => input.isConnected && !input.dataset.intlInitialized)) throw new Error('Phone field is not ready');
            if (form.isConnected) form.requestSubmit(submitter?.isConnected ? submitter : undefined);
        })
        .catch(reportError)
        .finally(() => submitting.delete(form));
}, true);
