<?php
declare(strict_types=1);

require_once SRC . '/sky.php';
require_once SRC . '/art.php';

const FONTS = 'https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=Instrument+Serif:ital@0;1&display=swap';

function page_start(string $page, string $title = ''): void
{
    secure_headers();
    header('Content-Type: text/html; charset=utf-8');
    $sky = sky(intdiv(time(), 86400));
    $title = $title !== '' ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') : t('title');
    $nonce = nonce();
    ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= $title ?></title>
<meta name="description" content="<?= t('description') ?>">
<meta name="theme-color" content="#020203">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="<?= FONTS ?>">
<link rel="stylesheet" href="<?= asset('app.css') ?>">
<style nonce="<?= $nonce ?>"><?= $sky['css'] ?></style>
<noscript><style nonce="<?= $nonce ?>">.intro{display:none}</style></noscript>
</head>
<body class="page-<?= $page ?>">
<?= $sky['html'] ?>
<script type="application/json" id="skyData"><?= $sky['data'] ?></script>

<?php
}

/** @param string[] $scripts asset names, loaded deferred in this order after the sky */
function page_end(array $scripts): void
{
    foreach (['sky.js', ...$scripts] as $name) {
        echo '<script src="' . asset($name) . '" defer></script>' . "\n";
    }
    echo "</body>\n</html>\n";
}
