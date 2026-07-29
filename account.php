<?php require_once 'includes/config.php'; include 'includes/header.php';

if (!isset($_SESSION['user'])) {
  header('Location: login.php');
  exit;
}

$user = $_SESSION['user'];

// Update profile
$error = $success = '';
if (isset($_POST['update'])) {
  $name = mysqli_real_escape_string($conn, $_POST['name']);
  $phone = mysqli_real_escape_string($conn, $_POST['phone']);
  $q = mysqli_query($conn, "UPDATE users SET name='$name', phone='$phone' WHERE id={$user['id']}");
  if ($q) {
    $user['name'] = $name;
    $user['phone'] = $phone;
    $_SESSION['user'] = $user;
    $success = 'Profile updated';
  } else {
    $error = 'Update failed';
  }
}

// Add address
if (isset($_POST['add_address'])) {
  $label = mysqli_real_escape_string($conn, $_POST['label']);
  $address = mysqli_real_escape_string($conn, $_POST['address']);
  $pincode = preg_match('/^\d{6}$/', $_POST['pincode'] ?? '') ? $_POST['pincode'] : '';
  $is_def = isset($_POST['is_default']) ? 1 : 0;
  if ($is_def) mysqli_query($conn, "UPDATE addresses SET is_default=0 WHERE user_id={$user['id']}");
  mysqli_query($conn, "INSERT INTO addresses (user_id, label, address, pincode, is_default) VALUES ({$user['id']}, '$label', '$address', '$pincode', $is_def)");
  $success = 'Address added';
}

// Edit address
if (isset($_POST['edit_address'])) {
  $aid = (int)$_POST['address_id'];
  $label = mysqli_real_escape_string($conn, $_POST['label']);
  $address = mysqli_real_escape_string($conn, $_POST['address']);
  $pincode = preg_match('/^\d{6}$/', $_POST['pincode'] ?? '') ? $_POST['pincode'] : '';
  $is_def = isset($_POST['is_default']) ? 1 : 0;
  if ($is_def) mysqli_query($conn, "UPDATE addresses SET is_default=0 WHERE user_id={$user['id']}");
  mysqli_query($conn, "UPDATE addresses SET label='$label', address='$address', pincode='$pincode', is_default=$is_def WHERE id=$aid AND user_id={$user['id']}");
  $success = 'Address updated';
}

// Delete address
if (isset($_GET['delete_address'])) {
  $aid = (int)$_GET['delete_address'];
  mysqli_query($conn, "DELETE FROM addresses WHERE id=$aid AND user_id={$user['id']}");
  header('Location: account.php?tab=addresses');
  exit;
}

// Set default address
if (isset($_GET['default_address'])) {
  $aid = (int)$_GET['default_address'];
  mysqli_query($conn, "UPDATE addresses SET is_default=0 WHERE user_id={$user['id']}");
  mysqli_query($conn, "UPDATE addresses SET is_default=1 WHERE id=$aid AND user_id={$user['id']}");
  header('Location: account.php?tab=addresses');
  exit;
}

$addr_q = mysqli_query($conn, "SELECT * FROM addresses WHERE user_id={$user['id']} ORDER BY is_default DESC, id DESC");
$addresses = $addr_q ? $addr_q : false;

$orders_q = mysqli_query($conn, "SELECT * FROM orders WHERE user_id={$user['id']} ORDER BY created_at DESC LIMIT 10");
$orders = $orders_q ? $orders_q : false;
?>
<div class="container py-3 py-md-5">
  <h4 style="font-weight:800;margin-bottom:24px;">My Account</h4>

  <?php if ($error): ?>
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:16px;color:#dc2626;font-size:13px;font-weight:500;"><?php echo $error; ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:16px;color:#16a34a;font-size:13px;font-weight:500;"><?php echo $success; ?></div>
  <?php endif; ?>

  <div class="row g-4 g-md-5">
    <div class="col-md-4 col-lg-3">
      <div class="ac-sidebar">
        <div class="ac-user">
          <div class="ac-avatar"><?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?></div>
          <div>
            <div class="ac-name"><?php echo $user['name']; ?></div>
            <div class="ac-email"><?php echo $user['email']; ?></div>
          </div>
        </div>
        <div class="ac-nav">
          <a href="?tab=profile" class="ac-nav-item <?php echo (!isset($_GET['tab']) || $_GET['tab']=='profile') ? 'active' : ''; ?>" onclick="switchTab(event,'profile')"><i class="bi bi-person"></i> My Profile</a>
          <a href="?tab=orders" class="ac-nav-item <?php echo (isset($_GET['tab']) && $_GET['tab']=='orders') ? 'active' : ''; ?>" onclick="switchTab(event,'orders')"><i class="bi bi-box"></i> My Orders</a>
          <a href="?tab=addresses" class="ac-nav-item <?php echo (isset($_GET['tab']) && $_GET['tab']=='addresses') ? 'active' : ''; ?>" onclick="switchTab(event,'addresses')"><i class="bi bi-geo-alt"></i> My Addresses</a>
          <a href="logout.php" class="ac-nav-item" style="color:#dc2626;"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
        </div>
      </div>
    </div>
    <div class="col-md-8 col-lg-9">
      <!-- Profile Tab -->
      <div id="tab-profile" class="ac-content" style="display:<?php echo (!isset($_GET['tab']) || $_GET['tab']=='profile') ? 'block' : 'none'; ?>;">
        <div class="ac-card">
          <h6 class="ac-card-title"><i class="bi bi-pencil-square"></i> Edit Profile</h6>
          <form method="POST">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="ac-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo $user['name']; ?>" required>
              </div>
              <div class="col-md-6">
                <label class="ac-label">Email</label>
                <input type="email" class="form-control" value="<?php echo $user['email']; ?>" disabled style="background:#f5f5f5;">
              </div>
              <div class="col-md-6">
                <label class="ac-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?php echo $user['phone']; ?>">
              </div>
              <div class="col-12">
                <button type="submit" name="update" class="btn-red"><i class="bi bi-check"></i> Save Changes</button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Orders Tab -->
      <div id="tab-orders" class="ac-content" style="display:<?php echo (isset($_GET['tab']) && $_GET['tab']=='orders') ? 'block' : 'none'; ?>;">
        <div class="ac-card">
          <h6 class="ac-card-title"><i class="bi bi-box"></i> Recent Orders</h6>
          <?php if ($orders && mysqli_num_rows($orders) > 0): ?>
            <?php while ($o = mysqli_fetch_assoc($orders)): ?>
              <div class="ac-order-item">
                <div>
                  <div class="ac-order-id">#<?php echo $o['order_number']; ?></div>
                  <div class="ac-order-date"><?php echo date('d M Y', strtotime($o['created_at'])); ?></div>
                </div>
                <div class="text-end">
                  <div class="ac-order-amount">₹<?php echo number_format($o['total_amount']); ?></div>
                  <span class="ac-order-status <?php echo $o['order_status']; ?>"><?php echo ucfirst($o['order_status']); ?></span>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <p class="ac-empty">No orders yet</p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Addresses Tab -->
      <div id="tab-addresses" class="ac-content" style="display:<?php echo (isset($_GET['tab']) && $_GET['tab']=='addresses') ? 'block' : 'none'; ?>;">
        <div class="ac-card">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h6 class="ac-card-title" style="margin:0;"><i class="bi bi-geo-alt"></i> Saved Addresses</h6>
            <button class="btn-red btn-sm" id="addAddrBtn" onclick="document.getElementById('addAddrForm').style.display='block';this.style.display='none'"><i class="bi bi-plus-lg"></i> Add New</button>
          </div>

          <div id="addAddrForm" style="display:none;background:#f8f9fa;border:1px solid #eee;border-radius:10px;padding:20px;margin-bottom:16px;">
            <form method="POST">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="ac-label">Label</label>
                  <select name="label" class="form-control" style="font-size:13px;">
                    <option value="Home">Home</option>
                    <option value="Work">Work</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
                <div class="col-md-8">
                  <label class="ac-label">Address</label>
                  <textarea name="address" class="form-control" rows="2" required placeholder="Street, area, city..." style="font-size:13px;"></textarea>
                </div>
                <div class="col-md-4">
                  <label class="ac-label">PIN Code</label>
                  <input type="text" name="pincode" class="form-control" maxlength="6" placeholder="6-digit code" pattern="\d{6}" required style="font-size:13px;">
                </div>
                <div class="col-md-8" style="display:flex;align-items:center;gap:8px;padding-top:24px;">
                  <input type="checkbox" name="is_default" id="add_default" style="accent-color:var(--red);">
                  <label for="add_default" style="font-size:12px;color:#555;cursor:pointer;">Set as default</label>
                </div>
                <div class="col-12" style="display:flex;gap:8px;">
                  <button type="submit" name="add_address" class="btn-red btn-sm">Save</button>
                  <button type="button" class="btn-outline-red btn-sm" onclick="document.getElementById('addAddrForm').style.display='none';document.getElementById('addAddrBtn').style.display=''">Cancel</button>
                </div>
              </div>
            </form>
          </div>

          <?php if ($addresses && mysqli_num_rows($addresses) > 0): ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px;">
              <?php while ($a = mysqli_fetch_assoc($addresses)): ?>
                <div class="ac-addr-card">
                  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                    <div>
                      <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                        <span class="ac-label-badge"><?php echo $a['label']; ?></span>
                        <?php if ($a['is_default']): ?>
                          <span class="ac-default-badge">Default</span>
                        <?php endif; ?>
                      </div>
                      <p style="margin:0;color:#555;font-size:13px;line-height:1.5;white-space:pre-line;"><?php echo htmlspecialchars($a['address']); ?></p>
                      <?php if ($a['pincode']): ?><p style="margin:4px 0 0;color:#888;font-size:12px;">PIN: <strong><?php echo $a['pincode']; ?></strong></p><?php endif; ?>
                    </div>
                  </div>
                  <div style="display:flex;gap:6px;margin-top:10px;padding-top:10px;border-top:1px solid #f0f0f0;">
                    <button class="btn-outline-secondary btn-sm" style="padding:4px 10px;font-size:11px;" onclick="editAddr(<?php echo $a['id']; ?>, '<?php echo htmlspecialchars($a['label'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($a['address'], ENT_QUOTES); ?>', <?php echo $a['is_default']; ?>, '<?php echo $a['pincode']; ?>')"><i class="bi bi-pencil"></i> Edit</button>
                    <?php if (!$a['is_default']): ?>
                      <a href="?default_address=<?php echo $a['id']; ?>&tab=addresses" class="btn-outline-secondary btn-sm" style="padding:4px 10px;font-size:11px;" title="Set as default"><i class="bi bi-check-lg"></i> Set Default</a>
                    <?php endif; ?>
                    <a href="?delete_address=<?php echo $a['id']; ?>&tab=addresses" class="btn btn-sm" style="padding:4px 10px;font-size:11px;color:#dc2626;background:#fef2f2;border:1px solid #fecaca;text-decoration:none;border-radius:6px;" onclick="return confirm('Delete this address?')"><i class="bi bi-trash"></i> Delete</a>
                  </div>
                </div>
              <?php endwhile; ?>
            </div>
          <?php else: ?>
            <p class="ac-empty">No saved addresses yet.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Address Modal -->
<div id="editAddrModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:99999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;" onclick="if(event.target===this)this.style.display='none'">
  <div style="background:#fff;border-radius:12px;padding:24px;width:90%;max-width:420px;margin:20px;">
    <h6 style="font-weight:700;font-size:15px;margin-bottom:14px;">Edit Address</h6>
    <form method="POST" id="editAddrForm">
      <input type="hidden" name="address_id" id="edit_addr_id">
      <div style="margin-bottom:10px;">
        <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:4px;">Label</label>
        <select name="label" id="edit_label" class="form-control" style="font-size:13px;">
          <option value="Home">Home</option>
          <option value="Work">Work</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div style="margin-bottom:10px;">
        <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:4px;">Address</label>
        <textarea name="address" id="edit_address" class="form-control" rows="2" required style="font-size:13px;"></textarea>
      </div>
      <div style="margin-bottom:10px;">
        <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:4px;">PIN Code</label>
        <input type="text" name="pincode" id="edit_pincode" class="form-control" maxlength="6" placeholder="6-digit PIN code" pattern="\d{6}" required style="font-size:13px;width:180px;">
      </div>
      <div style="margin-bottom:10px;display:flex;align-items:center;gap:6px;">
        <input type="checkbox" name="is_default" id="edit_default" style="accent-color:var(--red);">
        <label for="edit_default" style="font-size:12px;color:#555;">Set as default</label>
      </div>
      <div style="display:flex;gap:8px;">
        <button type="submit" name="edit_address" class="btn-red btn-sm">Update</button>
        <button type="button" class="btn-outline-red btn-sm" onclick="document.getElementById('editAddrModal').style.display='none'">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function switchTab(e, tab) {
  e.preventDefault();
  document.querySelectorAll('.ac-content').forEach(function(el) { el.style.display = 'none'; });
  document.getElementById('tab-' + tab).style.display = 'block';
  document.querySelectorAll('.ac-nav-item').forEach(function(el) { el.classList.remove('active'); });
  e.currentTarget.classList.add('active');
  var url = new URL(window.location);
  url.searchParams.set('tab', tab);
  window.history.replaceState({}, '', url);
  window.scrollTo({top:0,behavior:'smooth'});
}

function editAddr(id, label, address, isDefault, pincode) {
  document.getElementById('edit_addr_id').value = id;
  document.getElementById('edit_label').value = label;
  document.getElementById('edit_address').value = address;
  document.getElementById('edit_pincode').value = pincode || '';
  document.getElementById('edit_default').checked = isDefault ? true : false;
  document.getElementById('editAddrModal').style.display = 'flex';
}

// On page load, set active tab from URL
(function() {
  var params = new URLSearchParams(window.location.search);
  var tab = params.get('tab') || 'profile';
  var navItem = document.querySelector('.ac-nav-item[href*="tab=' + tab + '"]');
  if (navItem) navItem.classList.add('active');
  document.querySelectorAll('.ac-content').forEach(function(el) { el.style.display = 'none'; });
  var content = document.getElementById('tab-' + tab);
  if (content) content.style.display = 'block';
})();
</script>

<?php include 'includes/footer.php'; ?>