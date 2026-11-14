import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

// Run the actual category cascade in isolation from unrelated form widgets.
const source = readFileSync(new URL('../../resources/js/seller-product-form.js', import.meta.url), 'utf8');
const categoryCode = source.slice(source.indexOf('  const catWrapper ='), source.indexOf('  const countrySelectEl ='));
const categories = [
  { id: 7, parent_id: null },
  { id: 217, parent_id: 7 },
  { id: 218, parent_id: 217 },
  { id: 219, parent_id: 217 },
];

function setup(initialId = '') {
  const pending = [];
  const displays = [];
  let display = '';
  const warning = { style: {
    get display() { return display; },
    set display(value) { display = value; displays.push(value); },
  } };
  const hidden = { value: initialId, tagName: 'INPUT' };
  const selects = [];
  const wrapperListeners = {};
  function select() {
    const listeners = {};
    return {
      tagName: 'SELECT', value: '',
      get nextElementSibling() { return selects[selects.indexOf(this) + 1] ?? hidden; },
      addEventListener(type, callback) { listeners[type] = callback; },
      matches(selector) { return selector === '.category-select'; },
      appendChild() {},
      insertAdjacentElement(position, child) { selects.splice(selects.indexOf(this) + 1, 0, child); },
      remove() { selects.splice(selects.indexOf(this), 1); },
      change(value) {
        this.value = value;
        const event = { target: this };
        listeners.change?.(event);
        wrapperListeners.change?.(event);
      },
    };
  }
  const root = select();
  selects.push(root);
  const wrapper = {
    querySelector: () => root,
    addEventListener(type, callback) { wrapperListeners[type] = callback; },
  };
  vm.runInNewContext(categoryCode, {
    categoryChildrenCache: new Map(),
    window: { allCategories: categories },
    document: {
      getElementById: id => ({ 'categories-wrapper': wrapper, category_id: hidden, 'category-missing-warning': warning })[id],
      createElement: tag => tag === 'select' ? select() : {},
    },
    fetch: url => new Promise(resolve => pending.push(() => resolve({
      ok: true,
      json: async () => categories.filter(category => String(category.parent_id) === url.split('/')[3]),
    }))),
  });
  async function flush() {
    for (let i = 0; i < 12; i++) {
      pending.splice(0).forEach(resolve => resolve());
      await new Promise(resolve => setImmediate(resolve));
    }
  }
  return { warning, displays, hidden, selects, root, flush };
}

test('creation: empty, parent, leaf, another leaf, parent again and cleared selection', async () => {
  const form = setup();
  assert.equal(form.warning.style.display, '');
  form.root.change('7');
  await form.flush();
  assert.equal(form.warning.style.display, '');
  form.selects[1].change('217');
  await form.flush();
  assert.equal(form.warning.style.display, '');
  form.selects[2].change('218');
  assert.equal(form.warning.style.display, 'none');
  assert.equal(form.hidden.value, '218');
  form.selects[2].change('219');
  assert.equal(form.warning.style.display, 'none');
  form.root.change('7');
  assert.equal(form.warning.style.display, '');
  form.root.change('');
  assert.equal(form.warning.style.display, '');
});

for (const state of ['edit', 'old input after validation']) {
  test(`${state}: restored leaf stays hidden throughout asynchronous restoration`, async () => {
    const form = setup('218');
    assert.equal(form.warning.style.display, 'none');
    await form.flush();
    assert.deepEqual(form.selects.map(select => String(select.value)), ['7', '217', '218']);
    assert.equal(String(form.hidden.value), '218');
    assert.ok(form.displays.every(display => display === 'none'));
  });
}

test('restored intermediate or unknown category does not hide the warning', async () => {
  for (const id of ['217', '999']) {
    const form = setup(id);
    await form.flush();
    assert.equal(form.warning.style.display, '');
  }
});
