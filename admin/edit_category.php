<?php
$page_title = 'Edit Category';
require_once 'includes/header.php';

global $conn;
$id = (int)$_GET['id'];
$cat_q = mysqli_query($conn, "SELECT * FROM categories WHERE id=$id");
$cat = $cat_q ? mysqli_fetch_assoc($cat_q) : false;
if (!$cat) { header('Location: categories.php?error=Category+not+found'); exit; }

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $name = mysqli_real_escape_string($conn, $_POST['name']);
  $desc = mysqli_real_escape_string($conn, $_POST['description']);

    if (mysqli_query($conn, "UPDATE categories SET name='$name', description='$desc' WHERE id=$id")) {
      header('Location: categories.php?success=Category+updated');
      exit;
    } else {
      $error = 'Database error: ' . mysqli_error($conn);
    }
  }
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="form-card">
      <?php if ($error): ?>
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:var(--radius-sm);padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;color:#dc2626;font-size:13px;font-weight:500;">
          <i class="bi bi-exclamation-circle" style="font-size:18px;"></i> <?php echo $error; ?>
        </div>
      <?php endif; ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Category Name</label>
          <input type="text" name="name" class="form-control" value="<?php echo $cat['name']; ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3"><?php echo $cat['description']; ?></textarea>
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
