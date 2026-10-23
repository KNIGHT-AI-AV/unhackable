<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

serve_asset('text/javascript', __FILE__);
?>
/* The roll-in. Black, and a red light at the centre. Two sets of rings spread from it, their centres circling a few
   pixels apart so they interfere into a slow moire, with a hair of chromatic split and film grain, while a readout
   pretends to verify you. Then the centres meet, the rings resolve, the readout says SECURE, and an iris of light opens
   from the centre onto the abyss behind.

   Hand-written WebGL2 (one fullscreen triangle, one fragment shader) with a CSS fallback. Click or press any key to
   skip ahead. With reduced motion there is no intro at all. If this file never runs, a CSS failsafe clears the
   black after six seconds. */
(function () {
  "use strict";
  const doc = document, root = doc.documentElement;
  const el = doc.getElementById("intro");
  if (!el) return;
  const reveal = () => root.classList.add("revealed");
  if (matchMedia("(prefers-reduced-motion: reduce)").matches) { el.remove(); return; }

  root.classList.add("intro-run");
  el.classList.add("is-live");

  const IRIS_AT = 2.25, IRIS_LEN = 1.0, SKIP_LEN = 0.55, READ_AT = 0.4, OK_AT = 1.95;
  const clamp = (v, a, b) => (v < a ? a : v > b ? b : v);
  const outQ = (u) => 1 - Math.pow(1 - u, 5);
  const inOut = (u) => (u < 0.5 ? 4 * u * u * u : 1 - Math.pow(-2 * u + 2, 3) / 2);
  const bump = (t, at, w) => Math.exp(-((t - at) * (t - at)) / (2 * w * w));

  const VS = "#version 300 es\nvoid main(){vec2 p=vec2(float((gl_VertexID<<1)&2),float(gl_VertexID&2));gl_Position=vec4(p*2.0-1.0,0.0,1.0);}";
  const FS = [
    "#version 300 es",
    "precision highp float;",
    "uniform vec2 uRes,uC;uniform float uT,uO,uK,uIris,uEdge,uAmt,uCore,uRim,uPx;",
    "out vec4 o;",
    "float hash(vec2 p){vec3 q=fract(vec3(p.xyx)*.1031);q+=dot(q,q.yzx+33.33);return fract((q.x+q.y)*q.z);}",
    "float rings(vec2 p,vec2 a,vec2 b){return (.5+.5*cos(length(p-a)*uK))*(.5+.5*cos(length(p-b)*uK));}",
    "void main(){",
    "  vec2 p=gl_FragCoord.xy,q=p-uC;",
    "  vec2 a=uC+uO*vec2(cos(uT*1.7),sin(uT*1.7));",
    "  vec2 b=uC+uO*vec2(cos(2.4-uT*1.3),sin(2.4-uT*1.3));",
    "  vec3 c=vec3(rings(uC+q*1.003,a,b),rings(p,a,b),rings(uC+q*.997,a,b));",   // the product of two gratings is their interference
    "  c*=vec3(.86,.88,.9)*.36;",
    "  float d=length(q);",
    "  c*=mix(.22,1.,1.-smoothstep(.3,1.05,d/(.5*length(uRes))));",              // vignette
    "  float s=24.*uPx;c+=vec3(.9,.14,.17)*exp(-d*d/(2.*s*s))*uCore;",           // the red light at the centre
    "  c+=(hash(floor(p/max(uPx,1.))+floor(uT*48.)*7.31)-.5)*.06;",              // grain
    "  c=max(c,0.)*uAmt;",
    "  float hole=smoothstep(uIris-uEdge,uIris+uEdge,d);",                       // 0 inside the iris: the page shows through
    "  float rim=exp(-pow((d-uIris)/(uEdge*1.6+1.),2.))*uRim;",                   // a soft ring of light on its edge
    "  float al=clamp(hole+rim,0.,1.);",
    "  o=vec4(min(c*hole+vec3(1.,.95,.88)*rim,vec3(al)),al);",                    // premultiplied
    "}",
  ].join("\n");

  const canvas = doc.getElementById("introGl");
  const cssEl = doc.getElementById("introCss");
  const word = doc.getElementById("introWord");
  const hex = doc.getElementById("introHex");
  let gl = null, prog = null, U = null, ext = null, linked = false, mode = "gl";

  function makeGL() {
    try {
      gl = canvas.getContext("webgl2", { alpha: true, premultipliedAlpha: true, antialias: false, depth: false, stencil: false,
        preserveDrawingBuffer: false, powerPreference: "low-power", failIfMajorPerformanceCaveat: true });
    } catch (_) { gl = null; }
    if (!gl) return false;
    canvas.addEventListener("webglcontextlost", (e) => { e.preventDefault(); toCss(); }, { once: true });
    ext = gl.getExtension("KHR_parallel_shader_compile");
    const sh = (type, src) => { const s = gl.createShader(type); gl.shaderSource(s, src); gl.compileShader(s); return s; };
    prog = gl.createProgram();
    gl.attachShader(prog, sh(gl.VERTEX_SHADER, VS));
    gl.attachShader(prog, sh(gl.FRAGMENT_SHADER, FS));
    gl.linkProgram(prog);
    gl.bindVertexArray(gl.createVertexArray());
    return true;
  }
  /* checked once compiled (in parallel where the browser can), never by blocking */
  function ready() {
    if (linked) return true;
    if (ext && !gl.getProgramParameter(prog, ext.COMPLETION_STATUS_KHR)) return false;
    if (!gl.getProgramParameter(prog, gl.LINK_STATUS)) { toCss(); return false; }
    U = {};
    for (const n of ["uRes", "uC", "uT", "uO", "uK", "uIris", "uEdge", "uAmt", "uCore", "uRim", "uPx"]) U[n] = gl.getUniformLocation(prog, n);
    linked = true;
    return true;
  }
  function giveBack() {
    if (!gl) return;
    try { const lose = gl.getExtension("WEBGL_lose_context"); if (lose) lose.loseContext(); } catch (_) {}
    gl = null;
  }
  function toCss() {
    if (mode === "css") return;
    mode = "css";
    giveBack();
    canvas.hidden = true;
    cssEl.hidden = false;
  }

  if (!makeGL()) toCss();

  const phone = matchMedia("(max-width: 640px)").matches;
  let t0 = performance.now(), irisLen = IRIS_LEN, opened = false, done = false, lastHex = 0, drawn = false;

  function skip() {
    if ((performance.now() - t0) / 1000 >= IRIS_AT) return;
    t0 = performance.now() - IRIS_AT * 1000;
    irisLen = SKIP_LEN;
  }
  el.addEventListener("pointerdown", skip);
  addEventListener("keydown", skip, { once: true });

  function randomHex() {
    let s = "0x";
    for (let i = 0; i < 8; i++) s += "0123456789ABCDEF"[(Math.random() * 16) | 0];
    return s;
  }

  function frame(now) {
    if (done) return;
    const t = (now - t0) / 1000;
    const w = innerWidth, h = innerHeight;
    const maxR = Math.hypot(w, h) / 2 + 80;
    const u = clamp((t - IRIS_AT) / irisLen, 0, 1);
    const p = {
      amt: outQ(clamp((t - 0.1) / 0.8, 0, 1)),
      orbit: 18 * (1 - inOut(clamp((t - 0.3) / 1.7, 0, 1))),
      k: 0.36 + 0.24 * inOut(clamp((t - 0.2) / 1.8, 0, 1)),          // radians per CSS pixel
      iris: t < IRIS_AT ? -80 : maxR * inOut(u),
      edge: 2 + 40 * u,
      core: (0.6 * outQ(clamp(t / 0.5, 0, 1)) + 0.8 * bump(t, OK_AT + 0.1, 0.1)) * (1 - u),
      rim: t < IRIS_AT ? 0 : 0.85 * Math.pow(1 - u, 1.3) * clamp(u * 14, 0, 1),
    };

    if (t >= READ_AT && t < OK_AT) {
      el.classList.add("is-reading");
      if (now - lastHex > 55) { hex.textContent = randomHex(); lastHex = now; }
    } else if (t >= OK_AT && !el.classList.contains("is-ok")) {
      el.classList.add("is-reading", "is-ok");
      word.textContent = word.dataset.ok;
    }
    if (t >= IRIS_AT && !opened) { opened = true; el.classList.add("is-opening"); reveal(); }

    if (mode === "gl" && gl && ready()) {
      const dpr = Math.min(devicePixelRatio || 1, phone ? 1.25 : 1.5) * (phone ? 0.75 : 1);
      const cw = Math.max(1, Math.round(w * dpr)), ch = Math.max(1, Math.round(h * dpr));
      if (canvas.width !== cw || canvas.height !== ch) { canvas.width = cw; canvas.height = ch; }
      gl.viewport(0, 0, cw, ch);
      gl.useProgram(prog);
      gl.uniform2f(U.uRes, cw, ch);
      gl.uniform2f(U.uC, cw / 2, ch / 2);
      gl.uniform1f(U.uT, t);
      gl.uniform1f(U.uO, p.orbit * dpr);
      gl.uniform1f(U.uK, p.k / dpr);
      gl.uniform1f(U.uIris, p.iris * dpr);
      gl.uniform1f(U.uEdge, p.edge * dpr);
      gl.uniform1f(U.uAmt, p.amt);
      gl.uniform1f(U.uCore, p.core);
      gl.uniform1f(U.uRim, p.rim);
      gl.uniform1f(U.uPx, dpr);
      gl.drawArrays(gl.TRIANGLES, 0, 3);
      if (!drawn) { drawn = true; el.classList.add("is-drawn"); }
    } else if (mode === "css") {
      cssEl.style.setProperty("--amt", p.amt.toFixed(3));
      cssEl.style.setProperty("--orbit", p.orbit.toFixed(1) + "px");
      cssEl.style.setProperty("--iris", p.iris.toFixed(1) + "px");
      if (!drawn) { drawn = true; el.classList.add("is-drawn"); }
    }

    if (u >= 1) { finish(); return; }
    requestAnimationFrame(frame);
  }

  function finish() {
    done = true;
    giveBack();
    el.remove();
  }

  requestAnimationFrame(frame);
})();
