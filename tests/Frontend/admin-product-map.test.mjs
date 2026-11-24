import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { initAdminProductMap } from '../../resources/js/admin-product-map.js';
import { geocodeLocation } from '../../resources/js/product-geocoding.js';
const tick = () => new Promise(resolve => setImmediate(resolve));
function fixture() {
    const fields = {};
    for (const key of ['[name="country_id"]', '[name="city_id"]', '#address', '#addressError', '#latitude', '#longitude', '#searchAddress']) {
        fields[key] = { value: '', selectedOptions: [], events: {}, classList: { toggle() {} }, addEventListener(type, handler) { this.events[type] = handler; } };
    }
    fields['[name="country_id"]'].value = '1'; fields['[name="country_id"]'].selectedOptions = [{ text: 'Страна' }];
    fields['[name="city_id"]'].value = '2'; fields['[name="city_id"]'].selectedOptions = [{ text: 'Город' }];
    fields['#latitude'].value = '47.123456789'; fields['#longitude'].value = '28.123456789'; fields['#address'].value = 'ул. Ленина, 2';
    let resolveLibrary, maps = 0;
    const events = {}, moves = [], requests = [];
    const map = { setView(point) { moves.push(point); return this; }, on(name, handler) { events[name] = handler; } };
    const marker = { addTo() { return this; }, setLatLng(point) { this.point = point; }, on(name, handler) { events[name] = handler; } };
    const L = { map() { maps++; return map; }, tileLayer: () => ({ addTo() {} }), marker: () => marker };
    globalThis.window = { loadLeaflet: () => new Promise(resolve => { resolveLibrary = resolve; }) };
    globalThis.fetch = (url, options) => new Promise((resolve, reject) => requests.push({ url, options, resolve, reject }));
    const element = { closest: () => ({ querySelector: key => fields[key] }) };
    return { fields, element, requests, moves, events, marker, count: () => maps, load: () => resolveLibrary(L), reply(index, data) { requests[index].resolve({ ok: true, json: async () => data }); } };
}
test('seller guard excludes admin before any initialization', () => {
    const source = readFileSync(new URL('../../resources/js/seller-product-form.js', import.meta.url), 'utf8').replace(/^import .*;\r?\n/gm, '');
    let handler;
    const window = {};
    vm.runInNewContext(source, { window, document: { addEventListener: (_, fn) => { handler = fn; }, querySelector: () => null } });
    handler(); assert.equal(window.__sellerProductFormInitialized, undefined);
    assert.match(readFileSync(new URL('../../resources/views/seller/products/form.blade.php', import.meta.url), 'utf8'), /data-seller-product-form/);
});

test('unchanged seller map initializer uses saved coordinates and creation defaults', () => {
    const source = readFileSync(new URL('../../resources/js/seller-product-form.js', import.meta.url), 'utf8');
    const start = source.indexOf('  function initMap()');
    const end = source.indexOf("  if (typeof L === 'undefined')", start);
    const code = source.slice(start, end) + '\ninitMap();';
    for (const dataset of [{}, { lat: '48.5', lng: '29.5', zoom: '14' }]) {
        let view, coords;
        const map = { setView(point) { view = point; return this; }, on() {} };
        const marker = { addTo() { return this; }, getLatLng: () => ({ lat: view[0], lng: view[1] }), on() {} };
        vm.runInNewContext(code, { mapEl: { dataset }, searchBtn: null, updateCoords: value => { coords = value; },
            L: { map: () => map, marker: () => marker, tileLayer: () => ({ addTo() {} }) } });
        assert.equal(coords.lat, dataset.lat ? 48.5 : 47.0105);
        assert.equal(coords.lng, dataset.lng ? 29.5 : 28.8638);
    }
});


test('shared search uses the seller URL, first result and no name filters', async () => {
    const calls = [];
    globalThis.fetch = async (...args) => { calls.push(args); return { ok: true, json: async () => [
        { lat: '47.7', lon: '27.9', name: 'Bălți', address: { country: 'Moldova' } },
        { lat: '48', lon: '28' },
    ] }; };
    assert.deepEqual(await geocodeLocation('Бельцы, Молдова'), { lat: 47.7, lng: 27.9 });
    assert.equal(calls[0][0], 'https://nominatim.openstreetmap.org/search?format=json&limit=1&addressdetails=1&q=' + encodeURIComponent('Бельцы, Молдова'));
    assert.equal(calls[0][1].headers['Accept-Language'], 'ru');
    for (const data of [[{ lat: 'NaN', lon: '1' }], [{ lat: '91', lon: '1' }], {}]) {
        globalThis.fetch = async () => ({ ok: true, json: async () => data });
        await assert.rejects(geocodeLocation('test'));
    }
    globalThis.fetch = async () => ({ ok: true, json: async () => [] });
    assert.equal(await geocodeLocation('test'), null);
});

// Synthetic responses exercise the real controller; they are not live geocoder checks.
for (const mode of ['create', 'edit']) {
    for (const [country, city] of [
        ['Молдова', 'Каушаны'], ['Молдова', 'Бельцы'], ['Молдова', 'Кишинёв'],
        ['Приднестровье', 'Днестровск'], ['Приднестровье', 'Тирасполь'],
        ['Приднестровье', 'Бендеры'], ['Приднестровье', 'Григориополь'], ['Украина', 'Киев'],
    ]) {
        test(`${mode}: ${country} → ${city}, city/address separation and manual marker`, async () => {
            const template = readFileSync(new URL(`../../resources/views/admin/products/${mode}.blade.php`, import.meta.url), 'utf8');
            assert.match(template, /initAdminProductMap/);
            assert.doesNotMatch(template, /L\.map\(/);
            const f = fixture();
            const oldAddress = 'переулок Штефан чел маре, 4, Briceni, Молдова';
            f.fields['#address'].value = oldAddress;
            const ready = initAdminProductMap(f.element, { latitude: 48.36, longitude: 27.08 });
            assert.equal(initAdminProductMap(f.element), ready);
            assert.equal(f.count(), 0); f.load(); await ready;
            assert.equal(f.count(), 1); assert.equal(f.requests.length, 0);
            assert.equal(f.fields['#latitude'].value, '47.123456789');
            assert.equal(f.fields['#address'].value, oldAddress);
            f.fields['[name="country_id"]'].selectedOptions = [{ text: country }];
            f.fields['[name="city_id"]'].selectedOptions = [{ text: city }];
            f.fields['[name="city_id"]'].events.change(); await tick();
            const url = new URL(f.requests[0].url);
            assert.equal(url.searchParams.get('q'), `${city}, ${country}`);
            assert.equal(url.searchParams.get('limit'), '1');
            assert.equal(url.searchParams.has('countrycodes'), false);
            f.reply(0, [{ lat: '47.5', lon: '29.5' }]); await tick();
            assert.equal(f.fields['#latitude'].value, '47.500000');
            assert.equal(f.fields['#address'].value, `${city}, ${country}`);
            f.fields['#address'].value = 'улица Центральная, 10';
            f.fields['#searchAddress'].events.click(); await tick();
            assert.equal(new URL(f.requests[1].url).searchParams.get('q'), 'улица Центральная, 10');
            f.reply(1, [{ lat: '47.6', lon: '29.6' }]); await tick();
            assert.equal(f.marker.point.lat, 47.6);
            f.events.dragend({ target: { getLatLng: () => ({ lat: 47.7, lng: 29.7 }) } });
            f.reply(2, { address: { road: 'Другая улица', city: 'Другой город', country: 'Другая страна' } }); await tick();
            assert.equal(f.fields['#latitude'].value, '47.700000');
            assert.match(f.fields['#address'].value, /Другая улица/);
            assert.equal(f.fields['[name="city_id"]'].selectedOptions[0].text, city);
            assert.equal(f.fields['[name="country_id"]'].selectedOptions[0].text, country);
            const reopened = fixture();
            const again = initAdminProductMap(reopened.element, { latitude: 47.7, longitude: 29.7 });
            reopened.load(); await again;
            assert.deepEqual(reopened.moves[0], [47.7, 29.7]);
            assert.equal(reopened.requests.length, 0);
        });
    }
}
test('failures and stale search cannot overwrite existing coordinates', async () => {
    const f = fixture(); const ready = initAdminProductMap(f.element); f.load(); await ready;
    f.fields['[name="city_id"]'].events.change(); await tick();
    f.reply(0, []); await tick();
    assert.equal(f.fields['#latitude'].value, '47.123456789');
    assert.match(f.fields['#addressError'].textContent, /остались прежними/);
    f.fields['[name="city_id"]'].events.change(); await tick();
    f.fields['[name="country_id"]'].events.change();
    f.reply(1, [{ lat: '51', lon: '31' }]); await tick();
    assert.equal(f.fields['#latitude'].value, '47.123456789');
    f.fields['[name="city_id"]'].events.change(); await tick();
    f.requests[2].reject(new Error('offline')); await tick();
    assert.equal(f.fields['#latitude'].value, '47.123456789');
    f.fields['[name="city_id"]'].events.change(); await tick();
    f.events.click({ latlng: { lat: 50, lng: 30 } });
    f.reply(3, [{ lat: '52', lon: '32' }]); await tick();
    assert.equal(f.fields['#latitude'].value, '50.000000');
    f.reply(4, {}); await tick();
});
test('seller city handler uses the shared geocoder without changing movement', async () => {
    const source = readFileSync(new URL('../../resources/js/seller-product-form.js', import.meta.url), 'utf8');
    const handler = source.slice(source.indexOf('  async function geocodeAndMove'), source.indexOf('  function initMap()'));
    const calls = [], moves = [], addressEl = { value: '' };
    const context = { geocodeLocation: async query => { calls.push(query); return { lat: 47, lng: 29 }; },
        mapInstance: { setView: (...args) => moves.push(args) }, markerInstance: { setLatLng() {} }, updateCoords() {}, addressEl };
    vm.createContext(context); vm.runInContext(handler, context);
    await vm.runInContext("geocodeAndMove('Днестровск', 'Приднестровье')", context);
    assert.deepEqual(calls, ['Днестровск, Приднестровье']);
    assert.equal(moves[0][1], 13); assert.equal(addressEl.value, 'Днестровск, Приднестровье');
});
