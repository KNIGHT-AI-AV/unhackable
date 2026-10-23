<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

serve_asset('text/css', __FILE__);
?>
/* The stylesheet. Yes, it is a PHP file. Everything here is. */

:root {
  --ink: #ecebe7;
  --ink-soft: rgba(236, 235, 231, .62);
  --ink-faint: rgba(236, 235, 231, .34);
  --gold: #d4ae55;
  --gold-hi: #f3dc94;
  --red: #e5242b;
  --green: #4ade80;
  --ease-out: cubic-bezier(.16, 1, .3, 1);
  --ease-in-out: cubic-bezier(.65, 0, .35, 1);
  --mono: "IBM Plex Mono", ui-monospace, "SF Mono", Consolas, monospace;
  --serif: "Instrument Serif", "Iowan Old Style", Georgia, serif;
  color-scheme: dark;
}

*, *::before, *::after { box-sizing: border-box; }
html, body { margin: 0; height: 100%; }
body {
  background: #020203;
  color: var(--ink);
  font-family: var(--mono);
  -webkit-font-smoothing: antialiased;
  overflow: hidden;
}
.page-secrets { overflow-x: hidden; overflow-y: auto; }
::selection { background: rgba(212, 174, 85, .35); color: #fff; }

/* ---------- the abyss ---------- */

/* Every moving part of the sky is a layer that is drawn once and then only moved or faded by the compositor
   (transform and opacity). Nothing here repaints per frame, which is what keeps it smooth on a 4K screen. */
.sky {
  position: fixed;
  inset: 0;
  z-index: 0;
  overflow: hidden;
  isolation: isolate;
  contain: strict;
  pointer-events: none;
  background: radial-gradient(120% 95% at 50% 46%, #07080c 0%, #030305 55%, #010102 100%);
}
/* sky.js draws the whole sky into this one canvas when WebGL2 is there, and the layers below are the fallback */
.sky-canvas { position: absolute; inset: 0; width: 100%; height: 100%; display: none; }
.gl-sky .sky-canvas { display: block; }
.gl-sky .sky > :not(.sky-canvas) { display: none; }
.nebula {
  position: absolute;
  inset: -8%;
  background:
    radial-gradient(42% 30% at 30% 62%, rgba(64, 78, 150, .1), transparent 72%),
    radial-gradient(38% 28% at 72% 36%, rgba(96, 56, 128, .075), transparent 72%),
    radial-gradient(30% 24% at 56% 50%, rgba(44, 100, 124, .055), transparent 72%);
  will-change: transform, opacity;
  animation: nebula 70s var(--ease-in-out) infinite alternate;
}
@keyframes nebula { to { transform: translate3d(-2%, 1.5%, 0) scale(1.05); opacity: .7; } }

.layer { position: absolute; inset: -60px; will-change: transform; }
.drift { position: absolute; inset: 0; will-change: transform; animation: drift 200s linear infinite alternate; }
.drift.mid { animation-duration: 150s; }
.drift.near { animation-duration: 110s; }
@keyframes drift { to { transform: translate3d(-48px, -30px, 0); } }
.stars { position: absolute; inset: 0; width: 100%; height: 100%; }
.tw {
  position: absolute;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 0 4px 1px rgba(255, 255, 255, .45);
  animation: twinkle 6s ease-in-out infinite;
}
@keyframes twinkle { 0%, 100% { opacity: .85; } 50% { opacity: .1; } }
.gl {
  position: absolute;
  width: 16px;
  height: 16px;
  margin: -8px 0 0 -8px;
  opacity: 0;
  animation: glint 14s ease-out infinite;
}
.gl::before,
.gl::after {
  content: "";
  position: absolute;
  left: 50%;
  top: 0;
  width: 1px;
  height: 100%;
  margin-left: -.5px;
  background: linear-gradient(transparent, rgba(255, 255, 255, .9), transparent);
}
.gl::after { transform: rotate(90deg); }
@keyframes glint {
  0%, 91% { opacity: 0; transform: scale(.2) rotate(0deg); }
  94% { opacity: 1; transform: scale(1) rotate(25deg); }
  100% { opacity: 0; transform: scale(.35) rotate(50deg); }
}

/* soft mottling, like velvet under low light: drawn once, never moves */
.texture {
  position: absolute;
  inset: 0;
  opacity: .035;
  background-image: url("<?= noise_uri(0.011, 4, 640, 1.1, -0.3) ?>");
  background-size: 640px;
}
/* the light you move around: a faint glow, moved by transform only */
.light {
  position: absolute;
  left: 0;
  top: 0;
  width: 900px;
  height: 900px;
  margin: -450px 0 0 -450px;
  background: radial-gradient(closest-side, rgba(226, 232, 255, .05), rgba(226, 232, 255, .018) 50%, rgba(226, 232, 255, 0));
  transform: translate3d(50vw, 46vh, 0);
  will-change: transform;
}
/* film grain, ten frames a second: one tile larger than the screen, jumped by whole offsets inside one tile, so it is
   drawn once and only moved. It sits over the gradients above, which also dithers away their banding. */
.grain {
  position: absolute;
  left: -240px;
  top: -240px;
  right: 0;
  bottom: 0;
  opacity: .05;
  background-image: url("<?= noise_uri(0.82, 2, 240, 1.7, -0.62) ?>");
  background-size: 240px;
  will-change: transform;
  animation: grain .8s steps(1) infinite;
}
@keyframes grain {
  0%, 100% { transform: translate3d(0, 0, 0); }
  12.5% { transform: translate3d(97px, 151px, 0); }
  25% { transform: translate3d(203px, 41px, 0); }
  37.5% { transform: translate3d(29px, 187px, 0); }
  50% { transform: translate3d(163px, 109px, 0); }
  62.5% { transform: translate3d(61px, 23px, 0); }
  75% { transform: translate3d(229px, 199px, 0); }
  87.5% { transform: translate3d(131px, 73px, 0); }
}
.vignette {
  position: absolute;
  inset: 0;
  background: radial-gradient(130% 100% at 50% 50%, transparent 42%, rgba(0, 0, 0, .55) 78%, rgba(0, 0, 0, .88) 100%);
}

/* ---------- the stage ---------- */

.stage {
  position: relative;
  z-index: 2;
  min-height: 100%;
  display: grid;
  place-items: center;
  padding: 24px 16px;
}
.vault {
  position: relative;
  display: grid;
  place-items: center;
  width: min(300px, 66vw);
  aspect-ratio: 300 / 240;
  container-type: inline-size;
}
.intro-run .vault { opacity: 0; }
.revealed .vault { animation: rise 1.5s var(--ease-out) .3s both; }
@keyframes rise {
  from { opacity: 0; transform: translateY(22px) scale(.95); filter: blur(10px); }
  to { opacity: 1; transform: none; filter: none; }
}

.aura {
  position: absolute;
  inset: -45%;
  z-index: -1;
  border-radius: 50%;
  background: radial-gradient(closest-side, rgba(212, 174, 85, .2), rgba(212, 174, 85, .05) 55%, transparent);
  opacity: 0;
  transition: opacity .8s var(--ease-out), background .6s;
  pointer-events: none;
}
.vault:has(.folder:hover) .aura,
.vault:has(.folder:focus-visible) .aura,
.vault[data-state="checking"] .aura { opacity: 1; }
.vault[data-state="denied"] .aura {
  opacity: 1;
  background: radial-gradient(closest-side, rgba(229, 36, 43, .2), rgba(229, 36, 43, .05) 55%, transparent);
}

/* the folder */
.floor {
  position: absolute;
  left: 8%;
  right: 8%;
  bottom: -9%;
  height: 22%;
  z-index: -1;
  border-radius: 50%;
  background: radial-gradient(closest-side, rgba(0, 0, 0, .7), rgba(0, 0, 0, .35) 55%, transparent);
  transition: transform .7s var(--ease-out), opacity .6s var(--ease-out);
  pointer-events: none;
}
.vault:has(.folder:hover) .floor,
.vault[data-state="checking"] .floor { transform: scale(1.08, 1.15); opacity: .8; }
.vault[data-state="denied"] .floor,
.vault[data-state="leaving"] .floor { opacity: 0; }
.vault-form {
  width: 100%;
  margin: 0;
  transition: opacity .6s var(--ease-out), transform .7s var(--ease-out), filter .7s var(--ease-out);
}
.folder {
  all: unset;
  position: relative;
  display: block;
  width: 100%;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  transform: perspective(900px) rotateX(var(--rx, 0deg)) rotateY(var(--ry, 0deg)) translateY(var(--lift, 0px));
  transition: transform .7s var(--ease-out);
}
.folder-art { display: block; width: 100%; height: auto; overflow: visible; }
.folder .gold { opacity: 0; transition: opacity .6s var(--ease-out); }
.folder .shine { opacity: 0; transform-box: fill-box; transform: translateX(-180%); }
.folder-label {
  position: absolute;
  left: 0;
  right: 0;
  top: 61%;
  padding-left: .34em;
  text-align: center;
  font: 500 clamp(8px, 3.5cqi, 11px)/1 var(--mono);
  letter-spacing: .34em;
  color: #3c3b37;
  text-shadow: 0 1px 0 rgba(255, 255, 255, .7);
  transition: color .6s var(--ease-out), text-shadow .6s var(--ease-out);
}

.folder:hover,
.folder:focus-visible,
.vault[data-state="checking"] .folder { --lift: -6px; }
.folder:hover .gold,
.folder:focus-visible .gold,
.vault[data-state="checking"] .gold { opacity: 1; }
.folder:hover .shine,
.folder:focus-visible .shine,
.vault[data-state="checking"] .shine { opacity: 1; animation: shine 2.8s var(--ease-in-out) .1s infinite; }
@keyframes shine {
  0% { transform: translateX(-180%); }
  55%, 100% { transform: translateX(420%); }
}
.folder:hover .folder-label,
.folder:focus-visible .folder-label,
.vault[data-state="checking"] .folder-label {
  color: #4a3407;
  text-shadow: 0 1px 0 rgba(255, 238, 178, .6), 0 -1px 0 rgba(70, 46, 0, .25);
}
.folder:focus-visible { outline: none; }
.folder:focus-visible .folder-art { outline: 1px solid rgba(243, 220, 148, .55); outline-offset: 10px; border-radius: 6px; }
.vault[data-state="checking"] .folder { animation: press .65s var(--ease-out); }
@keyframes press { 30% { transform: perspective(900px) translateY(-2px) scale(.975); } }

/* the answer */
.vault[data-state="denied"] .vault-form,
.vault[data-state="leaving"] .vault-form {
  opacity: 0;
  transform: scale(.8);
  filter: blur(14px);
  pointer-events: none;
  transition-duration: .35s, .45s, .45s;
}
.denied {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  align-content: center;
  gap: 26px;
  pointer-events: none;
  transition: opacity .4s var(--ease-out);
}
.lock {
  all: unset;
  display: block;
  width: 34%;
  color: var(--red);
  opacity: 0;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
}
.lock-art { display: block; width: 100%; height: auto; overflow: visible; }
.lock .shackle { transform: translateY(-15px); }
.denied-text {
  margin: 0;
  min-height: 1em;
  padding-left: .44em;
  font: 500 clamp(12px, 5.2cqi, 16px)/1 var(--mono);
  letter-spacing: .44em;
  color: var(--red);
  text-shadow: 0 0 18px rgba(229, 36, 43, .55);
  opacity: 0;
}
.vault[data-state="denied"] .denied { pointer-events: auto; }
.vault[data-state="denied"] .lock { animation: lock-in .55s var(--ease-out) .2s forwards; filter: drop-shadow(0 0 26px rgba(229, 36, 43, .38)); }
.vault[data-state="denied"] .shackle { animation: shackle .4s cubic-bezier(.4, 1.8, .55, 1) .62s both; }
.vault[data-state="denied"] .denied-text { animation: flicker 1s steps(1) .85s forwards; }
.vault[data-state="denied"] .denied { animation: thud .34s .9s; }
.vault[data-state="leaving"] .denied { opacity: 0; }
.vault[data-state="leaving"] .lock,
.vault[data-state="leaving"] .denied-text { opacity: 1; }
@keyframes lock-in {
  from { opacity: 0; transform: scale(1.35); filter: blur(8px); }
  to { opacity: 1; transform: none; filter: drop-shadow(0 0 26px rgba(229, 36, 43, .38)); }
}
@keyframes shackle { from { transform: translateY(-15px); } to { transform: translateY(0); } }
@keyframes flicker {
  0% { opacity: 0; }
  8% { opacity: .9; }
  13% { opacity: .1; }
  22% { opacity: 1; }
  30% { opacity: .25; }
  36%, 100% { opacity: 1; }
}
@keyframes thud {
  20% { transform: translate3d(0, 3px, 0); }
  45% { transform: translate3d(-2px, -1px, 0); }
  70% { transform: translate3d(1px, 1px, 0); }
}

.back {
  margin-top: 6px;
  font: 500 11px/1 var(--mono);
  letter-spacing: .3em;
  color: var(--ink-faint);
  text-decoration: none;
  text-transform: uppercase;
  padding: 10px 14px;
  transition: color .3s;
}
.back:hover, .back:focus-visible { color: var(--ink); }
.page-denied .denied-text { opacity: 1; animation: flicker 1s steps(1) .85s both; }

/* ---------- the intro ---------- */

.intro {
  position: fixed;
  inset: 0;
  z-index: 50;
  background: #000;
  animation: intro-failsafe .5s linear 6s forwards;
}
.intro.is-live { animation: none; }
.intro.is-drawn { background: transparent; }
.intro.is-opening { pointer-events: none; }
@keyframes intro-failsafe { to { opacity: 0; visibility: hidden; } }
.intro-gl { position: absolute; inset: 0; width: 100%; height: 100%; display: block; }
.intro-css {
  position: absolute;
  inset: 0;
  overflow: hidden;
  background: #000;
  -webkit-mask-image: radial-gradient(circle at 50% 50%, transparent var(--iris, -60px), #000 calc(var(--iris, -60px) + 30px));
  mask-image: radial-gradient(circle at 50% 50%, transparent var(--iris, -60px), #000 calc(var(--iris, -60px) + 30px));
}
.intro-css::before,
.intro-css::after {
  content: "";
  position: absolute;
  inset: -30%;
  opacity: var(--amt, 0);
  background: repeating-radial-gradient(circle at 50% 50%, rgba(220, 223, 228, .3) 0 2px, transparent 2px 16px);
  animation: orbit 2.6s linear infinite;
}
.intro-css::after { animation-direction: reverse; mix-blend-mode: difference; }
@keyframes orbit {
  from { transform: rotate(0deg) translateX(var(--orbit, 14px)) rotate(0deg); }
  to { transform: rotate(360deg) translateX(var(--orbit, 14px)) rotate(-360deg); }
}
.intro-mark {
  position: absolute;
  left: 50%;
  top: 50%;
  width: 6px;
  height: 6px;
  margin: -3px 0 0 -3px;
  border-radius: 50%;
  background: var(--red);
  box-shadow: 0 0 10px 2px rgba(229, 36, 43, .7);
  animation: mark-in .9s var(--ease-out) both;
  transition: opacity .25s, transform .4s var(--ease-out);
}
@keyframes mark-in {
  0% { transform: scale(0); }
  45% { transform: scale(1.8); }
  100% { transform: scale(1); }
}
.intro-read {
  position: absolute;
  left: 50%;
  top: calc(50% + 34px);
  translate: -50% 0;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
  white-space: nowrap;
  font: 500 10.5px/1 var(--mono);
  letter-spacing: .32em;
  color: rgba(236, 235, 231, .62);
  opacity: 0;
  transition: opacity .35s;
}
.intro-dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: var(--red);
  box-shadow: 0 0 8px rgba(229, 36, 43, .8);
  animation: blink .9s steps(2, jump-none) infinite;
}
@keyframes blink { 50% { opacity: .25; } }
.intro-hex { min-width: 11ch; letter-spacing: .12em; color: rgba(236, 235, 231, .32); }
.intro.is-reading .intro-read { opacity: 1; }
.intro.is-ok .intro-dot { background: var(--green); box-shadow: 0 0 8px rgba(74, 222, 128, .8); animation: none; }
.intro.is-ok #introWord { color: #fff; }
.intro.is-ok .intro-hex { opacity: 0; }
.intro.is-opening .intro-read { opacity: 0; transition-duration: .2s; }
.intro.is-opening .intro-mark { opacity: 0; transform: scale(3); }

/* ---------- the secrets ---------- */

/* a holographic blueprint lab: cyan linework on deep navy glass, a fine grid, corner brackets */
.page-secrets { --holo: #8fe3ff; --holo-dim: rgba(143, 227, 255, .32); --holo-faint: rgba(143, 227, 255, .14); }
.page-secrets::before {
  content: "";
  position: fixed;
  inset: 0;
  z-index: 1;
  pointer-events: none;
  background:
    linear-gradient(rgba(143, 227, 255, .045) 1px, transparent 1px) 0 0 / 40px 40px,
    linear-gradient(90deg, rgba(143, 227, 255, .045) 1px, transparent 1px) 0 0 / 40px 40px;
  -webkit-mask-image: radial-gradient(120% 90% at 50% 30%, #000 30%, transparent 85%);
  mask-image: radial-gradient(120% 90% at 50% 30%, #000 30%, transparent 85%);
}
.dossier {
  position: relative;
  z-index: 2;
  max-width: 1180px;
  margin: 0 auto;
  padding: clamp(48px, 9vh, 112px) 16px 96px;
}
.kicker {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 0;
  font: 500 11px/1 var(--mono);
  letter-spacing: .38em;
  color: var(--gold);
}
.kicker-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--green); box-shadow: 0 0 10px rgba(74, 222, 128, .8); }
.dossier h1 {
  margin: .3em 0 .2em;
  font: 400 clamp(46px, 8vw, 96px)/.95 var(--serif);
  letter-spacing: -.01em;
  background: linear-gradient(100deg, #a57b22 0%, #f6e3a1 32%, #c9a24a 55%, #fbefc3 75%, #a57b22 100%);
  background-size: 200% 100%;
  -webkit-background-clip: text;
  background-clip: text;
  color: transparent;
  animation: gilt 9s var(--ease-in-out) infinite alternate;
}
@keyframes gilt { to { background-position: 100% 0; } }
.project-code { margin: 0 0 14px; font: 500 11px/1.5 var(--mono); letter-spacing: .3em; color: var(--holo); }
.lede { margin: 0 0 40px; max-width: 640px; font-size: 14px; line-height: 1.7; color: var(--ink-soft); }
.section-title { margin: 56px 0 16px; font: 500 11px/1 var(--mono); letter-spacing: .38em; color: var(--holo); }

.panel {
  position: relative;
  border: 1px solid var(--holo-faint);
  border-radius: 10px;
  background: linear-gradient(180deg, rgba(8, 18, 30, .82), rgba(4, 10, 18, .86));
  box-shadow: inset 0 0 60px rgba(60, 170, 255, .05);
}
.panel::before,
.panel::after {
  content: "";
  position: absolute;
  width: 18px;
  height: 18px;
  border: 1.5px solid var(--holo);
  opacity: .7;
  pointer-events: none;
}
.panel::before { left: -1px; top: -1px; border-right: 0; border-bottom: 0; border-top-left-radius: 10px; }
.panel::after { right: -1px; bottom: -1px; border-left: 0; border-top: 0; border-bottom-right-radius: 10px; }
.panel-title { margin: 0 0 16px; font: 500 10.5px/1 var(--mono); letter-spacing: .34em; color: var(--holo); }

/* the hologram */
.holo { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 16px; }
.holo-stage { aspect-ratio: 16 / 10; overflow: hidden; contain: layout paint; }
.holo-canvas { position: absolute; inset: 0; width: 100%; height: 100%; cursor: grab; touch-action: pan-y; }
.holo-canvas:active { cursor: grabbing; }
.holo-labels { position: absolute; inset: 0; pointer-events: none; }
.holo-label {
  position: absolute;
  left: 0;
  top: 0;
  padding: 3px 6px;
  border: 1px solid var(--holo-faint);
  border-radius: 3px;
  background: rgba(4, 12, 22, .78);
  font: 500 9.5px/1.2 var(--mono);
  letter-spacing: .14em;
  color: #c9f1ff;
  white-space: nowrap;
  will-change: transform;
  transition: opacity .3s;
}
.holo-tag, .holo-hint { position: absolute; margin: 0; font: 500 10px/1 var(--mono); letter-spacing: .3em; color: var(--holo); }
.holo-tag { left: 18px; top: 18px; display: flex; align-items: center; gap: 8px; }
.holo-rec { width: 6px; height: 6px; border-radius: 50%; background: var(--red); box-shadow: 0 0 8px rgba(229, 36, 43, .9); animation: blink 1.1s steps(2, jump-none) infinite; }
.holo-hint { left: 18px; bottom: 18px; letter-spacing: .14em; color: var(--holo-dim); }
.holo-btn {
  position: absolute;
  right: 16px;
  bottom: 14px;
  padding: 9px 14px;
  border: 1px solid var(--holo-dim);
  border-radius: 4px;
  background: rgba(4, 12, 22, .7);
  font: 500 10px/1 var(--mono);
  letter-spacing: .26em;
  color: var(--holo);
  cursor: pointer;
  transition: background .25s, border-color .25s;
}
.holo-btn:hover, .holo-btn:focus-visible { background: rgba(143, 227, 255, .1); border-color: var(--holo); outline: none; }

/* telemetry */
.telemetry { padding: 20px 20px 10px; }
.telemetry dl { margin: 0; }
.tm { padding: 10px 0 12px; border-top: 1px solid var(--holo-faint); }
.tm dt { font: 500 9.5px/1 var(--mono); letter-spacing: .22em; color: var(--holo-dim); }
.tm dd { margin: 7px 0 0; font: 500 18px/1 var(--mono); color: #e6f8ff; font-variant-numeric: tabular-nums; }
.tm dd small { font-size: 9.5px; letter-spacing: .16em; color: var(--holo-dim); }
.tm .bar { display: block; height: 2px; margin-top: 8px; background: var(--holo-faint); overflow: hidden; }
.tm .bar i { display: block; height: 100%; background: var(--holo); transform-origin: left; transition: transform .45s var(--ease-out); }
.tm-alert dd { color: var(--red); text-shadow: 0 0 12px rgba(229, 36, 43, .5); }

/* the drawings */
.sheets { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.sheets .section-title { grid-column: 1 / -1; margin-bottom: 0; }
.sheet { margin: 0; padding: 10px; transition: transform .6s var(--ease-out); }
.sheet-wide { grid-column: 1 / -1; }
.sheet:hover { transform: perspective(1400px) rotateX(3deg) translateY(-3px); }
.bp-svg { display: block; width: 100%; height: auto; }
.bp-l { fill: none; stroke: var(--holo); stroke-width: 1.1; vector-effect: non-scaling-stroke; }
.bp-faint { stroke-opacity: .45; }
.bp-frame { stroke-opacity: .6; }
.bp-hidden { stroke-opacity: .35; stroke-dasharray: 4 4; }
.bp-axis { stroke-opacity: .4; stroke-dasharray: 14 4 2 4; }
.bp-head { fill: rgba(143, 227, 255, .08); }
.bp-box { fill: rgba(4, 14, 26, .9); }
.bp-dot, .bp-fill { fill: var(--holo); }
.bp-hatch { stroke: var(--holo); stroke-width: 1; stroke-opacity: .55; }
.bp-hatch-red { stroke: var(--red); stroke-width: 1; stroke-opacity: .8; }
.bp-red { stroke: var(--red); }
.bp-t { fill: #c9f1ff; font: 500 11px var(--mono); letter-spacing: .08em; }
.bp-h { font-size: 15px; letter-spacing: .28em; fill: var(--holo); }
.bp-sm { font-size: 9.5px; letter-spacing: .05em; }
.bp-xs { font-size: 8px; letter-spacing: .16em; fill: rgba(143, 227, 255, .7); }
.bp-mid { font-size: 12px; }
.bp-red-t { fill: #ff8a8e; }

/* lab notes */
.log-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.file {
  position: relative;
  padding: 24px 24px 22px;
  border: 1px solid var(--holo-faint);
  border-radius: 10px;
  background: linear-gradient(180deg, rgba(8, 18, 30, .82), rgba(4, 10, 18, .86));
  animation: file-in .9s var(--ease-out) both;
}
.file:nth-of-type(2) { animation-delay: .08s; }
.file:nth-of-type(3) { animation-delay: .16s; }
.file:nth-of-type(4) { animation-delay: .24s; }
.file:nth-of-type(5) { animation-delay: .32s; }
.file:nth-of-type(6) { animation-delay: .4s; }
@keyframes file-in { from { opacity: 0; transform: translateY(16px); } }
.file-no { margin: 0; font: 500 10.5px/1 var(--mono); letter-spacing: .32em; color: var(--holo); }
.file h2 { margin: .6em 0 .5em; font: 400 clamp(22px, 3vw, 28px)/1.1 var(--serif); color: #f4f2ec; }
.file p:last-child { margin: 0; font-size: 13px; line-height: 1.8; color: rgba(236, 235, 231, .8); }

@media (max-width: 900px) {
  .holo, .sheets, .log-grid { grid-template-columns: 1fr; }
  .holo-stage { aspect-ratio: 4 / 5; }
}
.stamp {
  position: absolute;
  top: 20px;
  right: 20px;
  padding: 5px 9px 4px 11px;
  border: 1.5px solid currentColor;
  border-radius: 4px;
  font: 500 10px/1 var(--mono);
  letter-spacing: .26em;
  color: rgba(229, 36, 43, .75);
  transform: rotate(7deg);
}
.file:nth-of-type(even) .stamp { transform: rotate(-5deg); }
.foot { margin: 48px 0 22px; font-size: 12.5px; line-height: 1.7; color: var(--ink-faint); }
.relock {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  padding: 13px 20px 13px 16px;
  border: 1px solid rgba(212, 174, 85, .4);
  border-radius: 999px;
  font: 500 11px/1 var(--mono);
  letter-spacing: .28em;
  text-transform: uppercase;
  color: var(--gold-hi);
  text-decoration: none;
  transition: background .3s, border-color .3s;
}
.relock .lock-art { width: 12px; color: var(--red); }
.relock .shackle { transform: translateY(-12px); transition: transform .35s cubic-bezier(.4, 1.8, .55, 1); }
.relock:hover, .relock:focus-visible { background: rgba(212, 174, 85, .08); border-color: rgba(212, 174, 85, .7); outline: none; }
.relock:hover .shackle, .relock:focus-visible .shackle { transform: none; }

/* ---------- 404 ---------- */

.lost { display: grid; justify-items: center; gap: 14px; text-align: center; }
.lost-code { margin: 0; font: 400 clamp(72px, 18vw, 140px)/1 var(--serif); color: rgba(236, 235, 231, .9); }
.lost-text { margin: 0; font-size: 13px; letter-spacing: .2em; color: var(--ink-soft); }

/* ---------- less motion ---------- */

@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
  .tw, .gl, .grain, .nebula, .drift { animation: none !important; }
  .gl { opacity: 0; }
}
