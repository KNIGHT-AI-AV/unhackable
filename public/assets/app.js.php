<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/src/bootstrap.php';

serve_asset('text/javascript', __FILE__);
?>
/* The folder, whose fate is decided by PHP on the server. Always the same fate. (The sky is sky.js.) */
(function () {
  "use strict";
  const doc = document;
  const reduce = matchMedia("(prefers-reduced-motion: reduce)").matches;

  const vault = doc.querySelector(".vault");
  const form = vault && vault.querySelector(".vault-form");
  if (!form) return;
  const folder = form.querySelector(".folder");
  const lock = vault.querySelector(".lock");
  const text = vault.querySelector(".denied-text");
  const state = (s) => { vault.dataset.state = s; };
  const wait = (ms) => new Promise((r) => setTimeout(r, ms));

  // the folder leans toward the pointer
  folder.addEventListener("pointermove", (e) => {
    if (reduce || e.pointerType !== "mouse") return;
    const r = folder.getBoundingClientRect();
    const dx = (e.clientX - r.left) / r.width - 0.5, dy = (e.clientY - r.top) / r.height - 0.5;
    folder.style.setProperty("--ry", (dx * 14).toFixed(2) + "deg");
    folder.style.setProperty("--rx", (-dy * 12).toFixed(2) + "deg");
  });
  folder.addEventListener("pointerleave", () => {
    folder.style.setProperty("--ry", "0deg");
    folder.style.setProperty("--rx", "0deg");
  });

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (vault.dataset.state !== "idle") return;
    state("checking");
    // Ask the server. Give it a moment to think it over, so the gold has time to shine.
    const [answer] = await Promise.all([
      fetch(form.action, { method: "POST", headers: { Accept: "application/json" }, credentials: "same-origin" })
        .then((r) => r.json())
        .catch(() => null),
      wait(700),
    ]);
    // Fails closed: no answer is also a no.
    text.textContent = answer && typeof answer.message === "string" && answer.message ? answer.message : text.dataset.text;
    state("denied");
    lock.tabIndex = 0;
    lock.focus({ preventScroll: true });
  });

  async function relock() {
    if (vault.dataset.state !== "denied") return;
    state("leaving");
    await wait(420);
    text.textContent = "";
    lock.tabIndex = -1;
    folder.style.setProperty("--ry", "0deg");
    folder.style.setProperty("--rx", "0deg");
    state("idle");
    folder.focus({ preventScroll: true });
  }
  lock.addEventListener("click", relock);
  addEventListener("keydown", (e) => { if (e.key === "Escape") relock(); });
})();
