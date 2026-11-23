import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import recommendedCarousel, { createCarousel, canAnimate, installMouseDrag } from '../../resources/js/recommended-carousel.js';

function dragFixture(t) {
    const target = extra => Object.assign({
        listeners: new Map(),
        addEventListener(name, fn, options) { this.listeners.set(name, { fn, options }); },
        removeEventListener(name) { this.listeners.delete(name); },
        emit(name, event) { this.listeners.get(name)?.fn(event); },
    }, extra);
    const track = target({ classList: { add() {}, remove() {} } });
    const windowTarget = target({});
    const node = (tag, parent = track) => ({
        tag, parent,
        closest(selector) {
            let current = this;
            while (current && current !== track) {
                if (selector.split(', ').includes(current.tag)) return current;
                current = current.parent;
            }
            return null;
        },
    });
    track.contains = element => {
        for (; element; element = element.parent) if (element === track) return true;
        return false;
    };
    const distances = [];
    const cleanup = installMouseDrag(track, { windowTarget, move: x => distances.push(x), interaction() {} });
    t.after(cleanup);
    function event(overrides = {}) {
        return { target: track, pointerType: 'mouse', pointerId: 1, button: 0, buttons: 1,
            clientX: 100, clientY: 20, timeStamp: 100, detail: 1,
            preventDefault() { this.defaultPrevented = true; },
            stopImmediatePropagation() { this.stopped = true; }, ...overrides };
    }
    const down = (element, overrides = {}) => {
        const e = event({ target: element, ...overrides });
        windowTarget.emit('pointerdown', e);
        track.emit('pointerdown', e);
    };
    const move = overrides => windowTarget.emit('pointermove', event(overrides));
    const up = (element, overrides = {}) => windowTarget.emit('pointerup', event({ target: element, ...overrides }));
    const click = (element, handler, overrides = {}) => {
        const e = event({ target: element, ...overrides });
        track.emit('click', e); // capture phase precedes the card's Alpine handler
        if (!e.stopped && !e.defaultPrevented) handler();
        return e;
    };
    return { track, windowTarget, node, distances, event, down, move, up, click };
}

test('cart, favorite and quick-view buttons and their nested SVG/span clicks reach handlers', t => {
    const f = dragFixture(t);
    for (const action of ['cart', 'favorite', 'quick-view', 'buy']) {
        const button = f.node('button', action === 'buy' ? f.node('form') : f.track);
        const targets = [button, f.node('path', f.node('svg', button)), f.node('span', button)];
        for (const element of targets) {
            let calls = 0;
            f.down(element);
            // Regression: the old handler classified this button gesture as drag.
            f.move({ clientX: 92 });
            f.up(element);
            f.click(element, () => calls++);
            assert.equal(calls, 1, action);
        }
    }
    assert.deepEqual(f.distances, []);
});

test('ordinary product/custom-ad links and tiny mouse movements stay clickable', t => {
    const f = dragFixture(t);
    for (const element of [f.node('a'), f.node('span', f.node('a')), f.node('div')]) {
        let calls = 0;
        f.down(element);
        f.move({ clientX: 97 });
        f.up(element);
        f.click(element, () => calls++);
        assert.equal(calls, 1);
    }
    assert.deepEqual(f.distances, []);
});

test('cart click executes the existing Blade addToCart method and updates its counter', async t => {
    const f = dragFixture(t);
    const blade = readFileSync(new URL('../../resources/views/components/product-card.blade.php', import.meta.url), 'utf8');
    const definition = blade.match(/x-data="(\{[\s\S]*?\})"\s+@keydown.escape.window/)[1]
        .replace(/\{\{\s*\$cartQuantity\s*\}\}/g, '0')
        .replace(/\{\{[\s\S]*?\}\}/g, '1');
    const requests = [], notifications = [];
    // Run the unchanged card method with a fake response, never against Laravel.
    const card = new Function('fetch', 'window', 'CustomEvent', `return (${definition})`)(
        (url, options) => {
            requests.push({ url, options });
            return Promise.resolve({ ok: true, json: async () => ({ success: true, quantity: 2 }) });
        },
        { dispatchEvent: event => notifications.push(event) },
        class { constructor(type, options) { this.type = type; this.detail = options.detail; } },
    );
    const icon = f.node('path', f.node('svg', f.node('button')));
    f.down(icon);
    f.move({ clientX: 92 });
    f.up(icon);
    f.click(icon, () => card.addToCart());
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(requests.length, 1);
    assert.equal(requests[0].options.method, 'POST');
    assert.equal(card.cartCount, 2);
    assert.deepEqual(notifications[0].detail, { productId: 1, quantity: 2 });
    f.down(f.node('a'));
    f.move({ clientX: 70 });
    f.up(icon);
    f.click(icon, () => card.addToCart());
    assert.equal(requests.length, 1);
});

test('drag over a nested cart button suppresses only the release click, not the next action', t => {
    const f = dragFixture(t);
    const link = f.node('a');
    const icon = f.node('path', f.node('svg', f.node('button')));
    let cartCalls = 0;
    f.down(link);
    f.move({ clientX: 70 });
    f.up(icon);
    assert.equal(f.click(icon, () => cartCalls++).defaultPrevented, true);
    assert.equal(cartCalls, 0);
    assert.deepEqual(f.distances, [30]);
    f.down(icon);
    f.up(icon);
    f.click(icon, () => cartCalls++);
    assert.equal(cartCalls, 1);
});

test('outside releases, cancelled pointers and missing pointerup cannot poison later clicks', t => {
    const f = dragFixture(t);
    const link = f.node('a');
    const button = f.node('button');
    for (const ending of ['outside', 'cancel', 'blur', 'lost-release']) {
        let calls = 0;
        f.down(link);
        f.move({ clientX: 70 });
        if (ending === 'outside') f.up({});
        if (ending === 'cancel') f.windowTarget.emit('pointercancel', f.event());
        if (ending === 'blur') f.windowTarget.emit('blur', {});
        if (ending === 'lost-release') f.move({ buttons: 0 });
        f.click(button, () => calls++);
        assert.equal(calls, 1, ending);
    }
});

test('suppression expires and never blocks keyboard, touch or a new pointer sequence', t => {
    const f = dragFixture(t);
    const link = f.node('a');
    const button = f.node('button');
    for (const kind of ['expired', 'keyboard', 'touch', 'new-pointer']) {
        let calls = 0;
        f.down(link);
        f.move({ clientX: 70 });
        f.up(link);
        if (kind === 'touch') f.down(button, { pointerType: 'touch', pointerId: 2 });
        if (kind === 'new-pointer') f.down(button);
        f.click(button, () => calls++, { timeStamp: kind === 'expired' ? 601 : 101, detail: kind === 'keyboard' ? 0 : 1 });
        assert.equal(calls, 1, kind);
    }
});

function fixture(count = 10, width = 600, step = 100) {
    let left = 0;
    const track = {
        clientWidth: width, children: [],
        get scrollWidth() { return this.children.length * step; },
        get scrollLeft() { return left; },
        set scrollLeft(value) { left = Math.max(0, Math.min(value, this.scrollWidth - this.clientWidth)); },
        get firstElementChild() { return this.children[0]; },
        get lastElementChild() { return this.children.at(-1); },
        appendChild(node) { this.children.splice(this.children.indexOf(node), 1); this.children.push(node); },
        insertBefore(node, before) {
            this.children.splice(this.children.indexOf(node), 1);
            this.children.splice(this.children.indexOf(before), 0, node);
        },
    };
    track.children = Array.from({ length: count }, (_, id) => ({
        id, state: { favorite: false, cart: 0, open: false },
        contains(element) { return element === this; },
        getBoundingClientRect() { return { left: track.children.indexOf(this) * step - left }; },
    }));
    let focus = null;
    const engine = createCarousel(track, { focused: () => focus });
    engine.measure();
    return { track, engine, setFocus: value => { focus = value; }, setStep: value => { step = value; } };
}

test('loops in both directions with compensated visible coordinates', () => {
    const { track, engine } = fixture();
    const originals = new Set(track.children);
    for (const distance of [49, 2, 100, 2700, -4000, 51]) {
        const before = track.children.map(node => ({ node, x: node.getBoundingClientRect().left }));
        engine.move(distance);
        assert.equal(new Set(track.children).size, 10);
        assert.ok(track.children.every(node => originals.has(node)));
        for (const { node, x } of before) {
            const expected = ((x - distance + track.scrollLeft) % 1000 + 1000) % 1000 - track.scrollLeft;
            assert.equal(node.getBoundingClientRect().left, expected);
        }
        assert.ok(track.scrollLeft >= 50 && track.scrollLeft < 150);
    }
});

test('short lists remain finite, including one card', () => {
    for (const count of [1, 6, 7]) {
        const { track, engine } = fixture(count);
        const order = [...track.children];
        assert.equal(engine.looping, false);
        engine.move(1000);
        assert.deepEqual(track.children, order);
    }
});

test('resize recalculates fractional step and switches looping', () => {
    const { track, engine, setStep } = fixture();
    track.clientWidth = 950;
    engine.measure();
    assert.equal(engine.looping, false);
    track.clientWidth = 600;
    setStep(120.5);
    engine.measure();
    assert.equal(engine.step, 120.5);
    assert.equal(engine.looping, true);
    engine.move(120.5);
    assert.ok(track.scrollLeft >= 60.25 && track.scrollLeft < 180.75);
});

test('interactive state survives recycling and focused nodes never move', () => {
    const { track, engine, setFocus } = fixture();
    const card = track.firstElementChild;
    card.state = { favorite: true, cart: 3, open: true };
    const state = card.state;
    setFocus(card);
    engine.move(60);
    assert.equal(track.firstElementChild, card);
    setFocus(null);
    engine.normalize();
    assert.equal(track.lastElementChild, card);
    assert.equal(card.state, state);
    assert.deepEqual(card.state, { favorite: true, cart: 3, open: true });
});

test('pause/resume respects each reason including modal and reduced motion', () => {
    const running = { looping: true, visible: true };
    assert.equal(canAnimate(running), true);
    for (const key of ['hidden', 'reduced', 'paused', 'hovered', 'focused', 'interacting', 'modal']) {
        assert.equal(canAnimate({ ...running, [key]: true }), false, key);
        assert.equal(canAnimate({ ...running, [key]: false }), true, key);
    }
    assert.equal(canAnimate({ ...running, visible: false }), false);
    assert.equal(canAnimate({ ...running, looping: false }), false);
    assert.equal(canAnimate({ ...running, paused: false, modal: true }), false);
});

test('Blade retains custom ads, card parameters, links and separate catalog', () => {
    const blade = readFileSync(new URL('../../resources/views/shop/index.blade.php', import.meta.url), 'utf8');
    const block = blade.slice(blade.indexOf('@if($hasRecommendedProducts)'), blade.indexOf('@if($weeklyShopCampaigns'));
    assert.match(block, /TYPE_CUSTOM/);
    assert.match(block, /\$campaign->resolved_url/);
    assert.match(block, /:promotion-label="\$campaign->label"/);
    assert.match(block, /promotion-label="Высокий рейтинг"/);
    assert.match(block, /recommendations.index/);
    assert.doesNotMatch(block, /@continue|recommendedCardIndex/);
    assert.match(blade, /id="products-grid"[\s\S]*?grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-5/);
});

test('Alpine adapter stops RAF, observes teleported modal state and suppresses drag clicks', t => {
    const eventTarget = object => Object.assign(object, {
        listeners: new Map(),
        addEventListener(name, fn) { this.listeners.set(name, fn); },
        removeEventListener(name) { this.listeners.delete(name); },
        emit(name, event = {}) { this.listeners.get(name)?.(event); },
    });
    const { track } = fixture();
    eventTarget(track);
    track.classList = { add() {}, remove() {} };
    track.contains = node => node === track;
    const card = { open: false };
    track.querySelectorAll = () => [card];
    const root = eventTarget({ contains: () => false });
    const media = eventTarget({ matches: false });
    const windowMock = eventTarget({ matchMedia: () => media });
    const documentMock = eventTarget({ hidden: false, activeElement: null });
    let intersection, resized, effect, released = false, rafId = 0;
    const frames = new Map();
    const globals = {
        window: windowMock, document: documentMock,
        requestAnimationFrame: fn => { frames.set(++rafId, fn); return rafId; },
        cancelAnimationFrame: id => frames.delete(id),
        ResizeObserver: class { constructor(fn) { resized = fn; } observe() {} disconnect() {} },
        IntersectionObserver: class { constructor(fn) { intersection = fn; } observe() {} disconnect() {} },
    };
    for (const [key, value] of Object.entries(globals)) {
        const descriptor = Object.getOwnPropertyDescriptor(globalThis, key);
        Object.defineProperty(globalThis, key, { configurable: true, writable: true, value });
        t.after(() => descriptor ? Object.defineProperty(globalThis, key, descriptor) : delete globalThis[key]);
    }
    const component = recommendedCarousel({
        mutateDom: fn => fn(), $data: node => node,
        effect: fn => { effect = fn; fn(); return fn; },
        release: () => { released = true; },
    });
    component.$el = root;
    component.$refs = { track };
    component.$nextTick = fn => fn();
    component.init();
    resized();
    intersection([{ isIntersecting: true }]);
    assert.equal(frames.size, 1);
    const frame = time => {
        const [id, fn] = frames.entries().next().value;
        frames.delete(id); fn(time);
    };
    const before = track.scrollLeft;
    frame(0); frame(50);
    assert.ok(Math.abs(track.scrollLeft - before - 1.2) < 0.001);
    root.emit('mouseenter');
    assert.equal(frames.size, 0);
    root.emit('mouseleave');
    assert.equal(frames.size, 1);
    component.togglePause();
    assert.equal(frames.size, 0);
    component.togglePause();
    card.open = true; effect();
    root.emit('mouseleave');
    assert.equal(frames.size, 0);
    card.open = false; effect();
    assert.equal(frames.size, 1);
    media.matches = true; media.emit('change');
    assert.equal(frames.size, 0);
    media.matches = false; media.emit('change');
    documentMock.hidden = true; documentMock.emit('visibilitychange');
    assert.equal(frames.size, 0);
    documentMock.hidden = false; documentMock.emit('visibilitychange');
    intersection([{ isIntersecting: false }]);
    assert.equal(frames.size, 0);
    intersection([{ isIntersecting: true }]);
    let prevented = 0, stopped = 0;
    track.emit('pointerdown', { target: track, pointerType: 'mouse', button: 0, pointerId: 1, clientX: 100, clientY: 0 });
    windowMock.emit('pointermove', { pointerId: 1, buttons: 1, clientX: 75, clientY: 0, preventDefault() { prevented++; } });
    windowMock.emit('pointerup', { target: track, pointerId: 1, timeStamp: 100 });
    track.emit('click', { detail: 1, pointerId: 1, timeStamp: 101, preventDefault() { prevented++; }, stopImmediatePropagation() { stopped++; } });
    assert.equal(prevented, 2);
    assert.equal(stopped, 1);
    track.emit('click', { detail: 1, preventDefault() { throw Error('ordinary click blocked'); } });
    component.destroy();
    assert.equal(frames.size, 0);
    assert.equal(released, true);
    assert.equal(track.listeners.size, 0);
});
