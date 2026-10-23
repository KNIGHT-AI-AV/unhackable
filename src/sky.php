<?php
declare(strict_types=1);

// The abyss, decided on the server. PHP picks every star (three depths, a faint dust band across the middle, the ones
// that twinkle and the ones that glint) and hands the catalogue to the page twice: as JSON, which sky.js draws in a
// single WebGL pass, and as static SVG layers, the fallback for browsers without WebGL. Seeded by the day, so the sky
// is the same for everyone today and different tomorrow.

const STAR_TINTS = ['#ffffff', '#cdd8ff', '#ffe8c8'];

function rnd(float $min, float $max): float
{
    return $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
}

function gauss(): float
{
    return sqrt(-2 * log(max(rnd(0, 1), 1e-9))) * cos(2 * M_PI * rnd(0, 1));
}

/** Mostly white (0), a few cool blue (1), a few warm (2). */
function star_tint(): int
{
    $roll = rnd(0, 1);
    return $roll < 0.14 ? 1 : ($roll < 0.24 ? 2 : 0);
}

/**
 * In the fallback nothing inside an SVG layer animates, so each layer is drawn once and only moved by the
 * compositor; its few twinkles and glints are small HTML elements animated with opacity and transform only.
 *
 * @return array{html: string, css: string, data: string} fallback markup, its per-star rules for the nonce'd
 *         <style>, and the star catalogue as JSON: [x, y, radius, alpha, tint, layer, kind, period, phase] per star,
 *         x and y in 0..1, kind 0 still, 1 twinkle, 2 glint
 */
function sky(int $seed): array
{
    mt_srand($seed);
    $css = '';
    $catalogue = [];
    $twinkles = 0;
    $glints = 0;

    // name, stars, radius range, alpha range, share that twinkle, glints, parallax depth in px
    $layers = [
        ['far', 300, [0.35, 0.7], [0.1, 0.4], 0.16, 0, 6],
        ['mid', 120, [0.55, 1.0], [0.22, 0.58], 0.2, 3, 14],
        ['near', 26, [0.95, 1.5], [0.38, 0.75], 0.3, 4, 26],
    ];

    $html = '<div class="sky" aria-hidden="true"><canvas class="sky-canvas" id="skyGl"></canvas><div class="nebula"></div>';
    foreach ($layers as $layer => [$name, $count, $radius, $alpha, $twinkleShare, $glintCount, $depth]) {
        $points = [];
        for ($i = 0; $i < $count; $i++) {
            $points[] = [rnd(0, 1600), rnd(0, 1000), rnd(...$radius), rnd(...$alpha)];
        }
        if ($name === 'far') {
            for ($i = 0; $i < 220; $i++) {
                $along = rnd(-0.1, 1.1);
                $across = gauss() * 110;
                $points[] = [$along * 1600 + $across * 0.35, 820 - $along * 640 + $across, rnd(0.3, 0.5), rnd(0.06, 0.24)];
            }
        }

        $svg = '';
        $sparks = '';
        foreach ($points as [$x, $y, $r, $a]) {
            $tint = star_tint();
            $twinkle = rnd(0, 1) < $twinkleShare;
            $catalogue[] = [round($x / 1600, 4), round($y / 1000, 4), round($r, 2), round($a, 2), $tint, $layer,
                $twinkle ? 1 : 0, round(rnd(3.5, 9), 1), round(rnd(0, 1), 3)];
            $svg .= sprintf('<circle cx="%.1f" cy="%.1f" r="%.2f" fill="%s" fill-opacity="%.2f"/>', $x, $y, $r, STAR_TINTS[$tint], $a);
            if ($name === 'near') {
                $svg .= sprintf('<circle cx="%.1f" cy="%.1f" r="%.1f" fill="#fff" fill-opacity="0.035"/>', $x, $y, $r * 5);
            }
            if ($twinkle && $twinkles < 26 && rnd(0, 1) < 0.5) {
                $twinkles++;
                $sparks .= "<i class=\"tw\" id=\"t$twinkles\"></i>";
                $css .= sprintf(
                    '#t%d{left:%.2f%%;top:%.2f%%;width:%.1fpx;height:%4$.1fpx;animation-duration:%.1fs;animation-delay:-%.1fs}',
                    $twinkles, $x / 16, $y / 10, $name === 'near' ? 2.4 : 1.6, rnd(3.5, 9), rnd(0, 9)
                );
            }
        }
        for ($i = 0; $i < $glintCount; $i++) {
            $glints++;
            [$x, $y] = [rnd(0.05, 0.95), rnd(0.05, 0.95)];
            $period = round(rnd(11, 19), 1);
            $phase = round(rnd(0, 1), 3);
            $catalogue[] = [round($x, 4), round($y, 4), 1.1, 1, 0, $layer, 2, $period, $phase];
            $sparks .= "<i class=\"gl\" id=\"g$glints\"></i>";
            $css .= sprintf('#g%d{left:%.2f%%;top:%.2f%%;animation-duration:%.1fs;animation-delay:-%.1fs}',
                $glints, $x * 100, $y * 100, $period, $phase * $period);
        }
        $html .= "<div class=\"layer\" data-depth=\"$depth\"><div class=\"drift $name\">"
            . "<svg class=\"stars\" viewBox=\"0 0 1600 1000\" preserveAspectRatio=\"xMidYMid slice\">$svg</svg>$sparks</div></div>";
    }
    $html .= '<div class="texture"></div><div class="light"></div><div class="grain"></div><div class="vignette"></div></div>';

    return ['html' => $html, 'css' => $css, 'data' => json_encode($catalogue)];
}
