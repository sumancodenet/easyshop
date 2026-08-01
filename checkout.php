<?php require_once 'includes/config.php'; include 'includes/header.php';

$pf_p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT `value` FROM settings WHERE `key`='platform_fee_percent'"))['value'] ?? 5;
$pf_f = mysqli_fetch_assoc(mysqli_query($conn, "SELECT `value` FROM settings WHERE `key`='platform_fee_fixed'"))['value'] ?? 0;

// Normalize legacy cart (same as cart.php)
if (!empty($_SESSION['cart'])) {
  $normalized = [];
  foreach ($_SESSION['cart'] as $key => $item) {
    if (is_numeric($key) && isset($item['qty']) && !isset($item['product_id'])) {
      $new_key = $key . '_0';
      $normalized[$new_key] = ['product_id' => (int)$key, 'size_id' => 0, 'qty' => $item['qty']];
    } else {
      $normalized[$key] = $item;
    }
  }
  $_SESSION['cart'] = $normalized;
}

$cart_items = $_SESSION['cart'] ?? [];
$products = [];
$total = 0;
$is_buy_now = false;

if (isset($_GET['buy_now'])) {
  $is_buy_now = true;
  $pid = (int)$_GET['buy_now'];
  $size_id = (int)($_GET['size_id'] ?? 0);
  $q = mysqli_query($conn, "SELECT * FROM products WHERE id=$pid AND status=1");
  if ($q && $row = mysqli_fetch_assoc($q)) {
    $row['cart_qty'] = 1;
    $row['cart_size_id'] = $size_id;
    $products[] = $row;
    $total = $row['price'];
  }
} elseif (count($cart_items) > 0) {
  $ids = [];
  foreach ($cart_items as $key => $item) {
    $ids[] = (int)$item['product_id'];
  }
  $ids = array_unique($ids);
  if (count($ids) > 0) {
    $ids_str = implode(',', $ids);
    $q = mysqli_query($conn, "SELECT * FROM products WHERE id IN ($ids_str) AND status=1");
    if ($q) {
      $product_map = [];
      while ($row = mysqli_fetch_assoc($q)) {
        $product_map[$row['id']] = $row;
      }

      // Load size names
      $size_ids = [];
      foreach ($cart_items as $item) {
        if ($item['size_id']) $size_ids[] = (int)$item['size_id'];
      }
      $size_names = [];
      if (!empty($size_ids)) {
        $size_ids_str = implode(',', array_unique($size_ids));
        $sz_q = mysqli_query($conn, "SELECT * FROM product_sizes WHERE id IN ($size_ids_str)");
        if ($sz_q) {
          while ($sz = mysqli_fetch_assoc($sz_q)) {
            $size_names[$sz['id']] = $sz['size_name'];
          }
        }
      }

      foreach ($cart_items as $key => $item) {
        $pid = (int)$item['product_id'];
        if (isset($product_map[$pid])) {
          $p = $product_map[$pid];
          $p['cart_qty'] = $item['qty'];
          $p['cart_size_id'] = $item['size_id'];
          $p['cart_size_name'] = $item['size_id'] ? ($size_names[$item['size_id']] ?? '') : '';
          $products[] = $p;
          $total += $p['price'] * $p['cart_qty'];
        }
      }
    }
  }
} else {
  header('Location: cart.php');
  exit;
}

$platform_fee = round($total * $pf_p / 100 + $pf_f, 2);
$grand_total = $total + $platform_fee;

$user = $_SESSION['user'] ?? null;
$error = $success = '';

// Fetch saved addresses if logged in
$saved_addresses = false;
if ($user) {
  $aq = mysqli_query($conn, "SELECT * FROM addresses WHERE user_id={$user['id']} ORDER BY is_default DESC, id DESC");
  if ($aq) $saved_addresses = mysqli_fetch_all($aq, MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $name = mysqli_real_escape_string($conn, $_POST['name']);
  $email = mysqli_real_escape_string($conn, $_POST['email']);
  $phone = mysqli_real_escape_string($conn, $_POST['phone']);

  // Address: use selected saved address or textarea input
  $address = '';
  if (!empty($_POST['address_id'])) {
    $aid = (int)$_POST['address_id'];
    $ar = mysqli_query($conn, "SELECT address FROM addresses WHERE id=$aid" . ($user ? " AND user_id={$user['id']}" : ""));
    if ($ar && $a = mysqli_fetch_assoc($ar)) $address = $a['address'];
  }
  if (empty($address) && !empty($_POST['address'])) {
    $address = mysqli_real_escape_string($conn, $_POST['address']);
  }

  // Save as new address?
  if ($user && !empty($_POST['save_address']) && !empty($address)) {
    $pincode = preg_match('/^\d{6}$/', $_POST['pincode'] ?? '') ? $_POST['pincode'] : '';
    mysqli_query($conn, "INSERT INTO addresses (user_id, label, address, pincode, is_default) VALUES ({$user['id']}, 'Other', '$address', '$pincode', 0)");
  }

  $payment = mysqli_real_escape_string($conn, $_POST['payment']);
  $order_no = 'ORD' . strtoupper(uniqid());
  $uid = $user ? $user['id'] : 'NULL';

  $q = "INSERT INTO orders (user_id, order_number, total_amount, payment_method, payment_status, name, email, phone, address) VALUES ($uid, '$order_no', $grand_total, '$payment', '$payment', '$name', '$email', '$phone', '$address')";
  if (mysqli_query($conn, $q)) {
    $oid = mysqli_insert_id($conn);
    foreach ($products as $p) {
      $size_id_sql = $p['cart_size_id'] ? (int)$p['cart_size_id'] : 'NULL';
      mysqli_query($conn, "INSERT INTO order_items (order_id, product_id, size_id, quantity, price) VALUES ($oid, {$p['id']}, $size_id_sql, {$p['cart_qty']}, {$p['price']})");
      mysqli_query($conn, "UPDATE products SET stock = stock - {$p['cart_qty']} WHERE id={$p['id']}");
    }
    unset($_SESSION['cart']);
    $success = $order_no;
  } else {
    $error = 'Order failed: ' . mysqli_error($conn);
  }
}
?>
<div class="container py-4" style="max-width:800px;">
  <h4 style="font-weight:800;margin-bottom:20px;">Checkout</h4>

  <?php if ($error): ?>
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:16px;color:#dc2626;font-size:13px;font-weight:500;"><?php echo $error; ?></div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div style="text-align:center;padding:40px 20px;">
      <i class="bi bi-check-circle" style="font-size:60px;color:#059669;"></i>
      <h5 style="margin-top:16px;font-weight:700;">Order Placed!</h5>
      <p style="color:#666;">Order #<?php echo $success; ?></p>
      <p style="color:#999;font-size:14px;"><?php echo $_POST['payment'] == 'COD' ? 'Pay ₹'.number_format($grand_total).' at delivery' : 'Payment successful'; ?></p>
      <a href="shop.php" class="btn-red" style="text-decoration:none;">Continue Shopping</a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <div class="col-lg-7">
        <div style="background:#fff;border:1px solid #eee;border-radius:12px;padding:20px;">
          <h6 style="font-weight:700;font-size:14px;margin-bottom:14px;">Delivery Details</h6>
          <form method="POST" id="checkoutForm">
            <div style="margin-bottom:12px;">
              <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:4px;">Full Name</label>
              <input type="text" name="name" class="form-control" value="<?php echo $user['name'] ?? ''; ?>" required>
            </div>
            <div style="margin-bottom:12px;">
              <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:4px;">Email</label>
              <input type="email" name="email" class="form-control" value="<?php echo $user['email'] ?? ''; ?>" required>
            </div>
            <div style="margin-bottom:12px;">
              <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:4px;">Phone</label>
              <input type="text" name="phone" class="form-control" value="<?php echo $user['phone'] ?? ''; ?>" required>
            </div>

            <?php if ($saved_addresses && count($saved_addresses) > 0): ?>
              <!-- Saved Addresses (Flipkart-style) -->
              <div style="margin-bottom:12px;">
                <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:8px;">Delivery Address</label>
                <div style="display:flex;flex-direction:column;gap:8px;">
                  <?php foreach ($saved_addresses as $sa): ?>
                    <label class="addr-option" style="display:flex;align-items:flex-start;gap:10px;padding:12px;border:2px solid <?php echo $sa['is_default'] ? '#8B0000' : '#e0e0e0'; ?>;border-radius:10px;cursor:pointer;transition:0.2s;background:<?php echo $sa['is_default'] ? '#fff5f5' : '#fff'; ?>;" onclick="selectAddress(this, <?php echo $sa['id']; ?>)">
                      <input type="radio" name="address_id" value="<?php echo $sa['id']; ?>" style="display:none;" <?php echo $sa['is_default'] ? 'checked' : ''; ?>>
                      <div style="flex:1;font-size:13px;">
                        <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                          <span style="font-weight:700;"><?php echo $sa['label']; ?></span>
                          <?php if ($sa['is_default']): ?><span style="background:#8B0000;color:#fff;padding:0 8px;border-radius:50px;font-size:10px;font-weight:600;">Default</span><?php endif; ?>
                        </div>
                        <p style="margin:0;color:#666;white-space:pre-line;"><?php echo htmlspecialchars($sa['address']); ?></p>
                      </div>
                      <div style="flex-shrink:0;"><i class="bi bi-check-circle" style="font-size:18px;color:<?php echo $sa['is_default'] ? '#8B0000' : '#ddd'; ?>;"></i></div>
                    </label>
                  <?php endforeach; ?>
                </div>
                <button type="button" class="btn-outline-red btn-sm" style="margin-top:8px;" onclick="showNewAddrInput()"><i class="bi bi-plus-lg"></i> Add New Address</button>
              </div>
            <?php else: ?>
              <!-- Manual Address Input -->
              <div style="margin-bottom:12px;">
                <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:4px;">Delivery Address</label>
                <textarea name="address" class="form-control" rows="3" required placeholder="Street, area, city..."><?php echo $user['address'] ?? ''; ?></textarea>
              </div>
              <div style="margin-bottom:12px;">
                <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:4px;">PIN Code</label>
                <input type="text" name="pincode" class="form-control" maxlength="6" placeholder="6-digit PIN code" pattern="\d{6}" required style="width:180px;font-size:13px;">
              </div>
              <?php if ($user): ?>
                <div style="margin-bottom:12px;display:flex;align-items:center;gap:6px;">
                  <input type="checkbox" name="save_address" id="save_address" style="accent-color:#8B0000;">
                  <label for="save_address" style="font-size:12px;color:#555;cursor:pointer;">Save this address for future</label>
                </div>
              <?php endif; ?>
            <?php endif; ?>

            <!-- New address input (shown when user clicks Add New) -->
            <div id="newAddrInput" style="display:none;margin-bottom:12px;">
              <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:4px;">New Address</label>
              <textarea name="address" class="form-control" rows="3" placeholder="Street, area, city..."></textarea>
              <div style="margin-top:8px;">
                <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:4px;">PIN Code</label>
                <input type="text" name="pincode" class="form-control" maxlength="6" placeholder="6-digit PIN code" pattern="\d{6}" required style="width:180px;font-size:13px;">
              </div>
              <?php if ($user): ?>
                <div style="margin-top:8px;display:flex;align-items:center;gap:6px;">
                  <input type="checkbox" name="save_address" id="save_address_new" style="accent-color:#8B0000;">
                  <label for="save_address_new" style="font-size:12px;color:#555;cursor:pointer;">Save this address for future</label>
                </div>
              <?php endif; ?>
            </div>

            <div style="margin-bottom:16px;">
              <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:8px;">Payment Method</label>
              <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <label class="payment-option" style="flex:1;min-width:120px;border:2px solid #059669;border-radius:10px;padding:14px;text-align:center;cursor:pointer;transition:0.2s;background:#f0fdf4;" onclick="selectPayment(this, 'COD')">
                  <input type="radio" name="payment" value="COD" style="display:none;" checked>
                  <i class="bi bi-cash" style="font-size:24px;color:#059669;display:block;margin-bottom:4px;"></i>
                  <span style="font-size:12px;font-weight:600;">Cash on Delivery</span>
                </label>
                <label class="payment-option" style="flex:1;min-width:120px;border:2px solid #e0e0e0;border-radius:10px;padding:14px;text-align:center;cursor:pointer;transition:0.2s;background:#fff;" onclick="selectPayment(this, 'Online')">
                  <input type="radio" name="payment" value="Online" style="display:none;">
                  <i class="bi bi-credit-card" style="font-size:24px;color:#2563eb;display:block;margin-bottom:4px;"></i>
                  <span style="font-size:12px;font-weight:600;">Pay Online</span>
                </label>
              </div>
            </div>
            <button type="submit" class="btn-red" style="width:100%;padding:12px;font-size:15px;" id="placeOrderBtn"><i class="bi bi-lock"></i> Place Order · ₹<?php echo number_format($grand_total); ?></button>
          </form>
        </div>
      </div>
      <div class="col-lg-5">
        <div style="background:#fff;border:1px solid #eee;border-radius:12px;padding:16px;position:sticky;top:80px;">
          <h6 style="font-weight:700;font-size:13px;margin-bottom:12px;">Order Summary</h6>
          <?php foreach ($products as $p): ?>
            <div style="display:flex;gap:10px;padding:8px 0;border-bottom:1px solid #f5f5f5;">
              <div style="width:44px;height:44px;border-radius:6px;overflow:hidden;background:#f5f5f5;flex-shrink:0;">
                <?php if ($p['image']): ?><img src="<?php echo $p['image']; ?>" style="width:100%;height:100%;object-fit:cover;"><?php endif; ?>
              </div>
              <div style="flex:1;font-size:12px;">
                <div style="font-weight:600;"><?php echo $p['name']; ?> × <?php echo $p['cart_qty']; ?></div>
                <?php if (!empty($p['cart_size_name'])): ?>
                  <div style="color:#999;">Size: <?php echo $p['cart_size_name']; ?></div>
                <?php endif; ?>
                <div style="color:#999;">₹<?php echo number_format($p['price'] * $p['cart_qty']); ?></div>
              </div>
            </div>
          <?php endforeach; ?>
          <div style="display:flex;justify-content:space-between;font-size:13px;color:#666;margin-top:8px;">
            <span>Subtotal</span><span>₹<?php echo number_format($total); ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:13px;color:#059669;">
            <span>Delivery</span><span>Free</span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:13px;color:#666;">
            <span>Platform Fee <i class="bi bi-info-circle" style="cursor:help;" title="<?php echo $pf_p;?>% + ₹<?php echo $pf_f;?>"></i></span><span>₹<?php echo number_format($platform_fee); ?></span>
          </div>
          <hr style="border-color:#eee;margin:8px 0;">
          <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:800;">
            <span>Total</span><span>₹<?php echo number_format($grand_total); ?></span>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
function selectPayment(el, val) {
  document.querySelectorAll('.payment-option').forEach(function(o) {
    o.style.borderColor = '#e0e0e0';
    o.style.background = '#fff';
  });
  el.style.borderColor = val === 'COD' ? '#059669' : '#2563eb';
  el.style.background = val === 'COD' ? '#f0fdf4' : '#eff6ff';
  el.querySelector('input[type=radio]').checked = true;
}

function selectAddress(el, id) {
  document.querySelectorAll('.addr-option').forEach(function(o) {
    o.style.borderColor = '#e0e0e0';
    o.style.background = '#fff';
    o.querySelector('i.bi-check-circle').style.color = '#ddd';
  });
  el.style.borderColor = '#8B0000';
  el.style.background = '#fff5f5';
  el.querySelector('i.bi-check-circle').style.color = '#8B0000';
  el.querySelector('input[type=radio]').checked = true;
  document.getElementById('newAddrInput').style.display = 'none';
}

function showNewAddrInput() {
  document.querySelectorAll('.addr-option').forEach(function(o) {
    o.style.borderColor = '#e0e0e0';
    o.style.background = '#fff';
    o.querySelector('i.bi-check-circle').style.color = '#ddd';
    o.querySelector('input[type=radio]').checked = false;
  });
  document.getElementById('newAddrInput').style.display = 'block';
}
</script>

<?php include 'includes/footer.php'; ?>
