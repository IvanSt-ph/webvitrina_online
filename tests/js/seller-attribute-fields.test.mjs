import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { renderAttributeFields, updateColorSummary, snapshotAttributeFields, attributeDefinition, compatibleAttributeValue, captureAttributeDrafts, restoreAttributeDrafts, resetAttributeColor } from '../../resources/js/seller-attribute-fields.js';

const colors = ['Белый', 'Черный', 'Серый', 'Красный', 'Синий', 'Голубой', 'Зелёный', 'Жёлтый', 'Оранжевый', 'Розовый', 'Фиолетовый', 'Коричневый', 'Бежевый', 'Золотой', 'Серебристый']
  .map((name, index) => ({ id: index + 40, name, hex: '#FFFFFF' }));

test('all supplied colors retain IDs and names; selection is a single native radio', () => {
  const html = renderAttributeFields([{ id: 263, name: 'Цвет', type: 'color', value: '54', colors }]);
  assert.equal((html.match(/type="radio"/g) ?? []).length, 15);
  assert.equal((html.match(/ checked /g) ?? []).length, 1);
  for (const c of colors) {
    assert.ok(html.includes(`value="${c.id}"`));
    assert.ok(html.includes(c.name));
  }
  assert.ok(html.includes('Выбран: Серебристый'));
  assert.ok(html.includes('name="attributes[263]"'));
  assert.ok(!html.includes('class="hidden"'));
  assert.ok(renderAttributeFields([{ id: 3, type: 'color', colors: colors.slice(0, 2) }]).includes('Цвет не выбран'));
});

test('ordinary fields retain their type, unit and existing value; options are selected and escaped', () => {
  const html = renderAttributeFields([
    { id: 1, name: 'Размер', type: 'number', value: 0, unit: 'см' },
    { id: 2, name: 'Текст', type: 'text', value: '<script>"&' },
    { id: 3, name: 'Выбор', type: 'select', value: 'B', options: ['A', 'B'] },
  ]);
  assert.ok(html.includes('type="number" step="any"'));
  assert.ok(html.includes('value="0"'));
  assert.ok(html.includes('Размер (см)'));
  assert.ok(html.includes('&lt;script&gt;&quot;&amp;'));
  assert.ok(html.includes('<option value="B" selected>'));
  assert.ok(html.includes('Выберите значение'));
});

test('summary changes on native change, without forcing or toggling radio values', () => {
  const summary = { textContent: '' };
  const reset = { hidden: true };
  const input = { type: 'radio', checked: true, dataset: { colorName: 'Черный' }, closest: () => ({ querySelector: selector => selector.includes(':checked') ? input : selector === '[data-color-reset]' ? reset : summary }) };
  updateColorSummary(input);
  assert.equal(summary.textContent, 'Выбран: Черный');
  input.dataset.colorName = 'Синий';
  updateColorSummary(input);
  assert.equal(summary.textContent, 'Выбран: Синий');
  assert.equal(input.checked, true);
});

test('category snapshot serializes live radio, select and text values without mutating controls', () => {
  const original = [{ value: '2', checked: true }, { value: '3', checked: false }, { value: 'new text' }, { options: [{ selected: false }, { selected: true }] }];
  const fake = tagName => ({ tagName, attrs: {}, setAttribute(k, v) { this.attrs[k] = v; }, toggleAttribute(k, v) { this.attrs[k] = v; } });
  const copies = [fake('INPUT'), fake('INPUT'), fake('TEXTAREA'), { tagName: 'SELECT', options: [fake('OPTION'), fake('OPTION')] }];
  const clone = { querySelectorAll: () => copies, get innerHTML() { return JSON.stringify(copies); } };
  const before = JSON.stringify(original);
  snapshotAttributeFields({ cloneNode: () => clone, querySelectorAll: () => original });
  assert.equal(copies[0].attrs.checked, true);
  assert.equal(copies[1].attrs.checked, false);
  assert.equal(copies[2].textContent, 'new text');
  assert.equal(copies[3].options[1].attrs.selected, true);
  assert.equal(JSON.stringify(original), before);
});

test('category loader rejects stale responses and restores cached edit markup', async () => {
  const source = readFileSync(new URL('../../resources/js/seller-product-form.js', import.meta.url), 'utf8');
  const code = source.slice(source.indexOf('  const attrWrapper ='), source.indexOf("  document.addEventListener('change', e => {", source.indexOf('  const attrWrapper =')));
  const wrapper = { innerHTML: 'initial selected color 2', addEventListener() {} };
  const pending = [];
  const context = { document: { getElementById: () => wrapper }, catHidden: { value: '129' }, attributesCache: new Map(),
    snapshotAttributeFields: root => root.innerHTML, updateColorSummary, renderAttributeFields,
    captureAttributeDrafts() {}, restoreAttributeDrafts() {},
    fetch: url => new Promise(resolve => pending.push({ url, resolve })) };
  vm.createContext(context);
  vm.runInContext(code, context);
  const first = context.loadAttributes('200');
  const second = context.loadAttributes('201');
  pending[1].resolve({ ok: true, json: async () => [{ id: 2, name: 'Latest', type: 'text' }] });
  await second;
  pending[0].resolve({ ok: true, json: async () => [{ id: 1, name: 'Stale', type: 'text' }] });
  await first;
  assert.ok(wrapper.innerHTML.includes('Latest'));
  assert.ok(!wrapper.innerHTML.includes('Stale'));
  await context.loadAttributes('129');
  assert.equal(wrapper.innerHTML, 'initial selected color 2');
  await context.loadAttributes(0);
  assert.equal(wrapper.innerHTML, '');
});

function field(attr, value = '') {
  const definition = attributeDefinition(attr);
  const control = { value, checked: value === '1' };
  const radios = (attr.colors ?? []).map(c => ({ value: String(c.id), checked: String(c.id) === value, dataset: { colorName: c.name }, focus() {} }));
  const summary = { textContent: '' };
  const reset = { hidden: !radios.some(r => r.checked) };
  const element = {
    dataset: { attributeDefinition: JSON.stringify(definition) },
    querySelector(selector) {
      if (selector === '[data-color-summary]') return summary;
      if (selector === '[data-color-reset]') return reset;
      if (selector.includes(':checked')) return radios.find(r => r.checked);
      if (selector === 'input[type="radio"]') return radios[0];
      return control;
    },
    querySelectorAll: () => radios,
  };
  return { element, control, radios, summary, reset,
    value: () => attr.type === 'color' ? radios.find(r => r.checked)?.value ?? '' : control.value };
}
const rootOf = fields => ({ querySelectorAll: () => fields.map(f => f.element) });

test('shared IDs preserve all four types, including zero, without accidental explicit clears', () => {
  const attrs = [
    { id: 1, type: 'text' }, { id: 2, type: 'number', unit: 'кг' },
    { id: 3, type: 'select', options: ['S', 'M'] }, { id: 4, type: 'color', colors },
  ];
  const values = ['Хлопок', '0', 'M', '54'];
  const source = attrs.map((a, i) => field(a, values[i]));
  const destination = attrs.map(a => field(a));
  const drafts = new Map();
  captureAttributeDrafts(rootOf(source), drafts);
  restoreAttributeDrafts(rootOf(destination), drafts);
  assert.deepEqual(destination.map(f => f.value()), values);
  assert.equal(destination[3].summary.textContent, 'Выбран: Серебристый');
  assert.equal(destination[3].reset.hidden, false);
  // Going back must use the latest draft, not stale cached markup from category A.
  destination[0].control.value = 'Лён';
  captureAttributeDrafts(rootOf(destination), drafts);
  restoreAttributeDrafts(rootOf(source), drafts);
  assert.equal(source[0].value(), 'Лён');
});

test('same names with different IDs, incompatible units/types/options/colors never transfer', () => {
  const source = { definition: attributeDefinition({ id: 4, name: 'Цвет', type: 'color', colors }), value: '54' };
  for (const attr of [
    { id: 5, name: 'Цвет', type: 'color', colors },
    { id: 4, type: 'text' }, { id: 4, type: 'color', colors: colors.slice(0, 2) },
  ]) assert.equal(compatibleAttributeValue(source, attributeDefinition(attr)), false);
  assert.equal(compatibleAttributeValue({ definition: attributeDefinition({ id: 1, type: 'number', unit: 'кг' }), value: '12.5' }, attributeDefinition({ id: 1, type: 'number', unit: 'г' })), false);
  assert.equal(compatibleAttributeValue({ definition: attributeDefinition({ id: 1, type: 'select', options: ['M'] }), value: 'M' }, attributeDefinition({ id: 1, type: 'select', options: ['S'] })), false);
  const destination = field({ id: 5, name: 'Цвет', type: 'color', colors });
  restoreAttributeDrafts(rootOf([destination]), new Map([['4', source]]));
  assert.equal(destination.value(), '');
});

test('invalid values are not transferred; valid fractions and explicit empties are', () => {
  for (const [type, value, expected] of [['number', '12.5', true], ['number', '1e999', false], ['number', 'abc', false], ['text', 'x'.repeat(256), false], ['color', '', true]]) {
    const definition = attributeDefinition({ id: 1, type, colors });
    assert.equal(compatibleAttributeValue({ definition, value }, definition), expected);
  }
});

test('reset clears selection and submits an explicit empty value, including after switching back', () => {
  const attr = { id: 4, type: 'color', colors };
  const current = field(attr, '54');
  resetAttributeColor({ closest: () => current.element });
  assert.equal(current.value(), '');
  assert.equal(current.summary.textContent, 'Цвет не выбран');
  assert.equal(current.reset.hidden, true);
  const drafts = new Map();
  captureAttributeDrafts(rootOf([current]), drafts);
  const cached = field(attr, '54');
  restoreAttributeDrafts(rootOf([cached]), drafts);
  assert.equal(cached.value(), '');
  const html = renderAttributeFields([attr]);
  assert.ok(html.includes('<input type="hidden" name="attributes[4]" value="">'));
  assert.ok(html.indexOf('type="hidden"') < html.indexOf('type="radio"'));
});
