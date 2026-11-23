import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/views/profile/partials/telegram-phone-verification.blade.php', import.meta.url), 'utf8');
const script = source.slice(source.indexOf('async function refreshPhoneVerification('), source.indexOf("document.addEventListener('click'"));

test('pending blocks duplicate creation; inactive re-enables it after expiry', async () => {
    const startButton = { disabled: false };
    const startForm = { dataset: {}, hidden: false };
    const status = { textContent: '' };
    const finalize = { hidden: true, elements: { verification_id: { value: '' } } };
    const elements = {
        '[data-phone-start]': startForm,
        '[data-phone-start] button[type="submit"]': startButton,
        '[data-phone-status]': status,
        '[data-phone-finalize]': finalize,
    };
    const check = { disabled: false, dataset: { url: '/phone/telegram/status' },
        closest: () => ({ querySelector: selector => elements[selector] }) };
    let data;
    const context = vm.createContext({ fetch: async () => ({ ok: true, json: async () => data }) });
    vm.runInContext(script, context);
    for (const state of ['pending', 'pending', 'inactive', 'contact_verified', 'verified']) {
        data = { status: state, verification_id: 42 };
        await context.refreshPhoneVerification(check);
        assert.equal(startButton.disabled, state !== 'inactive');
        assert.equal(startForm.hidden, state === 'verified');
        assert.equal(finalize.hidden, state !== 'contact_verified');
        assert.equal(check.disabled, false);
    }
});
