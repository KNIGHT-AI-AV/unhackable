<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

serve_asset('text/javascript', __FILE__);
?>
/* The hologram of the Turbo Encabulator Mark VII: a wireframe built from rings, cylinders, a gear, a fan and a helix,
   turning slowly in perspective with its fan spinning and its spring breathing. Drag to turn it, and the Explode
   button pulls the parts apart along the shaft. Callout labels follow their parts in 3D. Canvas 2D, two strokes per
   depth band, nothing else; it sleeps when scrolled out of view.

   Also the telemetry: numbers that wander a little around their nominal values, as telemetry does. */
(function () {
  "use strict";
  const doc = document;
  const reduce = matchMedia("(prefers-reduced-motion: reduce)").matches;
  const TAU = Math.PI * 2;

  /* ---------- telemetry ---------- */
  const nums = [...doc.querySelectorAll(".telemetry .num")].map((el) => ({
    el, bar: el.closest(".tm").querySelector(".bar i"),
    base: +el.dataset.base, jitter: +el.dataset.jitter, dec: +el.dataset.dec, max: +el.dataset.max,
  }));
  function wander() {
    for (const n of nums) {
      const v = n.base + (Math.random() - 0.5) * n.jitter;
      n.el.textContent = n.dec ? v.toFixed(n.dec) : Math.round(v).toLocaleString("en-US");
      if (n.bar) n.bar.style.transform = `scaleX(${Math.max(0, Math.min(1, v / n.max)).toFixed(3)})`;
    }
  }
  wander();
  if (!reduce) setInterval(wander, 450);

  /* ---------- the model ---------- */
  const stage = doc.querySelector(".holo-stage");
  if (!stage) return;
  const canvas = stage.querySelector(".holo-canvas");
  const ctx = canvas.getContext("2d");
  const labels = Object.fromEntries([...stage.querySelectorAll(".holo-label")].map((el) => [el.dataset.part, el]));
  const button = stage.querySelector(".holo-btn");

  const ring = (out, r, y, n = 48, cx = 0, cz = 0) => {
    for (let i = 0; i < n; i++) {
      const a = (i / n) * TAU, b = ((i + 1) / n) * TAU;
      out.push([cx + r * Math.cos(a), y, cz + r * Math.sin(a), cx + r * Math.cos(b), y, cz + r * Math.sin(b)]);
    }
  };
  const frustum = (out, r0, r1, y0, y1, n, bands, ribs) => {
    for (let k = 0; k <= bands; k++) { const u = k / bands; ring(out, r0 + (r1 - r0) * u, y0 + (y1 - y0) * Math.pow(u, 0.8), n); }
    for (let i = 0; i < ribs; i++) {
      const a = (i / ribs) * TAU;
      out.push([r0 * Math.cos(a), y0, r0 * Math.sin(a), r1 * Math.cos(a), y1, r1 * Math.sin(a)]);
    }
  };
  const box = (out, w, h, d, y) => {
    const xs = [-w / 2, w / 2], ys = [y, y + h], zs = [-d / 2, d / 2];
    for (const yy of ys) for (const zz of zs) out.push([xs[0], yy, zz, xs[1], yy, zz]);
    for (const yy of ys) for (const xx of xs) out.push([xx, yy, zs[0], xx, yy, zs[1]]);
    for (const xx of xs) for (const zz of zs) out.push([xx, ys[0], zz, xx, ys[1], zz]);
    for (let i = 1; i < 6; i++) { const x = -w / 2 + (w * i) / 6; out.push([x, y + h, zs[0], x, y + h, zs[1]]); }
  };

  // each part: lines, a callout anchor, how far it travels when exploded, how fast it spins on its own
  const parts = [];
  const part = (key, build, anchor, explode, spin = 0) => { const lines = []; build(lines); parts.push({ key, lines, anchor, explode, spin }); };
  part("base", (o) => { box(o, 2.3, 0.12, 1.5, -1.12); ring(o, 0.85, -1.0, 64); }, [1.1, -1.06, 0.7], -0.55);
  part("casing", (o) => frustum(o, 0.72, 0.56, -0.98, -0.2, 40, 6, 16), [-0.66, -0.6, 0.3], -0.25);
  part("bearings", (o) => { for (let i = 0; i < 6; i++) { const a = (i / 6) * TAU; ring(o, 0.07, -0.55, 14, 0.8 * Math.cos(a), 0.8 * Math.sin(a)); ring(o, 0.035, -0.55, 10, 0.8 * Math.cos(a), 0.8 * Math.sin(a)); } }, [0.8, -0.55, 0], -0.25, 0.4);
  part("grammeter", (o) => {
    ring(o, 0.66, -0.12, 64); ring(o, 0.58, -0.12, 64);
    for (let i = 0; i < 32; i++) { const a = (i / 32) * TAU; o.push([0.66 * Math.cos(a), -0.12, 0.66 * Math.sin(a), 0.75 * Math.cos(a), -0.12, 0.75 * Math.sin(a)]); }
  }, [-0.72, -0.12, -0.1], 0.05, -0.35);
  part("marzlevanes", (o) => { for (let k = 0; k < 8; k++) { const a = (k / 8) * TAU; ring(o, 0.95 + 0.05 * Math.cos(a), 0.05 + 0.05 * Math.sin(a), 72); } }, [0.95, 0.05, 0.2], 0.2, 0.15);
  part("shaft", (o) => { frustum(o, 0.06, 0.06, -0.15, 1.05, 12, 2, 6); ring(o, 0.12, 1.05, 20); }, [0.08, 0.9, 0], 0.45, 1.2);
  part("fan", (o) => {
    ring(o, 0.56, 0.38, 64); ring(o, 0.1, 0.38, 16);
    for (let b = 0; b < 7; b++) {
      let prev = null;
      for (let i = 0; i <= 10; i++) {
        const u = i / 10, r = 0.1 + 0.44 * u, a = (b / 7) * TAU + u * 0.9, y = 0.38 + 0.04 * Math.sin(u * Math.PI);
        const p = [r * Math.cos(a), y, r * Math.sin(a)];
        if (prev) o.push([...prev, ...p]);
        prev = p;
      }
    }
  }, [-0.52, 0.4, 0.1], 0.35, 5);
  part("spring", (o) => {
    let prev = null;
    for (let i = 0; i <= 160; i++) { const u = i / 160, a = u * 8 * TAU; const p = [0.2 * Math.cos(a), 0.5 + 0.38 * u, 0.2 * Math.sin(a)]; if (prev) o.push([...prev, ...p]); prev = p; }
  }, [0.2, 0.72, 0.1], 0.65);
  part("trunnions", (o) => {
    for (const s of [-1, 1]) {
      for (let i = 0; i < 12; i++) {
        const a = (i / 12) * TAU, b = ((i + 1) / 12) * TAU, r = 0.07;
        for (const x of [0.7, 1.15]) o.push([s * x, -0.45 + r * Math.cos(a), r * Math.sin(a), s * x, -0.45 + r * Math.cos(b), r * Math.sin(b)]);
        if (i % 3 === 0) o.push([s * 0.7, -0.45 + r * Math.cos(a), r * Math.sin(a), s * 1.15, -0.45 + r * Math.cos(a), r * Math.sin(a)]);
      }
    }
  }, [-1.15, -0.45, 0], -0.25);
  const floor = [];
  for (let k = 1; k <= 4; k++) ring(floor, 0.4 * k, -1.2, 72);
  for (let i = 0; i < 12; i++) { const a = (i / 12) * TAU; floor.push([0.3 * Math.cos(a), -1.2, 0.3 * Math.sin(a), 1.6 * Math.cos(a), -1.2, 1.6 * Math.sin(a)]); }

  /* ---------- view ---------- */
  let yaw = 0.6, pitch = 0.32, dragging = false, lastX = 0, lastY = 0, spinVel = 0.22, explode = 0, explodeTarget = 0, visible = true;
  let w = 0, h = 0, dpr = 1;

  function resize() {
    const r = canvas.getBoundingClientRect();
    dpr = Math.min(devicePixelRatio || 1, 2);
    w = r.width; h = r.height;
    canvas.width = Math.round(w * dpr); canvas.height = Math.round(h * dpr);
  }
  addEventListener("resize", resize);
  resize();

  canvas.addEventListener("pointerdown", (e) => { dragging = true; lastX = e.clientX; lastY = e.clientY; canvas.setPointerCapture(e.pointerId); });
  canvas.addEventListener("pointermove", (e) => {
    if (!dragging) return;
    yaw += (e.clientX - lastX) * 0.008;
    pitch = Math.max(-0.2, Math.min(0.9, pitch + (e.clientY - lastY) * 0.005));
    lastX = e.clientX; lastY = e.clientY;
  });
  const release = () => { dragging = false; };
  canvas.addEventListener("pointerup", release);
  canvas.addEventListener("pointercancel", release);
  if (button) button.addEventListener("click", () => {
    explodeTarget = explodeTarget ? 0 : 1;
    button.textContent = explodeTarget ? button.dataset.on : button.dataset.off;
  });
  new IntersectionObserver((entries) => { visible = entries[0].isIntersecting; }).observe(stage);

  function project(x, y, z, cy, sy, cp, sp, scale) {
    const rx = x * cy - z * sy, rz = x * sy + z * cy;
    const ry = y * cp - rz * sp, rz2 = y * sp + rz * cp;
    const d = 4.2 + rz2;
    return [w / 2 + (rx / d) * scale, h * 0.52 - (ry / d) * scale, rz2];
  }

  const bands = [[], [], []];
  function draw(t) {
    const cy = Math.cos(yaw), sy = Math.sin(yaw), cp = Math.cos(pitch), sp = Math.sin(pitch);
    const scale = Math.min(w, h * 1.45) * 0.92 * (1 - 0.24 * explode);   // pull back to fit the exploded parts
    for (const b of bands) b.length = 0;

    const add = (lines, ox, oy, spin) => {
      const c = Math.cos(spin), s = Math.sin(spin);
      for (const L of lines) {
        const x0 = L[0] * c - L[2] * s + ox, z0 = L[0] * s + L[2] * c, x1 = L[3] * c - L[5] * s + ox, z1 = L[3] * s + L[5] * c;
        const a = project(x0, L[1] + oy, z0, cy, sy, cp, sp, scale), b = project(x1, L[4] + oy, z1, cy, sy, cp, sp, scale);
        const depth = (a[2] + b[2]) / 2;
        bands[depth < -0.35 ? 0 : depth < 0.35 ? 1 : 2].push(a[0], a[1], b[0], b[1]);
      }
    };
    add(floor, 0, 0, 0);
    const anchors = {};
    for (const p of parts) {
      const oy = p.explode * explode * 1.1;
      const breathe = p.key === "spring" ? 1 + 0.06 * Math.sin(t * 2.2) : 1;
      const lines = breathe === 1 ? p.lines : p.lines.map((L) => [L[0], 0.5 + (L[1] - 0.5) * breathe, L[2], L[3], 0.5 + (L[4] - 0.5) * breathe, L[5]]);
      add(lines, 0, oy, p.spin * t);
      const A = p.anchor, c = Math.cos(p.spin * t), s = Math.sin(p.spin * t);
      anchors[p.key] = project(A[0] * c - A[2] * s, A[1] + oy, A[0] * s + A[2] * c, cy, sy, cp, sp, scale);
    }

    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.clearRect(0, 0, w, h);
    ctx.globalCompositeOperation = "lighter";
    ctx.lineCap = "round";
    const alphas = [0.85, 0.5, 0.22];      // near, middle, far
    bands.forEach((b, i) => {
      ctx.beginPath();
      for (let k = 0; k < b.length; k += 4) { ctx.moveTo(b[k], b[k + 1]); ctx.lineTo(b[k + 2], b[k + 3]); }
      ctx.strokeStyle = `rgba(80, 200, 255, ${alphas[i] * 0.16})`;
      ctx.lineWidth = 4;
      ctx.stroke();
      ctx.strokeStyle = `rgba(150, 228, 255, ${alphas[i]})`;
      ctx.lineWidth = 1;
      ctx.stroke();
    });

    // a scan ring sweeping up and down the machine
    const scanY = -1.1 + (Math.sin(t * 0.7) * 0.5 + 0.5) * 2.2;
    const scan = [];
    ring(scan, 1.25, scanY, 72);
    ctx.beginPath();
    for (const L of scan) {
      const a = project(L[0], L[1], L[2], cy, sy, cp, sp, scale), b = project(L[3], L[4], L[5], cy, sy, cp, sp, scale);
      ctx.moveTo(a[0], a[1]); ctx.lineTo(b[0], b[1]);
    }
    ctx.strokeStyle = "rgba(120, 255, 220, 0.35)";
    ctx.lineWidth = 1.5;
    ctx.stroke();

    // callouts: a dot on the part, an elbow, the label beside it
    ctx.globalCompositeOperation = "source-over";
    ctx.strokeStyle = "rgba(150, 228, 255, 0.55)";
    ctx.fillStyle = "rgba(190, 240, 255, 0.95)";
    ctx.lineWidth = 1;
    // place labels per side top to bottom so they stack instead of overlapping, all inside the panel
    const items = Object.entries(labels).filter(([key]) => anchors[key]).map(([key, el]) => {
      const a = anchors[key];
      return { el, a, right: a[0] >= w / 2, lw: el.offsetWidth, lh: el.offsetHeight, ey: a[1] - 18 };
    });
    for (const side of [true, false]) {
      const col = items.filter((it) => it.right === side).sort((p, q) => p.ey - q.ey);
      let floorY = 8;
      for (const it of col) { it.ey = Math.max(it.ey, floorY + it.lh / 2); floorY = it.ey + it.lh / 2 + 4; }
      let ceil = h - 8;
      for (let i = col.length - 1; i >= 0; i--) { const it = col[i]; it.ey = Math.min(it.ey, ceil - it.lh / 2); ceil = it.ey - it.lh / 2 - 4; }
    }
    for (const { el, a, right, lw, lh, ey } of items) {
      const ex = a[0] + (right ? 34 : -34);
      const lx = Math.max(8, Math.min(w - lw - 8, right ? ex + 6 : ex - 6 - lw));
      ctx.beginPath();
      ctx.moveTo(a[0], a[1]); ctx.lineTo(ex, ey); ctx.lineTo(right ? ex + 6 : ex - 6, ey);
      ctx.stroke();
      ctx.beginPath(); ctx.arc(a[0], a[1], 2.2, 0, TAU); ctx.fill();
      el.style.transform = `translate3d(${Math.round(lx)}px, ${Math.round(ey - lh / 2)}px, 0)`;
      el.style.opacity = a[2] > 0.6 ? "0.45" : "1";
    }
  }

  let last = performance.now(), clock = 0;
  function loop(now) {
    const dt = Math.min(0.05, (now - last) / 1000);
    last = now;
    if (visible) {
      clock += dt;
      if (!dragging) yaw += spinVel * dt;
      explode += (explodeTarget - explode) * Math.min(1, dt * 3.5);
      draw(clock);
    }
    requestAnimationFrame(loop);
  }
  if (reduce) { draw(0); addEventListener("resize", () => draw(0)); } else requestAnimationFrame(loop);
})();
