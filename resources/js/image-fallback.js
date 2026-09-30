// Opt-in display recovery. Candidate URLs are supplied by the application.
const attempts = new WeakMap();

function localUrl(value) {
  if (typeof value !== 'string' || !value) return null;
  try {
    const url = new URL(value, document.baseURI);
    return url.origin === location.origin && ['http:', 'https:'].includes(url.protocol)
      && !url.username && !url.password ? url.href : null;
  } catch { return null; }
}

export function recoverImage(image) {
  if (!(image instanceof HTMLImageElement)) return;
  if (image.dataset.imageUnavailable) {
    const notice = document.createElement('span');
    notice.textContent = image.dataset.imageUnavailable;
    notice.className = 'block p-4 text-sm';
    image.replaceWith(notice);
    return;
  }
  const fallback = localUrl(image.dataset.imageFallback);
  if (!fallback) return;

  const source = image.src;
  const selected = image.currentSrc;
  const sources = [...(image.closest('picture')?.querySelectorAll('source[srcset]') ?? [])];
  const responsive = image.hasAttribute('srcset') || sources.length > 0;
  // Only called after failure. A working responsive image is never modified.
  image.removeAttribute('srcset');
  sources.forEach(item => item.removeAttribute('srcset'));

  const signature = image.dataset.imageCandidates ?? '';
  let state = attempts.get(image);
  if (!state || state.expected !== source || state.signature !== signature) {
    state = { expected: source, signature, tried: new Set() };
    attempts.set(image, state);
  }

  let candidates = [];
  try {
    const parsed = JSON.parse(signature || '[]');
    if (Array.isArray(parsed)) candidates = parsed.slice(0, 6).map(localUrl).filter(Boolean);
  } catch { /* Invalid metadata cannot supply a URL. */ }
  candidates = [...new Set([...candidates, fallback])];

  // src may already be the placeholder while a picture/source actually failed.
  const trySrc = responsive && selected !== source && localUrl(source);
  if (!trySrc) state.tried.add(source);
  const position = candidates.indexOf(source);
  const remaining = position >= 0 ? candidates.slice(position + 1) : [fallback];
  const next = [...(trySrc ? [source] : []), ...remaining].find(url => !state.tried.has(url));
  if (!next) return;
  state.tried.add(next);
  state.expected = next;
  image.src = next;
}

if (typeof document !== 'undefined') {
  document.addEventListener('error', event => recoverImage(event.target), true);
  const recoverCompletedImages = () => {
    document.querySelectorAll('img[data-image-fallback], img[data-image-unavailable]').forEach(image => {
      if (image.complete && image.naturalWidth === 0 && image.getAttribute('src')) recoverImage(image);
    });
  };
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', recoverCompletedImages, { once: true });
  } else {
    recoverCompletedImages();
  }
}
