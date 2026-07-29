<?php
$page_title = 'Edit Banner';
require_once 'includes/header.php';

$id = (int)($_GET['id'] ?? 0);

// Delete
if (isset($_GET['delete'])) {
  $did = (int)$_GET['delete'];
  $dq = mysqli_query($conn, "SELECT image FROM banners WHERE id=$did");
  if ($dq && $dr = mysqli_fetch_assoc($dq)) {
    if ($dr['image'] && file_exists('../' . $dr['image'])) unlink('../' . $dr['image']);
  }
  mysqli_query($conn, "DELETE FROM banners WHERE id=$did");
  header('Location: banners.php?success=Banner deleted');
  exit;
}

if (!$id) { header('Location: banners.php'); exit; }
$q = mysqli_query($conn, "SELECT * FROM banners WHERE id=$id");
$b = $q ? mysqli_fetch_assoc($q) : null;
if (!$b) { header('Location: banners.php'); exit; }

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $title = mysqli_real_escape_string($conn, $_POST['title']);
  $subtitle = mysqli_real_escape_string($conn, $_POST['subtitle']);
  $description = mysqli_real_escape_string($conn, $_POST['description']);
  $link = mysqli_real_escape_string($conn, $_POST['link']);
  $sort_order = (int)$_POST['sort_order'];
  $status = isset($_POST['status']) ? 1 : 0;
  $image = $b['image'];

  if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','avif'];
    if (in_array($ext, $allowed)) {
      if ($image && file_exists('../' . $image)) unlink('../' . $image);
      $name = 'banner_' . time() . '_' . rand(100,999) . '.' . $ext;
      $dest = '../uploads/banners/' . $name;
      if (!is_dir('../uploads/banners')) mkdir('../uploads/banners', 0777, true);
      move_uploaded_file($_FILES['image']['tmp_name'], $dest);
      $image = 'uploads/banners/' . $name;
    } else {
      $error = 'Invalid image format';
    }
  }

  if (!$error) {
    $q = mysqli_query($conn, "UPDATE banners SET title='$title', subtitle='$subtitle', description='$description', link='$link', image='$image', sort_order=$sort_order, status=$status WHERE id=$id");
    if ($q) {
      header('Location: banners.php?success=Banner updated');
      exit;
    } else {
      $error = 'Failed: ' . mysqli_error($conn);
    }
  }
}

$img = $b['image'] && file_exists('../' . $b['image']) ? '../' . $b['image'] : '';
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
            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($b['title'] ?? ''); ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Subtitle</label>
            <input type="text" name="subtitle" class="form-control" value="<?php echo htmlspecialchars($b['subtitle'] ?? ''); ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Description <small style="color:var(--text-muted);">(optional - shown as paragraph on banner)</small></label>
            <textarea name="description" class="form-control" rows="2" style="font-size:14px;"><?php echo htmlspecialchars($b['description'] ?? ''); ?></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Link URL</label>
            <input type="text" name="link" class="form-control" value="<?php echo htmlspecialchars($b['link'] ?? 'shop.php'); ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Sort Order</label>
            <input type="number" name="sort_order" class="form-control" value="<?php echo (int)$b['sort_order']; ?>" min="0">
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <div class="form-check">
              <input type="checkbox" name="status" class="form-check-input" id="status" <?php echo $b['status'] ? 'checked' : ''; ?> style="accent-color:var(--red);">
              <label class="form-check-label" for="status" style="font-size:14px;">Active</label>
            </div>
          </div>
          <div class="col-12">
            <label class="form-label">Banner Image <small style="color:var(--text-muted);">(leave empty to keep current)</small></label>
            <?php if ($img): ?>
              <div style="margin-bottom:8px;">
                <img src="<?php echo $img; ?>" style="max-height:120px;border-radius:8px;border:1px solid var(--border-color);">
              </div>
            <?php endif; ?>
            <input type="file" name="image" class="form-control" accept="image/*">
          </div>
        </div>
        <hr style="border-color:var(--border-color);margin:24px 0;">
        <button type="submit" class="btn-red"><i class="bi bi-check-lg"></i> Update Banner</button>
        <a href="banners.php" class="btn btn-outline-secondary" style="border-radius:50px;padding:8px 24px;font-weight:600;font-size:14px;text-decoration:none;margin-left:8px;">Cancel</a>
      </form>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
