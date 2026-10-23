<?php
declare(strict_types=1);

// The two drawings: the folder (white, with a gold twin underneath that fades in on hover) and the flat red lock.

const FOLDER_BACK = 'M22 44Q22 32 34 32H108Q116 32 121 38L130 50H266Q278 50 278 62V212Q278 224 266 224H34Q22 224 22 212Z';
const FOLDER_FRONT = 'M16 94Q16 82 28 82H272Q284 82 284 94L278 214Q277 228 264 228H36Q23 228 22 214Z';

function folder_svg(): string
{
    $back = FOLDER_BACK;
    $front = FOLDER_FRONT;
    return <<<SVG
<svg class="folder-art" viewBox="0 0 300 240" aria-hidden="true" focusable="false">
  <defs>
    <linearGradient id="fBackW" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#e2e1dc"/><stop offset="1" stop-color="#c4c3be"/></linearGradient>
    <linearGradient id="fFrontW" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffffff"/><stop offset="1" stop-color="#e6e5df"/></linearGradient>
    <linearGradient id="fBackG" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#a57b22"/><stop offset="1" stop-color="#5a3e0b"/></linearGradient>
    <linearGradient id="fFrontG" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#fcefbd"/><stop offset=".26" stop-color="#e3bc5d"/><stop offset=".52" stop-color="#b5872b"/>
      <stop offset=".78" stop-color="#ebcb78"/><stop offset="1" stop-color="#a47820"/>
    </linearGradient>
    <linearGradient id="fLip" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#000" stop-opacity="0"/><stop offset="1" stop-color="#000" stop-opacity=".16"/></linearGradient>
    <linearGradient id="fShine" x1="0" y1="0" x2="1" y2="0">
      <stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset=".5" stop-color="#fff" stop-opacity=".9"/><stop offset="1" stop-color="#fff" stop-opacity="0"/>
    </linearGradient>
    <clipPath id="fFrontClip"><path d="$front"/></clipPath>
  </defs>
  <path d="$back" fill="url(#fBackW)"/>
  <path class="gold" d="$back" fill="url(#fBackG)"/>
  <rect x="40" y="52" width="222" height="150" rx="4" fill="#efeee9" transform="rotate(-3 151 127)"/>
  <rect x="46" y="58" width="212" height="150" rx="4" fill="#fbfbf8"/>
  <path d="M64 70H178M64 78H140" stroke="#d6d5cf" stroke-width="2.2" stroke-linecap="round"/>
  <rect x="20" y="70" width="260" height="13" fill="url(#fLip)"/>
  <path d="$front" fill="url(#fFrontW)"/>
  <path class="gold" d="$front" fill="url(#fFrontG)"/>
  <g clip-path="url(#fFrontClip)"><g transform="skewX(-22)"><rect class="shine" x="40" y="70" width="70" height="170" fill="url(#fShine)"/></g></g>
  <path d="M28 82.7H272" stroke="#fff" stroke-opacity=".85" stroke-width="1.2"/>
</svg>
SVG;
}

function lock_svg(): string
{
    return <<<'SVG'
<svg class="lock-art" viewBox="0 0 120 150" aria-hidden="true" focusable="false">
  <path class="shackle" d="M36 72V46a24 24 0 0 1 48 0V72" fill="none" stroke="currentColor" stroke-width="11" stroke-linecap="round"/>
  <rect x="16" y="64" width="88" height="74" rx="14" fill="currentColor"/>
  <circle cx="60" cy="94" r="9" fill="#050506"/>
  <rect x="56" y="96" width="8" height="20" rx="4" fill="#050506"/>
</svg>
SVG;
}
