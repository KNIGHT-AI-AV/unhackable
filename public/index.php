<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require SRC . '/layout.php';

page_start('home');
?>
<!-- <?= t('source_hint') ?> -->
<main class="stage">
  <div class="vault" data-state="idle">
    <div class="aura"></div>
    <div class="floor"></div>
    <form class="vault-form" method="post" action="/vault.php">
      <button class="folder" type="submit" aria-label="<?= t('folder_aria') ?>">
        <?= folder_svg() ?>
        <span class="folder-label"><?= t('folder') ?></span>
      </button>
    </form>
    <div class="denied" role="status" aria-live="polite">
      <button class="lock" type="button" aria-label="<?= t('try_again') ?>" tabindex="-1"><?= lock_svg() ?></button>
      <p class="denied-text" data-text="<?= t('denied') ?>"></p>
    </div>
  </div>
</main>

<div class="intro" id="intro" aria-hidden="true">
  <canvas class="intro-gl" id="introGl"></canvas>
  <div class="intro-css" id="introCss" hidden></div>
  <span class="intro-mark"></span>
  <p class="intro-read"><span class="intro-dot"></span><span id="introWord" data-ok="<?= t('intro_ok') ?>"><?= t('intro_check') ?></span><span class="intro-hex" id="introHex">0x00000000</span></p>
</div>
<?php
page_end(['intro.js', 'app.js']);
