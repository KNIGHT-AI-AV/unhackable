<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require SRC . '/layout.php';

http_response_code(404);
page_start('lost', words()['notfound_title'] ?? '');
?>
<main class="stage">
  <div class="lost">
    <p class="lost-code">404</p>
    <p class="lost-text"><?= t('notfound') ?></p>
    <a class="back" href="/"><?= t('back') ?></a>
  </div>
</main>
<?php
page_end(['app.js']);
