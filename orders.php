<?php require_once 'includes/config.php'; include 'includes/header.php';

if (!isset($_SESSION['user'])) {
  $_SESSION['redirect_after_login'] = 'orders.php';
  header('Location: login.php');
  exit;
}

$user = $_SESSION['user'];
$q = mysqli_query($conn, "SELECT * FROM orders WHERE user_id={$user['id']} ORDER BY created_at DESC");
$orders = $q ? $q : false;
?>
<div class="container py-4">
  <h4 style="font-weight:800;margin-bottom:20px;">My Orders</h4>

  <?php if ($orders && mysqli_num_rows($orders) > 0): ?>
    <div style="display:flex;flex-direction:column;gap:12px;">
      <?php while ($o = mysqli_fetch_assoc($orders)):
        $items_q = mysqli_query($conn, "SELECT oi.*, p.name, p.image, s.size_name FROM order_items oi LEFT JOIN products p ON oi.product_id=p.id LEFT JOIN product_sizes s ON oi.size_id=s.id WHERE oi.order_id={$o['id']}");
        $items = $items_q ? $items_q : false;
      ?>
        <div style="background:#fff;border:1px solid #eee;border-radius:12px;padding:16px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px;">
            <div>
              <span style="font-size:12px;color:#999;">Order</span>
              <span style="font-weight:700;font-size:14px;">#<?php echo $o['order_number']; ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
              <span style="font-size:12px;color:#999;"><?php echo date('d M Y, h:i A', strtotime($o['created_at'])); ?></span>
              <span style="display:inline-block;padding:3px 12px;border-radius:50px;font-size:11px;font-weight:600;text-transform:capitalize;background:<?php
                $s = $o['order_status'];
                echo $s == 'delivered' ? '#f0fdf4;color:#059669' : (($s == 'cancelled' || $s == 'rejected') ? '#fef2f2;color:#dc2626' : (($s == 'initiated' || $s == 'shipped') ? '#eff6ff;color:#2563eb' : '#fef9c3;color:#b45309'));
              ?>;"><?php echo $s; ?></span>
            </div>
          </div>
          <?php if ($items): while ($item = mysqli_fetch_assoc($items)): ?>
            <div style="display:flex;gap:12px;padding:8px 0;border-top:1px solid #f5f5f5;">
              <div style="width:48px;height:48px;border-radius:8px;overflow:hidden;background:#f5f5f5;flex-shrink:0;">
                <?php if ($item['image']): ?><img src="<?php echo $item['image']; ?>" style="width:100%;height:100%;object-fit:cover;"><?php endif; ?>
              </div>
              <div style="flex:1;font-size:13px;">
                <div style="font-weight:600;"><?php echo $item['name']; ?> × <?php echo $item['quantity']; ?></div>
                <?php if ($item['size_name']): ?><div style="color:#888;font-size:12px;">Size: <?php echo $item['size_name']; ?></div><?php endif; ?>
                <div style="color:#999;">₹<?php echo number_format($item['price'] * $item['quantity']); ?></div>
              </div>
            </div>
          <?php endwhile; endif; ?>
          <div style="display:flex;justify-content:space-between;font-size:15px;font-weight:700;margin-top:8px;padding-top:8px;border-top:1px solid #eee;">
            <span>Total</span>
            <span style="color:var(--red);">₹<?php echo number_format($o['total_amount']); ?></span>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <div style="text-align:center;padding:60px 20px;">
      <i class="bi bi-box" style="font-size:60px;color:#ddd;"></i>
      <h5 style="margin-top:16px;font-weight:600;">No orders yet</h5>
      <p style="color:#999;margin-bottom:20px;">Your order history will appear here.</p>
      <a href="shop.php" class="btn-red">Start Shopping</a>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
