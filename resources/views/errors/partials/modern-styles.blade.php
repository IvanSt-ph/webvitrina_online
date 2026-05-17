<style>
.error-chip {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    border-radius: 1rem;
    background: rgba(255,255,255,.9);
    padding: .65rem .85rem;
    font-size: .875rem;
    font-weight: 500;
    color: rgb(51 65 85);
    box-shadow: inset 0 0 0 1px rgba(203,213,225,.9);
    transition: .2s ease;
}
.error-chip:hover {
    color: rgb(67 56 202);
    box-shadow: inset 0 0 0 1px rgba(165,180,252,.95);
    transform: translateY(-1px);
}
.error-art-wrap {
    position: relative;
    display: flex;
    min-height: 320px;
    width: min(100%, 520px);
    align-items: center;
    justify-content: center;
}
@media (min-width: 640px) {
    .error-art-wrap { min-height: 420px; }
}
.art-badge {
    position: absolute;
    left: 0;
    top: 0;
    border-radius: 999px;
    background: rgba(255,255,255,.82);
    padding: .45rem .85rem;
    font-weight: 900;
    color: rgb(15 23 42);
    box-shadow: 0 12px 30px rgba(15,23,42,.08);
    backdrop-filter: blur(10px);
}
.art-caption {
    position: absolute;
    right: 0;
    bottom: 0;
    border-radius: 999px;
    border: 1px solid rgba(255,255,255,.8);
    background: rgba(255,255,255,.68);
    padding: .45rem .8rem;
    font-size: .75rem;
    font-weight: 700;
    color: rgb(100 116 139);
    backdrop-filter: blur(10px);
}
.cat-scene, .guard-scene, .server-scene {
    position: relative;
    width: 280px;
    height: 240px;
}
.cat-orbit {
    position: absolute;
    inset: 18px;
    border-radius: 999px;
    background: radial-gradient(circle at center, rgba(99,102,241,.18), rgba(99,102,241,0));
}
.cat, .guard-cat {
    position: absolute;
    left: 42px;
    bottom: 34px;
    width: 150px;
    height: 122px;
}
.cat .body, .guard-cat .body {
    position: absolute;
    left: 28px;
    bottom: 0;
    width: 108px;
    height: 76px;
    border-radius: 54px 54px 36px 36px;
    background: linear-gradient(145deg, #7c5cff, #5b4bd3);
    box-shadow: inset -10px -12px 0 rgba(255,255,255,.09);
    animation: cat-breathe 4s ease-in-out infinite;
}
.guard-cat .body {
    background: linear-gradient(145deg, #f59e0b, #d97706);
}
.cat .head, .guard-cat .head {
    position: absolute;
    left: 0;
    top: 8px;
    width: 94px;
    height: 80px;
    border-radius: 44px 44px 36px 36px;
    background: linear-gradient(145deg, #7c5cff, #5b4bd3);
}
.guard-cat .head {
    background: linear-gradient(145deg, #f59e0b, #d97706);
}
.cat .tail, .guard-cat .tail {
    position: absolute;
    right: 2px;
    bottom: 18px;
    width: 58px;
    height: 58px;
    border-right: 12px solid #6653de;
    border-bottom: 12px solid #6653de;
    border-radius: 0 0 52px 0;
    transform-origin: left top;
    animation: tail-wave 4s ease-in-out infinite;
}
.guard-cat .tail {
    border-color: #d97706;
}
.ear {
    position: absolute;
    top: -12px;
    width: 0;
    height: 0;
    border-left: 14px solid transparent;
    border-right: 14px solid transparent;
    border-bottom: 24px solid #705af0;
}
.guard-cat .ear { border-bottom-color: #f59e0b; }
.ear.left { left: 10px; transform: rotate(-18deg); }
.ear.right { right: 10px; transform: rotate(18deg); }
.eye {
    position: absolute;
    top: 31px;
    width: 8px;
    height: 12px;
    border-radius: 999px;
    background: #fff;
}
.eye.left { left: 24px; }
.eye.right { right: 24px; }
.nose {
    position: absolute;
    left: 42px;
    top: 48px;
    width: 10px;
    height: 8px;
    border-radius: 999px;
    background: #ff8fab;
}
.whisker {
    position: absolute;
    width: 24px;
    height: 2px;
    border-radius: 999px;
    background: rgba(255,255,255,.9);
}
.whisker.left.top { left: -8px; top: 47px; transform: rotate(12deg); }
.whisker.left.bottom { left: -8px; top: 58px; transform: rotate(-10deg); }
.whisker.right.top { right: -8px; top: 47px; transform: rotate(-12deg); }
.whisker.right.bottom { right: -8px; top: 58px; transform: rotate(10deg); }
.paw {
    position: absolute;
    left: 80px;
    top: 76px;
    width: 28px;
    height: 28px;
    border-radius: 999px;
    background: #fff;
    box-shadow: 0 4px 10px rgba(15,23,42,.12);
}
.magnifier {
    position: absolute;
    right: 18px;
    top: 30px;
    width: 88px;
    height: 88px;
    animation: scan 4.8s ease-in-out infinite;
}
.glass {
    position: absolute;
    width: 64px;
    height: 64px;
    border: 5px solid #334155;
    border-radius: 999px;
    background: rgba(255,255,255,.45);
    box-shadow: 0 14px 28px rgba(15,23,42,.12), inset 0 0 0 3px rgba(255,255,255,.45);
}
.glass span {
    position: absolute;
    left: 12px;
    top: 10px;
    width: 20px;
    height: 10px;
    border-radius: 999px;
    background: rgba(255,255,255,.9);
    transform: rotate(-22deg);
}
.handle {
    position: absolute;
    right: 4px;
    bottom: 10px;
    width: 42px;
    height: 12px;
    border-radius: 999px;
    background: #334155;
    transform: rotate(42deg);
    transform-origin: left center;
}
.shield {
    position: absolute;
    right: 18px;
    top: 22px;
    width: 92px;
    height: 108px;
    border-radius: 28px 28px 38px 38px;
    background: linear-gradient(145deg, rgba(251,191,36,.95), rgba(217,119,6,.95));
    clip-path: polygon(50% 0, 92% 18%, 92% 56%, 50% 100%, 8% 56%, 8% 18%);
    box-shadow: 0 18px 32px rgba(217,119,6,.18);
}
.shield::after {
    content: '';
    position: absolute;
    inset: 24px;
    border-radius: 999px;
    background: rgba(255,255,255,.72);
}
.guard-cat .paw {
    left: 102px;
    top: 72px;
}
.server-card {
    position: absolute;
    left: 50%;
    width: 194px;
    height: 118px;
    transform: translateX(-50%);
    border-radius: 28px;
}
.server-card.back {
    top: 42px;
    background: rgba(244,63,94,.18);
    transform: translateX(-50%) rotate(-8deg);
}
.server-card.front {
    top: 66px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 12px;
    padding: 0 26px;
    background: linear-gradient(145deg, #fb7185, #e11d48);
    box-shadow: 0 18px 35px rgba(225,29,72,.22);
    animation: server-float 4s ease-in-out infinite;
}
.server-card.front span {
    display: block;
    height: 10px;
    border-radius: 999px;
    background: rgba(255,255,255,.82);
}
.server-card.front span:nth-child(2) { width: 72%; }
.server-card.front span:nth-child(3) { width: 48%; }
.pulse {
    position: absolute;
    left: 50%;
    bottom: 24px;
    width: 150px;
    height: 30px;
    transform: translateX(-50%);
    border-radius: 999px;
    background: rgba(244,63,94,.18);
    filter: blur(10px);
    animation: pulse 4s ease-in-out infinite;
}
@keyframes cat-breathe {
    0%,100% { transform: scaleY(1); }
    50% { transform: scaleY(1.05); }
}
@keyframes tail-wave {
    0%,100% { transform: rotate(0deg); }
    50% { transform: rotate(10deg); }
}
@keyframes scan {
    0%,100% { transform: translate(0,0) rotate(0deg); }
    25% { transform: translate(-8px,4px) rotate(-5deg); }
    50% { transform: translate(-2px,10px) rotate(2deg); }
    75% { transform: translate(-10px,2px) rotate(-4deg); }
}
@keyframes server-float {
    0%,100% { transform: translateX(-50%) translateY(0); }
    50% { transform: translateX(-50%) translateY(-8px); }
}
@keyframes pulse {
    0%,100% { transform: translateX(-50%) scaleX(1); opacity: .8; }
    50% { transform: translateX(-50%) scaleX(.84); opacity: .45; }
}
@media (prefers-reduced-motion: reduce) {
    .cat .body,
    .cat .tail,
    .guard-cat .body,
    .guard-cat .tail,
    .magnifier,
    .server-card.front,
    .pulse {
        animation: none !important;
    }
}
</style>
