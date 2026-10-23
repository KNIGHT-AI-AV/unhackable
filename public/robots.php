<?php
declare(strict_types=1);

// Served as /robots.txt. Every good vault tells the robots exactly where the vault is.

require dirname(__DIR__) . '/src/bootstrap.php';

header_remove('X-Powered-By');
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');
echo "User-agent: *\nDisallow: /vault.php\n\n# " . (words()['robots_note'] ?? '') . "\n";
