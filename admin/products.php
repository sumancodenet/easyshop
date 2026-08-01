<?php
$page_title = 'Products';
require_once 'includes/header.php';

if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  $p_q = mysqli_query($conn, "SELECT image FROM products WHERE id=$id");
  if ($p_q) {
    $p = mysqli_fetch_assoc($p_q);
    if ($p && $p['image'] && file_exists("../" . $p['image'])) unlink("../" . $p['image']);
  }
  if (mysqli_query($conn, "DELETE FROM products WHERE id=$id")) {
    header('Location: products.php?success=Product+deleted');
  } else {
    header('Location: products.php?error=Delete+failed');
  }
  exit;
}

$products_q = mysqli_query($conn, "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.id DESC");
$products = $products_q ? $products_q : false;

$all_colors = [];
$cols_q = mysqli_query($conn, "SELECT * FROM product_colors ORDER BY product_id, id");
if ($cols_q) {
  while ($col = mysqli_fetch_assoc($cols_q)) {
    $all_colors[$col['product_id']][] = $col;
  }
}

$all_sizes = [];
$sizes_q = mysqli_query($conn, "SELECT * FROM product_sizes ORDER BY product_id, id");
if ($sizes_q) {
  while ($sz = mysqli_fetch_assoc($sizes_q)) {
    $all_sizes[$sz['product_id']][] = $sz;
  }
}
?>
<div class="table-card">
  <div class="table-header">
    <h5><i class="bi bi-box-seam-fill me-2" style="color:var(--red);"></i>All Products</h5>
    <div class="table-actions">
      <div class="search-wrap">
        <i class="bi bi-search"></i>
        <input type="text" class="search-input" placeholder="Search products..." onkeyup="filterTable(this)">
      </div>
      <a href="add_product.php" class="btn-red btn-sm"><i class="bi bi-plus-lg"></i> Add Product</a>
    </div>
  </div>
  <div class="table-responsive">
    <table id="dataTable">
      <thead>
        <tr>
          <th style="width:50px;">#</th>
          <th style="width:60px;">Image</th>
          <th>Name</th>
          <th>Category</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Delivery</th>
          <th>Colors</th>
          <th>Sizes</th>
          <th>Status</th>
          <th style="width:120px;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($products && mysqli_num_rows($products) > 0): ?>
          <?php $i = 1; while ($p = mysqli_fetch_assoc($products)): ?>
            <tr>
              <td><?php echo $i++; ?></td>
              <td>
                <?php if ($p['image']): ?>
                  <img src="../<?php echo $p['image']; ?>" width="40" height="40" style="object-fit:cover;border-radius:8px;">
                <?php else: ?>
                  <span style="color:var(--text-muted);font-size:20px;"><i class="bi bi-image"></i></span>
                <?php endif; ?>
              </td>
              <td><strong><?php echo $p['name']; ?></strong></td>
              <td><span class="badge-status bg-secondary"><?php echo $p['cat_name'] ?? 'Uncategorized'; ?></span></td>
              <td><strong>₹<?php echo number_format($p['price']); ?></strong></td>
              <td>
                <span class="badge-status bg-<?php echo $p['stock'] > 0 ? 'success' : 'danger'; ?>">
                  <span class="dot"></span> <?php echo $p['stock'] > 0 ? $p['stock'] . ' in stock' : 'Out of stock'; ?>
                </span>
              </td>
              <td style="text-align:center;font-size:16px;">
                <?php if ($p['free_delivery']): ?>
                  <span style="color:#059669;" title="Free Delivery"><i class="bi bi-truck"></i></span>
                <?php else: ?>
                  <span style="color:#ccc;" title="Paid Delivery"><i class="bi bi-truck"></i></span>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($all_colors[$p['id']])): ?>
                  <div style="display:flex;gap:3px;flex-wrap:wrap;">
                    <?php foreach ($all_colors[$p['id']] as $col): ?>
                      <span title="<?php echo $col['color_name']; ?>" style="display:inline-block;width:18px;height:18px;border-radius:50%;background:<?php echo $col['color_code'] ?? '#ccc'; ?>;border:1px solid #ddd;cursor:default;"></span>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <small style="color:var(--text-muted);">—</small>
                <?php endif; ?>
              </td>
              <td>
                <?php if (!empty($all_sizes[$p['id']])): ?>
                  <div style="display:flex;gap:4px;flex-wrap:wrap;">
                    <?php foreach ($all_sizes[$p['id']] as $sz): ?>
                      <span style="display:inline-block;padding:1px 8px;border-radius:4px;background:#f0f0f0;font-size:11px;font-weight:600;"><?php echo $sz['size_name']; ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <small style="color:var(--text-muted);">—</small>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge-status bg-<?php echo $p['status'] ? 'success' : 'secondary'; ?>">
                  <span class="dot"></span> <?php echo $p['status'] ? 'Active' : 'Inactive'; ?>
                </span>
              </td>
              <td>
                <div style="display:flex;gap:6px;">
                  <a href="edit_product.php?id=<?php echo $p['id']; ?>" class="btn-outline-secondary btn-sm" style="padding:5px 10px;"><i class="bi bi-pencil"></i></a>
                  <a href="?delete=<?php echo $p['id']; ?>" class="btn-outline-secondary btn-sm" style="padding:5px 10px;color:#dc2626;border-color:#fecaca;" onclick="return confirm('Delete this product?')"><i class="bi bi-trash"></i></a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="11"><div class="empty-state"><div class="empty-icon"><i class="bi bi-box-seam"></i></div><h6>No products yet</h6><p>Add your first product to start selling.</p><a href="add_product.php" class="btn-red btn-sm"><i class="bi bi-plus-lg"></i> Add Product</a></div></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="table-footer">
    <span><?php echo $products && mysqli_num_rows($products) > 0 ? mysqli_num_rows($products) . ' products' : '0 products'; ?></span>
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
