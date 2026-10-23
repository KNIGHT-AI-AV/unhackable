<?php
declare(strict_types=1);

// The engineering drawings for the vault, drawn by PHP as SVG: an exploded assembly with its parts list, a hatched
// cross-section with dimensions nobody should trust, and a flux routing diagram. Every label is a word from
// strings.txt, so the drawings go through the same text cleaning as the rest of the site.

function svg_text(float $x, float $y, string $key, string $class = 'bp-t', string $extra = ''): string
{
    return sprintf('<text x="%.1f" y="%.1f" class="%s"%s>%s</text>', $x, $y, $class, $extra, t($key));
}

/** The drawing's title block: DWG, scale, revision, drawn, checked, sheet. */
function title_block(float $x, float $y, float $w, string $dwg, int $sheet, int $h = 30): string
{
    $cw = $w / 3;
    $valueClass = $h < 30 ? 'bp-t bp-sm' : 'bp-t';
    $cells = [
        ['tb_dwg', $dwg], ['tb_scale', t('tb_scale_v')], ['tb_rev', 'Q'],
        ['tb_drawn', t('tb_drawn_v')], ['tb_checked', t('tb_checked_v')], ['tb_sheet', "$sheet / 7"],
    ];
    $out = sprintf('<g class="bp-tb"><rect x="%.1f" y="%.1f" width="%.1f" height="%d" class="bp-l"/>', $x, $y, $w, $h * 2);
    foreach ($cells as $i => [$label, $value]) {
        $cx = $x + ($i % 3) * $cw;
        $cy = $y + intdiv($i, 3) * $h;
        $out .= sprintf('<rect x="%.1f" y="%.1f" width="%.1f" height="%d" class="bp-l"/>', $cx, $cy, $cw, $h);
        $out .= sprintf('<text x="%.1f" y="%.1f" class="bp-t bp-xs">%s</text>', $cx + 6, $cy + 10, t($label));
        $out .= sprintf('<text x="%.1f" y="%.1f" class="%s">%s</text>', $cx + 6, $cy + $h - 6, $valueClass, $value);
    }
    return $out . '</g>';
}

function frame(float $w, float $h, string $titleKey): string
{
    return sprintf('<rect x="6" y="6" width="%.1f" height="%.1f" class="bp-l bp-frame"/>', $w - 12, $h - 12)
        . sprintf('<rect x="14" y="14" width="%.1f" height="%.1f" class="bp-l bp-faint"/>', $w - 28, $h - 28)
        . svg_text(30, 42, $titleKey, 'bp-t bp-h');
}

/** An upright cylinder seen from slightly above: top ellipse, sides, the front half of the bottom. */
function iso_cyl(float $cx, float $cy, float $rx, float $ry, float $h, string $class = 'bp-l'): string
{
    $b = $cy + $h;
    return sprintf('<ellipse cx="%.1f" cy="%.1f" rx="%.1f" ry="%.1f" class="%s"/>', $cx, $cy, $rx, $ry, $class)
        . sprintf('<path d="M%.1f %.1fV%.1fA%.1f %.1f 0 0 0 %.1f %.1fV%.1f" class="%s"/>', $cx - $rx, $cy, $b, $rx, $ry, $cx + $rx, $b, $cy, $class)
        . sprintf('<path d="M%.1f %.1fA%.1f %.1f 0 0 1 %.1f %.1f" class="bp-l bp-hidden"/>', $cx - $rx, $b, $rx, $ry, $cx + $rx, $b);
}

function balloon(float $bx, float $by, float $px, float $py, int $n): string
{
    $dx = $px - $bx;
    $dy = $py - $by;
    $len = max(1.0, sqrt($dx * $dx + $dy * $dy));
    return sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" class="bp-l bp-faint"/>', $bx + $dx / $len * 13, $by + $dy / $len * 13, $px, $py)
        . sprintf('<circle cx="%.1f" cy="%.1f" r="2" class="bp-dot"/>', $px, $py)
        . sprintf('<circle cx="%.1f" cy="%.1f" r="13" class="bp-l"/>', $bx, $by)
        . sprintf('<text x="%.1f" y="%.1f" class="bp-t bp-mid" text-anchor="middle">%d</text>', $bx, $by + 4, $n);
}

function sheet_exploded(): string
{
    $cx = 290;
    $s = '<svg class="bp-svg" viewBox="0 0 1000 560" role="img" aria-label="' . t('sheet1_title') . '">' . frame(1000, 560, 'sheet1_title');
    $s .= sprintf('<line x1="%d" y1="70" x2="%d" y2="528" class="bp-l bp-axis"/>', $cx, $cx);

    // 7 differential girdlespring: a six-turn helix
    $pts = [];
    for ($i = 0; $i <= 72; $i++) {
        $a = $i / 72 * 6 * 2 * M_PI;
        $pts[] = sprintf('%.1f,%.1f', $cx + 30 * cos($a), 92 + $i / 72 * 50 + 8 * sin($a));
    }
    $s .= '<polyline points="' . implode(' ', $pts) . '" class="bp-l"/>';

    // 6 panametric fan: rim, hub, seven swept blades
    $s .= sprintf('<ellipse cx="%d" cy="185" rx="105" ry="28" class="bp-l"/><ellipse cx="%d" cy="185" rx="16" ry="5" class="bp-l"/>', $cx, $cx);
    for ($k = 0; $k < 7; $k++) {
        $a = $k / 7 * 2 * M_PI;
        $s .= sprintf('<path d="M%.1f %.1fQ%.1f %.1f %.1f %.1f" class="bp-l"/>',
            $cx + 16 * cos($a), 185 + 5 * sin($a), $cx + 70 * cos($a + 0.15), 185 + 20 * sin($a + 0.15), $cx + 100 * cos($a + 0.55), 185 + 27 * sin($a + 0.55));
    }

    // 5 ambifacient lunar waneshaft
    $s .= iso_cyl($cx, 215, 9, 3, 85);

    // 4 cardinal grammeter ring: two ellipses and forty teeth
    $s .= sprintf('<ellipse cx="%d" cy="318" rx="125" ry="32" class="bp-l"/><ellipse cx="%d" cy="318" rx="112" ry="28" class="bp-l bp-faint"/>', $cx, $cx);
    for ($k = 0; $k < 40; $k++) {
        $a = $k / 40 * 2 * M_PI;
        $s .= sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" class="bp-l"/>', $cx + 125 * cos($a), 318 + 32 * sin($a), $cx + 133 * cos($a), 318 + 34.5 * sin($a));
    }

    // 2 malleable logarithmic casing, with its logarithmic bands
    $s .= iso_cyl($cx, 370, 100, 26, 64);
    foreach ([7, 17, 34] as $d) {
        $s .= sprintf('<path d="M%.1f %.1fA100 26 0 0 0 %.1f %.1f" class="bp-l bp-faint"/>', $cx - 100, 370 + $d, $cx + 100, 370 + $d);
    }

    // 3 spurving bearings (three of six shown)
    foreach ([420, 450, 480] as $bx) {
        $s .= sprintf('<ellipse cx="%d" cy="350" rx="12" ry="4.5" class="bp-l"/><ellipse cx="%d" cy="350" rx="5" ry="2" class="bp-l"/>', $bx, $bx);
    }

    // 8 novertrunnions, left and right
    foreach ([[150, 185], [395, 430]] as [$x0, $x1]) {
        $s .= sprintf('<ellipse cx="%d" cy="400" rx="4" ry="9" class="bp-l"/><path d="M%d 391H%dM%d 409H%d" class="bp-l"/><path d="M%d 391A4 9 0 0 1 %d 409" class="bp-l"/>',
            $x0, $x0, $x1, $x0, $x1, $x1, $x1);
    }

    // 1 prefabulated amulite base
    $s .= sprintf('<path d="M140 480L%d 450L440 480L%d 510Z" class="bp-l"/><path d="M140 480V492L%d 522L440 492V480M%d 510V522" class="bp-l"/>', $cx, $cx, $cx, $cx);

    $s .= balloon(70, 480, 150, 482, 1) . balloon(70, 372, 192, 392, 2) . balloon(530, 330, 492, 350, 3)
        . balloon(70, 300, 168, 318, 4) . balloon(530, 255, 300, 255, 5) . balloon(70, 185, 186, 185, 6)
        . balloon(530, 115, 322, 115, 7) . balloon(530, 400, 432, 400, 8);

    // parts list
    $x = 590;
    $y = 40;
    $cols = [[0, 30, 'pl_no'], [30, 172, 'pl_part'], [202, 34, 'pl_qty'], [236, 144, 'pl_mat']];
    $rows = [['part_base', 1, 'm1'], ['part_casing', 1, 'm2'], ['part_bearings', 6, 'm3'], ['part_grammeter', 1, 'm4'],
        ['part_shaft', 1, 'm5'], ['part_fan', 1, 'm6'], ['part_spring', 1, 'm7'], ['part_trunnions', 2, 'm8']];
    $s .= sprintf('<rect x="%d" y="%d" width="380" height="%d" class="bp-l"/>', $x, $y, 26 * 9);
    foreach ($cols as [$off, $w, $key]) {
        $s .= sprintf('<rect x="%d" y="%d" width="%d" height="26" class="bp-l bp-head"/>', $x + $off, $y, $w);
        $s .= svg_text($x + $off + 5, $y + 17, $key, 'bp-t bp-xs');
    }
    foreach ($rows as $i => [$part, $qty, $mat]) {
        $ry = $y + 26 * ($i + 1);
        $s .= sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" class="bp-l bp-faint"/>', $x, $ry + 26, $x + 380, $ry + 26);
        $s .= sprintf('<text x="%d" y="%d" class="bp-t bp-sm">%d</text>', $x + 10, $ry + 17, $i + 1);
        $s .= svg_text($x + 35, $ry + 17, $part, 'bp-t bp-sm');
        $s .= sprintf('<text x="%d" y="%d" class="bp-t bp-sm">%d</text>', $x + 214, $ry + 17, $qty);
        $s .= svg_text($x + 241, $ry + 17, $mat, 'bp-t bp-sm');
    }
    foreach ([30, 202, 236] as $off) {
        $s .= sprintf('<line x1="%d" y1="%d" x2="%d" y2="%d" class="bp-l bp-faint"/>', $x + $off, $y + 26, $x + $off, $y + 26 * 9);
    }

    // notes
    $s .= svg_text($x, 310, 'n_title', 'bp-t bp-xs');
    foreach (['n1', 'n2', 'n3'] as $i => $key) {
        $s .= sprintf('<text x="%d" y="%d" class="bp-t bp-sm">%d. %s</text>', $x, 332 + $i * 20, $i + 1, t($key));
    }

    return $s . title_block($x, 462, 380, 'UH-0042-A', 2) . '</svg>';
}

function sheet_section(): string
{
    $s = '<svg class="bp-svg" viewBox="0 0 600 460" role="img" aria-label="' . t('sheet2_title') . '">'
        . '<defs><pattern id="hatchA" width="7" height="7" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><line x1="0" y1="0" x2="0" y2="7" class="bp-hatch"/></pattern>'
        . '<pattern id="hatchR" width="5" height="5" patternUnits="userSpaceOnUse" patternTransform="rotate(-45)"><line x1="0" y1="0" x2="0" y2="5" class="bp-hatch-red"/></pattern>'
        . '<marker id="arrA" viewBox="0 0 10 10" refX="10" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0L10 5L0 10z" class="bp-fill"/></marker></defs>'
        . frame(600, 460, 'sheet2_title');

    $s .= '<rect x="90" y="360" width="340" height="26" fill="url(#hatchA)" class="bp-l"/>';
    $s .= '<path d="M120 360H150L165 150H140Z" fill="url(#hatchA)" class="bp-l"/>';
    $s .= '<path d="M370 360H400L380 150H355Z" fill="url(#hatchA)" class="bp-l"/>';
    $s .= '<rect x="140" y="130" width="240" height="20" fill="url(#hatchA)" class="bp-l"/>';
    $s .= '<rect x="248" y="112" width="24" height="248" fill="url(#hatchA)" class="bp-l"/>';
    $s .= '<line x1="260" y1="106" x2="260" y2="400" class="bp-l bp-axis"/>';
    foreach ([172, 348] as $bx) {
        $s .= sprintf('<circle cx="%d" cy="250" r="11" class="bp-l"/><path d="M%d 242L%d 258M%d 242L%d 258" class="bp-l"/>', $bx, $bx - 8, $bx + 8, $bx + 8, $bx - 8);
    }
    $s .= '<rect x="178" y="296" width="44" height="40" fill="url(#hatchR)" class="bp-l bp-red"/>';
    $s .= '<line x1="222" y1="316" x2="290" y2="316" class="bp-l bp-faint"/>' . svg_text(294, 320, 'sec_zone', 'bp-t bp-sm bp-red-t');

    // dimensions
    $s .= '<path d="M140 128V92M380 128V92" class="bp-l bp-faint"/><line x1="140" y1="100" x2="380" y2="100" class="bp-l" marker-start="url(#arrA)" marker-end="url(#arrA)"/>';
    $s .= svg_text(260, 92, 'dim1', 'bp-t bp-sm', ' text-anchor="middle"');
    $s .= '<path d="M402 130H458M432 386H458" class="bp-l bp-faint"/><line x1="450" y1="130" x2="450" y2="386" class="bp-l" marker-start="url(#arrA)" marker-end="url(#arrA)"/>';
    $s .= svg_text(466, 258, 'dim2', 'bp-t bp-sm', ' text-anchor="middle" transform="rotate(-90 466 258)"');
    $s .= '<polyline points="165,152 112,120 40,120" class="bp-l bp-faint"/>' . svg_text(40, 114, 'dim3', 'bp-t bp-sm');

    $s .= svg_text(30, 438, 'sec_note', 'bp-t bp-xs');
    return $s . title_block(312, 392, 270, 'UH-0042-B', 3, 26) . '</svg>';
}

function sheet_flux(): string
{
    $s = '<svg class="bp-svg" viewBox="0 0 600 460" role="img" aria-label="' . t('sheet3_title') . '">' . frame(600, 460, 'sheet3_title');

    $box = fn (float $x, float $y, float $w, string $key) => sprintf('<rect x="%.1f" y="%.1f" width="%.1f" height="40" rx="6" class="bp-l bp-box"/>', $x, $y, $w)
        . svg_text($x + $w / 2, $y + 24, $key, 'bp-t bp-sm', ' text-anchor="middle"');
    $s .= $box(50, 80, 150, 'flux1') . $box(50, 200, 150, 'flux2') . $box(240, 140, 150, 'flux3')
        . $box(420, 70, 160, 'flux4') . $box(420, 220, 160, 'flux5');

    // wires
    $s .= '<polyline points="200,100 222,100 222,160 240,160" class="bp-l"/><polyline points="200,220 222,220 222,160" class="bp-l"/>';
    $s .= '<polyline points="390,160 405,160 405,90 420,90" class="bp-l"/><polyline points="405,160 405,240 420,240" class="bp-l"/>';
    $s .= '<polyline points="50,100 30,100 30,220 50,220" class="bp-l"/>';
    $s .= '<circle cx="30" cy="160" r="10" class="bp-l bp-box"/><text x="30" y="164" class="bp-t bp-sm" text-anchor="middle">?</text>';
    foreach ([[222, 160], [405, 160]] as [$x, $y]) {
        $s .= sprintf('<circle cx="%d" cy="%d" r="3.5" class="bp-dot"/>', $x, $y);
    }
    // a resistor down to ground, and a capacitor between the two right-hand modules
    $s .= '<polyline points="315,180 315,205 323,210 307,218 323,226 307,234 323,242 315,247 315,300" class="bp-l"/>';
    $s .= '<path d="M295 300H335M302 308H328M309 316H321" class="bp-l"/>' . svg_text(315, 340, 'flux6', 'bp-t bp-sm', ' text-anchor="middle"');
    $s .= '<path d="M500 110V158M486 158H514M486 166H514M500 166V220" class="bp-l"/>';

    $s .= svg_text(30, 438, 'flux_note', 'bp-t bp-xs');
    return $s . title_block(312, 392, 270, 'UH-0042-C', 4, 26) . '</svg>';
}
