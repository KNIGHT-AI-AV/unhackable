<?php
declare(strict_types=1);

// Served as /favicon.svg. A red lock on black, drawn by PHP like everything else.

header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=86400');
header('X-Content-Type-Options: nosniff');
?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
  <rect width="64" height="64" rx="14" fill="#050506"/>
  <path d="M23 31v-7a9 9 0 0 1 18 0v7" fill="none" stroke="#e5242b" stroke-width="5" stroke-linecap="round"/>
  <rect x="16" y="28" width="32" height="25" rx="6" fill="#e5242b"/>
  <circle cx="32" cy="39" r="3.4" fill="#050506"/>
  <rect x="30.5" y="40" width="3" height="7" rx="1.5" fill="#050506"/>
</svg>
