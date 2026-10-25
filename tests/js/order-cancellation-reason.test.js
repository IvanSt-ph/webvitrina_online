import { test } from 'node:test';
import assert from 'node:assert/strict';
import createForm from '../../resources/js/order-cancellation-reason.js';
const options = ['Передумал(а) покупать.', 'Нашёл(ла) более подходящий товар.', 'Заказ оформлен по ошибке.', 'Хочу изменить состав заказа.', 'Не устраивает цена.', 'Не устраивают условия самовывоза.', 'Не получается связаться с продавцом.'];
test('empty selection is blocked; all presets submit actual text', () => {
    const form = createForm({ options });
    assert.equal(form.canSubmit, false);
    for (const choice of options) {
        form.choice = choice;
        assert.equal(form.canSubmit, true);
        assert.equal(form.selectedReason, choice);
    }
});
test('custom text requires non-whitespace input within 700 characters', () => {
    const form = createForm({ options, choice: 'other' });
    for (const text of ['', '  \n\t ', 'я'.repeat(701)]) {
        form.customReason = text;
        assert.equal(form.canSubmit, false);
    }
    form.customReason = '  Не смогу приехать  ';
    assert.equal(form.selectedReason, 'Не смогу приехать');
    assert.equal(form.canSubmit, true);
    form.customReason = 'я'.repeat(700);
    assert.equal(form.canSubmit, true);
});
test('switching to preset never sends stale custom text', () => {
    const form = createForm({ options, choice: 'other', reason: 'Свой текст' });
    form.choice = options[0];
    assert.equal(form.selectedReason, options[0]);
    form.choice = '';
    assert.equal(form.selectedReason, '');
    assert.equal(form.canSubmit, false);
    form.choice = 'other';
    assert.equal(form.selectedReason, 'Свой текст');
});
test('validation return restores selection and custom text', () => {
    const preset = createForm({ options, reason: options[1], choice: options[1] });
    assert.equal(preset.choice, options[1]);
    assert.equal(preset.selectedReason, options[1]);
    const custom = createForm({ options, reason: 'я'.repeat(701), choice: 'other' });
    assert.equal(custom.choice, 'other');
    assert.equal(custom.customReason, 'я'.repeat(701));
    assert.equal(custom.canSubmit, false);
    const matching = createForm({ options, reason: options[0], choice: 'other' });
    assert.equal(matching.choice, 'other');
    assert.equal(matching.customReason, options[0]);
});
test('legacy and malformed old input are handled safely', () => {
    assert.equal(createForm({ options, reason: 'Старая причина' }).choice, 'other');
    const form = createForm({ options, reason: ['invalid'], choice: ['invalid'] });
    assert.equal(form.canSubmit, false);
    form.choice = 'unknown';
    assert.equal(form.selectedReason, '');
    assert.equal(form.canSubmit, false);
});

test('dropdown navigation preserves choice, limits indices and restores focus', () => {
    const form = createForm({ options, choice: options[1] });
    let focused = '';
    form.$nextTick = callback => callback();
    form.$refs = {
        reasonTrigger: { focus: () => { focused = 'trigger'; } },
        reasonList: { focus: () => { focused = 'list'; }, querySelectorAll: () => [] },
    };
    form.openMenu();
    assert.equal(form.menuOpen, true);
    assert.equal(focused, 'list');
    assert.equal(form.activeIndex, 1);
    form.moveOption(99);
    assert.equal(form.activeIndex, 7);
    form.moveOption(-1);
    assert.equal(form.activeIndex, 0);
    form.chooseOption(7);
    assert.equal(form.choice, 'other');
    assert.equal(form.choiceLabel, 'Другая причина (указать свою)');
    assert.equal(form.menuOpen, false);
    assert.equal(focused, 'trigger');
    assert.equal(form.canSubmit, false);
    form.customReason = 'Своя причина';
    form.openMenu();
    form.closeMenu(true);
    assert.equal(form.selectedReason, 'Своя причина');
    form.chooseOption(0);
    assert.equal(form.selectedReason, options[0]);
});
