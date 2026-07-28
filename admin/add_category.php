<?php
$page_title = 'Add Category';
require_once 'includes/header.php';

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $name = mysqli_real_escape_string($conn, $_POST['name']);
  $desc = mysqli_real_escape_string($conn, $_POST['description']);

    if (mysqli_query($conn, "INSERT INTO categories (name, description) VALUES ('$name', '$desc')")) {
      header('Location: categories.php?success=Category+added+successfully');
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
          <input type="text" name="name" class="form-control" placeholder="e.g. Western Wear" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Brief description of this category"></textarea>
        </div>
        <div style="display:flex;gap:10px;">
          <button type="submit" class="btn-red"><i class="bi bi-check-lg"></i> Save Category</button>
          <a href="categories.php" class="btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
