const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
  '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
}[char]));
const safeColor = value => /^#(?:[a-f0-9]{3}|[a-f0-9]{6}|[a-f0-9]{8})$/i.test(value ?? '') ? value : '#e2e8f0';

export function attributeDefinition(attr) {
  return { id: String(attr.id), type: attr.type, unit: attr.unit ?? null,
    options: (attr.options ?? []).map(String), colors: (attr.colors ?? []).map(c => String(c.id)) };
}

export function compatibleAttributeValue(source, target) {
  if (!source || source.definition.id !== target.id || source.definition.type !== target.type
      || source.definition.unit !== target.unit) return false;
  const value = source.value;
  if (typeof value !== 'string' || [...value].length > 255) return false;
  if (value === '') return true; // A deliberate clear must survive a category switch too.
  switch (target.type) {
    case 'text': case 'string': return true;
    case 'number': return /^[+-]?(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?$/.test(value) && Number.isFinite(Number(value));
    case 'select': return target.options.includes(value);
    case 'color': return target.colors.includes(value);
    case 'boolean': return value === '0' || value === '1';
    default: return false;
  }
}

// Only values actually displayed/entered in this form are retained. No history is loaded.
export function captureAttributeDrafts(root, drafts) {
  root.querySelectorAll('[data-attribute-definition]').forEach(field => {
    const definition = JSON.parse(field.dataset.attributeDefinition);
    let value;
    if (definition.type === 'color') value = field.querySelector('input[type="radio"]:checked')?.value ?? '';
    else if (definition.type === 'boolean') value = field.querySelector('input[type="checkbox"]').checked ? '1' : '0';
    else value = field.querySelector('input, select, textarea').value;
    drafts.set(definition.id, { definition, value });
  });
}

export function restoreAttributeDrafts(root, drafts) {
  root.querySelectorAll('[data-attribute-definition]').forEach(field => {
    const definition = JSON.parse(field.dataset.attributeDefinition);
    const draft = drafts.get(definition.id);
    if (!compatibleAttributeValue(draft, definition)) return;
    if (definition.type === 'color') {
      field.querySelectorAll('input[type="radio"]').forEach(input => { input.checked = input.value === draft.value; });
      refreshColorField(field);
    } else if (definition.type === 'boolean') {
      field.querySelector('input[type="checkbox"]').checked = draft.value === '1';
    } else field.querySelector('input, select, textarea').value = draft.value;
  });
}

export function renderAttributeFields(attributes) {
  return `<section class="seller-form-card">
    <div class="seller-section-head"><div><p class="seller-section-kicker">03</p>
      <h2 class="seller-section-title">Характеристики</h2></div>
      <p class="seller-section-hint">Поля меняются под выбранную категорию.</p></div>
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">${attributes.map(attr => {
    const id = `seller-attribute-${escapeHtml(attr.id)}`;
    const name = `attributes[${escapeHtml(attr.id)}]`;
    const value = attr.value ?? '';
    const label = escapeHtml(attr.name) + (attr.unit ? ` (${escapeHtml(attr.unit)})` : '');
    const color = attr.type === 'color';
    let control;
    if (color) {
      const colors = attr.colors ?? [];
      const selected = colors.find(c => String(c.id) === String(value));
      control = `<input type="hidden" name="${name}" value="">
        <p id="${id}-hint" class="mb-3 text-sm text-neutral-500">Выберите один цвет.</p>
        ${colors.length ? `<div class="seller-color-grid">${colors.map(c => `<label class="seller-color-option">
          <input type="radio" name="${name}" value="${escapeHtml(c.id)}" data-color-name="${escapeHtml(c.name)}"
            class="sr-only" ${String(c.id) === String(value) ? 'checked' : ''} aria-describedby="${id}-hint">
          <span class="seller-color-card"><span class="seller-color-swatch" style="background-color:${safeColor(c.hex)}" aria-hidden="true"></span>
            <span class="min-w-0 break-words">${escapeHtml(c.name)}</span><span class="seller-color-check" aria-hidden="true">✓</span></span>
        </label>`).join('')}</div>` : '<p class="text-sm text-neutral-500">Цвета не настроены администратором.</p>'}
        <p class="mt-3 text-sm font-medium text-neutral-700" data-color-summary aria-live="polite">${selected ? `Выбран: ${escapeHtml(selected.name)}` : 'Цвет не выбран'}</p>
        <button type="button" data-color-reset ${selected ? '' : 'hidden'} class="mt-2 rounded text-sm font-medium text-brand-700 underline underline-offset-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-brand-600">Сбросить цвет</button>`;
    } else if (attr.type === 'select') {
      control = `<select id="${id}" name="${name}" class="seller-input"><option value="">Выберите значение</option>
        ${(attr.options ?? []).map(o => `<option value="${escapeHtml(o)}" ${String(o) === String(value) ? 'selected' : ''}>${escapeHtml(o)}</option>`).join('')}</select>`;
    } else if (attr.type === 'boolean') {
      control = `<input type="hidden" name="${name}" value="0"><label class="inline-flex items-center gap-3 text-sm text-neutral-700">
        <input id="${id}" type="checkbox" name="${name}" value="1" ${value && value !== '0' ? 'checked' : ''}
          class="rounded border-neutral-300 text-brand-600 focus:ring-brand-500">Да / Нет</label>`;
    } else {
      const number = attr.type === 'number';
      control = `<input id="${id}" type="${number ? 'number' : 'text'}" ${number ? 'step="any"' : ''}
        name="${name}" value="${escapeHtml(value)}" class="seller-input" placeholder="${number ? 'Введите число' : 'Введите значение'}"
        ${number ? '' : `aria-describedby="${id}-hint"`}>
        ${number ? '' : `<p id="${id}-hint" class="mt-2 text-xs text-neutral-500">Укажите значение для вашего товара.</p>`}`;
    }
    return `<fieldset data-attribute-definition="${escapeHtml(JSON.stringify(attributeDefinition(attr)))}" class="seller-attribute-field min-w-0 ${color ? 'sm:col-span-2' : ''}" ${color ? 'data-color-field' : ''}>
      <legend class="mb-2 text-sm font-semibold text-neutral-800">${color ? label : `<label for="${id}">${label}</label>`}</legend>${control}</fieldset>`;
  }).join('')}</div></section>`;
}

export function updateColorSummary(input) {
  const field = input.closest('[data-color-field]');
  if (field && input.type === 'radio' && input.checked) {
    refreshColorField(field);
  }
}

function refreshColorField(field) {
  const selected = field.querySelector('input[type="radio"]:checked');
  field.querySelector('[data-color-summary]').textContent = selected ? `Выбран: ${selected.dataset.colorName}` : 'Цвет не выбран';
  field.querySelector('[data-color-reset]').hidden = !selected;
}

export function resetAttributeColor(button) {
  const field = button.closest('[data-color-field]');
  if (!field) return;
  field.querySelectorAll('input[type="radio"]').forEach(input => { input.checked = false; });
  refreshColorField(field);
  field.querySelector('input[type="radio"]')?.focus();
}

// innerHTML alone loses the current input properties when a category is revisited.
export function snapshotAttributeFields(root) {
  const clone = root.cloneNode(true);
  const originals = root.querySelectorAll('input, select, textarea');
  clone.querySelectorAll('input, select, textarea').forEach((field, i) => {
    const original = originals[i];
    if (field.tagName === 'SELECT') {
      [...field.options].forEach((option, index) => option.toggleAttribute('selected', original.options[index].selected));
    } else if (field.tagName === 'TEXTAREA') {
      field.textContent = original.value;
    } else {
      field.setAttribute('value', original.value);
      field.toggleAttribute('checked', original.checked);
    }
  });
  return clone.innerHTML;
}
