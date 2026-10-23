<?php
declare(strict_types=1);

// What every request shares: the words on the page, the headers that make it "unhackable", and asset URLs.

const SRC = __DIR__;
const PUB = __DIR__ . '/../public';

/** Every visible word on the site lives in strings.txt, one key=value per line. */
function words(): array
{
    static $words = null;
    if ($words !== null) {
        return $words;
    }
    $words = [];
    foreach (file(SRC . '/strings.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $words[trim($key)] = trim($value);
    }
    return $words;
}

/** A word, escaped for HTML. */
function t(string $key): string
{
    return htmlspecialchars(words()[$key] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** One random nonce per response, for the single inline <style> block the star field needs. */
function nonce(): string
{
    static $nonce = null;
    return $nonce ??= base64_encode(random_bytes(18));
}

function secure_headers(): void
{
    $n = nonce();
    header_remove('X-Powered-By');
    header('X-Powered-By: ' . (words()['powered_by'] ?? 'PHP'));
    header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self' 'nonce-$n' https://fonts.googleapis.com; "
        . "font-src https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Strict-Transport-Security: max-age=31536000');
}

/** /assets/<name>.php?v=<hash of the file>, so assets can be cached forever and still change on deploy. */
function asset(string $name): string
{
    return '/assets/' . $name . '.php?v=' . substr(md5_file(PUB . '/assets/' . $name . '.php'), 0, 10);
}

/** Headers for a stylesheet or script that happens to be a PHP file. Answers 304 when the browser already has it. */
function serve_asset(string $type, string $file): void
{
    $etag = '"' . substr(md5_file($file), 0, 16) . '"';
    header_remove('X-Powered-By');
    header("Content-Type: $type; charset=utf-8");
    header('Cache-Control: public, max-age=31536000, immutable');
    header("ETag: $etag");
    header('X-Content-Type-Options: nosniff');
    if (trim($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
        http_response_code(304);
        exit;
    }
}

/**
 * A tiling noise texture as an SVG data URI: white, with the noise in the alpha channel.
 * $gain and $bias shape that alpha (a = gain * noise + bias), so the same filter makes fine grain or soft mottling.
 */
function noise_uri(float $frequency, int $octaves, int $size, float $gain, float $bias): string
{
    $svg = "<svg xmlns='http://www.w3.org/2000/svg' width='$size' height='$size'>"
        . "<filter id='n' x='0' y='0' width='100%' height='100%'>"
        . "<feTurbulence type='fractalNoise' baseFrequency='$frequency' numOctaves='$octaves' stitchTiles='stitch'/>"
        . "<feColorMatrix type='matrix' values='0 0 0 0 1 0 0 0 0 1 0 0 0 0 1 $gain 0 0 0 $bias'/>"
        . "</filter><rect width='100%' height='100%' filter='url(#n)'/></svg>";
    return 'data:image/svg+xml,' . rawurlencode($svg);
}
