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
  $is_def = isset($_POST['is_default']) ? 1 : 0;
  if ($is_def) mysqli_query($conn, "UPDATE addresses SET is_default=0 WHERE user_id={$user['id']}");
  mysqli_query($conn, "INSERT INTO addresses (user_id, label, address, is_default) VALUES ({$user['id']}, '$label', '$address', $is_def)");
  $success = 'Address added';
}

// Edit address
if (isset($_POST['edit_address'])) {
  $aid = (int)$_POST['address_id'];
  $label = mysqli_real_escape_string($conn, $_POST['label']);
  $address = mysqli_real_escape_string($conn, $_POST['address']);
  $is_def = isset($_POST['is_default']) ? 1 : 0;
  if ($is_def) mysqli_query($conn, "UPDATE addresses SET is_default=0 WHERE user_id={$user['id']}");
  mysqli_query($conn, "UPDATE addresses SET label='$label', address='$address', is_default=$is_def WHERE id=$aid AND user_id={$user['id']}");
  $success = 'Address updated';
}

// Delete address
if (isset($_GET['delete_address'])) {
  $aid = (int)$_GET['delete_address'];
  mysqli_query($conn, "DELETE FROM addresses WHERE id=$aid AND user_id={$user['id']}");
  header('Location: account.php');
  exit;
}

// Set default address
if (isset($_GET['default_address'])) {
  $aid = (int)$_GET['default_address'];
  mysqli_query($conn, "UPDATE addresses SET is_default=0 WHERE user_id={$user['id']}");
  mysqli_query($conn, "UPDATE addresses SET is_default=1 WHERE id=$aid AND user_id={$user['id']}");
  header('Location: account.php');
  exit;
}

$addr_q = mysqli_query($conn, "SELECT * FROM addresses WHERE user_id={$user['id']} ORDER BY is_default DESC, id DESC");
$addresses = $addr_q ? $addr_q : false;

$orders_q = mysqli_query($conn, "SELECT * FROM orders WHERE user_id={$user['id']} ORDER BY created_at DESC LIMIT 10");
$orders = $orders_q ? $orders_q : false;
?>
<div class="container py-4">
  <h4 style="font-weight:800;margin-bottom:20px;">My Account</h4>

  <?php if ($error): ?>
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:16px;color:#dc2626;font-size:13px;font-weight:500;"><?php echo $error; ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 16px;margin-bottom:16px;color:#16a34a;font-size:13px;font-weight:500;"><?php echo $success; ?></div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-md-5">
      <div style="background:#fff;border:1px solid #eee;border-radius:12px;padding:20px;">
        <h6 style="font-weight:700;font-size:14px;margin-bottom:14px;">Profile Details</h6>
        <form method="POST">
          <div style="margin-bottom:12px;">
            <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:4px;">Email</label>
            <input type="email" class="form-control" value="<?php echo $user['email']; ?>" disabled style="background:#f5f5f5;">
          </div>
          <div style="margin-bottom:12px;">
            <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:4px;">Full Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo $user['name']; ?>" required>
          </div>
          <div style="margin-bottom:12px;">
            <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:4px;">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?php echo $user['phone']; ?>">
          </div>
          <button type="submit" name="update" class="btn-red" style="width:100%;padding:10px;"><i class="bi bi-check"></i> Update</button>
        </form>
        <hr style="border-color:#eee;margin:16px 0;">
        <a href="logout.php" style="color:#dc2626;text-decoration:none;font-size:13px;font-weight:600;"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
      </div>
    </div>
    <div class="col-md-7">
      <div style="background:#fff;border:1px solid #eee;border-radius:12px;padding:20px;">
        <h6 style="font-weight:700;font-size:14px;margin-bottom:14px;">Recent Orders</h6>
        <?php if ($orders && mysqli_num_rows($orders) > 0): ?>
          <div style="display:flex;flex-direction:column;gap:8px;">
            <?php while ($o = mysqli_fetch_assoc($orders)): ?>
              <div style="display:flex;justify-content:space-between;align-items:center;padding:10px;border:1px solid #f0f0f0;border-radius:8px;font-size:13px;">
                <div>
                  <div style="font-weight:600;">#<?php echo $o['order_number']; ?></div>
                  <div style="color:#999;font-size:11px;"><?php echo date('d M Y', strtotime($o['created_at'])); ?></div>
                </div>
                <div style="text-align:right;">
                  <div style="font-weight:700;">₹<?php echo number_format($o['total_amount']); ?></div>
                  <span style="display:inline-block;padding:2px 8px;border-radius:50px;font-size:10px;font-weight:600;background:<?php echo $o['order_status'] == 'delivered' ? '#f0fdf4' : '#fef9c3'; ?>;color:<?php echo $o['order_status'] == 'delivered' ? '#059669' : '#b45309'; ?>;"><?php echo ucfirst($o['order_status']); ?></span>
                </div>
              </div>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <p style="color:#999;font-size:13px;text-align:center;padding:20px;">No orders yet</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Saved Addresses -->
  <div style="background:#fff;border:1px solid #eee;border-radius:12px;padding:20px;margin-top:20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
      <h6 style="font-weight:700;font-size:14px;margin:0;">Saved Addresses</h6>
      <button class="btn-red btn-sm" onclick="document.getElementById('addAddrForm').style.display='block';this.style.display='none'"><i class="bi bi-plus-lg"></i> Add New</button>
    </div>

    <!-- Add Address Form -->
    <div id="addAddrForm" style="display:none;background:#f8f9fa;border:1px solid #eee;border-radius:8px;padding:16px;margin-bottom:16px;">
      <form method="POST">
        <div style="margin-bottom:10px;">
          <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:4px;">Label</label>
          <select name="label" class="form-control" style="font-size:13px;">
            <option value="Home">Home</option>
            <option value="Work">Work</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div style="margin-bottom:10px;">
          <label style="font-size:12px;font-weight:600;color:#555;display:block;margin-bottom:4px;">Address</label>
          <textarea name="address" class="form-control" rows="2" required placeholder="Street, area, city, pincode..." style="font-size:13px;"></textarea>
        </div>
        <div style="margin-bottom:10px;display:flex;align-items:center;gap:6px;">
          <input type="checkbox" name="is_default" id="add_default" style="accent-color:var(--red);">
          <label for="add_default" style="font-size:12px;color:#555;">Set as default</label>
        </div>
        <div style="display:flex;gap:8px;">
          <button type="submit" name="add_address" class="btn-red btn-sm">Save</button>
          <button type="button" class="btn-outline-red btn-sm" onclick="document.getElementById('addAddrForm').style.display='none';document.querySelector('[onclick*=\\'addAddrForm\\']').style.display=''">Cancel</button>
        </div>
      </form>
    </div>

    <?php if ($addresses && mysqli_num_rows($addresses) > 0): ?>
      <div style="display:flex;flex-direction:column;gap:10px;">
        <?php while ($a = mysqli_fetch_assoc($addresses)): ?>
          <div style="display:flex;justify-content:space-between;align-items:flex-start;padding:14px;border:1px solid #eee;border-radius:10px;font-size:13px;">
            <div style="flex:1;">
              <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                <span style="font-weight:700;"><?php echo $a['label']; ?></span>
                <?php if ($a['is_default']): ?>
                  <span style="background:var(--red);color:#fff;padding:1px 8px;border-radius:50px;font-size:10px;font-weight:600;">Default</span>
                <?php endif; ?>
              </div>
              <p style="margin:0;color:#666;font-size:13px;white-space:pre-line;"><?php echo htmlspecialchars($a['address']); ?></p>
            </div>
            <div style="display:flex;gap:6px;flex-shrink:0;margin-left:12px;">
              <button class="btn-outline-secondary btn-sm" style="padding:4px 8px;font-size:11px;" onclick="editAddr(<?php echo $a['id']; ?>, '<?php echo htmlspecialchars($a['label'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($a['address'], ENT_QUOTES); ?>', <?php echo $a['is_default']; ?>)"><i class="bi bi-pencil"></i></button>
              <?php if (!$a['is_default']): ?>
                <a href="?default_address=<?php echo $a['id']; ?>" class="btn-outline-secondary btn-sm" style="padding:4px 8px;font-size:11px;" title="Set as default"><i class="bi bi-check-lg"></i></a>
              <?php endif; ?>
              <a href="?delete_address=<?php echo $a['id']; ?>" class="btn-outline-secondary btn-sm" style="padding:4px 8px;font-size:11px;color:#dc2626;border-color:#fecaca;" onclick="return confirm('Delete this address?')"><i class="bi bi-trash"></i></a>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <p style="color:#999;font-size:13px;text-align:center;padding:20px;">No saved addresses yet.</p>
    <?php endif; ?>
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
function editAddr(id, label, address, isDefault) {
  document.getElementById('edit_addr_id').value = id;
  document.getElementById('edit_label').value = label;
  document.getElementById('edit_address').value = address;
  document.getElementById('edit_default').checked = isDefault ? true : false;
  document.getElementById('editAddrModal').style.display = 'flex';
}
</script>

<?php include 'includes/footer.php'; ?>