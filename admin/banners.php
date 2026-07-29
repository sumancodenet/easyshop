<?php
$page_title = 'Banners';
require_once 'includes/header.php';

$banner_q = mysqli_query($conn, "SELECT * FROM banners ORDER BY sort_order ASC, id DESC");
$banners = $banner_q ? $banner_q : false;
?>
<div class="row justify-content-center">
  <div class="col-lg-10">
    <div class="d-flex justify-content-between align-items-center mb-3" style="flex-wrap:wrap;gap:10px;">
      <p style="color:var(--text-muted);margin:0;font-size:14px;">Manage homepage carousel banners</p>
      <a href="add_banner.php" class="btn-red btn-sm"><i class="bi bi-plus-lg"></i> Add Banner</a>
    </div>

    <?php if (isset($_GET['success'])): ?>
      <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:var(--radius-sm);padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;gap:10px;color:#16a34a;font-size:13px;font-weight:500;"><i class="bi bi-check-circle" style="font-size:18px;"></i> <?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>

    <div class="table-card">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Image</th>
              <th>Title</th>
              <th>Subtitle</th>
              <th>Link</th>
              <th>Order</th>
              <th>Status</th>
              <th style="width:100px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($banners && mysqli_num_rows($banners) > 0):
              $i = 1;
              while ($b = mysqli_fetch_assoc($banners)):
                $img = $b['image'] && file_exists('../' . $b['image']) ? '../' . $b['image'] : '';
            ?>
              <tr>
                <td><?php echo $i++; ?></td>
                <td>
                  <?php if ($img): ?>
                    <img src="<?php echo $img; ?>" style="width:80px;height:40px;border-radius:4px;object-fit:cover;border:1px solid var(--border-color);">
                  <?php else: ?>
                    <span style="display:inline-block;width:80px;height:40px;border-radius:4px;background:linear-gradient(135deg,#1a1a2e,var(--red));color:#fff;font-size:9px;display:flex;align-items:center;justify-content:center;font-weight:600;border:1px solid var(--border-color);">No Image</span>
                  <?php endif; ?>
                </td>
                <td><strong><?php echo htmlspecialchars($b['title'] ?? '-'); ?></strong></td>
                <td style="color:var(--text-muted);font-size:13px;"><?php echo htmlspecialchars($b['subtitle'] ?? '-'); ?></td>
                <td><code><?php echo htmlspecialchars($b['link'] ?? '-'); ?></code></td>
                <td><?php echo $b['sort_order']; ?></td>
                <td>
                  <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 12px;border-radius:50px;font-size:11px;font-weight:600;background:<?php echo $b['status'] ? '#f0fdf4' : '#fef2f2'; ?>;color:<?php echo $b['status'] ? '#059669' : '#dc2626'; ?>;">
                    <i class="bi bi-circle-fill" style="font-size:6px;"></i> <?php echo $b['status'] ? 'Active' : 'Inactive'; ?>
                  </span>
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="edit_banner.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-secondary" style="padding:2px 8px;font-size:12px;" title="Edit"><i class="bi bi-pencil"></i></a>
                    <a href="edit_banner.php?delete=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-secondary" style="padding:2px 8px;font-size:12px;color:#dc2626;border-color:#fecaca;" onclick="return confirm('Delete this banner?')" title="Delete"><i class="bi bi-trash"></i></a>
                  </div>
                </td>
              </tr>
            <?php endwhile; else: ?>
              <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted);">No banners yet. <a href="add_banner.php">Add one</a>.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once 'includes/footer.php'; ?>
