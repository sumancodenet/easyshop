<?php
$page_title = 'Reviews';
require_once 'includes/header.php';

// Toggle review status
if (isset($_GET['toggle'])) {
  $rid = (int)$_GET['toggle'];
  $q = mysqli_query($conn, "SELECT status FROM reviews WHERE id=$rid");
  if ($q && $r = mysqli_fetch_assoc($q)) {
    $ns = $r['status'] ? 0 : 1;
    mysqli_query($conn, "UPDATE reviews SET status=$ns WHERE id=$rid");
  }
  header('Location: reviews.php');
  exit;
}

// Delete review
if (isset($_GET['delete'])) {
  $rid = (int)$_GET['delete'];
  mysqli_query($conn, "DELETE FROM reviews WHERE id=$rid");
  header('Location: reviews.php?success=Review+deleted');
  exit;
}

$q = mysqli_query($conn, "SELECT r.*, p.name as product_name, u.name as user_name, u.email as user_email FROM reviews r LEFT JOIN products p ON r.product_id=p.id LEFT JOIN users u ON r.user_id=u.id ORDER BY r.id DESC");
$reviews = $q ? $q : false;

$success = $_GET['success'] ?? '';
?>
<div style="margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
  <h5 style="margin:0;font-weight:700;">All Reviews</h5>
</div>

<?php if ($success): ?>
  <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:var(--radius-sm);padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;color:#16a34a;font-size:13px;font-weight:500;"><i class="bi bi-check-circle" style="font-size:18px;"></i> <?php echo $success; ?></div>
<?php endif; ?>

<div class="table-card">
  <div class="table-header">
    <div class="table-search">
      <i class="bi bi-search"></i>
      <input type="text" id="searchInput" placeholder="Search reviews..." onkeyup="filterTable()">
    </div>
  </div>
  <div class="table-responsive">
    <table class="admin-table" id="dataTable">
      <thead>
        <tr>
          <th>Product</th>
          <th>User</th>
          <th>Rating</th>
          <th>Review</th>
          <th>Date</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($reviews && mysqli_num_rows($reviews) > 0):
          while ($r = mysqli_fetch_assoc($reviews)): ?>
            <tr>
              <td><div style="font-weight:600;font-size:13px;"><?php echo $r['product_name'] ?? 'Deleted'; ?></div></td>
              <td>
                <div style="font-weight:600;font-size:13px;"><?php echo $r['user_name'] ?? 'Deleted'; ?></div>
                <div style="font-size:11px;color:var(--text-muted);"><?php echo $r['user_email']; ?></div>
              </td>
              <td>
                <div style="display:flex;gap:2px;">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi bi-star<?php echo $i <= $r['rating'] ? '-fill' : ''; ?>" style="color:<?php echo $i <= $r['rating'] ? '#f59e0b' : '#ddd'; ?>;font-size:14px;"></i>
                  <?php endfor; ?>
                </div>
              </td>
              <td><div style="max-width:200px;font-size:13px;color:#666;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?php echo $r['review'] ? htmlspecialchars($r['review']) : '<span style="color:#ccc;">—</span>'; ?></div></td>
              <td style="font-size:12px;color:var(--text-muted);white-space:nowrap;"><?php echo date('d M Y', strtotime($r['created_at'])); ?></td>
              <td>
                <a href="reviews.php?toggle=<?php echo $r['id']; ?>" style="display:inline-block;padding:4px 12px;border-radius:50px;font-size:11px;font-weight:600;text-decoration:none;background:<?php echo $r['status'] ? '#f0fdf4' : '#fef2f2'; ?>;color:<?php echo $r['status'] ? '#059669' : '#dc2626'; ?>;">
                  <?php echo $r['status'] ? 'Visible' : 'Hidden'; ?>
                </a>
              </td>
              <td>
                <a href="reviews.php?delete=<?php echo $r['id']; ?>" class="btn-outline-secondary btn-sm" style="padding:4px 10px;font-size:11px;color:#dc2626;border-color:#fecaca;text-decoration:none;" onclick="return confirm('Delete this review?')"><i class="bi bi-trash"></i></a>
              </td>
            </tr>
          <?php endwhile;
        else: ?>
          <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted);">No reviews yet</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function filterTable() {
  var q = document.getElementById('searchInput').value.toLowerCase();
  document.querySelectorAll('#dataTable tbody tr').forEach(function(r) {
    r.style.display = r.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}
</script>

<?php require_once 'includes/footer.php'; ?>
