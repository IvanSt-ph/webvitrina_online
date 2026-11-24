import test from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';
import { readFileSync } from 'node:fs';
const read = path => readFileSync(new URL('../../' + path, import.meta.url), 'utf8');
const loader = read('resources/js/phone-inputs.js').replace("import('./phone-input-runtime')", 'importRuntime()');
const runtime = read('resources/js/phone-input-runtime.js').replace(/^import .*;\r?\n/gm, '').replace('export function', 'function');
const tick = () => new Promise(resolve => setImmediate(resolve));
function harness(manual = false, initiallyPresent = true) {
    const events = {}, calls = [], formEvents = {}, inputEvents = {}, requests = [];
    let present = initiallyPresent, observer, imports = 0, submits = 0;
    const input = { dataset: manual ? { intlManual: 'true' } : {}, value: '+37377700000', isConnected: true,
        matches: () => true, querySelectorAll: () => [], addEventListener: (type, cb) => { (inputEvents[type] ??= []).push(cb); },
        closest: selector => selector === '.iti' ? null : form };
    const form = { isConnected: true, querySelectorAll: () => [input], querySelector: () => input,
        closest: () => ({ querySelector: () => ({ textContent: '+37377700000' }) }),
        addEventListener: (type, cb) => { (formEvents[type] ??= []).push(cb); },
        requestSubmit() { submit(); } };
    const document = { readyState: 'complete', body: {}, documentElement: { style: { setProperty() {} } },
        querySelectorAll: () => present ? [input] : [], querySelector: selector => selector === '#phone' ? input : null,
        addEventListener: (type, cb) => { (events[type] ??= []).push(cb); } };
    const instance = { getNumber: () => '+37312345678', isValidNumber: () => true, setNumber() {}, promise: Promise.resolve() };
    const window = { showSiteToast() {} };
    const context = vm.createContext({ window, document, console, Map, WeakSet, Promise,
        intlTelInput: (element, options) => { calls.push({ element, options }); return instance; },
        itiFlags: '', itiFlags2x: '', itiGlobe: '', itiGlobe2x: '',
        MutationObserver: class { constructor(cb) { observer = cb; } observe() {} },
        importRuntime: () => { imports++; return new Promise((resolve, reject) => requests.push({ resolve, reject })); } });
    function submit() {
        let prevented = false;
        const event = { target: form, preventDefault() { prevented = true; }, stopImmediatePropagation() {} };
        for (const cb of events.submit ?? []) cb(event);
        if (!prevented) { for (const cb of formEvents.submit ?? []) cb(); submits++; }
    }
    vm.runInContext(loader, context);
    return { input, calls, form, window, context, submit, inputEvents, events,
        imports: () => imports, submits: () => submits,
        add() { present = true; observer([{ addedNodes: [{ nodeType: 1, matches: () => false, querySelector: () => input, querySelectorAll: () => [input] }] }]); },
        resolve(index = 0) { vm.runInContext(runtime, context); requests[index].resolve({ initializeAutomaticPhoneInputs: context.initializeAutomaticPhoneInputs }); },
        reject() { requests[0].reject(Error('offline')); } };
}
test('no phone means zero imports; dynamic fields initialize once and submissions format numbers', async () => {
    const h = harness(false, false); await tick(); assert.equal(h.imports(), 0);
    h.add(); h.add(); assert.equal(h.imports(), 1);
    h.submit(); assert.equal(h.submits(), 0);
    h.resolve(); await tick(); await tick();
    assert.equal(h.calls.length, 1); assert.equal(h.submits(), 1); assert.equal(h.input.value, '+37312345678');
    assert.equal(h.calls[0].options.separateDialCode, true);
    assert.equal(typeof h.calls[0].options.loadUtils, 'function');
    h.add(); await tick(); assert.equal(h.calls.length, 1);
});
test('manual initializer shares async lock; automatic scan cannot take ownership', async () => {
    const h = harness(true);
    let setups = 0;
    const setup = () => { setups++; return {}; };
    const one = h.window.initializePhoneInput(h.input, setup);
    assert.equal(h.window.initializePhoneInput(h.input, setup), one);
    h.resolve(); await one; await tick();
    assert.equal(setups, 1); assert.equal(h.calls.length, 0); assert.equal(h.input.dataset.intlInitialized, 'true');
});
test('failed load blocks submission and can be retried', async () => {
    const h = harness(); h.submit(); h.reject(); await tick(); await tick();
    assert.equal(h.submits(), 0); assert.equal(h.input.dataset.intlInitialized, undefined);
    h.add(); assert.equal(h.imports(), 2); h.resolve(1); await tick();
    h.submit(); assert.equal(h.submits(), 1); assert.equal(h.calls.length, 1);
});
test('buyer manual options and full phone submit survive lazy loading', async () => {
    const h = harness(true);
    const source = read('resources/views/buyer/profile/general.blade.php');
    const start = source.indexOf("document.addEventListener('DOMContentLoaded', function () {");
    vm.runInContext(source.slice(start, source.indexOf('</script>', start)), h.context);
    h.events.DOMContentLoaded[0](); h.resolve(); await tick();
    assert.equal(h.calls.length, 1); assert.equal(h.calls[0].options.separateDialCode, false);
    assert.equal(h.calls[0].options.hiddenInput().phone, 'phone_full');
    h.submit(); assert.equal(h.input.value, '+37312345678');
});
test('verification manual initializer binds once, after plugin readiness', async () => {
    const h = harness(true);
    const source = read('resources/views/profile/partials/telegram-phone-verification.blade.php');
    vm.runInContext(source.slice(source.indexOf('function initSellerAccountPhone('), source.indexOf('async function refreshPhoneVerification(')), h.context);
    h.context.initSellerAccountPhone(h.form); h.context.initSellerAccountPhone(h.form);
    h.resolve(); await tick();
    assert.equal(h.calls.length, 1); assert.equal(h.calls[0].options.separateDialCode, true);
    h.submit(); assert.equal(h.input.value, '+37312345678');
});
