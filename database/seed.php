<?php
/**
 * GLAIMAGAIN - Database Migration & Seeder Script
 * Execute via CLI: php database/seed.php
 */

require_once __DIR__ . '/../config/config.php';

echo "====================================================\n";
echo " GLAIMAGAIN - Database Setup & Seeding Engine\n";
echo " Brand: GLAIMAGAIN (FASHION BEYOND TODAY)\n";
echo "====================================================\n\n";

try {
    // 1. Initial Connection to MySQL server to ensure DB exists
    $dsnNoDb = sprintf("mysql:host=%s;port=%s;charset=utf8mb4", DB_HOST, DB_PORT);
    $pdoRoot = new PDO($dsnNoDb, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    echo "✔ Database `" . DB_NAME . "` verified / created successfully.\n";

    // 2. Connect to the glaimagain_db database
    $pdo = getDb();

    // 3. Execute Schema
    $sqlFile = __DIR__ . '/glaimagain.sql';
    if (!file_exists($sqlFile)) {
        die("Error: SQL file not found at " . $sqlFile . "\n");
    }
    $sql = file_get_contents($sqlFile);
    $pdo->exec($sql);
    echo "✔ All 16 database tables constructed successfully.\n";

    // 4. Seed Super Admin
    $adminPasswordHash = password_hash('Glaimagain@1026#', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("
        INSERT INTO `admins` (`username`, `email`, `password`, `name`, `status`)
        VALUES (?, ?, ?, ?, 'active')
        ON DUPLICATE KEY UPDATE `password` = VALUES(`password`), `name` = VALUES(`name`)
    ");
    $stmt->execute(['Glaimagain', 'admin@glaimagain.com', $adminPasswordHash, 'GLAIMAGAIN Super Admin']);
    echo "✔ Super Admin created: Username: Glaimagain | Password: Glaimagain@1026#\n";

    // 5. Seed Test Customer User
    $customerPasswordHash = password_hash('Customer@123456', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("
        INSERT INTO `users` (`first_name`, `last_name`, `username`, `email`, `mobile`, `password`, `status`)
        VALUES (?, ?, ?, ?, ?, ?, 'active')
        ON DUPLICATE KEY UPDATE `password` = VALUES(`password`)
    ");
    $stmt->execute(['Yusuf', 'Shaikh', 'yusuf_shaikh', 'yusuf@example.com', '+91 9876543210', $customerPasswordHash]);
    $testUserId = $pdo->lastInsertId() ?: 1;

    // Seed User Address
    $stmt = $pdo->prepare("
        INSERT INTO `user_addresses` (`user_id`, `full_name`, `mobile`, `address_line_1`, `address_line_2`, `area`, `city`, `state`, `pincode`, `address_type`, `is_default`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'home', 1)
    ");
    $stmt->execute([
        $testUserId,
        'Yusuf Shaikh',
        '+91 9876543210',
        'Flat 402, Royal Emerald Heights',
        'Near Luxury Boulevard, Fashion Lane',
        'Bandra West',
        'Mumbai',
        'Maharashtra',
        '400050'
    ]);
    echo "✔ Demo Customer seeded: Username: yusuf_shaikh | Email: yusuf@example.com | Password: Customer@123456\n";

    // 6. Seed Categories
    $categories = [
        [
            'name' => 'Men',
            'slug' => 'men',
            'description' => 'Sophisticated, tailored menswear designed for modern elegance.',
            'image' => 'assets/images/category-men.jpg',
            'is_featured' => 1,
            'sort_order' => 1
        ],
        [
            'name' => 'Women',
            'slug' => 'women',
            'description' => 'Haute couture and contemporary silhouettes crafted with perfection.',
            'image' => 'assets/images/category-women.jpg',
            'is_featured' => 1,
            'sort_order' => 2
        ],
        [
            'name' => 'Luxury T-Shirts',
            'slug' => 'luxury-t-shirts',
            'description' => 'Heavyweight 280 GSM combed cotton oversized and signature t-shirts.',
            'image' => 'assets/images/category-tshirts.jpg',
            'is_featured' => 1,
            'sort_order' => 3
        ],
        [
            'name' => 'Premium Shirts',
            'slug' => 'premium-shirts',
            'description' => 'Egyptian cotton bespoke dress shirts and luxury linen resort shirts.',
            'image' => 'assets/images/category-shirts.jpg',
            'is_featured' => 1,
            'sort_order' => 4
        ],
        [
            'name' => 'Tailored Jackets',
            'slug' => 'tailored-jackets',
            'description' => 'Structured blazers, emerald overcoats, and statement outerwear.',
            'image' => 'assets/images/category-jackets.jpg',
            'is_featured' => 1,
            'sort_order' => 5
        ],
        [
            'name' => 'Designer Hoodies',
            'slug' => 'designer-hoodies',
            'description' => 'Ultra-soft fleece oversized luxury streetwear hoodies with gold embroidery.',
            'image' => 'assets/images/category-hoodies.jpg',
            'is_featured' => 1,
            'sort_order' => 6
        ],
        [
            'name' => 'Jeans & Trousers',
            'slug' => 'jeans-trousers',
            'description' => 'Selvedge denim and pleated trousers with impeccable drape.',
            'image' => 'assets/images/category-jeans.jpg',
            'is_featured' => 1,
            'sort_order' => 7
        ],
        [
            'name' => 'Luxury Accessories',
            'slug' => 'accessories',
            'description' => 'Artisan leather goods, signature scarves, and gold-finish cufflinks.',
            'image' => 'assets/images/category-accessories.jpg',
            'is_featured' => 1,
            'sort_order' => 8
        ]
    ];

    $catStmt = $pdo->prepare("
        INSERT INTO `categories` (`name`, `slug`, `description`, `image`, `status`, `is_featured`, `sort_order`)
        VALUES (?, ?, ?, ?, 'active', ?, ?)
    ");
    $catMap = [];
    foreach ($categories as $cat) {
        $catStmt->execute([$cat['name'], $cat['slug'], $cat['description'], $cat['image'], $cat['is_featured'], $cat['sort_order']]);
        $catMap[$cat['slug']] = $pdo->lastInsertId();
    }
    echo "✔ " . count($categories) . " luxury categories seeded.\n";

    // 7. Seed Luxury Products with Variants and Images
    $products = [
        [
            'category_slug' => 'luxury-t-shirts',
            'name' => 'Signature Emerald Oversized Heavyweight Tee',
            'slug' => 'signature-emerald-oversized-heavyweight-tee',
            'sku' => 'GLA-TSH-001',
            'short_description' => 'Crafted from 280 GSM luxury combed cotton with signature gold silicone branding.',
            'detailed_description' => "<p>Redefine casual luxury with the GLAIMAGAIN Signature Emerald Oversized Tee. Cut from custom-milled 280 GSM organic combed cotton, this heavyweight t-shirt features a relaxed boxy drape, reinforced ribbed collar, and high-density gold micro-embroidery across the chest.</p><ul><li>100% Combed Compact Organic Cotton (280 GSM)</li><li>Pre-shrunk and silicone-washed for extreme softness</li><li>Embossed metallic gold logo accent on nape and chest</li><li>Handcrafted in limited batches</li></ul>",
            'original_price' => 2999.00,
            'selling_price' => 1799.00,
            'stock' => 50,
            'sizes' => 'S,M,L,XL,XXL',
            'colors' => 'Emerald Green,Midnight Black,Ivory White',
            'is_featured' => 1,
            'is_new_arrival' => 1,
            'is_best_seller' => 1,
            'images' => [
                'assets/images/product-tshirt-emerald-1.jpg',
                'assets/images/product-tshirt-emerald-2.jpg',
                'assets/images/product-tshirt-emerald-3.jpg'
            ]
        ],
        [
            'category_slug' => 'premium-shirts',
            'name' => 'Royal Oxford French Cuff Dress Shirt',
            'slug' => 'royal-oxford-french-cuff-dress-shirt',
            'sku' => 'GLA-SHT-002',
            'short_description' => 'Two-ply Egyptian Giza cotton tailored dress shirt with mother-of-pearl buttons.',
            'detailed_description' => "<p>The quintessential luxury dress shirt. Woven from superfine two-ply 120s Egyptian Giza cotton, this shirt offers an exquisite lustrous finish and structured cutaway collar. Completed with genuine Australian mother-of-pearl buttons and convertible French cuffs.</p><ul><li>100% Egyptian Giza Cotton (120s Two-Ply)</li><li>Hand-sewn mother-of-pearl buttons</li><li>Stiffened collar stays included</li><li>Wrinkle-resistant luxury weave</li></ul>",
            'original_price' => 4999.00,
            'selling_price' => 3299.00,
            'stock' => 35,
            'sizes' => '38,40,42,44,46',
            'colors' => 'Crisp White,Midnight Navy,Champagne Gold',
            'is_featured' => 1,
            'is_new_arrival' => 1,
            'is_best_seller' => 1,
            'images' => [
                'assets/images/product-shirt-white-1.jpg',
                'assets/images/product-shirt-white-2.jpg'
            ]
        ],
        [
            'category_slug' => 'tailored-jackets',
            'name' => 'Monogram Gold Crest Velvet Tuxedo Blazer',
            'slug' => 'monogram-gold-crest-velvet-tuxedo-blazer',
            'sku' => 'GLA-JKT-003',
            'short_description' => 'Deep emerald silk-blend velvet blazer accented with handcrafted gold bullion embroidery.',
            'detailed_description' => "<p>Command any evening in this statement tuxedo blazer. Cut from plush silk-cotton velvet in our signature deep emerald hue, featuring a shawl lapel lined with satin and antique gold crest hand-embroidery on the pocket.</p><ul><li>Silk-Cotton Micro-Velvet exterior with duchess satin lapels</li><li>Cupro luxury gold monogram jacquard lining</li><li>Tailored slim silhouette with double back vents</li><li>Dry clean only</li></ul>",
            'original_price' => 14999.00,
            'selling_price' => 8999.00,
            'stock' => 20,
            'sizes' => '38,40,42,44',
            'colors' => 'Emerald Green,Obsidian Black',
            'is_featured' => 1,
            'is_new_arrival' => 1,
            'is_best_seller' => 0,
            'images' => [
                'assets/images/product-jacket-velvet-1.jpg',
                'assets/images/product-jacket-velvet-2.jpg'
            ]
        ],
        [
            'category_slug' => 'designer-hoodies',
            'name' => 'Heritage 450 GSM French Terry Gold Crest Hoodie',
            'slug' => 'heritage-450-gsm-french-terry-gold-crest-hoodie',
            'sku' => 'GLA-HDY-004',
            'short_description' => 'Super-heavy 450 GSM unbrushed loopback cotton hoodie with engraved gold aglets.',
            'detailed_description' => "<p>Engineered for unmatched comfort and architectural drape. Built with bespoke 450 GSM Japanese loopback French terry, custom heavyweight dual-layer hood, and 24K gold-plated drawstring aglets.</p><ul><li>450 GSM 100% Loopback French Terry Cotton</li><li>24K antique gold electroplated drawstring aglets</li><li>Hidden side seam kangaroo pockets</li><li>Preshrunk double-ribbed side gussets</li></ul>",
            'original_price' => 5499.00,
            'selling_price' => 3499.00,
            'stock' => 40,
            'sizes' => 'S,M,L,XL,XXL',
            'colors' => 'Deep Emerald,Midnight Black,Desert Sand',
            'is_featured' => 1,
            'is_new_arrival' => 0,
            'is_best_seller' => 1,
            'images' => [
                'assets/images/product-hoodie-emerald-1.jpg',
                'assets/images/product-hoodie-emerald-2.jpg'
            ]
        ],
        [
            'category_slug' => 'jeans-trousers',
            'name' => 'Selvedge Raw Indigo Tapered Denim',
            'slug' => 'selvedge-raw-indigo-tapered-denim',
            'sku' => 'GLA-JNS-005',
            'short_description' => '14.5 oz Kurabo Japanese raw shuttle-loom selvedge denim with gold selvedge ID line.',
            'detailed_description' => "<p>Woven on antique shuttle looms in Okayama, Japan, using 100% long-staple cotton dipped 16 times in natural indigo. Features our exclusive gold thread selvedge ticker along the outseam.</p><ul><li>14.5 oz Japanese Raw Indigo Selvedge Denim</li><li>Gold-line selvedge ticker</li><li>Solid brass custom stamped doughnut buttons</li><li>Embossed saddle leather back patch</li></ul>",
            'original_price' => 6999.00,
            'selling_price' => 4499.00,
            'stock' => 30,
            'sizes' => '30,32,34,36,38',
            'colors' => 'Raw Indigo,Washed Obsidian',
            'is_featured' => 0,
            'is_new_arrival' => 1,
            'is_best_seller' => 1,
            'images' => [
                'assets/images/product-jeans-indigo-1.jpg',
                'assets/images/product-jeans-indigo-2.jpg'
            ]
        ],
        [
            'category_slug' => 'luxury-accessories',
            'name' => 'Handcrafted Full-Grain Leather Gold Buckle Belt',
            'slug' => 'handcrafted-full-grain-leather-gold-buckle-belt',
            'sku' => 'GLA-ACC-006',
            'short_description' => 'Italian vegetable-tanned bridle leather belt with solid brass gold-polished buckle.',
            'detailed_description' => "<p>Hand-cut and burnished by master leather artisans. Crafted from premium 4mm Tuscan vegetable-tanned full-grain leather that patinas beautifully with age. Finished with a heavy brass buckle dipped in 24K gold lacquer.</p><ul><li>Full-grain Tuscan vegetable-tanned leather</li><li>Solid forged brass buckle with mirror gold finish</li><li>Beveled and hand-waxed edges</li><li>Presented in luxury emerald velvet dust bag</li></ul>",
            'original_price' => 3499.00,
            'selling_price' => 2199.00,
            'stock' => 45,
            'sizes' => '32,34,36,38,40',
            'colors' => 'Onyx Black,Cognac Brown,Emerald Green',
            'is_featured' => 1,
            'is_new_arrival' => 0,
            'is_best_seller' => 1,
            'images' => [
                'assets/images/product-belt-black-1.jpg',
                'assets/images/product-belt-black-2.jpg'
            ]
        ]
    ];

    $prodStmt = $pdo->prepare("
        INSERT INTO `products` (
            `category_id`, `name`, `slug`, `sku`, `short_description`, `detailed_description`,
            `original_price`, `selling_price`, `stock`, `sizes`, `colors`, `status`,
            `is_featured`, `is_new_arrival`, `is_best_seller`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?, ?)
    ");

    $imgStmt = $pdo->prepare("
        INSERT INTO `product_images` (`product_id`, `image_path`, `is_primary`, `sort_order`)
        VALUES (?, ?, ?, ?)
    ");

    $varStmt = $pdo->prepare("
        INSERT INTO `product_variants` (`product_id`, `size`, `color`, `sku`, `stock`, `additional_price`, `status`)
        VALUES (?, ?, ?, ?, ?, 0.00, 'active')
    ");

    $reviewStmt = $pdo->prepare("
        INSERT INTO `reviews` (`product_id`, `user_id`, `rating`, `title`, `comment`, `status`)
        VALUES (?, ?, ?, ?, ?, 'approved')
    ");

    foreach ($products as $p) {
        $catId = $catMap[$p['category_slug']] ?? 1;
        $prodStmt->execute([
            $catId,
            $p['name'],
            $p['slug'],
            $p['sku'],
            $p['short_description'],
            $p['detailed_description'],
            $p['original_price'],
            $p['selling_price'],
            $p['stock'],
            $p['sizes'],
            $p['colors'],
            $p['is_featured'],
            $p['is_new_arrival'],
            $p['is_best_seller']
        ]);
        $productId = $pdo->lastInsertId();

        // Seed Images
        foreach ($p['images'] as $idx => $img) {
            $imgStmt->execute([$productId, $img, ($idx === 0 ? 1 : 0), $idx]);
        }

        // Seed Variants
        $sizeList = explode(',', $p['sizes']);
        $colorList = explode(',', $p['colors']);
        foreach ($sizeList as $s) {
            $s = trim($s);
            foreach ($colorList as $c) {
                $c = trim($c);
                $varSku = $p['sku'] . '-' . strtoupper(substr($s, 0, 3)) . '-' . strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $c), 0, 3));
                $varStock = rand(5, 15);
                $varStmt->execute([$productId, $s, $c, $varSku, $varStock]);
            }
        }

        // Seed Sample Review
        $reviewStmt->execute([
            $productId,
            $testUserId,
            5,
            'Exquisite craftsmanship and fit',
            'The fabric weight, precision stitching, and deep emerald/gold tone are unmatched. Truly feels like bespoke luxury.'
        ]);
    }
    echo "✔ " . count($products) . " flagship luxury products with variants, images, and reviews seeded.\n";

    // 8. Seed Settings
    $settings = [
        'site_name' => 'GLAIMAGAIN',
        'site_tagline' => 'FASHION BEYOND TODAY',
        'contact_email' => 'concierge@glaimagain.com',
        'contact_phone' => '+91 98765 43210',
        'contact_whatsapp' => '+91 98765 43210',
        'store_address' => 'GLAIMAGAIN Flagship House, Level 4, Luxury Pavilion, Mumbai, Maharashtra 400050, India',
        'instagram_url' => 'https://instagram.com/glaimagain',
        'facebook_url' => 'https://facebook.com/glaimagain',
        'google_business_url' => 'https://maps.google.com/?q=GLAIMAGAIN+Mumbai',
        'working_hours' => 'Mon - Sat: 10:00 AM - 08:00 PM IST',
        'shipping_fee' => '99.00',
        'free_shipping_threshold' => '1999.00',
        'currency_symbol' => '₹',
        'meta_description' => 'GLAIMAGAIN - Fashion Beyond Today. Discover handcrafted luxury apparel, heavyweight tees, bespoke shirts, velvet tuxedos, and artisan streetwear.',
        'meta_keywords' => 'luxury fashion, bespoke clothing, heavyweight tshirts, velvet blazers, premium menswear, streetwear india, glaimagain'
    ];

    $settStmt = $pdo->prepare("
        INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`)
        VALUES (?, ?, 'general')
        ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`)
    ");
    foreach ($settings as $k => $v) {
        $settStmt->execute([$k, $v]);
    }
    echo "✔ Store settings and brand configuration seeded.\n";

    // 9. Seed Homepage Sections
    $sections = [
        [
            'section_key' => 'hero',
            'title' => 'FASHION BEYOND TODAY',
            'subtitle' => 'Autograph Collection — Fall / Winter 2026',
            'content' => 'Impeccable tailoring meets contemporary luxury streetwear. Designed for the modern visionary.',
            'button_text' => 'EXPLORE COLLECTION',
            'button_link' => 'shop.php',
            'image_path' => 'assets/images/hero-banner.jpg',
            'is_active' => 1,
            'sort_order' => 1
        ],
        [
            'section_key' => 'brand_story',
            'title' => 'THE GLAIMAGAIN PHILOSOPHY',
            'subtitle' => 'Timeless Elegance. Uncompromising Craft.',
            'content' => 'Born at the intersection of haute couture precision and modern cultural resonance, GLAIMAGAIN crafts garments that transcend transient trends. Every seam, cut, and gold-hued emblem reflects an obsessive pursuit of sartorial distinction.',
            'button_text' => 'DISCOVER OUR STORY',
            'button_link' => 'about.php',
            'image_path' => 'assets/images/brand-story.jpg',
            'is_active' => 1,
            'sort_order' => 2
        ],
        [
            'section_key' => 'promo_banner',
            'title' => 'THE EMERALD & GOLD CAPSULE',
            'subtitle' => 'Limited Edition Masterpieces',
            'content' => 'Indulge in 280 GSM combed cottons and silk-velvet outerwear infused with 24K gold accents.',
            'button_text' => 'SHOP THE CAPSULE',
            'button_link' => 'shop.php?category=luxury-t-shirts',
            'image_path' => 'assets/images/promo-capsule.jpg',
            'is_active' => 1,
            'sort_order' => 3
        ]
    ];

    $secStmt = $pdo->prepare("
        INSERT INTO `homepage_sections` (`section_key`, `title`, `subtitle`, `content`, `button_text`, `button_link`, `image_path`, `is_active`, `sort_order`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `subtitle` = VALUES(`subtitle`), `content` = VALUES(`content`), `button_text` = VALUES(`button_text`), `button_link` = VALUES(`button_link`), `image_path` = VALUES(`image_path`), `is_active` = VALUES(`is_active`)
    ");
    foreach ($sections as $s) {
        $secStmt->execute([
            $s['section_key'],
            $s['title'],
            $s['subtitle'],
            $s['content'],
            $s['button_text'],
            $s['button_link'],
            $s['image_path'],
            $s['is_active'],
            $s['sort_order']
        ]);
    }
    echo "✔ Dynamic homepage CMS sections seeded.\n";

    echo "\n====================================================\n";
    echo " ✔ DATABASE SEEDING COMPLETED SUCCESSFULLY!\n";
    echo "====================================================\n";

} catch (Exception $e) {
    echo "\n❌ Migration Failed: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
