<?php
$page_title = 'Edit Category';
require_once 'includes/header.php';

$id = (int)$_GET['id'];
$cat_q = mysqli_query($conn, "SELECT * FROM categories WHERE id=$id");
$cat = $cat_q ? mysqli_fetch_assoc($cat_q) : false;
if (!$cat) { header('Location: categories.php?error=Category+not+found'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $name = mysqli_real_escape_string($conn, $_POST['name']);
  $desc = mysqli_real_escape_string($conn, $_POST['description']);
  $image = $cat['image'];

  if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','webp','avif'])) {
      if ($image && file_exists('../' . $image)) unlink('../' . $image);
      $name_f = 'cat_' . time() . '_' . rand(100,999) . '.' . $ext;
      $dest = '../uploads/categories/' . $name_f;
      if (!is_dir('../uploads/categories')) mkdir('../uploads/categories', 0777, true);
      move_uploaded_file($_FILES['image']['tmp_name'], $dest);
      $image = 'uploads/categories/' . $name_f;
    } else {
      $error = 'Invalid image format';
    }
  }

  if (!$error) {
    if (mysqli_query($conn, "UPDATE categories SET name='$name', description='$desc', image='$image' WHERE id=$id")) {
      header('Location: categories.php?success=Category+updated');
      exit;
    } else {
      $error = 'Database error: ' . mysqli_error($conn);
    }
  }
}
$img = $cat['image'] && file_exists('../' . $cat['image']) ? '../' . $cat['image'] : '';
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="form-card">
      <?php if ($error): ?>
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:var(--radius-sm);padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;color:#dc2626;font-size:13px;font-weight:500;">
          <i class="bi bi-exclamation-circle" style="font-size:18px;"></i> <?php echo $error; ?>
        </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label">Category Name</label>
          <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($cat['name']); ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($cat['description'] ?? ''); ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Category Image <small style="color:var(--text-muted);">(leave empty to keep current)</small></label>
          <?php if ($img): ?>
            <div style="margin-bottom:8px;">
              <img src="<?php echo $img; ?>" style="height:80px;border-radius:8px;border:1px solid var(--border-color);">
            </div>
          <?php endif; ?>
          <input type="file" name="image" class="form-control" accept="image/*">
        </div>
        <div style="display:flex;gap:10px;">
          <button type="submit" class="btn-red"><i class="bi bi-check-lg"></i> Update Category</button>
          <a href="categories.php" class="btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
