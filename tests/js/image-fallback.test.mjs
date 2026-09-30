import { test } from 'node:test';
import assert from 'node:assert/strict';

const listeners = new Map();
globalThis.location = { origin: 'https://example.test' };
globalThis.document = {
  baseURI: 'https://example.test/', readyState: 'loading',
  addEventListener(name, callback) { listeners.set(name, callback); },
  createElement() { return { textContent: '', className: '' }; },
};
globalThis.HTMLImageElement = class {};
const { recoverImage } = await import('../../resources/js/image-fallback.js');

function image({ src = 'https://example.test/storage/missing.webp', fallback = 'https://example.test/images/image-placeholder.svg', candidates, srcset = false, picture = false, currentSrc } = {}) {
  const source = { removed: false, removeAttribute(name) { if (name === 'srcset') this.removed = true; } };
  const img = new HTMLImageElement();
  img.src = src;
  img.currentSrc = currentSrc ?? src;
  img.dataset = { imageFallback: fallback, ...(candidates ? { imageCandidates: JSON.stringify(candidates) } : {}) };
  img.hasAttribute = name => name === 'srcset' && srcset && !img.srcsetRemoved;
  img.removeAttribute = name => { if (name === 'srcset') img.srcsetRemoved = true; };
  img.closest = () => picture ? { querySelectorAll: () => source.removed ? [] : [source] } : null;
  return { img, source };
}

test('tries thumb, original, then fallback once, without looping', () => {
  const thumb = 'https://example.test/storage/thumb/x.webp';
  const original = 'https://example.test/storage/medium/x.webp';
  const fallback = 'https://example.test/images/image-placeholder.svg';
  const { img } = image({ src: thumb, candidates: [thumb, original, fallback] });
  recoverImage(img); assert.equal(img.src, original);
  recoverImage(img); assert.equal(img.src, fallback);
  recoverImage(img); assert.equal(img.src, fallback);
});

test('broken img srcset and picture source are removed before fallback', () => {
  const { img, source } = image({ srcset: true, picture: true });
  recoverImage(img);
  assert.equal(img.srcsetRemoved, true);
  assert.equal(source.removed, true);
  assert.equal(img.src, img.dataset.imageFallback);
});

test('src equal to fallback with broken picture source retries the img URL', () => {
  const fallback = 'https://example.test/images/image-placeholder.svg';
  const { img, source } = image({ src: fallback, picture: true, currentSrc: 'https://example.test/storage/broken.webp' });
  recoverImage(img);
  assert.equal(source.removed, true);
  assert.equal(img.src, fallback);
  recoverImage(img);
  assert.equal(img.src, fallback);
});

test('unsafe candidate metadata cannot send the browser to another origin', () => {
  const { img } = image({ candidates: ['https://evil.test/a.jpg', 'javascript:alert(1)'] });
  recoverImage(img);
  assert.equal(img.src, img.dataset.imageFallback);
});
