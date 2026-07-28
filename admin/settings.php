<?php
$page_title = 'Settings';
require_once 'includes/header.php';

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $percent = (float)$_POST['platform_fee_percent'];
  $fixed = (float)$_POST['platform_fee_fixed'];
  mysqli_query($conn, "UPDATE settings SET `value`='$percent' WHERE `key`='platform_fee_percent'");
  mysqli_query($conn, "UPDATE settings SET `value`='$fixed' WHERE `key`='platform_fee_fixed'");
  $success = 'Settings saved';
}

$pf_p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT `value` FROM settings WHERE `key`='platform_fee_percent'"))['value'] ?? 5;
$pf_f = mysqli_fetch_assoc(mysqli_query($conn, "SELECT `value` FROM settings WHERE `key`='platform_fee_fixed'"))['value'] ?? 0;
?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="form-card">
      <?php if ($success): ?>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:var(--radius-sm);padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;color:#16a34a;font-size:13px;font-weight:500;"><i class="bi bi-check-circle" style="font-size:18px;"></i> <?php echo $success; ?></div>
      <?php endif; ?>
      <form method="POST">
        <h6 style="font-weight:700;margin-bottom:16px;">Platform Fee</h6>
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px;">This fee is added to every order. Customers will see it at checkout.</p>
        <div class="row g-4">
          <div class="col-md-6">
            <label class="form-label">Percentage (%)</label>
            <input type="number" step="0.01" name="platform_fee_percent" class="form-control" value="<?php echo $pf_p; ?>" placeholder="5">
            <small style="color:var(--text-muted);">e.g. 5 = 5% of subtotal</small>
          </div>
          <div class="col-md-6">
            <label class="form-label">Fixed Fee (₹)</label>
            <input type="number" step="0.01" name="platform_fee_fixed" class="form-control" value="<?php echo $pf_f; ?>" placeholder="0">
            <small style="color:var(--text-muted);">Extra fixed amount added to every order</small>
          </div>
        </div>
        <hr style="border-color:var(--border-color);margin:24px 0;">
        <button type="submit" class="btn-red"><i class="bi bi-check-lg"></i> Save Settings</button>
      </form>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>