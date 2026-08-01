<?php
/**
 * EasyShop product seeder
 * Adds 1000+ demo products with sizes, colors and placeholder images.
 * Run: php database/seed_products.php
 */
require_once __DIR__ . '/../includes/config.php';

$uploads_dir = __DIR__ . '/../uploads/products';
if (!is_dir($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
}

$styles = ['Classic', 'Premium', 'Trendy', 'Party Wear', 'Casual', 'Elegant', 'Everyday', 'New Season', 'Best Seller', 'Signature'];

$palette = [
    'Red'        => '#CC0000',
    'Navy Blue'  => '#1A2A5E',
    'Black'      => '#111111',
    'White'      => '#F5F5F5',
    'Beige'      => '#D2B48C',
    'Maroon'     => '#800000',
    'Teal'       => '#008080',
    'Mustard'    => '#E1AD01',
    'Pink'       => '#F47FB0',
    'Lavender'   => '#B57EDC',
    'Olive'      => '#556B2F',
    'Grey'       => '#7F8C8D',
    'Royal Blue' => '#4169E1',
    'Peach'      => '#FFDAB9',
    'Burgundy'   => '#900020',
    'Emerald'    => '#1E8449',
    'Rust'       => '#B7410E',
    'Sky Blue'   => '#87CEEB',
    'Off White'  => '#FAF3E0',
    'Charcoal'   => '#36454F',
];
$color_names = array_keys($palette);

$cats = [
    'Women\'s Wear' => [
        'count'   => 250,
        'price'   => [499, 2499],
        'sizes'   => ['S', 'M', 'L', 'XL', 'XXL'],
        'fabrics' => ['Premium Cotton', 'Soft Crepe', 'Breathable Linen', 'Silk Blend', 'Chiffon', 'Rayon'],
        'fit'     => ['Slim Fit', 'Relaxed Fit', 'A-Line', 'Regular Fit'],
        'desc'    => [
            'This stylish piece is tailored for modern women who love comfort with elegance. Ideal for daily wear, office, and casual outings.',
            'Crafted with premium fabric that feels soft on the skin and drapes beautifully. Pairs well with heels, flats or sneakers.',
            'A versatile addition to your wardrobe with flattering cut and breathable fabric. Machine washable and easy to maintain.',
            'Designed for the fashion-forward woman. Perfect for parties, brunches and festive gatherings.',
        ],
        'bases'   => [
            'Floral Print A-Line Dress', 'Solid Wrap Midi Dress', 'Sleeveless Bodycon Dress', 'Tiered Midi Maxi Dress',
            'Cotton Printed Kurta', 'Embroidered Anarkali Gown', 'Bell Sleeve Top', 'Off-Shoulder Crop Top',
            'High-Waist Denim Jeans', 'Flared Palazzo Pants', 'Pleated Midi Skirt', 'Straight Fit Casual Trousers',
            'Ribbed Knit Turtleneck', 'Cotton Shirt Dress', 'Ruffle Hem Romper', 'Printed Lounge Co-ords Set',
            'Chiffon Evening Gown', 'Denim Jacket', 'Wool Blend Cardigan', 'Sequin Party Top',
            'Linen Relaxed Blouse', 'Halter Neck Sundress', 'Cargo Utility Pants', 'Wide Leg Culottes',
            'Tulle Layered Skirt', 'Puffer Jacket', 'Silk Camisole', 'Printed Palazzo Co-ords',
            'Jersey Wrap Skirt', 'Lace Trim Nightgown',
        ],
    ],
    'Men\'s Wear' => [
        'count'   => 250,
        'price'   => [499, 2299],
        'sizes'   => ['S', 'M', 'L', 'XL', 'XXL'],
        'fabrics' => ['100% Cotton', 'Cotton Blend', 'Linen Blend', 'Premium Denim', 'Poly-Cotton', 'Knit Fabric'],
        'fit'     => ['Slim Fit', 'Regular Fit', 'Relaxed Fit', 'Tailored Fit'],
        'desc'    => [
            'Sharp and comfortable menswear built for everyday style. Great for office, travel and weekends.',
            'Made from quality fabric with clean stitching and a comfortable cut. Easy care, long lasting.',
            'A wardrobe essential that works with everything. Breathable, durable and effortlessly stylish.',
            'Modern fit with premium finish. Ideal for casual meets, college and street style.',
        ],
        'bases'   => [
            'Classic Oxford Shirt', 'Slim Fit Polo T-Shirt', 'Printed Casual T-Shirt', 'Straight Fit Denim Jeans',
            'Chino Trousers', 'Formal Check Shirt', 'Crew Neck Sweatshirt', 'Zip-Up Hoodie',
            'Cotton V-Neck Tee', 'Linen Short Sleeve Shirt', 'Cargo Shorts', 'Tailored Suit Blazer',
            'Formal Trousers', 'Track Pants', 'Sports Jersey Tee', 'Denim Shorts',
            'Full Sleeve Henley', 'Puffer Jacket', 'Bomber Jacket', 'Leather Moto Jacket',
            'Lounge Joggers', 'Graphic Oversized Tee', 'Button Down Cardigan', 'Windbreaker Jacket',
            'Bermuda Shorts', 'Slim Jogger Shorts', 'Oversized Flannel Shirt', 'Knit Crewneck Sweater',
            'Performance Running Tee', 'Formal Waistcoat',
        ],
    ],
    'Kids Wear' => [
        'count'   => 150,
        'price'   => [299, 1499],
        'sizes'   => ['2-3Y', '4-5Y', '6-7Y', '8-9Y', '10-12Y'],
        'fabrics' => ['Soft Cotton', 'Pure Cotton', 'Cotton Blend', 'Jersey Knit', 'Terry Cotton'],
        'fit'     => ['Comfort Fit', 'Regular Fit', 'Loose Fit'],
        'desc'    => [
            'Soft, skin-friendly fabric perfect for kids who love to play all day. Easy to wash and durable.',
            'Bright and cheerful design that kids adore. Gentle on skin with no rough edges or tags.',
            'Comfortable everyday wear for school, play and family outings. Machine washable.',
            'Made with child-safe materials and vibrant colours that stay bright after many washes.',
        ],
        'bases'   => [
            'Cotton Printed T-Shirt', 'Dungaree Playsuit', 'Floral Frock Dress', 'Cargo Shorts Set',
            'Hoodie Jacket', 'Denim Overalls', 'Striped Polo Tee', 'Cartoon Print Pyjama Set',
            'Party Gown Dress', 'Knit Sweater', 'Track Suit Set', 'School Uniform Shirt',
            'Pleated Skirt Set', 'Casual Jeans', 'Graphic Tee Set', 'Ruffle Top',
            'Winter Jacket', 'Romper Onesie', 'Sports Shorts Set', 'Cotton Kurti Set',
            'Birthday Outfit Set', 'Ethnic Lehenga Set', 'Basic Cotton Leggings', 'Printed Dungarees',
            'Layered Party Dress', 'Warm Sweatpants', 'Denim Jacket', 'Pyjama Shorts Set',
            'Festival Ethnic Set', 'Athleisure Set',
        ],
    ],
    'Traditional' => [
        'count'   => 150,
        'price'   => [699, 3999],
        'sizes'   => ['S', 'M', 'L', 'XL', 'XXL'],
        'fabrics' => ['Banarasi Silk', 'Pure Georgette', 'Handloom Cotton', 'Velvet', 'Chiffon Silk', 'Art Silk'],
        'fit'     => ['Regular Fit', 'Relaxed Fit', 'A-Line'],
        'desc'    => [
            'Beautifully crafted ethnic wear with fine embroidery and traditional detailing. Perfect for festivals and weddings.',
            'Made with luxurious fabric and rich colours. A timeless piece for any Indian celebration.',
            'Hand-finished motifs and elegant drapes make this a standout addition to your ethnic wardrobe.',
            'Comfortable yet grand, ideal for family functions, pujas and wedding season.',
        ],
        'bases'   => [
            'Silk Banarasi Saree', 'Cotton Handloom Saree', 'Georgette Printed Saree', 'Embroidered Lehenga Choli',
            'Anarkali Suit Set', 'Straight Cut Salwar Suit', 'Chikankari Kurti Set', 'Patiala Salwar Suit',
            'Designer Sherwani', 'Bandhgala Jacket', 'Kurta Pyjama Set', 'Silk Dupatta Set',
            'Ghagra Choli Set', 'Cotton Dhoti Set', 'Groom Wedding Sherwani', 'Velvet Lehenga Set',
            'Banarasi Silk Kurta', 'Phulkari Dupatta Set', 'Dhoti Kurta Set', 'Festive Salwar Kameez',
            'Net Anarkali Gown', 'Handloom Kota Saree', 'Silk Choli Blouse Set', 'Party Wear Saree',
            'Peshwai Lehenga', 'Achkan Kurta Set', 'Ethnic Cotton Kurti', 'Brocade Gown Set',
            'Satin Anarkali Dress', 'Embroidered Waistcoat Set',
        ],
    ],
    'Footwear' => [
        'count'   => 130,
        'price'   => [399, 1999],
        'sizes'   => ['6', '7', '8', '9', '10'],
        'fabrics' => ['Breathable Mesh', 'Genuine Leather', 'PU Leather', 'Canvas', 'Memory Foam', 'Rubber Sole'],
        'fit'     => ['Standard Fit', 'Wide Fit', 'Comfort Fit'],
        'desc'    => [
            'Lightweight and comfortable footwear built for all-day wear. Anti-skid sole for safety.',
            'Cushioned insole and breathable material keep your feet fresh. Perfect for daily use.',
            'Durable construction with flexible sole. Pairs well with casual and semi-formal outfits.',
            'Designed for comfort with extra padding and arch support. Easy to slip on and off.',
        ],
        'bases'   => [
            'Casual Sneakers', 'Running Shoes', 'Formal Derby Shoes', 'Leather Loafers',
            'Canvas Shoes', 'High-Top Sneakers', 'Sports Sandals', 'Floaters',
            'Flip Flops', 'Party Heels', 'Ankle Boots', 'Slip-On Shoes',
            'Wedge Sandals', 'Ballerina Flats', 'Comfort Slides', 'Gym Training Shoes',
            'Wedge Heels', 'Strappy Heels', 'Casual Boat Shoes', 'Kids Casual Shoes',
            'Walking Shoes', 'Lace-Up Boots', 'Moccasins', 'Ballet Flats',
            'Espadrille Sandals', 'Skateboard Sneakers', 'Comfort Mules', 'Dressy Block Heels',
            'Sporty Slides', 'Travel Sandals',
        ],
    ],
    'Accessories' => [
        'count'   => 90,
        'price'   => [99, 999],
        'sizes'   => ['Free Size'],
        'fabrics' => ['Genuine Leather', 'Premium PU', 'Stainless Steel', 'Gold-Plated Alloy', 'Silk Blend', 'Cotton Canvas'],
        'fit'     => ['One Size'],
        'desc'    => [
            'A chic accessory that adds the perfect finishing touch to any outfit. Quality crafted.',
            'Durable and stylish, designed for everyday use. Great as a gifting option too.',
            'Premium finish with attention to detail. Complements both casual and formal looks.',
            'Lightweight, practical and fashionable. A must-have everyday companion.',
        ],
        'bases'   => [
            'Designer Handbag', 'Slim Wallet', 'Leather Belt', 'Aviator Sunglasses',
            'Round Sunglasses', 'Silk Scarf', 'Statement Necklace Set', 'Stud Earrings Set',
            'Cuff Bracelet', 'Analog Watch', 'Gold-Plated Chain', 'Hair Clutch Clips',
            'Cotton Tote Bag', 'Backpack', 'Laptop Sleeve Bag', 'Casual Cap',
            'Beanie Hat', 'Turban Style Cap', 'Waist Belt Chain', 'Anklet Set',
            'Leather Watch Strap', 'Crossbody Sling Bag', 'Clutch Evening Bag', 'Bangles Gift Set',
            'Pearl Necklace Set', 'Fashion Rings Set', 'Auto Fold Umbrella', 'Travel Spray Bottle',
            'Polarized Sunglasses', 'Travel Duffel Bag',
        ],
    ],
];

function es_slugify($s)
{
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function es_hex_luminance($hex)
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) == 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;
    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
}

function es_svg($path, $name, $color_name, $color_code)
{
    $fill = es_hex_luminance($color_code) > 0.6 ? '#111111' : '#ffffff';
    $words = preg_split('/\s+/', $name);
    $lines = [];
    $cur = '';
    foreach ($words as $w) {
        if (mb_strlen($cur . ' ' . $w) > 22 && $cur !== '') {
            $lines[] = $cur;
            $cur = $w;
        } else {
            $cur = trim($cur . ' ' . $w);
        }
        if (count($lines) >= 3) break;
    }
    if ($cur !== '' && count($lines) < 4) $lines[] = $cur;
    $lineStr = '';
    $y = 360;
    foreach (array_slice($lines, 0, 3) as $l) {
        $lineStr .= '<text x="300" y="' . $y . '" text-anchor="middle" font-family="Arial, sans-serif" font-size="30" fill="' . $fill . '" font-weight="bold">' . htmlspecialchars($l) . '</text>';
        $y += 42;
    }
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="750" viewBox="0 0 600 750">'
        . '<rect width="600" height="750" fill="' . $color_code . '"/>'
        . '<text x="300" y="140" text-anchor="middle" font-family="Arial, sans-serif" font-size="22" fill="' . $fill . '" letter-spacing="4">EASYSHOP</text>'
        . $lineStr
        . '<text x="300" y="560" text-anchor="middle" font-family="Arial, sans-serif" font-size="24" fill="' . $fill . '">' . htmlspecialchars($color_name) . '</text>'
        . '<rect x="230" y="600" width="140" height="6" rx="3" fill="' . $fill . '" opacity="0.5"/>'
        . '</svg>';
    file_put_contents($path, $svg);
}

$ins = mysqli_prepare($conn, "INSERT INTO products (category_id, name, slug, description, price, old_price, image, stock, status, featured, free_delivery, created_at) VALUES (?,?,?,?,?,?,?,?,1,?,?,?)");
$insCol = mysqli_prepare($conn, "INSERT INTO product_colors (product_id, color_name, color_code) VALUES (?,?,?)");
$insImg = mysqli_prepare($conn, "INSERT INTO product_images (product_id, color_id, image) VALUES (?,?,?)");
$insSize = mysqli_prepare($conn, "INSERT INTO product_sizes (product_id, size_name) VALUES (?,?)");

if (!$ins || !$insCol || !$insImg || !$insSize) {
    die('Prepare failed: ' . mysqli_error($conn));
}

$ts_start = time();
$total_inserted = 0;
$global_idx = 1;

foreach ($cats as $cat_name => $cfg) {
    $cq = mysqli_query($conn, "SELECT id FROM categories WHERE name = '" . mysqli_real_escape_string($conn, $cat_name) . "'");
    if (!$cq || mysqli_num_rows($cq) == 0) {
        echo "SKIP: category '{$cat_name}' not found" . PHP_EOL;
        continue;
    }
    $cat_id = (int) mysqli_fetch_assoc($cq)['id'];
    $nb = count($cfg['bases']);

    for ($i = 0; $i < $cfg['count']; $i++) {
        $base = $cfg['bases'][$i % $nb];
        $style = $styles[$i % count($styles)];
        $name = $base . ' - ' . $style;
        $slug = es_slugify($base) . '-' . es_slugify($style) . '-' . sprintf('%04d', $global_idx);

        $price = mt_rand($cfg['price'][0], $cfg['price'][1]);
        $old_price = round($price * (100 + mt_rand(15, 40)) / 100) - 1;
        if ($old_price <= $price) $old_price = $price + 50;

        $fabric = $cfg['fabrics'][array_rand($cfg['fabrics'])];
        $fit = $cfg['fit'][array_rand($cfg['fit'])];
        $sizes_note = implode(', ', array_slice($cfg['sizes'], 0, 3)) . (count($cfg['sizes']) > 3 ? ' and more' : '');
        $desc_tpl = $cfg['desc'][array_rand($cfg['desc'])];
        $description = ucfirst(strtolower($base)) . ' - ' . $style . '. ' . $desc_tpl . ' Fabric: ' . $fabric . '. Fit: ' . $fit . '. Available sizes: ' . $sizes_note . '. Care: gentle machine wash, avoid bleach, iron on low heat. Dispatch within 24 hours.';

        $stock = mt_rand(5, 120);
        $featured = mt_rand(1, 100) <= 15 ? 1 : 0;
        $free_delivery = mt_rand(1, 100) <= 80 ? 1 : 0;
        $created = date('Y-m-d H:i:s', mt_rand(strtotime('2024-01-01'), $ts_start));

        $n_colors = mt_rand(2, 4);
        $color_keys = array_rand($color_names, $n_colors);
        if (!is_array($color_keys)) $color_keys = [$color_keys];

        $first_svg = '';
        $img_rows = [];
        foreach ($color_keys as $ci => $ck) {
            $color_name = $color_names[$ck];
            $code = $palette[$color_name];
            $svg_name = 'p' . sprintf('%04d', $global_idx) . '_' . $ci . '.svg';
            es_svg($uploads_dir . '/' . $svg_name, $name, $color_name, $code);
            $rel = 'uploads/products/' . $svg_name;
            if ($ci === 0) $first_svg = $rel;
            $img_rows[] = [$color_name, $code, $rel];
        }

        mysqli_begin_transaction($conn);
        mysqli_stmt_bind_param($ins, 'isssddssiis', $cat_id, $name, $slug, $description, $price, $old_price, $first_svg, $stock, $featured, $free_delivery, $created);
        if (!mysqli_stmt_execute($ins)) {
            mysqli_rollback($conn);
            echo "INSERT FAIL: " . mysqli_stmt_error($ins) . " ({$name})" . PHP_EOL;
            $global_idx++;
            continue;
        }
        $pid = mysqli_insert_id($conn);

        foreach ($img_rows as $row) {
            mysqli_stmt_bind_param($insCol, 'iss', $pid, $row[0], $row[1]);
            mysqli_stmt_execute($insCol);
            $cid = mysqli_insert_id($conn);
            mysqli_stmt_bind_param($insImg, 'iis', $pid, $cid, $row[2]);
            mysqli_stmt_execute($insImg);
        }
        foreach ($cfg['sizes'] as $sz) {
            mysqli_stmt_bind_param($insSize, 'is', $pid, $sz);
            mysqli_stmt_execute($insSize);
        }
        mysqli_commit($conn);

        $total_inserted++;
        $global_idx++;
        if ($total_inserted % 100 === 0) {
            echo "Inserted {$total_inserted} products so far..." . PHP_EOL;
        }
    }
    echo "Done: {$cat_name} ({$cfg['count']} products)" . PHP_EOL;
}

echo PHP_EOL . "=== COMPLETE: {$total_inserted} products inserted ===" . PHP_EOL;
