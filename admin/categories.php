<?php
$page_title = 'Categories';
require_once 'includes/header.php';

if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  if (mysqli_query($conn, "DELETE FROM categories WHERE id=$id")) {
    header('Location: categories.php?success=Category+deleted');
  } else {
    header('Location: categories.php?error=Delete+failed');
  }
  exit;
}

if (isset($_GET['toggle'])) {
  $id = (int)$_GET['toggle'];
  mysqli_query($conn, "UPDATE categories SET status = IF(status=1, 0, 1) WHERE id=$id");
  header('Location: categories.php?success=Status+updated');
  exit;
}

$categories_q = mysqli_query($conn, "SELECT * FROM categories ORDER BY id DESC");
$categories = $categories_q ? $categories_q : false;
?>

<div class="table-card">
  <div class="table-header">
    <h5><i class="bi bi-tags-fill me-2" style="color:var(--red);"></i>All Categories</h5>
    <div class="table-actions">
      <div class="search-wrap">
        <i class="bi bi-search"></i>
        <input type="text" class="search-input" placeholder="Search categories..." onkeyup="filterTable(this)">
      </div>
      <a href="add_category.php" class="btn-red btn-sm"><i class="bi bi-plus-lg"></i> Add Category</a>
    </div>
  </div>
  <div class="table-responsive">
    <table id="dataTable">
      <thead>
        <tr>
          <th style="width:50px;">#</th>
          <th>Name</th>
          <th>Description</th>
          <th>Status</th>
          <th style="width:120px;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($categories && mysqli_num_rows($categories) > 0): ?>
          <?php $i = 1; while ($c = mysqli_fetch_assoc($categories)): ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td><strong><?php echo $c['name']; ?></strong></td>
              <td><small style="color:var(--text-muted);"><?php echo substr($c['description'] ?? '', 0, 60); ?></small></td>
              <td>
                <a href="?toggle=<?php echo $c['id']; ?>" style="text-decoration:none;color:inherit;">
                  <span class="badge-status bg-<?php echo $c['status'] ? 'success' : 'secondary'; ?>" style="cursor:pointer;">
                    <span class="dot"></span> <?php echo $c['status'] ? 'Active' : 'Inactive'; ?>
                  </span>
                </a>
              </td>
              <td>
                <div style="display:flex;gap:6px;">
                  <a href="edit_category.php?id=<?php echo $c['id']; ?>" class="btn-outline-secondary btn-sm" style="padding:5px 10px;"><i class="bi bi-pencil"></i></a>
                  <a href="?delete=<?php echo $c['id']; ?>" class="btn-outline-secondary btn-sm" style="padding:5px 10px;color:#dc2626;border-color:#fecaca;" onclick="return confirm('Delete this category?')"><i class="bi bi-trash"></i></a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5"><div class="empty-state"><div class="empty-icon"><i class="bi bi-tags"></i></div><h6>No categories found</h6><p>Create your first category to organize products.</p><a href="add_category.php" class="btn-red btn-sm"><i class="bi bi-plus-lg"></i> Add Category</a></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="table-footer">
    <span><?php echo $categories && mysqli_num_rows($categories) > 0 ? mysqli_num_rows($categories) . ' categories' : '0 categories'; ?></span>
  </div>
</div>

<script>
function filterTable(input) {
  var val = input.value.toLowerCase();
  var rows = document.querySelectorAll('#dataTable tbody tr');
  rows.forEach(function(row) {
    var text = row.textContent.toLowerCase();
    row.style.display = text.indexOf(val) > -1 ? '' : 'none';
  });
}
</script>

<?php require_once 'includes/footer.php'; ?>
