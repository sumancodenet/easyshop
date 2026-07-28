<?php require_once 'includes/config.php'; include 'includes/header.php';

$cat_filter = (int)($_GET['category'] ?? 0);
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$where = "WHERE p.status=1";
if ($cat_filter) $where .= " AND p.category_id=$cat_filter";
if ($search) $where .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%')";

$q = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id $where ORDER BY p.id DESC");
$products = $q ? $q : false;

$cats_q = mysqli_query($conn, "SELECT * FROM categories WHERE status=1 ORDER BY name");
$cats = $cats_q ? $cats_q : false;

$wishlist = $_SESSION['wishlist'] ?? [];
$wishlist_set = array_flip($wishlist);

$ratings = [];
$r_q = mysqli_query($conn, "SELECT product_id, ROUND(AVG(rating),1) as avg, COUNT(*) as total FROM reviews WHERE status=1 GROUP BY product_id");
if ($r_q) { while ($r = mysqli_fetch_assoc($r_q)) { $ratings[$r['product_id']] = $r; } }

$all_colors = [];
$cols_q = mysqli_query($conn, "SELECT * FROM product_colors ORDER BY product_id, id");
if ($cols_q) {
  while ($col = mysqli_fetch_assoc($cols_q)) {
    $all_colors[$col['product_id']][] = $col;
  }
}
?>
<div class="container py-4">
  <div class="row g-4">
    <div class="col-md-3">
      <div style="background:#f8f9fa;border-radius:12px;padding:20px;border:1px solid #eee;">
        <h6 style="font-weight:700;margin-bottom:14px;font-size:14px;">Categories</h6>
        <div style="display:flex;flex-direction:column;gap:6px;">
          <a href="shop.php" style="text-decoration:none;font-size:13px;color:<?php echo !$cat_filter ? 'var(--red)' : '#555'; ?>;font-weight:<?php echo !$cat_filter ? '700' : '400'; ?>;padding:6px 10px;border-radius:6px;background:<?php echo !$cat_filter ? '#fff5f5' : 'transparent'; ?>;">All Products</a>
          <?php if ($cats): while ($c = mysqli_fetch_assoc($cats)): ?>
            <a href="shop.php?category=<?php echo $c['id']; ?>" style="text-decoration:none;font-size:13px;color:<?php echo $cat_filter == $c['id'] ? 'var(--red)' : '#555'; ?>;font-weight:<?php echo $cat_filter == $c['id'] ? '700' : '400'; ?>;padding:6px 10px;border-radius:6px;background:<?php echo $cat_filter == $c['id'] ? '#fff5f5' : 'transparent'; ?>;"><?php echo $c['name']; ?></a>
          <?php endwhile; endif; ?>
        </div>
      </div>
    </div>
    <div class="col-md-9">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:8px;">
        <h5 style="font-weight:800;margin:0;font-size:18px;">
          <?php echo $search ? "Search: \"$search\"" : ($cat_filter ? "Category" : "All Products"); ?>
          <span style="font-weight:400;color:#999;font-size:14px;">(<?php echo $products ? mysqli_num_rows($products) : 0; ?> items)</span>
        </h5>
      </div>

      <?php if ($products && mysqli_num_rows($products) > 0): ?>
        <div class="row g-3">
          <?php while ($p = mysqli_fetch_assoc($products)):
            $discount = $p['old_price'] > 0 ? round((1 - $p['price']/$p['old_price'])*100) : 0;
          ?>
            <div class="col-6 col-md-4">
              <a href="product-detail.php?id=<?php echo $p['id']; ?>" class="product-card">
                <div class="img-wrap">
                  <?php if ($discount > 0): ?><span class="badge-top"><?php echo $discount; ?>% OFF</span><?php endif; ?>
                  <button class="wishlist-btn <?php echo isset($wishlist_set[$p['id']]) ? 'active' : ''; ?>" data-id="<?php echo $p['id']; ?>" onclick="event.preventDefault(); toggleWishlist(<?php echo $p['id']; ?>, this)" style="position:absolute;top:8px;right:8px;z-index:2;width:34px;height:34px;border-radius:50%;border:none;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.12);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:17px;transition:0.2s;color:<?php echo isset($wishlist_set[$p['id']]) ? '#dc2626' : '#bbb'; ?>;"><i class="bi bi-heart<?php echo isset($wishlist_set[$p['id']]) ? '-fill' : ''; ?>"></i></button>
                  <?php if ($p['image']): ?>
                    <img src="<?php echo $p['image']; ?>" alt="<?php echo $p['name']; ?>" style="width:100%;height:100%;object-fit:cover;">
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
      <?php else: ?>
        <div style="text-align:center;padding:60px 20px;">
          <i class="bi bi-search" style="font-size:50px;color:#ddd;"></i>
          <h5 style="margin-top:16px;font-weight:600;">No products found</h5>
          <p style="color:#999;">Try a different category or search term.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

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
