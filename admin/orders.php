<?php
$page_title = 'Orders';
require_once 'includes/header.php';

if (isset($_POST['update_status'])) {
  $oid = (int)$_POST['order_id'];
  $status = mysqli_real_escape_string($conn, $_POST['order_status']);
  if (mysqli_query($conn, "UPDATE orders SET order_status='$status' WHERE id=$oid")) {
    header('Location: orders.php?success=Order+status+updated');
  } else {
    header('Location: orders.php?error=Update+failed');
  }
  exit;
}

$orders_q = mysqli_query($conn, "SELECT * FROM orders ORDER BY id DESC");
$orders = $orders_q ? $orders_q : false;
?>

<div class="table-card">
  <div class="table-header">
    <h5><i class="bi bi-truck me-2" style="color:var(--red);"></i>All Orders</h5>
    <div class="table-actions">
      <div class="search-wrap">
        <i class="bi bi-search"></i>
        <input type="text" class="search-input" placeholder="Search orders..." onkeyup="filterTable(this)">
      </div>
    </div>
  </div>
  <div class="table-responsive">
    <table id="dataTable">
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Amount</th>
          <th>Payment</th>
          <th>Status</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($orders && mysqli_num_rows($orders) > 0): ?>
          <?php while ($o = mysqli_fetch_assoc($orders)): ?>
            <tr>
              <td><strong>#<?php echo $o['order_number']; ?></strong></td>
              <td>
                <strong><?php echo $o['name']; ?></strong><br>
                <small style="color:var(--text-muted);"><?php echo $o['phone']; ?></small>
              </td>
              <td><strong>₹<?php echo number_format($o['total_amount']); ?></strong></td>
              <td><span class="badge-status bg-secondary"><?php echo $o['payment_method']; ?></span></td>
              <td>
                <span class="badge-status bg-<?php
                  echo $o['order_status'] == 'delivered' ? 'success' : (($o['order_status'] == 'cancelled' || $o['order_status'] == 'rejected') ? 'danger' : (($o['order_status'] == 'shipped' || $o['order_status'] == 'initiated') ? 'primary' : 'warning'));
                ?>">
                  <span class="dot"></span> <?php echo ucfirst($o['order_status']); ?>
                </span>
              </td>
              <td><small style="color:var(--text-muted);"><?php echo date('d M Y', strtotime($o['created_at'])); ?></small></td>
              <td>
                <button class="btn-outline-secondary btn-sm" style="padding:5px 10px;" data-bs-toggle="collapse" data-bs-target="#order<?php echo $o['id']; ?>"><i class="bi bi-eye"></i></button>
              </td>
            </tr>
            <tr class="collapse" id="order<?php echo $o['id']; ?>">
              <td colspan="7" class="order-detail-row">
                <div class="row g-4">
                  <div class="col-md-6">
                    <h6 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);margin-bottom:12px;">Customer Details</h6>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:13px;">
                      <span style="color:var(--text-muted);">Name</span><span><strong><?php echo $o['name']; ?></strong></span>
                      <span style="color:var(--text-muted);">Email</span><span><?php echo $o['email']; ?></span>
                      <span style="color:var(--text-muted);">Phone</span><span><?php echo $o['phone']; ?></span>
                      <span style="color:var(--text-muted);">Address</span><span><?php echo $o['address']; ?></span>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <h6 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);margin-bottom:12px;">Update Status</h6>
                    <form method="POST" style="display:flex;gap:8px;margin-bottom:16px;">
                      <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                      <select name="order_status" class="form-select" style="width:auto;border-radius:50px;font-size:13px;padding:6px 14px;">
                        <option value="pending" <?php echo $o['order_status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $o['order_status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="shipped" <?php echo $o['order_status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $o['order_status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $o['order_status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                      </select>
                      <button type="submit" name="update_status" class="btn-red btn-sm">Update</button>
                    </form>
                    <h6 style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-muted);margin-bottom:8px;">Order Items</h6>
                    <?php
                    $items_q = mysqli_query($conn, "SELECT oi.*, p.name as pname, s.size_name FROM order_items oi LEFT JOIN products p ON oi.product_id=p.id LEFT JOIN product_sizes s ON oi.size_id=s.id WHERE oi.order_id=" . $o['id']);
                    if ($items_q):
                      while ($item = mysqli_fetch_assoc($items_q)):
                    ?>
                      <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-color);font-size:13px;">
                        <span><?php echo $item['pname']; ?> × <?php echo $item['quantity']; ?> <?php echo $item['size_name'] ? '[Size: '.$item['size_name'].']' : ''; ?></span>
                        <span><strong>₹<?php echo number_format($item['price'] * $item['quantity']); ?></strong></span>
                      </div>
                    <?php endwhile; endif; ?>
                  </div>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7"><div class="empty-state"><div class="empty-icon"><i class="bi bi-inbox"></i></div><h6>No orders yet</h6><p>Customer orders will appear here.</p></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="table-footer">
    <span><?php echo $orders && mysqli_num_rows($orders) > 0 ? mysqli_num_rows($orders) . ' orders' : '0 orders'; ?></span>
  </div>
</div>

<script>
function filterTable(input) {
  var val = input.value.toLowerCase();
  var rows = document.querySelectorAll('#dataTable tbody tr:not(.collapse)');
  rows.forEach(function(row) {
    var text = row.textContent.toLowerCase();
    row.style.display = text.indexOf(val) > -1 ? '' : 'none';
  });
}
</script>

<?php require_once 'includes/footer.php'; ?>
