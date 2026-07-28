<?php require_once 'includes/config.php'; include 'includes/header.php';

// Cart actions
$action = $_GET['action'] ?? '';
$pid = (int)($_GET['id'] ?? 0);

if ($action == 'remove' && $pid) {
  unset($_SESSION['cart'][$pid]);
  header('Location: cart.php');
  exit;
}

if ($action == 'update' && $pid) {
  $qty = max(1, (int)($_GET['qty'] ?? 1));
  $_SESSION['cart'][$pid]['qty'] = $qty;
  header('Location: cart.php');
  exit;
}

if ($action == 'clear') {
  unset($_SESSION['cart']);
  header('Location: cart.php');
  exit;
}

$cart_items = $_SESSION['cart'] ?? [];
$products = [];
$total = 0;

if (count($cart_items) > 0) {
  $ids = implode(',', array_map('intval', array_keys($cart_items)));
  $q = mysqli_query($conn, "SELECT * FROM products WHERE id IN ($ids) AND status=1");
  if ($q) {
    while ($row = mysqli_fetch_assoc($q)) {
      $row['cart_qty'] = $cart_items[$row['id']]['qty'] ?? 1;
      $products[] = $row;
      $total += $row['price'] * $row['cart_qty'];
    }
  }
}
?>
<div class="container py-4">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
    <h4 style="font-weight:800;margin:0;">Shopping Cart <?php echo count($products) > 0 ? '<span style="font-weight:400;color:#999;font-size:14px;">(' . count($products) . ' items)</span>' : ''; ?></h4>
    <?php if (count($products) > 0): ?>
      <a href="cart.php?action=clear" class="btn-outline-red" style="padding:6px 16px;font-size:12px;text-decoration:none;" onclick="return confirm('Clear cart?')"><i class="bi bi-trash"></i> Clear</a>
    <?php endif; ?>
  </div>

  <?php if (count($products) > 0): ?>
    <div class="row g-4">
      <div class="col-lg-8">
        <?php foreach ($products as $p):
          $discount = $p['old_price'] > 0 ? round((1 - $p['price']/$p['old_price'])*100) : 0;
        ?>
          <div style="display:flex;gap:14px;padding:16px;background:#fff;border:1px solid #eee;border-radius:12px;margin-bottom:12px;align-items:center;">
            <div style="width:80px;height:80px;border-radius:10px;overflow:hidden;background:#f5f5f5;flex-shrink:0;">
              <?php if ($p['image']): ?>
                <img src="<?php echo $p['image']; ?>" style="width:100%;height:100%;object-fit:cover;">
              <?php else: ?>
                <i class="bi bi-handbag" style="font-size:30px;color:#ccc;display:flex;align-items:center;justify-content:center;width:100%;height:100%;"></i>
              <?php endif; ?>
            </div>
            <div style="flex:1;min-width:0;">
              <a href="product-detail.php?id=<?php echo $p['id']; ?>" style="text-decoration:none;color:var(--dark);font-weight:600;font-size:14px;"><?php echo $p['name']; ?></a>
              <div style="font-size:13px;color:var(--red);font-weight:700;margin-top:4px;">₹<?php echo number_format($p['price']); ?>
                <?php if ($p['old_price'] > 0): ?><span style="font-size:11px;color:#999;text-decoration:line-through;font-weight:400;">₹<?php echo number_format($p['old_price']); ?></span><?php endif; ?>
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
              <div style="display:flex;align-items:center;border:1.5px solid #ddd;border-radius:50px;overflow:hidden;">
                <a href="cart.php?action=update&id=<?php echo $p['id']; ?>&qty=<?php echo max(1, $p['cart_qty'] - 1); ?>" style="border:none;background:none;padding:6px 10px;text-decoration:none;color:#333;font-size:14px;font-weight:600;">−</a>
                <span style="padding:6px 10px;font-size:14px;font-weight:600;min-width:30px;text-align:center;"><?php echo $p['cart_qty']; ?></span>
                <a href="cart.php?action=update&id=<?php echo $p['id']; ?>&qty=<?php echo $p['cart_qty'] + 1; ?>" style="border:none;background:none;padding:6px 10px;text-decoration:none;color:#333;font-size:14px;font-weight:600;">+</a>
              </div>
              <span style="font-size:15px;font-weight:700;color:var(--dark);min-width:70px;text-align:right;">₹<?php echo number_format($p['price'] * $p['cart_qty']); ?></span>
              <a href="cart.php?action=remove&id=<?php echo $p['id']; ?>" style="color:#ccc;text-decoration:none;font-size:18px;padding:4px;" onclick="return confirm('Remove this item?')">&times;</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="col-lg-4">
        <div style="background:#fff;border:1px solid #eee;border-radius:12px;padding:20px;position:sticky;top:80px;">
          <h6 style="font-weight:700;font-size:15px;margin-bottom:16px;">Order Summary</h6>
          <div style="display:flex;justify-content:space-between;font-size:14px;color:#666;margin-bottom:8px;">
            <span>Items (<?php echo count($products); ?>)</span>
            <span>₹<?php echo number_format($total); ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:14px;color:#059669;margin-bottom:8px;">
            <span>Delivery</span>
            <span>Free</span>
          </div>
          <hr style="border-color:#eee;margin:12px 0;">
          <div style="display:flex;justify-content:space-between;font-size:17px;font-weight:800;color:var(--dark);">
            <span>Total</span>
            <span>₹<?php echo number_format($total); ?></span>
          </div>
          <a href="checkout.php" class="btn-red" style="display:block;text-align:center;margin-top:16px;padding:12px;text-decoration:none;font-size:15px;"><i class="bi bi-lock"></i> Proceed to Checkout</a>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div style="text-align:center;padding:60px 20px;">
      <i class="bi bi-bag" style="font-size:60px;color:#ddd;"></i>
      <h5 style="margin-top:16px;font-weight:600;">Your cart is empty</h5>
      <p style="color:#999;margin-bottom:20px;">Add some products to get started!</p>
      <a href="shop.php" class="btn-red">Shop Now</a>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
