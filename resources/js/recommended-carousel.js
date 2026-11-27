// Recycle the original nodes: Alpine state and teleported dialogs stay attached.
export function createCarousel(track, { mutate = fn => fn(), focused = () => document.activeElement } = {}) {
    let step = 0;
    let loop = false;
    const movable = node => node && !node.contains(focused());
    function normalize() {
        if (!loop) return;
        let guard = track.children.length;
        while (track.scrollLeft >= step * 1.5 && guard-- > 0) {
            const first = track.firstElementChild;
            if (!movable(first)) break;
            const left = track.scrollLeft;
            mutate(() => track.appendChild(first));
            track.scrollLeft = left - step;
        }
        guard = track.children.length;
        while (track.scrollLeft < step * 0.5 && guard-- > 0) {
            const last = track.lastElementChild;
            if (!movable(last)) break;
            const left = track.scrollLeft;
            mutate(() => track.insertBefore(last, track.firstElementChild));
            track.scrollLeft = left + step;
        }
    }
    return {
        get step() { return step; },
        get looping() { return loop; },
        measure() {
            const cards = track.children;
            step = cards.length > 1
                ? cards[1].getBoundingClientRect().left - cards[0].getBoundingClientRect().left
                : 0;
            // Two spare steps allow recycling only completely hidden cards.
            loop = step > 0 && track.scrollWidth - track.clientWidth >= 2 * step - 1;
            normalize();
        },
        normalize,
        move(distance) {
            normalize();
            // Bound each write so a large wheel/arrow movement cannot skip the buffer.
            let remaining = distance;
            let applied = 0;
            while (Math.abs(remaining) > 0.01) {
                const part = Math.sign(remaining) * Math.min(Math.abs(remaining), step / 2 || Math.abs(remaining));
                const before = track.scrollLeft;
                track.scrollLeft += part;
                applied += track.scrollLeft - before;
                remaining -= part;
                normalize();
            }
            return applied;
        },
    };
}

export function canAnimate(state) {
    return state.looping && state.visible && !state.hidden && !state.reduced
        && !state.paused && !state.hovered && !state.focused && !state.interacting && !state.modal;
}

export function installMouseDrag(track, { move, interaction, windowTarget = window }) {
    const cleanups = [];
    let drag = null;
    let blockedClick = null;
    const controls = 'button, input, select, textarea, label, form, [role="button"], [contenteditable]:not([contenteditable="false"]), [data-carousel-no-drag]';
    const listen = (target, name, fn, options) => {
        target.addEventListener(name, fn, options);
        cleanups.push(() => target.removeEventListener(name, fn, options));
    };
    const finish = (event, cancelled = false) => {
        if (!drag || (event.pointerId !== undefined && event.pointerId !== drag.id)) return;
        // Only the click belonging to this release may be cancelled. An outside
        // release/cancellation must never poison a later click on a card control.
        blockedClick = !cancelled && drag.moved && track.contains(event.target)
            ? { id: drag.id, expires: event.timeStamp + 500 }
            : null;
        drag = null;
        track.classList.remove('is-dragging');
        interaction(false);
    };
    listen(windowTarget, 'pointerdown', () => {
        blockedClick = null;
        if (drag) finish({}, true);
    }, true);
    listen(track, 'pointerdown', event => {
        blockedClick = null;
        if (event.pointerType !== 'mouse' || event.button !== 0) return;
        // closest() also catches SVG paths and spans inside a button.
        if (event.target.closest?.(controls)) return;
        drag = { id: event.pointerId, x: event.clientX, startX: event.clientX, startY: event.clientY, moved: false };
        interaction(true);
    });
    listen(windowTarget, 'pointermove', event => {
        if (!drag || event.pointerId !== drag.id) return;
        if (!(event.buttons & 1)) { finish(event, true); return; }
        const dx = event.clientX - drag.startX;
        const dy = event.clientY - drag.startY;
        if (!drag.moved && (Math.abs(dx) < 8 || Math.abs(dx) <= Math.abs(dy))) return;
        drag.moved = true;
        event.preventDefault();
        track.classList.add('is-dragging');
        move(drag.x - event.clientX);
        drag.x = event.clientX;
    }, { passive: false });
    listen(windowTarget, 'pointerup', event => finish(event));
    listen(windowTarget, 'pointercancel', event => finish(event, true));
    listen(windowTarget, 'blur', event => finish(event, true));
    listen(track, 'dragstart', event => {
        if (drag) event.preventDefault();
    });
    listen(track, 'click', event => {
        if (!blockedClick || event.detail === 0) return;
        const matches = event.timeStamp <= blockedClick.expires
            && (event.pointerId === undefined || event.pointerId === blockedClick.id);
        blockedClick = null;
        if (!matches) return;
        event.preventDefault();
        event.stopImmediatePropagation();
    }, true);
    return () => {
        drag = blockedClick = null;
        track.classList.remove('is-dragging');
        cleanups.splice(0).forEach(cleanup => cleanup());
    };
}

export default function recommendedCarousel(Alpine) {
    let engine, track, root, frame = 0, previous = null, remainder = 0;
    let observer, resize, effect, media, resumeTimer, dragging = false, touching = false, destroyed = false;
    const cleanups = [];
    const state = { looping: false, visible: false, hidden: false, reduced: false,
        paused: false, hovered: false, focused: false, interacting: false, modal: false };
    function listen(target, name, callback, options) {
        target.addEventListener(name, callback, options);
        cleanups.push(() => target.removeEventListener(name, callback, options));
    }
    function sync() {
        if (!canAnimate(state)) {
            cancelAnimationFrame(frame);
            frame = 0;
            previous = null;
            remainder = 0;
        } else if (!frame) frame = requestAnimationFrame(tick);
    }
    function tick(time) {
        frame = 0;
        if (!canAnimate(state)) return;
        if (previous !== null) {
            remainder += Math.min(time - previous, 50) * 0.024; // 24 px/second
            // Preserve subpixel motion; retain rounding remainder on integer-scroll engines.
            remainder -= engine.move(remainder);
        }
        previous = time;
        frame = requestAnimationFrame(tick);
    }
    function interaction() {
        state.interacting = true;
        clearTimeout(resumeTimer);
        sync();
        resumeTimer = setTimeout(() => {
            if (dragging || touching) return;
            state.interacting = false;
            sync();
        }, 1500);
    }
    return {
        paused: false,
        reduced: false,
        init() {
            root = this.$el;
            track = this.$refs.track;
            engine = createCarousel(track, { mutate: fn => Alpine.mutateDom(fn) });
            media = window.matchMedia('(prefers-reduced-motion: reduce)');
            const motion = () => { this.reduced = state.reduced = media.matches; sync(); };
            motion();
            listen(media, 'change', motion);
            const visibility = () => { state.hidden = document.hidden; sync(); };
            visibility();
            listen(document, 'visibilitychange', visibility);
            listen(root, 'mouseenter', () => { state.hovered = true; sync(); });
            listen(root, 'mouseleave', () => { state.hovered = false; sync(); });
            listen(root, 'focusin', () => { state.focused = true; sync(); });
            listen(root, 'focusout', () => queueMicrotask(() => {
                state.focused = root.contains(document.activeElement); sync();
            }));
            listen(track, 'scroll', () => {
                engine.normalize();
                // Native touch inertia keeps postponing automatic motion.
                if (state.interacting) interaction();
            }, { passive: true });
            listen(track, 'wheel', event => {
                interaction();
                if (Math.abs(event.deltaX) > Math.abs(event.deltaY)) {
                    event.preventDefault();
                    engine.move(event.deltaX * (event.deltaMode === 1 ? 16 : event.deltaMode === 2 ? track.clientWidth : 1));
                }
            }, { passive: false });
            listen(track, 'touchstart', () => { touching = true; interaction(); }, { passive: true });
            const endTouch = () => { touching = false; interaction(); };
            listen(track, 'touchend', endTouch, { passive: true });
            listen(track, 'touchcancel', endTouch, { passive: true });
            cleanups.push(installMouseDrag(track, {
                move: distance => engine.move(distance),
                interaction: active => { dragging = active; interaction(); },
            }));
            listen(track, 'keydown', event => {
                if (event.target !== track || !['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
                event.preventDefault();
                this.scroll(event.key === 'ArrowLeft' ? -1 : 1);
            });
            resize = new ResizeObserver(() => {
                engine.measure(); state.looping = engine.looping; sync();
            });
            resize.observe(track);
            observer = new IntersectionObserver(entries => {
                state.visible = entries[0].isIntersecting; sync();
            });
            observer.observe(root);
            this.$nextTick(() => {
                if (destroyed) return;
                const cards = [...track.querySelectorAll('.pc-card')];
                effect = Alpine.effect(() => {
                    // Read every state so closing one dialog cannot hide another.
                    state.modal = cards.map(card => Boolean(Alpine.$data(card).open)).some(Boolean);
                    sync();
                });
            });
        },
        scroll(direction) { interaction(); engine.move(direction * engine.step); },
        togglePause() { state.paused = this.paused = !this.paused; sync(); },
        destroy() {
            destroyed = true;
            state.visible = false;
            sync();
            clearTimeout(resumeTimer);
            observer?.disconnect();
            resize?.disconnect();
            if (effect) Alpine.release(effect);
            cleanups.splice(0).forEach(cleanup => cleanup());
        },
    };
}

export function categoryCarousel(Alpine) {
    let engine, track, root, frame = 0, previous = null, remainder = 0;
    let observer, resize, media, resumeTimer, touchStartX = 0, touchMoved = false;
    let originals = [], generatedSets = 1, fillLoopBuffer = () => {}, interact = () => {};
    let blockedTouchClick = null;
    const cleanups = [];
    const state = { looping: false, visible: false, hidden: false, reduced: false,
        paused: false, hovered: false, focused: false, interacting: false, touching: false, dragging: false };

    function listen(target, name, callback, options) {
        target.addEventListener(name, callback, options);
        cleanups.push(() => target.removeEventListener(name, callback, options));
    }

    function sync() {
        if (!canAnimate(state)) {
            cancelAnimationFrame(frame);
            frame = 0;
            previous = null;
            remainder = 0;
        } else if (!frame) frame = requestAnimationFrame(tick);
    }

    function tick(time) {
        frame = 0;
        if (!canAnimate(state)) return;
        if (previous !== null) {
            remainder += Math.min(time - previous, 50) * 0.012; // 12 px/second, opposite to recommendations.
            const applied = engine.move(-remainder);
            remainder = Math.max(0, remainder + applied);
        }
        previous = time;
        frame = requestAnimationFrame(tick);
    }

    return {
        init() {
            root = this.$el;
            track = this.$refs.track;
            originals = [...track.children];
            if (!originals.length) return;

            const duplicate = source => {
                const clone = source.cloneNode(true);
                clone.setAttribute('aria-hidden', 'true');
                clone.tabIndex = -1;
                clone.querySelectorAll('a, button, [tabindex]').forEach(element => { element.tabIndex = -1; });
                return clone;
            };

            engine = createCarousel(track, { mutate: fn => Alpine.mutateDom(fn) });
            engine.measure();

            fillLoopBuffer = () => {
                // Append complete ordered rounds so edge-node recycling always preserves the cycle.
                while (generatedSets < 256) {
                    engine.measure();
                    if (engine.looping || !engine.step) return;
                    originals.forEach(category => track.appendChild(duplicate(category)));
                    generatedSets++;
                }
                engine.measure();
            };

            fillLoopBuffer();
            track.scrollLeft = engine.step;
            engine.measure();
            state.looping = engine.looping;

            interact = () => {
                state.interacting = true;
                clearTimeout(resumeTimer);
                sync();
                resumeTimer = setTimeout(() => {
                    if (state.touching || state.dragging) return;
                    state.interacting = false;
                    sync();
                }, 1500);
            };

            media = window.matchMedia('(prefers-reduced-motion: reduce)');
            const motion = () => { state.reduced = media.matches; sync(); };
            motion();
            listen(media, 'change', motion);
            const visibility = () => { state.hidden = document.hidden; sync(); };
            visibility();
            listen(document, 'visibilitychange', visibility);
            listen(root, 'mouseenter', () => { state.hovered = true; sync(); });
            listen(root, 'mouseleave', () => { state.hovered = false; sync(); });
            listen(root, 'focusin', () => {
                state.focused = track.contains(document.activeElement);
                sync();
            });
            listen(root, 'focusout', () => queueMicrotask(() => {
                state.focused = track.contains(document.activeElement);
                sync();
            }));
            listen(track, 'scroll', () => {
                engine.normalize();
                if (state.interacting) interact();
            }, { passive: true });
            listen(track, 'wheel', event => {
                interact();
                if (Math.abs(event.deltaX) > Math.abs(event.deltaY)) {
                    event.preventDefault();
                    engine.move(event.deltaX * (event.deltaMode === 1 ? 16 : event.deltaMode === 2 ? track.clientWidth : 1));
                }
            }, { passive: false });
            listen(track, 'touchstart', event => {
                state.touching = true;
                touchStartX = event.touches[0]?.clientX ?? 0;
                touchMoved = false;
                blockedTouchClick = null;
                interact();
            }, { passive: true });
            listen(track, 'touchmove', event => {
                if (Math.abs((event.touches[0]?.clientX ?? touchStartX) - touchStartX) > 8) touchMoved = true;
            }, { passive: true });
            const endTouch = event => {
                state.touching = false;
                if (touchMoved) blockedTouchClick = { expires: event.timeStamp + 600 };
                interact();
            };
            listen(track, 'touchend', endTouch, { passive: true });
            listen(track, 'touchcancel', endTouch, { passive: true });
            listen(track, 'click', event => {
                if (!blockedTouchClick || event.detail === 0 || !event.target.closest?.('a')) return;
                const shouldBlock = event.timeStamp <= blockedTouchClick.expires;
                blockedTouchClick = null;
                if (!shouldBlock) return;
                event.preventDefault();
                event.stopImmediatePropagation();
            }, true);
            cleanups.push(installMouseDrag(track, {
                move: distance => engine.move(distance),
                interaction: active => { state.dragging = active; interact(); },
            }));
            listen(track, 'keydown', event => {
                if (event.target !== track || !['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
                event.preventDefault();
                this.scroll(event.key === 'ArrowLeft' ? -1 : 1);
            });

            resize = new ResizeObserver(() => {
                fillLoopBuffer();
                engine.measure();
                state.looping = engine.looping;
                sync();
            });
            resize.observe(track);
            observer = new IntersectionObserver(entries => {
                state.visible = entries[0].isIntersecting;
                sync();
            });
            observer.observe(root);
        },
        scroll(direction) {
            if (!engine) return;
            fillLoopBuffer();
            engine.measure();
            state.looping = engine.looping;
            if (!engine.looping) return;
            interact();
            engine.move(direction * engine.step);
            sync();
        },
        destroy() {
            state.visible = false;
            sync();
            clearTimeout(resumeTimer);
            observer?.disconnect();
            resize?.disconnect();
            cleanups.splice(0).forEach(cleanup => cleanup());
        },
    };
}
