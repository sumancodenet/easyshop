<?php
$page_title = 'Add Product';
require_once 'includes/header.php';

$categories_q = mysqli_query($conn, "SELECT * FROM categories WHERE status=1 ORDER BY name");
$categories = $categories_q ? $categories_q : false;

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $cat_id = (int)$_POST['category_id'];
  $name = mysqli_real_escape_string($conn, $_POST['name']);
  $slug = strtolower(str_replace([' ', '/', '&'], '-', $name));
  $desc = mysqli_real_escape_string($conn, $_POST['description']);
  $price = (float)$_POST['price'];
  $old_price = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : 'NULL';
  $stock = (int)$_POST['stock'];
  $featured = isset($_POST['featured']) ? 1 : 0;
  $free_delivery = isset($_POST['free_delivery']) ? 1 : 0;

  $image = '';
  if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = time() . '.' . $ext;
    if (move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $filename)) {
      $image = 'uploads/' . $filename;
    }
  }

  $img = $image ? "'$image'" : "NULL";
  $q = "INSERT INTO products (category_id, name, slug, description, price, old_price, image, stock, featured, free_delivery) VALUES ($cat_id, '$name', '$slug', '$desc', $price, $old_price, $img, $stock, $featured, $free_delivery)";

  if (mysqli_query($conn, $q)) {
    $pid = mysqli_insert_id($conn);

    // Save sizes
    if (!empty($_POST['size_name'])) {
      foreach ($_POST['size_name'] as $sn) {
        $sn = mysqli_real_escape_string($conn, trim($sn));
        if ($sn === '') continue;
        mysqli_query($conn, "INSERT INTO product_sizes (product_id, size_name) VALUES ($pid, '$sn')");
      }
    }

    if (!empty($_POST['color_name'])) {
      $ts = time();
      foreach ($_POST['color_name'] as $i => $cn) {
        $cn = mysqli_real_escape_string($conn, $cn);
        if (trim($cn) === '') continue;
        $cc = !empty($_POST['color_code'][$i]) ? mysqli_real_escape_string($conn, $_POST['color_code'][$i]) : 'NULL';
        $cc = $cc !== 'NULL' ? "'$cc'" : 'NULL';
        mysqli_query($conn, "INSERT INTO product_colors (product_id, color_name, color_code) VALUES ($pid, '$cn', $cc)");
        $cid = mysqli_insert_id($conn);

        // Upload multiple images for this color
        if (isset($_FILES['color_images']['name'][$i]) && is_array($_FILES['color_images']['name'][$i])) {
          $imgs = $_FILES['color_images']['name'][$i];
          $tmp = $_FILES['color_images']['tmp_name'][$i];
          $err = $_FILES['color_images']['error'][$i];
          foreach ($imgs as $j => $fname) {
            if ($err[$j] == 0) {
              $ext = pathinfo($fname, PATHINFO_EXTENSION);
              $fn = $ts . '_' . $i . '_' . $j . '.' . $ext;
              if (move_uploaded_file($tmp[$j], '../uploads/' . $fn)) {
                $p = "'uploads/$fn'";
                mysqli_query($conn, "INSERT INTO product_images (product_id, color_id, image) VALUES ($pid, $cid, $p)");
              }
            }
          }
        }
      }
    }
    header('Location: products.php?success=Product+added+successfully');
    exit;
  } else {
    $error = 'Error: ' . mysqli_error($conn);
  }
}
?>
<div class="row justify-content-center">
  <div class="col-lg-10">
    <div class="form-card">
      <?php if ($error): ?>
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:var(--radius-sm);padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;color:#dc2626;font-size:13px;font-weight:500;">
          <i class="bi bi-exclamation-circle" style="font-size:18px;"></i> <?php echo $error; ?>
        </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <div class="row g-4">
          <div class="col-md-6">
            <label class="form-label">Product Name</label>
            <input type="text" name="name" class="form-control" placeholder="Enter product name" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select" required>
              <option value="">Select Category</option>
              <?php if ($categories): while ($c = mysqli_fetch_assoc($categories)): ?>
                <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
              <?php endwhile; endif; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Price (₹)</label>
            <input type="number" step="0.01" name="price" class="form-control" placeholder="2999" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Old Price (₹)</label>
            <input type="number" step="0.01" name="old_price" class="form-control" placeholder="Optional">
          </div>
          <div class="col-md-4">
            <label class="form-label">Stock</label>
            <input type="number" name="stock" class="form-control" value="1" min="0">
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Product description..."></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Product Image (thumbnail)</label>
            <input type="file" name="image" class="form-control" accept="image/*">
            <small style="color:var(--text-muted);margin-top:4px;display:block;">Main thumbnail for listings</small>
          </div>
          <div class="col-md-6" style="display:flex;align-items:flex-end;padding-bottom:12px;gap:16px;">
            <div class="form-check">
              <input type="checkbox" name="featured" class="form-check-input" id="featured" style="border-color:var(--red);">
              <label class="form-check-label" for="featured" style="font-size:14px;">Featured</label>
            </div>
            <div class="form-check">
              <input type="checkbox" name="free_delivery" class="form-check-input" id="free_delivery" checked style="border-color:var(--red);">
              <label class="form-check-label" for="free_delivery" style="font-size:14px;">Free Delivery</label>
            </div>
          </div>
        </div>

        <!-- COLORS WITH MULTIPLE IMAGES -->
        <hr style="border-color:var(--border-color);margin:20px 0;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
          <label class="form-label" style="margin:0;font-size:14px;font-weight:700;">Colors & Images <small style="font-weight:400;color:var(--text-muted);">(each color can have multiple photos)</small></label>
          <button type="button" class="btn-outline-secondary btn-sm" onclick="addColor()"><i class="bi bi-plus"></i> Add Color</button>
        </div>
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:12px;">Upload photos from different angles — front, back, side, detail for each color.</p>
        <div id="colorsWrap">
          <div class="color-row" style="background:#fafbfc;border:1px solid #eee;border-radius:8px;padding:12px;margin-bottom:12px;">
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:8px;">
              <input type="text" name="color_name[]" class="form-control" placeholder="Color name (e.g. Red)" style="flex:1;min-width:100px;">
              <input type="color" name="color_code[]" class="form-control" value="#cc0000" style="width:44px;height:36px;padding:2px;flex-shrink:0;">
              <button type="button" class="btn-outline-secondary btn-sm" style="padding:5px 10px;color:#dc2626;border-color:#fecaca;flex-shrink:0;" onclick="this.closest('.color-row').remove()"><i class="bi bi-x"></i></button>
            </div>
            <div>
              <label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">Upload photos for this color (front, back, side, etc.)</label>
              <input type="file" name="color_images[0][]" class="form-control" accept="image/*" multiple style="font-size:12px;padding:5px 10px;">
            </div>
          </div>
        </div>

        <!-- SIZES -->
        <hr style="border-color:var(--border-color);margin:20px 0;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
          <label class="form-label" style="margin:0;font-size:14px;font-weight:700;">Sizes <small style="font-weight:400;color:var(--text-muted);">(e.g. S, M, L, XL, XXL)</small></label>
          <button type="button" class="btn-outline-secondary btn-sm" onclick="addSize()"><i class="bi bi-plus"></i> Add Size</button>
        </div>
        <div id="sizesWrap">
          <div class="size-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
            <input type="text" name="size_name[]" class="form-control" placeholder="Size (e.g. M)" style="max-width:200px;font-size:13px;">
            <button type="button" class="btn-outline-secondary btn-sm" style="padding:5px 10px;color:#dc2626;border-color:#fecaca;flex-shrink:0;" onclick="this.closest('.size-row').remove()"><i class="bi bi-x"></i></button>
          </div>
        </div>

        <hr style="border-color:var(--border-color);margin:20px 0;">
        <div style="display:flex;gap:10px;">
          <button type="submit" class="btn-red"><i class="bi bi-check-lg"></i> Save Product</button>
          <a href="products.php" class="btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var colorIndex = 1;
function addColor() {
  var w = document.getElementById('colorsWrap');
  var d = document.createElement('div');
  d.className = 'color-row';
  d.style.cssText = 'background:#fafbfc;border:1px solid #eee;border-radius:8px;padding:12px;margin-bottom:12px;';
  d.innerHTML =
    '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:8px;">' +
    '<input type="text" name="color_name[]" class="form-control" placeholder="Color name" style="flex:1;min-width:100px;">' +
    '<input type="color" name="color_code[]" class="form-control" value="#cc0000" style="width:44px;height:36px;padding:2px;flex-shrink:0;">' +
    '<button type="button" class="btn-outline-secondary btn-sm" style="padding:5px 10px;color:#dc2626;border-color:#fecaca;flex-shrink:0;" onclick="this.closest(\'.color-row\').remove()"><i class="bi bi-x"></i></button>' +
    '</div>' +
    '<div>' +
    '<label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">Upload photos (front, back, side, etc.)</label>' +
    '<input type="file" name="color_images[' + colorIndex + '][]" class="form-control" accept="image/*" multiple style="font-size:12px;padding:5px 10px;">' +
    '</div>';
  w.appendChild(d);
  colorIndex++;
}

function addSize() {
  var w = document.getElementById('sizesWrap');
  var d = document.createElement('div');
  d.className = 'size-row';
  d.style.cssText = 'display:flex;gap:8px;align-items:center;margin-bottom:8px;';
  d.innerHTML =
    '<input type="text" name="size_name[]" class="form-control" placeholder="Size (e.g. L)" style="max-width:200px;font-size:13px;">' +
    '<button type="button" class="btn-outline-secondary btn-sm" style="padding:5px 10px;color:#dc2626;border-color:#fecaca;flex-shrink:0;" onclick="this.closest(\'.size-row\').remove()"><i class="bi bi-x"></i></button>';
  w.appendChild(d);
}
</script>

<?php require_once 'includes/footer.php'; ?>
