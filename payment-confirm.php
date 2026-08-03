<?php require_once 'includes/config.php'; include 'includes/header.php';

$user = $_SESSION['user'] ?? null;
$order_raw = isset($_GET['order']) ? $_GET['order'] : '';
$txn_redirect = isset($_GET['transactionId']) ? mysqli_real_escape_string($conn, $_GET['transactionId']) : '';
// SDK appends '?transactionId=..&status=..' to our redirectUrl which already has '?order=..'
$qpos = strpos($order_raw, '?');
if ($qpos !== false) {
  parse_str(substr($order_raw, $qpos + 1), $qq);
  $order_raw = substr($order_raw, 0, $qpos);
  if (empty($txn_redirect)) $txn_redirect = isset($qq['transactionId']) ? mysqli_real_escape_string($conn, $qq['transactionId']) : '';
}
$order_no = mysqli_real_escape_string($conn, $order_raw);
$pay_status = $_GET['status'] ?? '';

$pending = $_SESSION['pending_payment'] ?? null;

// ORDER IS CREATED HERE - only after the customer completed the payment
// (redirected here by the gateway with transactionId + status=initiated).
if ($pending && $pending['order_no'] === $order_no) {
  $txn_id = $txn_redirect ?: ($pending['txn_id'] ?? '');
  $esc_txn = mysqli_real_escape_string($conn, $txn_id);
  $esc_order = mysqli_real_escape_string($conn, $pending['order_no']);
  $name = mysqli_real_escape_string($conn, $pending['name'] ?? '');
  $email = mysqli_real_escape_string($conn, $pending['email'] ?? '');
  $phone = mysqli_real_escape_string($conn, $pending['phone'] ?? '');
  $address = mysqli_real_escape_string($conn, $pending['address'] ?? '');
  $grand_total = (float)($pending['grand_total'] ?? 0);
  $uid = $user ? $user['id'] : 'NULL';

  $q = mysqli_query($conn, "INSERT INTO orders (user_id, order_number, total_amount, payment_method, payment_status, transaction_id, order_status, name, email, phone, address) VALUES ($uid, '$esc_order', $grand_total, 'Online', 'initiated', '$esc_txn', 'initiated', '$name', '$email', '$phone', '$address')");
  if ($q) {
    $oid = mysqli_insert_id($conn);
    foreach ($pending['items'] as $it) {
      $pid = (int)$it['id'];
      $size_id_sql = $it['size_id'] ? (int)$it['size_id'] : 'NULL';
      $qty = (int)$it['qty'];
      $price = (float)$it['price'];
      mysqli_query($conn, "INSERT INTO order_items (order_id, product_id, size_id, quantity, price) VALUES ($oid, $pid, $size_id_sql, $qty, $price)");
      mysqli_query($conn, "UPDATE products SET stock = stock - $qty WHERE id=$pid");
    }
    // Payment transaction statement: update row if webhook already created it, else insert
    $ptx = mysqli_query($conn, "SELECT id FROM payment_transactions WHERE transaction_id='$esc_txn' LIMIT 1");
    if ($ptx && $row = mysqli_fetch_assoc($ptx)) {
      mysqli_query($conn, "UPDATE payment_transactions SET order_id=$oid, order_number='$esc_order', amount=$grand_total, status='initiated', customer_name='$name', customer_email='$email', customer_phone='$phone' WHERE id={$row['id']}");
    } else {
      mysqli_query($conn, "INSERT INTO payment_transactions (order_id, order_number, transaction_id, amount, currency, status, customer_name, customer_email, customer_phone) VALUES ($oid, '$esc_order', '$esc_txn', $grand_total, 'INR', 'initiated', '$name', '$email', '$phone')");
    }
    unset($_SESSION['pending_payment']);
    unset($_SESSION['cart']);
  }
}

// Load the order to display (created above, or already existing on refresh)
$order = false;
if ($order_no) {
  $q = mysqli_query($conn, "SELECT * FROM orders WHERE order_number='$order_no' ORDER BY id DESC LIMIT 1");
  if ($q && $o = mysqli_fetch_assoc($q)) $order = $o;
}
?>
<div class="container py-4" style="max-width:640px;">
  <?php if ($order): ?>
    <div style="text-align:center;padding:40px 20px;">
      <div style="width:72px;height:72px;border-radius:50%;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
        <i class="bi bi-credit-card" style="font-size:34px;"></i>
      </div>
      <h5 style="margin-bottom:8px;font-weight:800;">Payment Submitted - Order Confirmed!</h5>
      <p style="color:#666;font-size:14px;max-width:420px;margin:0 auto 16px;">
        Order <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong> is created.
        Your payment of <strong>₹<?php echo number_format($order['total_amount']); ?></strong> has been submitted
        and is awaiting merchant approval. The order will be processed once the payment is verified.
      </p>
      <div style="background:#f8f9fa;border:1px solid #eee;border-radius:12px;padding:16px;text-align:left;margin:0 auto 20px;max-width:380px;font-size:13px;">
        <?php if ($order['transaction_id']): ?>
          <div style="display:flex;justify-content:space-between;padding:4px 0;">
            <span style="color:#888;">Transaction ID</span>
            <strong><?php echo htmlspecialchars($order['transaction_id']); ?></strong>
          </div>
        <?php endif; ?>
        <div style="display:flex;justify-content:space-between;padding:4px 0;">
          <span style="color:#888;">Payment Status</span>
          <strong style="color:#2563eb;text-transform:capitalize;"><?php echo htmlspecialchars($pay_status ?: 'initiated'); ?></strong>
        </div>
        <div style="display:flex;justify-content:space-between;padding:4px 0;">
          <span style="color:#888;">Payment Method</span>
          <strong>UPI / Bank Transfer</strong>
        </div>
      </div>
      <p style="font-size:13px;color:#999;margin-bottom:20px;">
        <i class="bi bi-clock-history"></i> Once approved, your order status will update automatically.
      </p>
      <a href="orders.php" class="btn-red" style="text-decoration:none;display:inline-block;"><i class="bi bi-box"></i> Track My Order</a>
      <a href="shop.php" class="btn-outline-red" style="text-decoration:none;display:inline-block;margin-left:8px;">Continue Shopping</a>
    </div>
  <?php else: ?>
    <div style="text-align:center;padding:60px 20px;">
      <i class="bi bi-question-circle" style="font-size:56px;color:#ddd;"></i>
      <h5 style="margin-top:16px;font-weight:700;">Order not found</h5>
      <p style="color:#999;margin-bottom:20px;">We couldn't verify this order. Check your order history.</p>
      <a href="orders.php" class="btn-red" style="text-decoration:none;">My Orders</a>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
