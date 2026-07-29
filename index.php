<?php require_once 'includes/config.php'; include 'includes/header.php'; 

// Fetch active banners
$banner_list = [];
$banner_q = mysqli_query($conn, "SELECT * FROM banners WHERE status=1 ORDER BY sort_order ASC, id ASC");
if ($banner_q) { while ($b = mysqli_fetch_assoc($banner_q)) { $banner_list[] = $b; } }

$cats_q = mysqli_query($conn, "SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id=c.id AND p.status=1) as pcount FROM categories c WHERE c.status=1 ORDER BY c.id");
$cats = $cats_q ? $cats_q : false;

$featured_q = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.status=1 AND p.featured=1 ORDER BY RAND() LIMIT 6");
$featured = $featured_q ? $featured_q : false;

$all_q = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.status=1 ORDER BY RAND()");
$all = $all_q ? $all_q : false;

// Group products by category
$by_cat = [];
if ($all) {
  while ($p = mysqli_fetch_assoc($all)) {
    $by_cat[$p['cat_name'] ?? 'Other'][] = $p;
  }
}

// Fetch all colors
$all_colors = [];
$cols_q = mysqli_query($conn, "SELECT * FROM product_colors ORDER BY product_id, id");
if ($cols_q) {
  while ($col = mysqli_fetch_assoc($cols_q)) {
    $all_colors[$col['product_id']][] = $col;
  }
}

// Fetch first product_images image per product
$first_imgs = [];
$fi_q = mysqli_query($conn, "SELECT pi.product_id, MIN(pi.id) as mid FROM product_images pi GROUP BY pi.product_id");
if ($fi_q) {
  $first_img_ids = [];
  while ($row = mysqli_fetch_assoc($fi_q)) {
    $first_img_ids[] = (int)$row['mid'];
  }
  if (count($first_img_ids) > 0) {
    $fi_ids = implode(',', $first_img_ids);
    $fi2_q = mysqli_query($conn, "SELECT product_id, image FROM product_images WHERE id IN ($fi_ids)");
    if ($fi2_q) {
      while ($row = mysqli_fetch_assoc($fi2_q)) {
        $first_imgs[$row['product_id']] = $row['image'];
      }
    }
  }
}

$user = $_SESSION['user'] ?? null;

// Recommended products based on browsing history
$recommended = false;
$rec_title = 'Recommended for You';
$user_sid = session_id();
$uid_cond = is_array($user) ? "pv.user_id={$user['id']}" : "pv.session_id='$user_sid'";
$top_cat = mysqli_query($conn, "SELECT pv.category_id, c.name as cat_name, COUNT(*) as views FROM product_views pv LEFT JOIN categories c ON pv.category_id=c.id WHERE $uid_cond AND pv.category_id IS NOT NULL GROUP BY pv.category_id ORDER BY views DESC LIMIT 1");
if ($top_cat && $tc_row = mysqli_fetch_assoc($top_cat)) {
  $tc_id = (int)$tc_row['category_id'];
  $rec_title = 'More ' . $tc_row['cat_name'];
  $rec_q = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.category_id=$tc_id AND p.status=1 ORDER BY RAND() LIMIT 4");
  if ($rec_q && mysqli_num_rows($rec_q) > 0) $recommended = $rec_q;
}
if (!$recommended) {
  $rec_q = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.status=1 ORDER BY RAND() LIMIT 4");
  if ($rec_q && mysqli_num_rows($rec_q) > 0) $recommended = $rec_q;
}

$wishlist = $_SESSION['wishlist'] ?? [];
$wishlist_set = array_flip($wishlist);

$ratings = [];
$r_q = mysqli_query($conn, "SELECT product_id, ROUND(AVG(rating),1) as avg, COUNT(*) as total FROM reviews WHERE status=1 GROUP BY product_id");
if ($r_q) { while ($r = mysqli_fetch_assoc($r_q)) { $ratings[$r['product_id']] = $r; } }
?>
<!-- HERO CAROUSEL -->
<section class="hero-carousel">
  <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="5000">
    <div class="carousel-indicators">
      <?php foreach ($banner_list as $bi => $b): ?>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?php echo $bi; ?>" class="<?php echo $bi === 0 ? 'active' : ''; ?>"></button>
      <?php endforeach; ?>
    </div>
    <div class="carousel-inner">
      <?php
      $gradients = [
        'linear-gradient(135deg, var(--red-dark) 0%, var(--red) 50%, #b3442e 100%)',
        'linear-gradient(135deg, #1a1a2e 0%, var(--red-dark) 50%, var(--red) 100%)',
        'linear-gradient(135deg, #0a1628 0%, #1a1a2e 50%, var(--red-dark) 100%)',
        'linear-gradient(135deg, var(--red) 0%, var(--red-dark) 50%, #1a1a2e 100%)',
        'linear-gradient(135deg, #2d1a1a 0%, var(--red-dark) 50%, #1a1a2e 100%)',
      ];
      $icons = ['bi-magic', 'bi-lightning-fill', 'bi-truck', 'bi-star-fill', 'bi-fire'];
      foreach ($banner_list as $gi => $b):
        $img = $b['image'] && file_exists($b['image']) ? $b['image'] : '';
        $grad = $gradients[$gi % count($gradients)];
        $icon = $icons[$gi % count($icons)];
      ?>
        <div class="carousel-item <?php echo $gi === 0 ? 'active' : ''; ?>">
          <?php if ($img): ?>
            <div class="slide-bg-img" style="background-image:url('<?php echo $img; ?>');">
              <div class="slide-overlay"></div>
            </div>
          <?php else: ?>
            <div class="slide-bg" style="background: <?php echo $grad; ?>;">
              <div class="bg-pattern"></div>
            </div>
          <?php endif; ?>
          <div class="container">
            <div class="row">
              <div class="col-lg-7">
                <div class="slide-content">
                  <div class="slide-badge"><i class="bi <?php echo $icon; ?>"></i> <?php echo htmlspecialchars($b['title'] ?? 'New Collection'); ?></div>
                  <h1><?php echo nl2br(htmlspecialchars($b['title'] ?? '')); ?><span class="highlight"><?php echo htmlspecialchars($b['subtitle'] ?? ''); ?></span></h1>
                  <?php $desc = trim($b['description'] ?? ''); if ($desc): ?><p><?php echo htmlspecialchars($desc); ?></p><?php endif; ?>
                  <div class="d-flex gap-3 flex-wrap">
                    <a href="<?php echo htmlspecialchars($b['link'] ?? 'shop.php'); ?>" class="btn-white"><i class="bi bi-bag"></i> Shop Now</a>
                    <a href="#categories" class="btn-outline-white">Explore <i class="bi bi-arrow-right"></i></a>
                  </div>
                </div>
              </div>
              <div class="col-lg-5">
                <div class="slide-visual">
                  <div class="card-simple">
                    <?php if ($img): ?>
                      <div class="card-thumb"><img src="<?php echo $img; ?>" alt=""></div>
                      <div class="card-value"><?php echo htmlspecialchars($b['title'] ?? 'Featured'); ?></div>
                      <div class="card-label">Shop the latest trends</div>
                    <?php else: ?>
                      <i class="bi <?php echo $icon; ?> big-icon"></i>
                      <div class="card-value">New Arrivals</div>
                      <div class="card-label">Check out our latest collection</div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if (count($banner_list) > 1): ?>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
      <i class="bi bi-chevron-left"></i>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
      <i class="bi bi-chevron-right"></i>
    </button>
    <?php endif; ?>
  </div>
</section>

<!-- CATEGORIES -->
<section class="section" id="categories">
  <div class="container">
    <div class="section-title d-flex align-items-center justify-content-between">
      <h3>Shop by Category</h3>
      <a href="shop.php" class="see-all">View All <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
    <div class="row g-3">
      <?php if ($cats): while ($c = mysqli_fetch_assoc($cats)):
        $cimg = $c['image'] && file_exists($c['image']) ? $c['image'] : '';
      ?>
        <div class="col-6 col-md-4 col-lg-2">
          <a href="shop.php?category=<?php echo $c['id']; ?>" class="cat-card">
            <?php if ($cimg): ?>
              <div class="cat-img"><img src="<?php echo $cimg; ?>" alt="<?php echo $c['name']; ?>"></div>
            <?php else: ?>
              <div class="icon"><i class="bi bi-grid"></i></div>
            <?php endif; ?>
            <h6><?php echo $c['name']; ?></h6>
            <small><?php echo $c['pcount']; ?> items</small>
          </a>
        </div>
      <?php endwhile; endif; ?>
    </div>
  </div>
</section>

<!-- BANNER -->
<section class="pb-4">
  <div class="container">
    <div class="row g-3">
      <div class="col-md-6">
        <div class="banner-card" style="background: linear-gradient(135deg, var(--red-dark), var(--red)); color: #fff;">
          <h4>Fashion Mega Sale!</h4>
          <p>Up to 70% off on all dresses & clothing. Limited period offer.</p>
          <a href="shop.php" class="btn btn-light fw-bold rounded-pill px-4 py-2 align-self-start" style="font-size: 14px;">Shop Now <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
      </div>
      <div class="col-md-6">
        <div class="banner-card" style="background: #f8f9fa; border: 1px solid #e5e5e5;">
          <h4 style="color: var(--dark);">Free Delivery</h4>
          <p style="color: var(--gray4);">On orders above ₹499. Easy returns & size exchange.</p>
          <a href="shop.php" class="btn btn-red rounded-pill px-4 py-2 align-self-start" style="font-size: 14px;">Explore <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FEATURED PRODUCTS -->
<section class="section" id="featured">
  <div class="container">
    <div class="section-title d-flex align-items-center justify-content-between">
      <h3>Featured Products</h3>
      <a href="shop.php" class="see-all">View All <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
    <div class="row g-3">
      <?php if ($featured && mysqli_num_rows($featured) > 0):
        while ($p = mysqli_fetch_assoc($featured)):
          $discount = $p['old_price'] > 0 ? round((1 - $p['price']/$p['old_price'])*100) : 0;
      ?>
        <div class="col-6 col-md-4 col-lg-4">
          <a href="product-detail.php?id=<?php echo $p['id']; ?>" class="product-card">
            <div class="img-wrap">
              <?php if ($discount > 0): ?><span class="badge-top"><?php echo $discount; ?>% OFF</span><?php endif; ?>
              <button class="wishlist-btn <?php echo isset($wishlist_set[$p['id']]) ? 'active' : ''; ?>" data-id="<?php echo $p['id']; ?>" onclick="event.preventDefault(); toggleWishlist(<?php echo $p['id']; ?>, this)" style="position:absolute;top:8px;right:8px;z-index:2;width:34px;height:34px;border-radius:50%;border:none;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.12);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:17px;transition:0.2s;color:<?php echo isset($wishlist_set[$p['id']]) ? '#dc2626' : '#bbb'; ?>;"><i class="bi bi-heart<?php echo isset($wishlist_set[$p['id']]) ? '-fill' : ''; ?>"></i></button>
              <?php $p_img = $first_imgs[$p['id']] ?? $p['image']; ?>
              <?php if ($p_img): ?>
                <img src="<?php echo $p_img; ?>" alt="<?php echo $p['name']; ?>" style="width:100%;height:100%;object-fit:cover;">
              <?php else: ?>
                <i class="bi bi-handbag"></i>
              <?php endif; ?>
            </div>
            <div class="body">
              <div class="cat"><?php echo $p['cat_name']; ?></div>
              <div class="title"><?php echo $p['name']; ?></div>
              <?php if (isset($ratings[$p['id']])): ?>
                <div class="rating">
                  <span class="green"><?php echo $ratings[$p['id']]['avg']; ?><i class="bi bi-star-fill" style="font-size:10px;margin-left:2px;"></i></span>
                  <span style="color:#999;">(<?php echo $ratings[$p['id']]['total']; ?>)</span>
                </div>
              <?php endif; ?>
              <div class="price">₹<?php echo number_format($p['price']); ?>
                <?php if ($p['old_price'] > 0): ?>
                  <span class="old">₹<?php echo number_format($p['old_price']); ?></span>
                  <span class="off"><?php echo $discount; ?>% off</span>
                <?php endif; ?>
              </div>
              <?php if (!empty($all_colors[$p['id']])): ?>
                <div style="display:flex;gap:3px;margin:6px 0;">
                  <?php foreach (array_slice($all_colors[$p['id']], 0, 4) as $col): ?>
                    <span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:<?php echo $col['color_code'] ?? '#ccc'; ?>;border:1px solid #ddd;"></span>
                  <?php endforeach; ?>
                  <?php if (count($all_colors[$p['id']]) > 4): ?>
                    <span style="font-size:10px;color:#999;line-height:14px;">+<?php echo count($all_colors[$p['id']]) - 4; ?></span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <?php if ($p['free_delivery']): ?><div class="delivery"><i class="bi bi-truck"></i> Free Delivery</div><?php endif; ?>
            </div>
          </a>
        </div>
      <?php endwhile; else: ?>
        <div class="col-12 text-center py-4 text-muted">No featured products yet</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- RECOMMENDED FOR YOU -->
<?php if ($recommended && mysqli_num_rows($recommended) > 0): ?>
<section class="section">
  <div class="container">
    <div class="section-title d-flex align-items-center justify-content-between">
      <h3><?php echo $rec_title; ?></h3>
      <a href="shop.php" class="see-all">View All <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
    <div class="row g-3">
      <?php while ($p = mysqli_fetch_assoc($recommended)):
        $discount = $p['old_price'] > 0 ? round((1 - $p['price']/$p['old_price'])*100) : 0;
      ?>
        <div class="col-6 col-md-3">
          <a href="product-detail.php?id=<?php echo $p['id']; ?>" class="product-card">
            <div class="img-wrap">
              <?php if ($discount > 0): ?><span class="badge-top"><?php echo $discount; ?>% OFF</span><?php endif; ?>
              <button class="wishlist-btn <?php echo isset($wishlist_set[$p['id']]) ? 'active' : ''; ?>" data-id="<?php echo $p['id']; ?>" onclick="event.preventDefault(); toggleWishlist(<?php echo $p['id']; ?>, this)" style="position:absolute;top:8px;right:8px;z-index:2;width:34px;height:34px;border-radius:50%;border:none;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.12);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:17px;transition:0.2s;color:<?php echo isset($wishlist_set[$p['id']]) ? '#dc2626' : '#bbb'; ?>;"><i class="bi bi-heart<?php echo isset($wishlist_set[$p['id']]) ? '-fill' : ''; ?>"></i></button>
              <?php $p_img = $first_imgs[$p['id']] ?? $p['image']; ?>
              <?php if ($p_img): ?>
                <img src="<?php echo $p_img; ?>" alt="<?php echo $p['name']; ?>" style="width:100%;height:100%;object-fit:cover;">
              <?php else: ?>
                <i class="bi bi-handbag"></i>
              <?php endif; ?>
            </div>
            <div class="body">
              <div class="cat"><?php echo $p['cat_name']; ?></div>
              <div class="title"><?php echo $p['name']; ?></div>
              <?php if (isset($ratings[$p['id']])): ?>
                <div class="rating">
                  <span class="green"><?php echo $ratings[$p['id']]['avg']; ?><i class="bi bi-star-fill" style="font-size:10px;margin-left:2px;"></i></span>
                  <span style="color:#999;">(<?php echo $ratings[$p['id']]['total']; ?>)</span>
                </div>
              <?php endif; ?>
              <div class="price">₹<?php echo number_format($p['price']); ?>
                <?php if ($p['old_price'] > 0): ?>
                  <span class="old">₹<?php echo number_format($p['old_price']); ?></span>
                  <span class="off"><?php echo $discount; ?>% off</span>
                <?php endif; ?>
              </div>
              <?php if (!empty($all_colors[$p['id']])): ?>
                <div style="display:flex;gap:3px;margin:6px 0;">
                  <?php foreach (array_slice($all_colors[$p['id']], 0, 4) as $col): ?>
                    <span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:<?php echo $col['color_code'] ?? '#ccc'; ?>;border:1px solid #ddd;"></span>
                  <?php endforeach; ?>
                  <?php if (count($all_colors[$p['id']]) > 4): ?>
                    <span style="font-size:10px;color:#999;line-height:14px;">+<?php echo count($all_colors[$p['id']]) - 4; ?></span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <?php if ($p['free_delivery']): ?><div class="delivery"><i class="bi bi-truck"></i> Free Delivery</div><?php endif; ?>
            </div>
          </a>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- PRODUCTS BY CATEGORY -->
<?php foreach ($by_cat as $cat_name => $products): ?>
<?php if (count($products) == 0) continue; ?>
<section class="section pt-0">
  <div class="container">
    <div class="section-title d-flex align-items-center justify-content-between">
      <h3><?php echo $cat_name; ?></h3>
      <a href="shop.php?category=<?php echo $products[0]['category_id']; ?>" class="see-all">View All <i class="bi bi-arrow-right ms-1"></i></a>
    </div>
    <div class="row g-3">
      <?php foreach (array_slice($products, 0, 4) as $p):
        $discount = $p['old_price'] > 0 ? round((1 - $p['price']/$p['old_price'])*100) : 0;
      ?>
        <div class="col-6 col-md-4 col-lg-3">
          <a href="product-detail.php?id=<?php echo $p['id']; ?>" class="product-card">
            <div class="img-wrap">
              <?php if ($discount > 0): ?><span class="badge-top"><?php echo $discount; ?>% OFF</span><?php endif; ?>
              <button class="wishlist-btn <?php echo isset($wishlist_set[$p['id']]) ? 'active' : ''; ?>" data-id="<?php echo $p['id']; ?>" onclick="event.preventDefault(); toggleWishlist(<?php echo $p['id']; ?>, this)" style="position:absolute;top:8px;right:8px;z-index:2;width:34px;height:34px;border-radius:50%;border:none;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.12);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:17px;transition:0.2s;color:<?php echo isset($wishlist_set[$p['id']]) ? '#dc2626' : '#bbb'; ?>;"><i class="bi bi-heart<?php echo isset($wishlist_set[$p['id']]) ? '-fill' : ''; ?>"></i></button>
              <?php $p_img2 = $first_imgs[$p['id']] ?? $p['image']; ?>
              <?php if ($p_img2): ?>
                <img src="<?php echo $p_img2; ?>" alt="<?php echo $p['name']; ?>" style="width:100%;height:100%;object-fit:cover;">
              <?php else: ?>
                <i class="bi bi-handbag"></i>
              <?php endif; ?>
            </div>
            <div class="body">
              <div class="cat"><?php echo $p['cat_name']; ?></div>
              <div class="title"><?php echo $p['name']; ?></div>
              <?php if (isset($ratings[$p['id']])): ?>
                <div class="rating">
                  <span class="green"><?php echo $ratings[$p['id']]['avg']; ?><i class="bi bi-star-fill" style="font-size:10px;margin-left:2px;"></i></span>
                  <span style="color:#999;">(<?php echo $ratings[$p['id']]['total']; ?>)</span>
                </div>
              <?php endif; ?>
              <div class="price">₹<?php echo number_format($p['price']); ?>
                <?php if ($p['old_price'] > 0): ?>
                  <span class="old">₹<?php echo number_format($p['old_price']); ?></span>
                  <span class="off"><?php echo $discount; ?>% off</span>
                <?php endif; ?>
              </div>
              <?php if (!empty($all_colors[$p['id']])): ?>
                <div style="display:flex;gap:3px;margin:6px 0;">
                  <?php foreach (array_slice($all_colors[$p['id']], 0, 4) as $col): ?>
                    <span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:<?php echo $col['color_code'] ?? '#ccc'; ?>;border:1px solid #ddd;"></span>
                  <?php endforeach; ?>
                  <?php if (count($all_colors[$p['id']]) > 4): ?>
                    <span style="font-size:10px;color:#999;line-height:14px;">+<?php echo count($all_colors[$p['id']]) - 4; ?></span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
              <?php if ($p['free_delivery']): ?><div class="delivery"><i class="bi bi-truck"></i> Free Delivery</div><?php endif; ?>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endforeach; ?>

<script>
function toggleWishlist(id, btn) {
  var x = new XMLHttpRequest();
  x.open('POST', 'wishlist_ajax.php', true);
  x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  x.onload = function() {
    var r = JSON.parse(this.responseText);
    if (r.action === 'added') {
      btn.style.color = '#dc2626';
      btn.querySelector('i').className = 'bi bi-heart-fill';
      btn.classList.add('active');
      showToast('success', 'Added to Wishlist!', '');
    } else if (r.action === 'removed') {
      btn.style.color = '#bbb';
      btn.querySelector('i').className = 'bi bi-heart';
      btn.classList.remove('active');
      showToast('info', 'Removed from Wishlist', '');
    }
  };
  x.send('id=' + id);
}
</script>

<?php include 'includes/footer.php'; ?>
