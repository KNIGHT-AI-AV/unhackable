<?php
declare(strict_types=1);

// The vault's answer without JavaScript: the same lock, rendered by PHP, as a whole page.

require_once SRC . '/layout.php';

page_start('denied', words()['denied'] ?? '');
?>
<main class="stage">
  <div class="vault" data-state="denied">
    <div class="aura"></div>
    <div class="denied" role="status">
      <span class="lock"><?= lock_svg() ?></span>
      <p class="denied-text"><?= t('denied') ?></p>
      <a class="back" href="/"><?= t('back') ?></a>
    </div>
  </div>
</main>
<?php
page_end(['app.js']);
