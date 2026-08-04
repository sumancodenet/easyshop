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

// Fallback: if no order param in URL (clean redirect URL), use the pending session order
if (empty($order_no) && $pending) {
  $order_no = mysqli_real_escape_string($conn, $pending['order_no']);
}

// Order was already created at checkout.php. Here we only sync the gateway
// transaction id + status onto it, so the callback webhook can match it later.
if ($pending && $pending['order_no'] === $order_no && $order_no) {
  $txn_id = $txn_redirect ?: ($pending['txn_id'] ?? '');
  if ($txn_id) {
    $esc_txn = mysqli_real_escape_string($conn, $txn_id);
    mysqli_query($conn, "UPDATE orders SET transaction_id='$esc_txn' WHERE order_number='$order_no'");

    $ptx = mysqli_query($conn, "SELECT id FROM payment_transactions WHERE transaction_id='$esc_txn' LIMIT 1");
    $esc_order = $order_no;
    $name = mysqli_real_escape_string($conn, $pending['name'] ?? '');
    $email = mysqli_real_escape_string($conn, $pending['email'] ?? '');
    $phone = mysqli_real_escape_string($conn, $pending['phone'] ?? '');
    $grand_total = (float)($pending['grand_total'] ?? 0);
    $ord = mysqli_query($conn, "SELECT id FROM orders WHERE order_number='$esc_order' LIMIT 1");
    $oid = ($ord && $o2 = mysqli_fetch_assoc($ord)) ? (int)$o2['id'] : 'NULL';
    if ($ptx && $row = mysqli_fetch_assoc($ptx)) {
      mysqli_query($conn, "UPDATE payment_transactions SET order_id=$oid, order_number='$esc_order', amount=$grand_total, customer_name='$name', customer_email='$email', customer_phone='$phone' WHERE id={$row['id']}");
    } else {
      mysqli_query($conn, "INSERT INTO payment_transactions (order_id, order_number, transaction_id, amount, currency, status, customer_name, customer_email, customer_phone) VALUES ($oid, '$esc_order', '$esc_txn', $grand_total, 'INR', 'initiated', '$name', '$email', '$phone')");
    }
  }
  unset($_SESSION['pending_payment']);
  unset($_SESSION['cart']);
}

// Load the order to display (created at checkout.php, or already existing on refresh)
$order = false;
if ($order_no) {
  $q = mysqli_query($conn, "SELECT * FROM orders WHERE order_number='$order_no' ORDER BY id DESC LIMIT 1");
  if ($q && $o = mysqli_fetch_assoc($q)) $order = $o;
}

// Load order items with product + size details
$items = [];
if ($order) {
  $iq = mysqli_query($conn, "SELECT oi.quantity, oi.price, p.name, p.image, ps.size_name FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id LEFT JOIN product_sizes ps ON ps.id = oi.size_id WHERE oi.order_id = {$order['id']}");
  if ($iq) $items = mysqli_fetch_all($iq, MYSQLI_ASSOC);
}

// Effective payment status: prefer URL param (gateway), fall back to DB
$status = strtolower($pay_status ?: ($order['payment_status'] ?? 'initiated'));
$is_rejected = in_array($status, ['rejected', 'failed', 'cancelled']);
$is_success = in_array($status, ['success', 'paid', 'approved']);

if ($is_rejected) {
  $status_label = 'Payment Rejected';
  $status_icon  = 'bi-x-circle';
  $status_color = '#dc2626';
  $status_bg    = '#fef2f2';
  $status_note  = 'Your payment could not be verified. The order has been cancelled and your amount (if deducted) will be refunded within 3-5 business days.';
} elseif ($is_success) {
  $status_label = 'Payment Successful';
  $status_icon  = 'bi-check-circle';
  $status_color = '#059669';
  $status_bg    = '#ecfdf5';
  $status_note  = 'Your payment has been verified successfully. We are packing your order and will ship it soon.';
} else {
  $status_label = 'Payment Submitted';
  $status_icon  = 'bi-clock-history';
  $status_color = '#b45309';
  $status_bg    = '#fffbeb';
  $status_note  = 'Your payment has been submitted and is awaiting merchant approval. The order will be processed once the payment is verified.';
}

$page_skel = 'none';
?>
<div class="container py-4" style="max-width:560px;">
  <?php if ($order): ?>

    <!-- STATUS HERO CARD -->
    <div style="background:#fff;border:1px solid #eee;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.05);">
      <div style="padding:32px 24px 8px;text-align:center;">
        <div style="width:76px;height:76px;border-radius:50%;background:<?php echo $status_bg; ?>;color:<?php echo $status_color; ?>;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;border:2px solid <?php echo $status_color; ?>20;">
          <i class="bi <?php echo $status_icon; ?>" style="font-size:36px;"></i>
        </div>
        <h5 style="margin-bottom:6px;font-weight:800;font-size:19px;"><?php echo $status_label; ?></h5>
        <p style="color:#666;font-size:13.5px;line-height:1.6;margin:0 auto;max-width:400px;"><?php echo $status_note; ?></p>
        <div style="margin-top:14px;display:inline-flex;align-items:center;gap:8px;background:#f8f9fa;border:1px solid #eee;border-radius:50px;padding:7px 18px;font-size:13px;">
          <span style="color:#888;">Order</span>
          <strong style="color:var(--red);letter-spacing:0.3px;">#<?php echo htmlspecialchars($order['order_number']); ?></strong>
        </div>
      </div>

      <!-- ORDER DETAILS -->
      <div style="padding:22px 24px;border-top:1px solid #f0f0f0;margin-top:22px;">
        <div style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:12px;">Payment Details</div>
        <div style="background:#fafafa;border:1px solid #f0f0f0;border-radius:12px;padding:14px 16px;">
          <?php if ($order['transaction_id']): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px dashed #eee;">
              <span style="color:#888;font-size:13px;">Transaction ID</span>
              <strong style="font-size:12.5px;font-family:'SF Mono',Menlo,monospace;color:#333;word-break:break-all;text-align:right;max-width:230px;margin-left:12px;"><?php echo htmlspecialchars($order['transaction_id']); ?></strong>
            </div>
          <?php endif; ?>
          <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px dashed #eee;">
            <span style="color:#888;font-size:13px;">Payment Status</span>
            <span style="font-size:12px;font-weight:700;color:<?php echo $status_color; ?>;background:<?php echo $status_bg; ?>;padding:3px 12px;border-radius:50px;text-transform:capitalize;"><?php echo htmlspecialchars($status); ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px dashed #eee;">
            <span style="color:#888;font-size:13px;">Payment Method</span>
            <strong style="font-size:13px;">UPI / Bank Transfer</strong>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:9px 0 2px;">
            <span style="color:#888;font-size:13px;">Total Paid</span>
            <span style="font-size:17px;font-weight:800;color:var(--red);">₹<?php echo number_format($order['total_amount']); ?></span>
          </div>
        </div>

        <?php if (!empty($items)): ?>
          <div style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.8px;margin:22px 0 12px;">Order Items</div>
          <div style="background:#fafafa;border:1px solid #f0f0f0;border-radius:12px;padding:6px 16px;">
            <?php foreach ($items as $it): ?>
              <div style="display:flex;align-items:center;gap:12px;padding:10px 0;<?php echo $it !== end($items) ? 'border-bottom:1px dashed #eee;' : ''; ?>">
                <div style="width:46px;height:46px;border-radius:8px;overflow:hidden;background:#f0f0f0;flex-shrink:0;display:flex;align-items:center;justify-content:center;">
                  <?php if (!empty($it['image'])): ?>
                    <img src="<?php echo htmlspecialchars($it['image']); ?>" style="width:100%;height:100%;object-fit:cover;" alt="">
                  <?php else: ?>
                    <i class="bi bi-bag" style="color:#ccc;font-size:18px;"></i>
                  <?php endif; ?>
                </div>
                <div style="flex:1;min-width:0;">
                  <div style="font-size:13px;font-weight:600;color:#333;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($it['name'] ?? 'Product'); ?></div>
                  <div style="font-size:11.5px;color:#999;margin-top:2px;">
                    <?php if (!empty($it['size_name'])): ?>Size: <?php echo htmlspecialchars($it['size_name']); ?> · <?php endif; ?>
                    Qty: <?php echo (int)$it['quantity']; ?>
                  </div>
                </div>
                <strong style="font-size:13px;color:#333;flex-shrink:0;">₹<?php echo number_format($it['price'] * $it['quantity']); ?></strong>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- DELIVERY DETAILS -->
        <?php if ($order['address'] || $order['name'] || $order['phone']): ?>
          <div style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.8px;margin:22px 0 12px;">Delivery Details</div>
          <div style="background:#fafafa;border:1px solid #f0f0f0;border-radius:12px;padding:14px 16px;">
            <div style="font-size:13px;font-weight:700;color:#333;"><?php echo htmlspecialchars($order['name']); ?></div>
            <div style="font-size:12.5px;color:#666;line-height:1.6;margin-top:3px;white-space:pre-line;"><?php echo htmlspecialchars($order['address']); ?></div>
            <?php if ($order['phone']): ?>
              <div style="font-size:12.5px;color:#666;margin-top:3px;"><i class="bi bi-telephone" style="color:var(--red);"></i> +91 <?php echo htmlspecialchars($order['phone']); ?></div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- WHATS NEXT -->
        <div style="font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:0.8px;margin:22px 0 12px;">What Happens Next</div>
        <div style="display:flex;flex-direction:column;gap:0;">
          <?php
          $steps = [
            ['icon' => 'bi-cash-coin', 'title' => 'Payment Verification', 'desc' => 'Merchant verifies your UTR & payment details', 'done' => true],
            ['icon' => 'bi-box-seam', 'title' => 'Order Packed & Shipped', 'desc' => 'You will get an update once your order ships', 'done' => $is_success],
            ['icon' => 'bi-house-check', 'title' => 'Delivered to Your Door', 'desc' => 'Track delivery anytime from your orders page', 'done' => false],
          ];
          foreach ($steps as $i => $st):
            $line = $i < count($steps) - 1;
          ?>
            <div style="display:flex;gap:14px;position:relative;">
              <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;">
                <div style="width:34px;height:34px;border-radius:50%;background:<?php echo $st['done'] ? 'var(--red)' : '#f0f0f0'; ?>;color:<?php echo $st['done'] ? '#fff' : '#bbb'; ?>;display:flex;align-items:center;justify-content:center;z-index:1;">
                  <i class="bi <?php echo $st['icon']; ?>" style="font-size:15px;"></i>
                </div>
                <?php if ($line): ?>
                  <div style="width:2px;flex:1;background:<?php echo $st['done'] ? 'var(--red)' : '#e8e8e8'; ?>;min-height:34px;margin:2px 0;"></div>
                <?php endif; ?>
              </div>
              <div style="padding-bottom:<?php echo $line ? '20px' : '0'; ?>;">
                <div style="font-size:13.5px;font-weight:700;color:<?php echo $st['done'] ? '#333' : '#999'; ?>;"><?php echo $st['title']; ?></div>
                <div style="font-size:12px;color:#999;margin-top:2px;"><?php echo $st['desc']; ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- ACTIONS -->
    <div style="display:flex;gap:10px;margin-top:18px;flex-wrap:wrap;">
      <a href="orders.php" class="btn-red" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;flex:1;justify-content:center;padding:12px;font-size:14px;"><i class="bi bi-box"></i> Track My Order</a>
      <a href="shop.php" class="btn-outline-red" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;flex:1;justify-content:center;padding:12px;font-size:14px;"><i class="bi bi-bag"></i> Continue Shopping</a>
    </div>
    <p style="text-align:center;font-size:12px;color:#bbb;margin-top:14px;"><i class="bi bi-shield-check"></i> Secure payment processed via EasyPay</p>

  <?php else: ?>
    <!-- ORDER NOT FOUND -->
    <div style="background:#fff;border:1px solid #eee;border-radius:16px;text-align:center;padding:48px 24px;box-shadow:0 4px 24px rgba(0,0,0,0.05);">
      <div style="width:76px;height:76px;border-radius:50%;background:#f5f5f5;color:#ccc;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;">
        <i class="bi bi-question-circle" style="font-size:36px;"></i>
      </div>
      <h5 style="margin-bottom:8px;font-weight:800;">Order Not Found</h5>
      <p style="color:#999;font-size:13.5px;margin-bottom:22px;max-width:340px;margin-left:auto;margin-right:auto;">We couldn't verify this order. Check your order history or contact support if you made a payment.</p>
      <a href="orders.php" class="btn-red" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;padding:11px 26px;font-size:14px;"><i class="bi bi-box"></i> My Orders</a>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
