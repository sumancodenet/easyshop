<?php require_once 'includes/config.php'; include 'includes/header.php';

$id = (int)($_GET['id'] ?? 0);
$q = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id=$id AND p.status=1");
$p = $q ? mysqli_fetch_assoc($q) : false;
if (!$p) { echo '<div class="container py-5 text-center"><h5>Product not found</h5><a href="index.php" class="btn-red mt-3">Back to Home</a></div>'; include 'includes/footer.php'; exit; }

$discount = $p['old_price'] > 0 ? round((1 - $p['price']/$p['old_price'])*100) : 0;
$colors_q = mysqli_query($conn, "SELECT * FROM product_colors WHERE product_id=$id ORDER BY id");
$colors = $colors_q ? mysqli_fetch_all($colors_q, MYSQLI_ASSOC) : [];

$imgs_q = mysqli_query($conn, "SELECT * FROM product_images WHERE product_id=$id ORDER BY color_id, id");
$all_imgs = [];
$color_imgs = [];
if ($imgs_q) {
  while ($row = mysqli_fetch_assoc($imgs_q)) {
    $all_imgs[] = $row;
    $color_imgs[$row['color_id']][] = $row;
  }
}

$related_q = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.category_id={$p['category_id']} AND p.id!=$id AND p.status=1 ORDER BY RAND() LIMIT 4");
$related = $related_q ? $related_q : false;

$first_color_id = count($colors) > 0 ? $colors[0]['id'] : 0;
$first_imgs = $color_imgs[$first_color_id] ?? [];

$wishlist = $_SESSION['wishlist'] ?? [];
$wishlist_set = array_flip($wishlist);

// Reviews
$rev_q = mysqli_query($conn, "SELECT r.*, u.name as user_name FROM reviews r LEFT JOIN users u ON r.user_id=u.id WHERE r.product_id=$id AND r.status=1 ORDER BY r.id DESC");
$reviews = $rev_q ? mysqli_fetch_all($rev_q, MYSQLI_ASSOC) : [];
$avg_q = mysqli_query($conn, "SELECT ROUND(AVG(rating),1) as avg, COUNT(*) as total FROM reviews WHERE product_id=$id AND status=1");
$avg_row = $avg_q ? mysqli_fetch_assoc($avg_q) : ['avg'=>0,'total'=>0];
$user_reviewed = false;
$user = $_SESSION['user'] ?? null;
if ($user) {
  foreach ($reviews as $rv) { if ($rv['user_id'] == $user['id']) { $user_reviewed = true; break; } }
}

// Default address pincode for auto-delivery check
$default_pincode = '';
if ($user) {
  $dp_q = mysqli_query($conn, "SELECT pincode FROM addresses WHERE user_id={$user['id']} AND is_default=1 AND pincode IS NOT NULL AND pincode!='' LIMIT 1");
  if ($dp_q && $dp_row = mysqli_fetch_assoc($dp_q)) {
    $default_pincode = $dp_row['pincode'];
  }
}

// Track product view
$session_id = session_id();
$uid_sql = $user ? $user['id'] : 'NULL';
mysqli_query($conn, "INSERT INTO product_views (user_id, session_id, product_id, category_id) VALUES ($uid_sql, '$session_id', $id, {$p['category_id']})");

// Handle review submission
$rev_error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review']) && $user) {
  $rating = (int)$_POST['rating'];
  $review = mysqli_real_escape_string($conn, $_POST['review']);
  if ($rating < 1 || $rating > 5) { $rev_error = 'Please select a rating'; }
  else {
    $uid = $user['id'];
    mysqli_query($conn, "INSERT INTO reviews (product_id, user_id, rating, review) VALUES ($id, $uid, $rating, '$review')");
    header("Location: product-detail.php?id=$id&review=1");
    exit;
  }
}
?>
<div class="container py-4">
  <nav style="font-size:13px;color:#999;margin-bottom:20px;">
    <a href="index.php" style="color:#999;text-decoration:none;">Home</a> /
    <a href="shop.php" style="color:#999;text-decoration:none;">Shop</a> /
    <a href="shop.php?category=<?php echo $p['category_id']; ?>" style="color:#999;text-decoration:none;"><?php echo $p['cat_name']; ?></a> /
    <span style="color:#333;"><?php echo $p['name']; ?></span>
  </nav>

  <div class="row g-4">
    <div class="col-md-5">
      <div style="background:#f5f5f5;border-radius:12px;aspect-ratio:1;display:flex;align-items:center;justify-content:center;overflow:hidden;border:1px solid #eee;margin-bottom:10px;position:relative;">
        <img id="mainProductImg" src="<?php echo count($first_imgs) > 0 ? $first_imgs[0]['image'] : ($p['image'] ?? ''); ?>" alt="<?php echo $p['name']; ?>" style="width:100%;height:100%;object-fit:cover;">
        <button class="wishlist-btn <?php echo isset($wishlist_set[$p['id']]) ? 'active' : ''; ?>" data-id="<?php echo $p['id']; ?>" onclick="toggleWishlist(<?php echo $p['id']; ?>, this)" style="position:absolute;top:12px;right:12px;z-index:3;width:38px;height:38px;border-radius:50%;border:none;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.15);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:19px;transition:0.2s;color:<?php echo isset($wishlist_set[$p['id']]) ? '#dc2626' : '#bbb'; ?>;"><i class="bi bi-heart<?php echo isset($wishlist_set[$p['id']]) ? '-fill' : ''; ?>"></i></button>
      </div>

      <!-- Thumbnail Gallery -->
      <div id="thumbGallery" style="display:flex;gap:6px;overflow-x:auto;padding-bottom:4px;">
        <?php if (count($all_imgs) > 0): ?>
          <?php foreach ($all_imgs as $img_row): ?>
            <img src="<?php echo $img_row['image']; ?>" data-color-id="<?php echo $img_row['color_id']; ?>" onclick="setMainImg(this.src)" style="width:60px;height:60px;object-fit:cover;border-radius:8px;border:2px solid #ddd;cursor:pointer;flex-shrink:0;transition:0.2s;<?php echo ($img_row === $all_imgs[0]) ? 'border-color:var(--red);' : ''; ?>">
          <?php endforeach; ?>
        <?php elseif ($p['image']): ?>
          <img src="<?php echo $p['image']; ?>" onclick="setMainImg(this.src)" style="width:60px;height:60px;object-fit:cover;border-radius:8px;border:2px solid var(--red);cursor:pointer;flex-shrink:0;">
        <?php endif; ?>
      </div>
    </div>
    <div class="col-md-7">
      <p style="font-size:12px;font-weight:600;color:var(--red);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;"><?php echo $p['cat_name']; ?></p>
      <h1 style="font-size:24px;font-weight:800;margin-bottom:8px;"><?php echo $p['name']; ?></h1>
      <div style="display:flex;align-items:baseline;gap:12px;margin-bottom:12px;">
        <span style="font-size:28px;font-weight:800;color:var(--red);">₹<?php echo number_format($p['price']); ?></span>
        <?php if ($p['old_price'] > 0): ?>
          <span style="font-size:16px;color:#999;text-decoration:line-through;">₹<?php echo number_format($p['old_price']); ?></span>
          <span style="font-size:13px;font-weight:700;color:#388E3C;"><?php echo $discount; ?>% off</span>
        <?php endif; ?>
      </div>
      <?php if ($p['free_delivery']): ?><p style="font-size:13px;color:#388E3C;margin-bottom:16px;"><i class="bi bi-truck"></i> Free Delivery</p><?php endif; ?>

      <?php if (count($colors) > 0): ?>
        <div style="margin-bottom:16px;">
          <p style="font-size:13px;font-weight:600;color:#555;margin-bottom:8px;">Color: <span id="selectedColor" style="color:var(--red);"><?php echo $colors[0]['color_name']; ?></span></p>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <?php foreach ($colors as $col): ?>
              <button type="button" class="color-swatch" data-color-id="<?php echo $col['id']; ?>" data-name="<?php echo $col['color_name']; ?>" style="width:36px;height:36px;border-radius:50%;background:<?php echo $col['color_code'] ?? '#ccc'; ?>;border:2px solid #ddd;cursor:pointer;outline:none;transition:0.2s;<?php echo $col === $colors[0] ? 'border-color:var(--red);box-shadow:0 0 0 2px var(--red);' : ''; ?>" onclick="selectColor(this)"></button>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($p['stock'] > 0): ?>
        <p style="font-size:13px;color:#059669;margin-bottom:16px;"><i class="bi bi-check-circle"></i> In Stock (<?php echo $p['stock']; ?> available)</p>
      <?php else: ?>
        <p style="font-size:13px;color:#dc2626;margin-bottom:16px;"><i class="bi bi-x-circle"></i> Out of Stock</p>
      <?php endif; ?>

      <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
        <div style="display:flex;align-items:center;border:1.5px solid #ddd;border-radius:50px;overflow:hidden;">
          <button onclick="qty(-1)" style="border:none;background:none;padding:8px 14px;cursor:pointer;font-size:16px;font-weight:600;">−</button>
          <span id="qty" style="padding:8px 14px;font-size:15px;font-weight:600;min-width:40px;text-align:center;">1</span>
          <button onclick="qty(1)" style="border:none;background:none;padding:8px 14px;cursor:pointer;font-size:16px;font-weight:600;">+</button>
        </div>
      </div>

      <!-- PIN Code Check -->
      <div class="pincode-check">
        <label style="font-size:13px;font-weight:600;color:#555;display:block;margin-bottom:6px;"><i class="bi bi-geo-alt"></i> Check Delivery Date</label>
        <div style="display:flex;gap:8px;">
          <input type="text" id="pincodeInput" class="form-control" placeholder="Enter PIN code" maxlength="6" style="width:160px;font-size:13px;text-transform:uppercase;" onkeydown="if(event.key==='Enter')checkPincode()">
          <button onclick="checkPincode()" class="btn-outline-red" style="padding:8px 18px;font-size:13px;white-space:nowrap;">Check</button>
        </div>
        <div id="pincodeResult" style="margin-top:8px;font-size:13px;"></div>
      </div>

      <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <button onclick="addToCart(<?php echo $p['id']; ?>)" class="btn-red" style="padding:12px 36px;font-size:15px;"><i class="bi bi-bag"></i> Add to Cart</button>
        <a href="checkout.php?buy_now=<?php echo $p['id']; ?>" class="btn-outline-red" style="padding:12px 36px;font-size:15px;text-decoration:none;"><i class="bi bi-lightning"></i> Buy Now</a>
      </div>

      <?php if ($p['description']): ?>
        <hr style="margin:24px 0;border-color:#eee;">
        <h6 style="font-size:14px;font-weight:700;margin-bottom:8px;">Description</h6>
        <p style="font-size:14px;color:#666;line-height:1.6;"><?php echo nl2br($p['description']); ?></p>
      <?php endif; ?>
    </div>
  </div>

  <!-- REVIEWS -->
  <hr style="margin:40px 0 24px;border-color:#eee;">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:20px;">
    <h5 style="font-size:18px;font-weight:700;margin:0;">Reviews <?php if ($avg_row['total'] > 0): ?><span style="font-weight:400;color:#999;font-size:14px;">(<?php echo $avg_row['total']; ?>)</span><?php endif; ?></h5>
    <?php if ($avg_row['total'] > 0): ?>
      <div style="display:flex;align-items:center;gap:6px;font-size:14px;">
        <span style="font-weight:700;"><?php echo $avg_row['avg']; ?></span>
        <div style="display:flex;gap:2px;">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <i class="bi bi-star<?php echo $i <= round($avg_row['avg']) ? '-fill' : ''; ?>" style="color:<?php echo $i <= round($avg_row['avg']) ? '#f59e0b' : '#ddd'; ?>;"></i>
          <?php endfor; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($rev_error): ?>
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px;margin-bottom:16px;color:#dc2626;font-size:13px;"><i class="bi bi-exclamation-circle"></i> <?php echo $rev_error; ?></div>
  <?php endif; ?>

  <!-- Review Form -->
  <?php if ($user && !$user_reviewed): ?>
    <div style="background:#f8f9fa;border:1px solid #eee;border-radius:12px;padding:20px;margin-bottom:20px;">
      <h6 style="font-size:14px;font-weight:700;margin-bottom:12px;">Write a Review</h6>
      <form method="POST">
        <div style="margin-bottom:10px;">
          <label style="font-size:13px;font-weight:600;color:#555;display:block;margin-bottom:6px;">Rating</label>
          <div class="star-rating" style="display:flex;gap:4px;flex-direction:row-reverse;justify-content:flex-end;">
            <?php for ($i = 5; $i >= 1; $i--): ?>
              <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" <?php echo $i == 5 ? 'checked' : ''; ?> style="display:none;">
              <label for="star<?php echo $i; ?>" style="cursor:pointer;font-size:24px;color:#ddd;transition:0.2s;" onclick="this.style.color='#f59e0b'; document.querySelectorAll('.star-rating label').forEach(function(l){l.style.color=l.htmlFor <= 'star'+document.querySelector('.star-rating input:checked').value ? '#f59e0b' : '#ddd';});">&#9733;</label>
            <?php endfor; ?>
          </div>
        </div>
        <div style="margin-bottom:12px;">
          <label style="font-size:13px;font-weight:600;color:#555;display:block;margin-bottom:6px;">Review</label>
          <textarea name="review" class="form-control" rows="3" placeholder="Share your experience..." style="font-size:13px;"></textarea>
        </div>
        <button type="submit" name="submit_review" class="btn-red" style="padding:8px 24px;font-size:13px;">Submit Review</button>
      </form>
    </div>
  <?php elseif (!$user): ?>
    <div style="background:#f8f9fa;border:1px solid #eee;border-radius:12px;padding:16px;margin-bottom:20px;text-align:center;font-size:13px;color:#666;">
      <a href="login.php" style="color:var(--red);font-weight:600;">Sign in</a> to write a review
    </div>
  <?php endif; ?>

  <!-- Review List -->
  <?php if (count($reviews) > 0): ?>
    <div style="display:flex;flex-direction:column;gap:14px;margin-bottom:24px;">
      <?php foreach ($reviews as $rv): ?>
        <div style="border-bottom:1px solid #f0f0f0;padding-bottom:14px;">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
            <div style="display:flex;align-items:center;gap:8px;">
              <div style="width:32px;height:32px;border-radius:50%;background:var(--red);color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;text-transform:uppercase;"><?php echo strtoupper(substr($rv['user_name'] ?? 'A', 0, 1)); ?></div>
              <div>
                <div style="font-size:13px;font-weight:600;"><?php echo $rv['user_name'] ?? 'Anonymous'; ?></div>
                <div style="font-size:11px;color:#999;"><?php echo date('d M Y', strtotime($rv['created_at'])); ?></div>
              </div>
            </div>
            <div style="display:flex;gap:2px;">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="bi bi-star<?php echo $i <= $rv['rating'] ? '-fill' : ''; ?>" style="color:<?php echo $i <= $rv['rating'] ? '#f59e0b' : '#ddd'; ?>;font-size:13px;"></i>
              <?php endfor; ?>
            </div>
          </div>
          <?php if ($rv['review']): ?>
            <p style="font-size:13px;color:#555;margin:6px 0 0 40px;line-height:1.5;"><?php echo nl2br(htmlspecialchars($rv['review'])); ?></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div style="text-align:center;padding:20px;color:#999;font-size:13px;margin-bottom:16px;">No reviews yet. Be the first to review!</div>
  <?php endif; ?>

  <?php if ($related && mysqli_num_rows($related) > 0): ?>
  <hr style="margin:24px 0;border-color:#eee;">
  <h5 style="font-size:18px;font-weight:700;margin-bottom:16px;">You May Also Like</h5>
  <div class="row g-3">
    <?php while ($rp = mysqli_fetch_assoc($related)):
      $rd = $rp['old_price'] > 0 ? round((1 - $rp['price']/$rp['old_price'])*100) : 0;
      $r_img = $rp['image'];
      $r_c_q = mysqli_query($conn, "SELECT pi.image FROM product_images pi WHERE pi.product_id={$rp['id']} LIMIT 1");
      if ($r_c_q && $r_img_row = mysqli_fetch_assoc($r_c_q)) { $r_img = $r_img_row['image']; }
    ?>
      <div class="col-6 col-md-3">
        <a href="product-detail.php?id=<?php echo $rp['id']; ?>" class="product-card">
          <div class="img-wrap">
            <?php if ($rd > 0): ?><span class="badge-top"><?php echo $rd; ?>% OFF</span><?php endif; ?>
            <?php if ($r_img): ?>
              <img src="<?php echo $r_img; ?>" alt="<?php echo $rp['name']; ?>" style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
              <i class="bi bi-handbag"></i>
            <?php endif; ?>
          </div>
          <div class="body">
            <div class="cat"><?php echo $rp['cat_name']; ?></div>
            <div class="title"><?php echo $rp['name']; ?></div>
            <div class="price">₹<?php echo number_format($rp['price']); ?>
              <?php if ($rp['old_price'] > 0): ?>
                <span class="old">₹<?php echo number_format($rp['old_price']); ?></span>
                <span class="off"><?php echo $rd; ?>% off</span>
              <?php endif; ?>
            </div>
          </div>
        </a>
      </div>
    <?php endwhile; ?>
  </div>
  <?php endif; ?>
</div>

<script>
var colorImages = {};
<?php foreach ($color_imgs as $cid => $imgs): ?>
  colorImages[<?php echo $cid; ?>] = <?php echo json_encode(array_map(function($i){return $i['image'];}, $imgs)); ?>;
<?php endforeach; ?>
var allImages = <?php echo json_encode(array_map(function($i){return ['src'=>$i['image'], 'color_id'=>$i['color_id']];}, $all_imgs)); ?>;
var defaultImg = '<?php echo $p['image'] ?? ''; ?>';

function selectColor(el) {
  document.querySelectorAll('.color-swatch').forEach(function(s) { s.style.borderColor = '#ddd'; s.style.boxShadow = 'none'; });
  el.style.borderColor = 'var(--red)';
  el.style.boxShadow = '0 0 0 2px var(--red)';
  document.getElementById('selectedColor').textContent = el.getAttribute('data-name');
  var cid = parseInt(el.getAttribute('data-color-id'));
  var imgs = colorImages[cid] || [];
  var gallery = document.getElementById('thumbGallery');
  gallery.innerHTML = '';
  if (imgs.length > 0) {
    imgs.forEach(function(src, idx) {
      var t = document.createElement('img');
      t.src = src;
      t.onclick = function(){ setMainImg(this.src); };
      t.style.cssText = 'width:60px;height:60px;object-fit:cover;border-radius:8px;border:2px solid #ddd;cursor:pointer;flex-shrink:0;transition:0.2s;' + (idx === 0 ? 'border-color:var(--red);' : '');
      gallery.appendChild(t);
    });
    setMainImg(imgs[0]);
  } else if (defaultImg) {
    var t = document.createElement('img');
    t.src = defaultImg;
    t.onclick = function(){ setMainImg(this.src); };
    t.style.cssText = 'width:60px;height:60px;object-fit:cover;border-radius:8px;border:2px solid var(--red);cursor:pointer;flex-shrink:0;';
    gallery.appendChild(t);
    setMainImg(defaultImg);
  }
}

function setMainImg(src) {
  document.getElementById('mainProductImg').src = src;
  document.querySelectorAll('#thumbGallery img').forEach(function(t) { t.style.borderColor = '#ddd'; });
  document.querySelectorAll('#thumbGallery img').forEach(function(t) { if (t.src === src) t.style.borderColor = 'var(--red)'; });
}

function qty(d) {
  var el = document.getElementById('qty');
  var v = parseInt(el.textContent) + d;
  if (v < 1) v = 1;
  el.textContent = v;
}

function addToCart(id) {
  var qty = document.getElementById('qty').textContent;
  var x = new XMLHttpRequest();
  x.open('POST', 'cart_ajax.php', true);
  x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  x.onload = function() {
    var r = JSON.parse(this.responseText);
    if (r.success) {
      var navCount = document.querySelector('.nav-cart-count');
      if (navCount) navCount.textContent = r.total_items;
      showToast('success', 'Added to Cart!', '');
    }
  };
  x.send('id=' + id + '&qty=' + qty);
}

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

var defaultPincode = '<?php echo $default_pincode; ?>';
if (defaultPincode) {
  document.getElementById('pincodeInput').value = defaultPincode;
  document.addEventListener('DOMContentLoaded', function() { checkPincode(); });
}

function checkPincode() {
  var pincode = document.getElementById('pincodeInput').value.trim();
  var result = document.getElementById('pincodeResult');
  if (!/^\d{6}$/.test(pincode)) {
    result.innerHTML = '<span style="color:#dc2626;"><i class="bi bi-exclamation-circle"></i> Enter a valid 6-digit PIN code</span>';
    return;
  }
  result.innerHTML = '<span style="color:#999;"><i class="bi bi-hourglass-split"></i> Checking...</span>';
  var x = new XMLHttpRequest();
  x.open('POST', 'pincode_ajax.php', true);
  x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  x.onload = function() {
    var r = JSON.parse(this.responseText);
    if (r.success) {
      var codHtml = r.cod_available ? '<span style="color:#059669;"><i class="bi bi-cash"></i> COD Available</span>' : '<span style="color:#dc2626;"><i class="bi bi-x-circle"></i> COD Not Available</span>';
      result.innerHTML = '<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;">' +
        '<div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;"><i class="bi bi-check-circle" style="color:#16a34a;"></i> <span style="font-weight:600;color:#16a34a;">Delivery available!</span></div>' +
        '<div style="font-size:12px;color:#555;">Estimated delivery by <strong>' + r.estimated_date + '</strong></div>' +
        '<div style="font-size:12px;color:#555;margin-top:2px;">' + codHtml + '</div></div>';
    } else {
      result.innerHTML = '<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;color:#dc2626;font-size:12px;"><i class="bi bi-x-circle"></i> ' + r.message + '</div>';
    }
  };
  x.send('pincode=' + pincode);
}
</script>

<?php include 'includes/footer.php'; ?>
