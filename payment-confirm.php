<?php require_once 'includes/config.php'; include 'includes/header.php';

// ============================================================
// ORDER RESOLUTION - page kabhi blank nahi rahegi
// ============================================================
$order_raw = isset($_GET['order']) ? (string)$_GET['order'] : '';
$txn_redirect = isset($_GET['transactionId']) ? (string)$_GET['transactionId'] : '';

// SDK kabhi-kabhi malformed URL bhejta hai: ?order=ORDX?transactionId=..&status=..
// '?' ke baad wala hissa alag parse karke order value clean karo
$qpos = strpos($order_raw, '?');
if ($qpos !== false) {
  parse_str(substr($order_raw, $qpos + 1), $qq);
  $order_raw = substr($order_raw, 0, $qpos);
  if (empty($txn_redirect) && !empty($qq['transactionId'])) {
    $txn_redirect = (string)$qq['transactionId'];
  }
}

$order_no = mysqli_real_escape_string($conn, $order_raw);
$pay_status = isset($_GET['status']) ? (string)$_GET['status'] : '';
$pending = $_SESSION['pending_payment'] ?? null;

// Source 1: pending_payment session se order number
if (empty($order_no) && $pending && !empty($pending['order_no'])) {
  $order_no = mysqli_real_escape_string($conn, $pending['order_no']);
}

// Source 2: transaction_id reverse lookup (session miss hone par bhi kaam karega)
if (empty($order_no) && $txn_redirect) {
  $esc_txn = mysqli_real_escape_string($conn, $txn_redirect);
  $tq = mysqli_query($conn, "SELECT order_number FROM payment_transactions WHERE transaction_id='$esc_txn' ORDER BY id DESC LIMIT 1");
  if ($tq) {
    $trow = mysqli_fetch_assoc($tq);
    if ($trow && !empty($trow['order_number'])) {
      $order_no = mysqli_real_escape_string($conn, $trow['order_number']);
    }
  }
}

// ============================================================
// TRANSACTION SYNC - sirf tab jab pending session isi order ka ho
// ============================================================
if ($order_no && $pending && !empty($pending['order_no']) && $pending['order_no'] === $order_no) {
  $txn_id = $txn_redirect ?: ($pending['txn_id'] ?? '');
  if ($txn_id) {
    $esc_txn = mysqli_real_escape_string($conn, $txn_id);
    mysqli_query($conn, "UPDATE orders SET transaction_id='$esc_txn' WHERE order_number='$order_no'");

    $name = mysqli_real_escape_string($conn, $pending['name'] ?? '');
    $email = mysqli_real_escape_string($conn, $pending['email'] ?? '');
    $phone = mysqli_real_escape_string($conn, $pending['phone'] ?? '');
    $grand_total = (float)($pending['grand_total'] ?? 0);

    $oid_sql = 'NULL';
    $ord = mysqli_query($conn, "SELECT id FROM orders WHERE order_number='$order_no' LIMIT 1");
    if ($ord) {
      $o2 = mysqli_fetch_assoc($ord);
      if ($o2) $oid_sql = (int)$o2['id'];
    }

    $ptx = mysqli_query($conn, "SELECT id FROM payment_transactions WHERE transaction_id='$esc_txn' LIMIT 1");
    if ($ptx) {
      $prow = mysqli_fetch_assoc($ptx);
      if ($prow) {
        mysqli_query($conn, "UPDATE payment_transactions SET order_id=$oid_sql, order_number='$order_no', amount=$grand_total, customer_name='$name', customer_email='$email', customer_phone='$phone' WHERE id={$prow['id']}");
      } else {
        mysqli_query($conn, "INSERT INTO payment_transactions (order_id, order_number, transaction_id, amount, currency, status, customer_name, customer_email, customer_phone) VALUES ($oid_sql, '$order_no', '$esc_txn', $grand_total, 'INR', 'initiated', '$name', '$email', '$phone')");
      }
    }
  }
  unset($_SESSION['pending_payment']);
  unset($_SESSION['cart']);
}

// ============================================================
// ORDER + ITEMS LOAD (har query guarded - koi fatal nahi)
// ============================================================
$order = false;
if ($order_no) {
  $q = mysqli_query($conn, "SELECT * FROM orders WHERE order_number='$order_no' ORDER BY id DESC LIMIT 1");
  if ($q) {
    $o = mysqli_fetch_assoc($q);
    if ($o) $order = $o;
  }
}

$items = [];
if ($order) {
  $iq = mysqli_query($conn, "SELECT oi.quantity, oi.price, p.name, p.image, ps.size_name FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id LEFT JOIN product_sizes ps ON ps.id = oi.size_id WHERE oi.order_id = " . (int)$order['id']);
  if ($iq) {
    $rows = mysqli_fetch_all($iq, MYSQLI_ASSOC);
    if (is_array($rows)) $items = $rows;
  }
}

// ============================================================
// STATUS
// ============================================================
$status = strtolower($pay_status ?: ($order ? ($order['payment_status'] ?? 'initiated') : 'initiated'));
$is_rejected = in_array($status, ['rejected', 'failed', 'cancelled']);
$is_success = in_array($status, ['success', 'paid', 'approved']);

if ($is_rejected) {
  $status_label = 'Payment Rejected';
  $status_note  = 'Your payment could not be verified. The order has been cancelled and your amount (if deducted) will be refunded within 3-5 business days.';
} elseif ($is_success) {
  $status_label = 'Payment Successful';
  $status_note  = 'Your payment has been verified successfully. We are packing your order and will ship it soon.';
} else {
  $status_label = 'Payment Submitted';
  $status_note  = 'Your payment has been submitted and is awaiting merchant verification. The order will be processed once the payment is verified.';
}

$page_skel = 'none';
?>
<style>
  .pc-card { background:#fff;border:1px solid #eee;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.05);overflow:hidden; }
  .pc-icon-wrap { width:84px;height:84px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px; }
  .pc-icon-wrap svg { width:46px;height:46px; }
  .pc-dash { stroke-dasharray:300;stroke-dashoffset:300;animation:pcDraw .9s cubic-bezier(.65,0,.35,1) forwards; }
  .pc-dash-late { stroke-dasharray:60;stroke-dashoffset:60;animation:pcDraw .5s cubic-bezier(.65,0,.35,1) .7s forwards; }
  @keyframes pcDraw { to { stroke-dashoffset:0; } }
  .pc-pulse { animation:pcPulse 1.6s ease-in-out infinite; }
  @keyframes pcPulse { 0%,100%{opacity:1;} 50%{opacity:.5;} }
  .pc-title { font-weight:800;font-size:20px;margin-bottom:6px;color:#1f2937; }
  .pc-note { color:#6b7280;font-size:13.5px;line-height:1.6;margin:0 auto;max-width:420px; }
  .pc-pill { margin-top:14px;display:inline-flex;align-items:center;gap:8px;background:#f8f9fa;border:1px solid #eee;border-radius:50px;padding:7px 18px;font-size:13px;color:#888; }
  .pc-pill strong { color:var(--red);letter-spacing:.3px; }
  .pc-sec { font-size:12px;font-weight:700;color:#888;text-transform:uppercase;letter-spacing:.8px;margin:22px 0 12px; }
  .pc-tbl { background:#fafafa;border:1px solid #f0f0f0;border-radius:12px;padding:4px 16px; }
  .pc-row { display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 0;border-bottom:1px dashed #eee;font-size:13px; }
  .pc-row:last-child { border-bottom:0; }
  .pc-k { color:#888;flex-shrink:0; }
  .pc-v { font-weight:600;color:#333;text-align:right;word-break:break-all; }
  .pc-txn { font-size:12.5px;font-family:'SF Mono',Menlo,monospace; }
  .pc-step-dot { width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;z-index:1; }
  .pc-step-line { width:2px;flex:1;min-height:34px;margin:2px 0; }
  .pc-redirect { margin-top:12px;font-size:12.5px;color:#059669;background:#ecfdf5;border:1px solid #bbf7d0;border-radius:50px;padding:7px 16px;display:inline-flex;align-items:center;gap:8px; }
</style>

<div class="container py-4" style="max-width:600px;">
  <?php if ($order): ?>

    <!-- ================= STATUS HERO ================= -->
    <div class="pc-card">
      <div style="padding:36px 24px 12px;text-align:center;">
        <?php if ($is_success): ?>
          <div class="pc-icon-wrap" style="background:#ecfdf5;border:2px solid #05966933;">
            <svg viewBox="0 0 52 52">
              <circle class="pc-dash" cx="26" cy="26" r="23" fill="none" stroke="#059669" stroke-width="3.5" stroke-linecap="round"/>
              <path class="pc-dash-late" fill="none" stroke="#059669" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" d="M16 27l7.5 7.5L37 19"/>
            </svg>
          </div>
        <?php elseif ($is_rejected): ?>
          <div class="pc-icon-wrap" style="background:#fef2f2;border:2px solid #dc262633;">
            <svg viewBox="0 0 52 52">
              <circle class="pc-dash" cx="26" cy="26" r="23" fill="none" stroke="#dc2626" stroke-width="3.5" stroke-linecap="round"/>
              <path class="pc-dash-late" fill="none" stroke="#dc2626" stroke-width="3.5" stroke-linecap="round" d="M19 19l14 14M33 19L19 33"/>
            </svg>
          </div>
        <?php else: ?>
          <div class="pc-icon-wrap pc-pulse" style="background:#fffbeb;border:2px solid #b4530933;">
            <svg viewBox="0 0 52 52">
              <circle cx="26" cy="26" r="23" fill="none" stroke="#b45309" stroke-width="3.5" stroke-linecap="round"/>
              <path fill="none" stroke="#b45309" stroke-width="3.5" stroke-linecap="round" d="M26 16v11l7 4"/>
            </svg>
          </div>
        <?php endif; ?>

        <h5 class="pc-title" style="color:<?php echo $is_success ? '#059669' : ($is_rejected ? '#dc2626' : '#b45309'); ?>;">
          <?php echo $status_label; ?>
        </h5>
        <p class="pc-note"><?php echo $status_note; ?></p>
        <div class="pc-pill">Order <strong>#<?php echo htmlspecialchars($order['order_number']); ?></strong></div>

        <?php if ($is_success): ?>
          <div class="pc-redirect"><i class="bi bi-arrow-repeat"></i> Redirecting to <strong>My Orders</strong> in <span id="pcCount" style="font-weight:800;">10</span>s...</div>
        <?php endif; ?>
      </div>

      <!-- ================= PAYMENT DETAILS ================= -->
      <div style="padding:20px 24px 26px;border-top:1px solid #f0f0f0;margin-top:24px;">
        <div class="pc-sec">Payment Details</div>
        <div class="pc-tbl">
          <?php if (!empty($order['transaction_id'])): ?>
            <div class="pc-row">
              <span class="pc-k">Transaction ID</span>
              <span class="pc-v pc-txn" style="max-width:250px;"><?php echo htmlspecialchars($order['transaction_id']); ?></span>
            </div>
          <?php endif; ?>
          <div class="pc-row">
            <span class="pc-k">Payment Status</span>
            <span style="font-size:12px;font-weight:700;color:<?php echo $is_success ? '#059669' : ($is_rejected ? '#dc2626' : '#b45309'); ?>;background:<?php echo $is_success ? '#ecfdf5' : ($is_rejected ? '#fef2f2' : '#fffbeb'); ?>;padding:3px 12px;border-radius:50px;text-transform:capitalize;"><?php echo htmlspecialchars($status); ?></span>
          </div>
          <div class="pc-row">
            <span class="pc-k">Payment Method</span>
            <span class="pc-v">UPI / Bank Transfer</span>
          </div>
          <div class="pc-row" style="align-items:baseline;">
            <span class="pc-k" style="font-weight:700;color:#333;">Total Paid</span>
            <span style="font-size:18px;font-weight:800;color:var(--red);">₹<?php echo number_format($order['total_amount']); ?></span>
          </div>
        </div>

        <!-- ================= ORDER ITEMS ================= -->
        <?php if (!empty($items)): ?>
          <div class="pc-sec">Order Items</div>
          <div class="pc-tbl">
            <?php $n = count($items); foreach ($items as $i => $it): ?>
              <div class="pc-row" style="justify-content:flex-start;<?php echo $i < $n - 1 ? '' : 'border-bottom:0;'; ?>">
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

        <!-- ================= DELIVERY DETAILS ================= -->
        <?php if ($order['address'] || $order['name'] || $order['phone']): ?>
          <div class="pc-sec">Delivery Details</div>
          <div style="background:#fafafa;border:1px solid #f0f0f0;border-radius:12px;padding:14px 16px;">
            <div style="font-size:13px;font-weight:700;color:#333;"><?php echo htmlspecialchars($order['name']); ?></div>
            <div style="font-size:12.5px;color:#6b7280;line-height:1.6;margin-top:3px;white-space:pre-line;"><?php echo htmlspecialchars($order['address']); ?></div>
            <?php if (!empty($order['phone'])): ?>
              <div style="font-size:12.5px;color:#6b7280;margin-top:3px;"><i class="bi bi-telephone" style="color:var(--red);"></i> +91 <?php echo htmlspecialchars($order['phone']); ?></div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- ================= WHAT HAPPENS NEXT ================= -->
        <?php if (!$is_rejected): ?>
          <div class="pc-sec">What Happens Next</div>
          <div style="display:flex;flex-direction:column;">
            <?php
            $steps = [
              ['icon' => 'bi-cash-coin', 'title' => 'Payment Verification', 'desc' => 'Merchant verifies your UTR & payment details', 'done' => $is_success],
              ['icon' => 'bi-box-seam',   'title' => 'Order Packed & Shipped', 'desc' => 'You will get an update once your order ships', 'done' => $is_success],
              ['icon' => 'bi-house-check','title' => 'Delivered to Your Door', 'desc' => 'Track delivery anytime from your orders page', 'done' => false],
            ];
            $sc = count($steps);
            foreach ($steps as $i => $st):
              $line = $i < $sc - 1;
            ?>
              <div style="display:flex;gap:14px;">
                <div style="display:flex;flex-direction:column;align-items:center;flex-shrink:0;">
                  <div class="pc-step-dot" style="background:<?php echo $st['done'] ? 'var(--red)' : '#f0f0f0'; ?>;color:<?php echo $st['done'] ? '#fff' : '#bbb'; ?>;">
                    <i class="bi <?php echo $st['icon']; ?>" style="font-size:15px;"></i>
                  </div>
                  <?php if ($line): ?>
                    <div class="pc-step-line" style="background:<?php echo $st['done'] ? 'var(--red)' : '#e8e8e8'; ?>;"></div>
                  <?php endif; ?>
                </div>
                <div style="padding-bottom:<?php echo $line ? '22px' : '0'; ?>;">
                  <div style="font-size:13.5px;font-weight:700;color:<?php echo $st['done'] ? '#333' : '#999'; ?>;"><?php echo $st['title']; ?></div>
                  <div style="font-size:12px;color:#9ca3af;margin-top:2px;"><?php echo $st['desc']; ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="pc-sec">Refund</div>
          <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:14px 16px;font-size:13px;color:#b91c1c;line-height:1.6;">
            <i class="bi bi-arrow-counterclockwise"></i> If any amount was deducted, it will be refunded to your account within <strong>3-5 business days</strong>. Please contact support if you face any delay.
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ================= ACTIONS ================= -->
    <div style="display:flex;gap:10px;margin-top:18px;flex-wrap:wrap;">
      <a href="orders.php" class="btn-red" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;flex:1;justify-content:center;padding:12px;font-size:14px;min-width:150px;"><i class="bi bi-box"></i> Track My Order</a>
      <a href="shop.php" class="btn-outline-red" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;flex:1;justify-content:center;padding:12px;font-size:14px;min-width:150px;"><i class="bi bi-bag"></i> Continue Shopping</a>
    </div>
    <p style="text-align:center;font-size:12px;color:#c4c4c4;margin-top:14px;"><i class="bi bi-shield-check"></i> Secure payment processed via EasyPay</p>

  <?php else: ?>

    <!-- ================= ORDER NOT FOUND ================= -->
    <div class="pc-card" style="text-align:center;padding:52px 24px;">
      <div class="pc-icon-wrap" style="background:#f5f5f5;border:2px solid #eee;">
        <svg viewBox="0 0 52 52">
          <circle class="pc-dash" cx="26" cy="26" r="23" fill="none" stroke="#c4c4c4" stroke-width="3.5" stroke-linecap="round"/>
          <path class="pc-dash-late" fill="none" stroke="#c4c4c4" stroke-width="3.5" stroke-linecap="round" d="M26 17v11M26 33.5v.01"/>
        </svg>
      </div>
      <h5 style="margin-bottom:8px;font-weight:800;">Order Not Found</h5>
      <p style="color:#9ca3af;font-size:13.5px;margin-bottom:24px;max-width:340px;margin-left:auto;margin-right:auto;line-height:1.6;">
        We couldn't verify this order. Check your order history or contact support if you made a payment.
      </p>
      <div style="display:flex;gap:10px;flex-wrap:wrap;justify-content:center;">
        <a href="orders.php" class="btn-red" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;padding:11px 26px;font-size:14px;"><i class="bi bi-box"></i> My Orders</a>
        <a href="index.php" class="btn-outline-red" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;padding:11px 26px;font-size:14px;"><i class="bi bi-house-door"></i> Go to Home</a>
      </div>
    </div>

  <?php endif; ?>
</div>

<?php if ($order && $is_success): ?>
<script>
(function() {
  var sec = 10;
  var el = document.getElementById('pcCount');
  var t = setInterval(function() {
    sec--;
    if (el) el.textContent = sec;
    if (sec <= 0) {
      clearInterval(t);
      window.location.href = 'orders.php';
    }
  }, 1000);
})();
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
