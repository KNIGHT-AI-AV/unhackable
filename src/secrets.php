<?php
declare(strict_types=1);

// What is inside the folder: the classified plans. A live hologram, telemetry, three engineering drawings and the lab
// notes, all describing a machine that does nothing. Reached only by the most sophisticated of attacks (reading the
// page source).

require_once SRC . '/layout.php';
require_once SRC . '/blueprint.php';

const HOLO_PARTS = ['base', 'casing', 'bearings', 'grammeter', 'shaft', 'fan', 'spring', 'marzlevanes', 'trunnions'];

// label, value, unit, jitter, decimals, bar maximum (0 for a word instead of a number)
const TELEMETRY = [
    ['r1', 98.4, 'u1', 0.6, 1, 100],
    ['r2', 0.0031, 'u2', 0.0004, 4, 0.006],
    ['r3', 7.2, 'u3', 0.5, 1, 12],
    ['r4', 'r4v', '', 0, 0, 0],
    ['r5', 47, 'u5', 3, 0, 100],
    ['r6', 12000, 'u6', 400, 0, 15000],
    ['r7', 'r7v', '', 0, 0, 0],
    ['r8', 'r8v', '', 0, 0, 0],
];

page_start('secrets', words()['granted_title'] ?? '');
?>
<main class="dossier">
  <header class="bp-header">
    <p class="kicker"><span class="kicker-dot"></span><?= t('granted_kicker') ?></p>
    <h1><?= t('granted_title') ?></h1>
    <p class="project-code"><?= t('project_code') ?></p>
    <p class="lede"><?= t('granted_lede') ?></p>
  </header>

  <section class="holo">
    <div class="holo-stage panel">
      <canvas class="holo-canvas" aria-hidden="true"></canvas>
      <div class="holo-labels" aria-hidden="true">
<?php foreach (HOLO_PARTS as $part): ?>
        <span class="holo-label" data-part="<?= $part ?>"><?= t("part_$part") ?></span>
<?php endforeach; ?>
      </div>
      <p class="holo-tag"><span class="holo-rec"></span><?= t('holo_title') ?></p>
      <p class="holo-hint"><?= t('holo_hint') ?></p>
      <button class="holo-btn" type="button" data-on="<?= t('holo_assemble') ?>" data-off="<?= t('holo_explode') ?>"><?= t('holo_explode') ?></button>
    </div>
    <aside class="telemetry panel">
      <p class="panel-title"><?= t('readout_title') ?></p>
      <dl>
<?php foreach (TELEMETRY as [$label, $value, $unit, $jitter, $decimals, $max]): ?>
        <div class="tm<?= $label === 'r8' ? ' tm-alert' : '' ?>">
          <dt><?= t($label) ?></dt>
<?php if ($max > 0): ?>
          <dd><span class="num" data-base="<?= $value ?>" data-jitter="<?= $jitter ?>" data-dec="<?= $decimals ?>" data-max="<?= $max ?>"><?= number_format((float) $value, $decimals, '.', '') ?></span> <small><?= t($unit) ?></small></dd>
          <span class="bar"><i></i></span>
<?php else: ?>
          <dd><?= t($value) ?></dd>
<?php endif; ?>
        </div>
<?php endforeach; ?>
      </dl>
    </aside>
  </section>

  <section class="sheets">
    <p class="section-title"><?= t('sheets_title') ?></p>
    <figure class="sheet sheet-wide panel"><?= sheet_exploded() ?></figure>
    <figure class="sheet panel"><?= sheet_section() ?></figure>
    <figure class="sheet panel"><?= sheet_flux() ?></figure>
  </section>

  <section class="logs">
    <p class="section-title"><?= t('logs_title') ?></p>
    <div class="log-grid">
<?php for ($n = 1; isset(words()["log{$n}_title"]); $n++): ?>
      <article class="file">
        <p class="file-no"><?= t('log_label') ?> <?= sprintf('%03d', $n) ?></p>
        <span class="stamp"><?= t('stamp') ?></span>
        <h2><?= t("log{$n}_title") ?></h2>
        <p><?= t("log{$n}_body") ?></p>
      </article>
<?php endfor; ?>
    </div>
  </section>

  <p class="foot"><?= t('granted_foot') ?></p>
  <a class="relock" href="/"><?= lock_svg() ?><span><?= t('relock') ?></span></a>
</main>
<?php
page_end(['holo.js', 'app.js']);
