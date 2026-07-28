<?php
$page_title = 'Dashboard';
require_once 'includes/header.php';

$total_products = 0; $total_categories = 0; $total_orders = 0; $total_users = 0; $total_revenue = 0; $recent_orders = false;

$q1 = mysqli_query($conn, "SELECT COUNT(*) as c FROM products");
if ($q1) $total_products = mysqli_fetch_assoc($q1)['c'];

$q2 = mysqli_query($conn, "SELECT COUNT(*) as c FROM categories");
if ($q2) $total_categories = mysqli_fetch_assoc($q2)['c'];

$q3 = mysqli_query($conn, "SELECT COUNT(*) as c FROM orders");
if ($q3) $total_orders = mysqli_fetch_assoc($q3)['c'];

$q4 = mysqli_query($conn, "SELECT COUNT(*) as c FROM users");
if ($q4) $total_users = mysqli_fetch_assoc($q4)['c'];

$q5 = mysqli_query($conn, "SELECT SUM(total_amount) as c FROM orders WHERE order_status='delivered'");
if ($q5) $total_revenue = mysqli_fetch_assoc($q5)['c'];

$recent_orders_q = mysqli_query($conn, "SELECT * FROM orders ORDER BY id DESC LIMIT 5");
$recent_orders = $recent_orders_q ? $recent_orders_q : false;
?>

<div class="row-stats">
  <div class="stat-card">
    <div class="stat-top">
      <div class="stat-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-box-seam-fill"></i></div>
      <span class="stat-change">In Stock</span>
    </div>
    <div class="stat-number"><?php echo $total_products; ?></div>
    <div class="stat-label">Total Products</div>
    <div class="stat-bar"><div class="fill" style="width:70%;background:#2563eb;"></div></div>
  </div>
  <div class="stat-card">
    <div class="stat-top">
      <div class="stat-icon" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-tags-fill"></i></div>
    </div>
    <div class="stat-number"><?php echo $total_categories; ?></div>
    <div class="stat-label">Categories</div>
    <div class="stat-bar"><div class="fill" style="width:45%;background:#dc2626;"></div></div>
  </div>
  <div class="stat-card">
    <div class="stat-top">
      <div class="stat-icon" style="background:#fffbeb;color:#f59e0b;"><i class="bi bi-truck"></i></div>
      <span class="stat-change">+12%</span>
    </div>
    <div class="stat-number"><?php echo $total_orders; ?></div>
    <div class="stat-label">Total Orders</div>
    <div class="stat-bar"><div class="fill" style="width:60%;background:#f59e0b;"></div></div>
  </div>
  <div class="stat-card">
    <div class="stat-top">
      <div class="stat-icon" style="background:#ecfdf5;color:#059669;"><i class="bi bi-currency-rupee"></i></div>
    </div>
    <div class="stat-number">₹<?php echo number_format($total_revenue ?? 0); ?></div>
    <div class="stat-label">Total Revenue</div>
    <div class="stat-bar"><div class="fill" style="width:80%;background:#059669;"></div></div>
  </div>
</div>

<div class="row g-4 mb-4">
  <div class="col-lg-8">
    <div class="table-card">
      <div class="table-header">
        <h5><i class="bi bi-clock-history me-2" style="color:var(--red);"></i>Recent Orders</h5>
        <div class="table-actions">
          <a href="orders.php" class="btn-outline-red btn-sm">View All</a>
        </div>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>#Order</th>
              <th>Customer</th>
              <th>Amount</th>
              <th>Payment</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($recent_orders && mysqli_num_rows($recent_orders) > 0): ?>
              <?php while ($o = mysqli_fetch_assoc($recent_orders)): ?>
                <tr>
                  <td><strong>#<?php echo $o['order_number']; ?></strong></td>
                  <td><?php echo $o['name']; ?></td>
                  <td><strong>₹<?php echo number_format($o['total_amount']); ?></strong></td>
                  <td><?php echo $o['payment_method']; ?></td>
                  <td>
                    <span class="badge-status bg-<?php echo $o['order_status'] == 'delivered' ? 'success' : ($o['order_status'] == 'cancelled' ? 'danger' : ($o['order_status'] == 'shipped' ? 'primary' : 'warning')); ?>">
                      <span class="dot"></span> <?php echo ucfirst($o['order_status']); ?>
                    </span>
                  </td>
                  <td><small style="color:var(--text-muted);"><?php echo date('d M Y', strtotime($o['created_at'])); ?></small></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="6"><div class="empty-state"><div class="empty-icon"><i class="bi bi-inbox"></i></div><h6>No orders yet</h6><p>Orders will appear here once customers start shopping.</p></div></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="table-card">
      <div class="table-header">
        <h5><i class="bi bi-people-fill me-2" style="color:var(--red);"></i>Quick Stats</h5>
      </div>
      <div style="padding:20px;">
        <div style="display:flex;flex-direction:column;gap:16px;">
          <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:var(--body-bg);border-radius:var(--radius-sm);">
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:36px;height:36px;border-radius:8px;background:#eff6ff;display:flex;align-items:center;justify-content:center;color:#2563eb;font-size:16px;"><i class="bi bi-people-fill"></i></div>
              <div><div style="font-size:13px;font-weight:600;">Users</div><div style="font-size:11px;color:var(--text-muted);">Registered customers</div></div>
            </div>
            <div style="font-size:20px;font-weight:800;"><?php echo $total_users; ?></div>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:var(--body-bg);border-radius:var(--radius-sm);">
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:36px;height:36px;border-radius:8px;background:#ecfdf5;display:flex;align-items:center;justify-content:center;color:#059669;font-size:16px;"><i class="bi bi-truck"></i></div>
              <div><div style="font-size:13px;font-weight:600;">Orders</div><div style="font-size:11px;color:var(--text-muted);">Total placed</div></div>
            </div>
            <div style="font-size:20px;font-weight:800;"><?php echo $total_orders; ?></div>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;background:var(--body-bg);border-radius:var(--radius-sm);">
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:36px;height:36px;border-radius:8px;background:#fef2f2;display:flex;align-items:center;justify-content:center;color:#dc2626;font-size:16px;"><i class="bi bi-box-seam-fill"></i></div>
              <div><div style="font-size:13px;font-weight:600;">Products</div><div style="font-size:11px;color:var(--text-muted);">In catalog</div></div>
            </div>
            <div style="font-size:20px;font-weight:800;"><?php echo $total_products; ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
