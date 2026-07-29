<?php
$page_title = 'Add Banner';
require_once 'includes/header.php';

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $title = mysqli_real_escape_string($conn, $_POST['title']);
  $subtitle = mysqli_real_escape_string($conn, $_POST['subtitle']);
  $description = mysqli_real_escape_string($conn, $_POST['description']);
  $link = mysqli_real_escape_string($conn, $_POST['link']);
  $sort_order = (int)$_POST['sort_order'];
  $status = isset($_POST['status']) ? 1 : 0;
  $image = '';

  if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','avif'];
    if (in_array($ext, $allowed)) {
      $name = 'banner_' . time() . '_' . rand(100,999) . '.' . $ext;
      $dest = '../uploads/banners/' . $name;
      if (!is_dir('../uploads/banners')) mkdir('../uploads/banners', 0777, true);
      move_uploaded_file($_FILES['image']['tmp_name'], $dest);
      $image = 'uploads/banners/' . $name;
    } else {
      $error = 'Invalid image format. Allowed: jpg, jpeg, png, webp, avif';
    }
  }

  if (!$error) {
    $q = mysqli_query($conn, "INSERT INTO banners (title, subtitle, description, link, image, sort_order, status) VALUES ('$title', '$subtitle', '$description', '$link', '$image', $sort_order, $status)");
    if ($q) {
      header('Location: banners.php?success=Banner added');
      exit;
    } else {
      $error = 'Failed: ' . mysqli_error($conn);
    }
  }
}
?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="form-card">
      <?php if ($error): ?>
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:var(--radius-sm);padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;color:#dc2626;font-size:13px;font-weight:500;"><i class="bi bi-exclamation-circle" style="font-size:18px;"></i> <?php echo $error; ?></div>
      <?php endif; ?>
      <form method="POST" enctype="multipart/form-data">
        <div class="row g-4">
          <div class="col-md-6">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Fashion Sale" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Subtitle</label>
            <input type="text" name="subtitle" class="form-control" placeholder="e.g. Up to 70% off" value="<?php echo htmlspecialchars($_POST['subtitle'] ?? ''); ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Description <small style="color:var(--text-muted);">(optional - shown as paragraph on banner)</small></label>
            <textarea name="description" class="form-control" rows="2" placeholder="e.g. Shop our latest collection with up to 70% off..." style="font-size:14px;"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Link URL</label>
            <input type="text" name="link" class="form-control" placeholder="shop.php or product-detail.php?id=5" value="<?php echo htmlspecialchars($_POST['link'] ?? 'shop.php'); ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Sort Order</label>
            <input type="number" name="sort_order" class="form-control" value="<?php echo (int)($_POST['sort_order'] ?? 0); ?>" min="0">
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <div class="form-check">
              <input type="checkbox" name="status" class="form-check-input" id="status" checked style="accent-color:var(--red);">
              <label class="form-check-label" for="status" style="font-size:14px;">Active</label>
            </div>
          </div>
          <div class="col-12">
            <label class="form-label">Banner Image <small style="color:var(--text-muted);">(1920 × 500 recommended)</small></label>
            <input type="file" name="image" class="form-control" accept="image/*">
          </div>
        </div>
        <hr style="border-color:var(--border-color);margin:24px 0;">
        <button type="submit" class="btn-red"><i class="bi bi-plus-lg"></i> Add Banner</button>
        <a href="banners.php" class="btn btn-outline-secondary" style="border-radius:50px;padding:8px 24px;font-weight:600;font-size:14px;text-decoration:none;margin-left:8px;">Cancel</a>
      </form>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
