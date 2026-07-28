<?php require_once 'includes/config.php'; include 'includes/header.php';

$wishlist = $_SESSION['wishlist'] ?? [];
$products = [];
if (count($wishlist) > 0) {
  $ids = implode(',', array_map('intval', $wishlist));
  $q = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id WHERE p.id IN ($ids) AND p.status=1");
  if ($q) {
    while ($row = mysqli_fetch_assoc($q)) {
      $products[] = $row;
    }
  }
}

$ratings = [];
$r_q = mysqli_query($conn, "SELECT product_id, ROUND(AVG(rating),1) as avg, COUNT(*) as total FROM reviews WHERE status=1 GROUP BY product_id");
if ($r_q) { while ($r = mysqli_fetch_assoc($r_q)) { $ratings[$r['product_id']] = $r; } }
?>
<div class="container py-4">
  <h4 style="font-weight:800;margin-bottom:20px;">My Wishlist <?php echo count($products) > 0 ? '<span style="font-weight:400;color:#999;font-size:14px;">('.count($products).' items)</span>' : ''; ?></h4>

  <?php if (count($products) > 0): ?>
    <div class="row g-3">
      <?php foreach ($products as $p):
        $discount = $p['old_price'] > 0 ? round((1 - $p['price']/$p['old_price'])*100) : 0;
      ?>
        <div class="col-6 col-md-4 col-lg-3">
          <a href="product-detail.php?id=<?php echo $p['id']; ?>" class="product-card" style="position:relative;">
            <div class="img-wrap">
              <?php if ($discount > 0): ?><span class="badge-top"><?php echo $discount; ?>% OFF</span><?php endif; ?>
              <button class="wishlist-btn active" data-id="<?php echo $p['id']; ?>" onclick="event.preventDefault(); toggleWishlist(<?php echo $p['id']; ?>, this)" style="position:absolute;top:8px;right:8px;z-index:2;width:34px;height:34px;border-radius:50%;border:none;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.12);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:17px;color:#dc2626;">
                <i class="bi bi-heart-fill"></i>
              </button>
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
              <?php if ($p['free_delivery']): ?><div class="delivery"><i class="bi bi-truck"></i> Free Delivery</div><?php endif; ?>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div style="text-align:center;padding:60px 20px;">
      <i class="bi bi-heart" style="font-size:60px;color:#ddd;"></i>
      <h5 style="margin-top:16px;font-weight:600;">Your wishlist is empty</h5>
      <p style="color:#999;margin-bottom:20px;">Save your favorite items here!</p>
      <a href="index.php" class="btn-red">Start Shopping</a>
    </div>
  <?php endif; ?>
</div>

<script>
function toggleWishlist(id, btn) {
  var x = new XMLHttpRequest();
  x.open('POST', 'wishlist_ajax.php', true);
  x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  x.onload = function() {
    var r = JSON.parse(this.responseText);
    if (r.action === 'removed') {
      var card = btn.closest('.col-6');
      if (card) { card.style.transition = '0.3s'; card.style.opacity = '0'; setTimeout(function(){ card.remove(); }, 300); }
    }
  };
  x.send('id=' + id);
}
</script>

<?php include 'includes/footer.php'; ?>
