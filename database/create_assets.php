<?php
/**
 * GLAIMAGAIN - High-Definition Visual Vector Asset Generator
 * Produces luxury fashion image assets using crisp SVGs
 */

$imagesDir = __DIR__ . '/../assets/images/';
if (!is_dir($imagesDir)) {
    mkdir($imagesDir, 0755, true);
}

function generateLuxurySvg($title, $subtitle, $badge, $c1, $c2, $gold = '#B99036', $width = 800, $height = 1000) {
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$width} {$height}" width="100%" height="100%">
  <defs>
    <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$c1}" />
      <stop offset="50%" stop-color="{$c2}" />
      <stop offset="100%" stop-color="#02140D" />
    </linearGradient>
    <linearGradient id="goldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#F3DC87" />
      <stop offset="40%" stop-color="{$gold}" />
      <stop offset="100%" stop-color="#7C5D1E" />
    </linearGradient>
    <radialGradient id="vignette" cx="50%" cy="50%" r="60%">
      <stop offset="60%" stop-color="#000000" stop-opacity="0" />
      <stop offset="100%" stop-color="#000000" stop-opacity="0.6" />
    </radialGradient>
    <pattern id="gridPattern" width="40" height="40" patternUnits="userSpaceOnUse">
      <path d="M 40 0 L 0 0 0 40" fill="none" stroke="{$gold}" stroke-width="0.5" stroke-opacity="0.08" />
    </pattern>
  </defs>

  <!-- Background Base & Texture -->
  <rect width="{$width}" height="{$height}" fill="url(#bgGrad)" />
  <rect width="{$width}" height="{$height}" fill="url(#gridPattern)" />
  <rect width="{$width}" height="{$height}" fill="url(#vignette)" />

  <!-- Double Luxury Gold Framing -->
  <rect x="25" y="25" width="{$width}-50" height="{$height}-50" fill="none" stroke="url(#goldGrad)" stroke-width="1.5" stroke-opacity="0.4" />
  <rect x="35" y="35" width="{$width}-70" height="{$height}-70" fill="none" stroke="url(#goldGrad)" stroke-width="0.75" stroke-opacity="0.25" stroke-dasharray="6,4" />

  <!-- Corner Ornaments -->
  <g stroke="url(#goldGrad)" stroke-width="2" fill="none">
    <path d="M 20 45 L 20 20 L 45 20" />
    <path d="M {$width}-20 45 L {$width}-20 20 L {$width}-45 20" />
    <path d="M 20 {$height}-45 L 20 {$height}-20 L 45 {$height}-20" />
    <path d="M {$width}-20 {$height}-45 L {$width}-20 {$height}-20 L {$width}-45 {$height}-20" />
  </g>

  <!-- Central Luxury Fashion Emblem -->
  <g transform="translate({$width}/2, {$height}*0.42)">
    <circle cx="0" cy="0" r="100" fill="#011F14" fill-opacity="0.6" stroke="url(#goldGrad)" stroke-width="1.5" />
    <circle cx="0" cy="0" r="85" fill="none" stroke="url(#goldGrad)" stroke-width="0.75" stroke-opacity="0.5" stroke-dasharray="4,3" />

    <!-- Stylized Monogram G -->
    <path d="M 18 -20 C 6 -32, -18 -28, -26 -14 C -34 2, -28 26, -8 32 C 8 36, 22 24, 22 8 L -6 8" fill="none" stroke="url(#goldGrad)" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round" />
    <circle cx="0" cy="-35" r="3.5" fill="url(#goldGrad)" />
    
    <text y="58" text-anchor="middle" font-family="'Cinzel', 'Playfair Display', serif" font-size="11" font-weight="700" letter-spacing="4" fill="url(#goldGrad)">
      GLAIMAGAIN
    </text>
    <text y="74" text-anchor="middle" font-family="'Poppins', sans-serif" font-size="8" font-weight="600" letter-spacing="3" fill="#FFFFFF" fill-opacity="0.7">
      {$badge}
    </text>
  </g>

  <!-- Product / Category Heading Card at Bottom -->
  <g transform="translate({$width}/2, {$height}*0.75)">
    <line x1="-120" y1="-25" x2="120" y2="-25" stroke="url(#goldGrad)" stroke-width="1" stroke-opacity="0.6" />
    <circle cx="0" cy="-25" r="3" fill="url(#goldGrad)" />

    <text y="0" text-anchor="middle" font-family="'Cinzel', 'Playfair Display', 'Times New Roman', serif" font-size="24" font-weight="700" letter-spacing="3" fill="#FFFFFF">
      {$title}
    </text>
    <text y="24" text-anchor="middle" font-family="'Poppins', sans-serif" font-size="12" font-weight="500" letter-spacing="2" fill="url(#goldGrad)">
      {$subtitle}
    </text>
    <text y="55" text-anchor="middle" font-family="'Poppins', sans-serif" font-size="9.5" font-weight="600" letter-spacing="4" fill="#FFFFFF" fill-opacity="0.5">
      FASHION BEYOND TODAY
    </text>
  </g>
</svg>
SVG;
}

$assets = [
    // Banners
    'hero-banner.jpg' => ['title' => 'AUTOGRAPH COLLECTION 2026', 'subtitle' => 'TIMELESS LUXURY APPAREL', 'badge' => 'FALL / WINTER 2026', 'c1' => '#013C26', 'c2' => '#081F15', 'w' => 1600, 'h' => 800],
    'brand-story.jpg' => ['title' => 'THE ATELIER HERITAGE', 'subtitle' => 'CRAFTED WITHOUT COMPROMISE', 'badge' => 'HAUTE COUTURE', 'c1' => '#111827', 'c2' => '#013C26', 'w' => 1200, 'h' => 800],
    'promo-capsule.jpg' => ['title' => 'EMERALD &amp; GOLD CAPSULE', 'subtitle' => 'LIMITED EDITION SARTORIAL DROPS', 'badge' => '24K GOLD ACCENTS', 'c1' => '#013C26', 'c2' => '#1E1B18', 'w' => 1400, 'h' => 600],

    // Categories
    'category-men.jpg' => ['title' => 'MEN COUTURE', 'subtitle' => 'TAILORED &amp; STREETWEAR', 'badge' => 'BESPOKE MENSWEAR', 'c1' => '#0f172a', 'c2' => '#013C26', 'w' => 700, 'h' => 900],
    'category-women.jpg' => ['title' => 'WOMEN ATELIER', 'subtitle' => 'TIMELESS SILHOUETTES', 'badge' => 'HAUTE COUTURE', 'c1' => '#1c1917', 'c2' => '#2e1065', 'w' => 700, 'h' => 900],
    'category-tshirts.jpg' => ['title' => 'LUXURY T-SHIRTS', 'subtitle' => '280 GSM COMBED COTTON', 'badge' => 'SIGNATURE OVERSIZED', 'c1' => '#022c22', 'c2' => '#013C26', 'w' => 700, 'h' => 900],
    'category-shirts.jpg' => ['title' => 'PREMIUM SHIRTS', 'subtitle' => '120s EGYPTIAN GIZA', 'badge' => 'FRENCH CUFF DRESS SHIRTS', 'c1' => '#0f172a', 'c2' => '#1e293b', 'w' => 700, 'h' => 900],
    'category-jackets.jpg' => ['title' => 'TAILORED JACKETS', 'subtitle' => 'SILK-VELVET &amp; BLAZERS', 'badge' => 'GOLD CREST EMBROIDERED', 'c1' => '#013C26', 'c2' => '#022c22', 'w' => 700, 'h' => 900],
    'category-hoodies.jpg' => ['title' => 'DESIGNER HOODIES', 'subtitle' => '450 GSM FRENCH TERRY', 'badge' => 'GOLD AGLETS &amp; CREST', 'c1' => '#18181b', 'c2' => '#27272a', 'w' => 700, 'h' => 900],
    'category-jeans.jpg' => ['title' => 'JEANS &amp; TROUSERS', 'subtitle' => 'JAPANESE SELVEDGE DENIM', 'badge' => '14.5 OZ OKAYAMA LOOM', 'c1' => '#0c1a2e', 'c2' => '#1e293b', 'w' => 700, 'h' => 900],
    'category-accessories.jpg' => ['title' => 'LUXURY ACCESSORIES', 'subtitle' => 'FULL-GRAIN LEATHER &amp; GOLD', 'badge' => 'TUSCAN ARTISAN CRAFT', 'c1' => '#1c1917', 'c2' => '#451a03', 'w' => 700, 'h' => 900],

    // Products
    'product-tshirt-emerald-1.jpg' => ['title' => 'EMERALD OVERSIZED TEE', 'subtitle' => '280 GSM ORGANIC COTTON', 'badge' => 'MAIN STUDIO SHOT', 'c1' => '#013C26', 'c2' => '#042f1a', 'w' => 800, 'h' => 1000],
    'product-tshirt-emerald-2.jpg' => ['title' => 'EMERALD OVERSIZED TEE', 'subtitle' => 'GOLD SILICONE EMBROIDERY', 'badge' => 'DETAIL VIEW', 'c1' => '#013C26', 'c2' => '#0f291e', 'w' => 800, 'h' => 1000],
    'product-tshirt-emerald-3.jpg' => ['title' => 'EMERALD OVERSIZED TEE', 'subtitle' => 'RELAXED BOXY DRAPE', 'badge' => 'BACK PROFILE', 'c1' => '#022c22', 'c2' => '#013C26', 'w' => 800, 'h' => 1000],

    'product-shirt-white-1.jpg' => ['title' => 'ROYAL OXFORD SHIRT', 'subtitle' => '120s EGYPTIAN GIZA', 'badge' => 'FRENCH CUFF VIEW', 'c1' => '#1e293b', 'c2' => '#0f172a', 'w' => 800, 'h' => 1000],
    'product-shirt-white-2.jpg' => ['title' => 'ROYAL OXFORD SHIRT', 'subtitle' => 'MOTHER-OF-PEARL BUTTONS', 'badge' => 'COLLAR DETAIL', 'c1' => '#1e293b', 'c2' => '#334155', 'w' => 800, 'h' => 1000],

    'product-jacket-velvet-1.jpg' => ['title' => 'GOLD CREST VELVET BLAZER', 'subtitle' => 'EMERALD SILK-COTTON VELVET', 'badge' => 'TUXEDO STATEMENT', 'c1' => '#013C26', 'c2' => '#052e16', 'w' => 800, 'h' => 1000],
    'product-jacket-velvet-2.jpg' => ['title' => 'GOLD CREST VELVET BLAZER', 'subtitle' => 'GOLD BULLION EMBROIDERY', 'badge' => 'CREST DETAIL', 'c1' => '#013C26', 'c2' => '#022c22', 'w' => 800, 'h' => 1000],

    'product-hoodie-emerald-1.jpg' => ['title' => 'HERITAGE 450 GSM HOODIE', 'subtitle' => 'FRENCH TERRY WITH GOLD AGLETS', 'badge' => 'STREETWEAR ATELIER', 'c1' => '#0f291e', 'c2' => '#013C26', 'w' => 800, 'h' => 1000],
    'product-hoodie-emerald-2.jpg' => ['title' => 'HERITAGE 450 GSM HOODIE', 'subtitle' => '24K GOLD-PLATED HARDWARE', 'badge' => 'HOOD DETAIL', 'c1' => '#111827', 'c2' => '#013C26', 'w' => 800, 'h' => 1000],

    'product-jeans-indigo-1.jpg' => ['title' => 'RAW INDIGO SELVEDGE', 'subtitle' => '14.5 OZ OKAYAMA LOOM', 'badge' => 'JAPANESE SELVEDGE', 'c1' => '#0b1329', 'c2' => '#1e293b', 'w' => 800, 'h' => 1000],
    'product-jeans-indigo-2.jpg' => ['title' => 'RAW INDIGO SELVEDGE', 'subtitle' => 'GOLD TICKER LINE OUTSEAM', 'badge' => 'SELVEDGE ID DETAIL', 'c1' => '#0b1329', 'c2' => '#0f172a', 'w' => 800, 'h' => 1000],

    'product-belt-black-1.jpg' => ['title' => 'FULL-GRAIN LEATHER BELT', 'subtitle' => 'TUSCAN BRIDLE LEATHER', 'badge' => 'HAND-BURNISHED', 'c1' => '#18181b', 'c2' => '#27272a', 'w' => 800, 'h' => 1000],
    'product-belt-black-2.jpg' => ['title' => 'FULL-GRAIN LEATHER BELT', 'subtitle' => 'SOLID FORGED GOLD BUCKLE', 'badge' => 'HARDWARE DETAIL', 'c1' => '#1c1917', 'c2' => '#292524', 'w' => 800, 'h' => 1000],

    'placeholder-product.svg' => ['title' => 'GLAIMAGAIN ATELIER', 'subtitle' => 'PRODUCT PREVIEW', 'badge' => 'LUXURY ESSENTIAL', 'c1' => '#013C26', 'c2' => '#081F15', 'w' => 800, 'h' => 1000],
    'placeholder-category.svg' => ['title' => 'GLAIMAGAIN CAPSULE', 'subtitle' => 'CATEGORY SHOWCASE', 'badge' => 'COLLECTION', 'c1' => '#013C26', 'c2' => '#081F15', 'w' => 700, 'h' => 900],
];

foreach ($assets as $file => $info) {
    $svg = generateLuxurySvg($info['title'], $info['subtitle'], $info['badge'], $info['c1'], $info['c2'], '#B99036', $info['w'], $info['h']);
    file_put_contents($imagesDir . $file, $svg);
    echo "Generated: " . $file . "\n";
}

echo "\nAll HD Vector Fashion Assets Created Successfully!\n";
