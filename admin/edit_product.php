<?php
$page_title = 'Edit Product';
require_once 'includes/header.php';

$id = (int)$_GET['id'];
$product_q = mysqli_query($conn, "SELECT * FROM products WHERE id=$id");
$product = $product_q ? mysqli_fetch_assoc($product_q) : false;
if (!$product) { header('Location: products.php?error=Product+not+found'); exit; }

$categories_q = mysqli_query($conn, "SELECT * FROM categories WHERE status=1 ORDER BY name");
$categories = $categories_q ? $categories_q : false;

$colors_q = mysqli_query($conn, "SELECT * FROM product_colors WHERE product_id=$id ORDER BY id");
$existing_colors = $colors_q ? mysqli_fetch_all($colors_q, MYSQLI_ASSOC) : [];

$sizes_q = mysqli_query($conn, "SELECT * FROM product_sizes WHERE product_id=$id ORDER BY id");
$existing_sizes = $sizes_q ? mysqli_fetch_all($sizes_q, MYSQLI_ASSOC) : [];

// Load images per color
$images_q = mysqli_query($conn, "SELECT * FROM product_images WHERE product_id=$id ORDER BY color_id, id");
$existing_images = [];
if ($images_q) {
  while ($row = mysqli_fetch_assoc($images_q)) {
    $existing_images[$row['color_id']][] = $row;
  }
}

// Handle image deletion
if (isset($_GET['delete_img'])) {
  $img_id = (int)$_GET['delete_img'];
  $img_q = mysqli_query($conn, "SELECT * FROM product_images WHERE id=$img_id");
  $img_row = $img_q ? mysqli_fetch_assoc($img_q) : false;
  if ($img_row) {
    if (file_exists("../" . $img_row['image'])) unlink("../" . $img_row['image']);
    mysqli_query($conn, "DELETE FROM product_images WHERE id=$img_id");
  }
  header("Location: edit_product.php?id=$id&success=Image+deleted");
  exit;
}

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

  $image = $product['image'];
  if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    if ($product['image'] && file_exists("../" . $product['image'])) unlink("../" . $product['image']);
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = time() . '.' . $ext;
    if (move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $filename)) {
      $image = 'uploads/' . $filename;
    }
  }

  $img = "'$image'";
  $q = "UPDATE products SET category_id=$cat_id, name='$name', slug='$slug', description='$desc', price=$price, old_price=$old_price, image=$img, stock=$stock, featured=$featured, free_delivery=$free_delivery WHERE id=$id";

  if (mysqli_query($conn, $q)) {
    $ts = time();
    $submitted_ids = [];
    $submitted_size_ids = [];

    // Save sizes
    if (!empty($_POST['size_name'])) {
      foreach ($_POST['size_name'] as $i => $sn) {
        $sn = mysqli_real_escape_string($conn, trim($sn));
        if ($sn === '') continue;
        $sid = !empty($_POST['size_id'][$i]) ? (int)$_POST['size_id'][$i] : 0;
        if ($sid) {
          mysqli_query($conn, "UPDATE product_sizes SET size_name='$sn' WHERE id=$sid");
          $submitted_size_ids[] = $sid;
        } else {
          mysqli_query($conn, "INSERT INTO product_sizes (product_id, size_name) VALUES ($id, '$sn')");
          $submitted_size_ids[] = mysqli_insert_id($conn);
        }
      }
    }

    // Delete sizes that were removed
    if (!empty($submitted_size_ids)) {
      $keep_sizes = implode(',', $submitted_size_ids);
      mysqli_query($conn, "DELETE FROM product_sizes WHERE product_id=$id AND id NOT IN ($keep_sizes)");
    } else {
      mysqli_query($conn, "DELETE FROM product_sizes WHERE product_id=$id");
    }

    if (!empty($_POST['color_name'])) {
      foreach ($_POST['color_name'] as $i => $cn) {
        $cn = mysqli_real_escape_string($conn, $cn);
        if (trim($cn) === '') continue;
        $cc = !empty($_POST['color_code'][$i]) ? mysqli_real_escape_string($conn, $_POST['color_code'][$i]) : 'NULL';
        $cc = $cc !== 'NULL' ? "'$cc'" : 'NULL';
        $cid = !empty($_POST['color_id'][$i]) ? (int)$_POST['color_id'][$i] : 0;

        if ($cid) {
          mysqli_query($conn, "UPDATE product_colors SET color_name='$cn', color_code=$cc WHERE id=$cid");
          $submitted_ids[] = $cid;
        } else {
          mysqli_query($conn, "INSERT INTO product_colors (product_id, color_name, color_code) VALUES ($id, '$cn', $cc)");
          $cid = mysqli_insert_id($conn);
          $submitted_ids[] = $cid;
        }

        // Upload new images for this color
        if (isset($_FILES['color_images']['name'][$i]) && is_array($_FILES['color_images']['name'][$i])) {
          $imgs = $_FILES['color_images']['name'][$i];
          $tmp = $_FILES['color_images']['tmp_name'][$i];
          $err = $_FILES['color_images']['error'][$i];
          foreach ($imgs as $j => $fname) {
            if ($err[$j] == 0) {
              $ext = pathinfo($fname, PATHINFO_EXTENSION);
              $fn = $ts . '_' . $i . '_' . $j . '.' . $ext;
              if (move_uploaded_file($tmp[$j], '../uploads/' . $fn)) {
                mysqli_query($conn, "INSERT INTO product_images (product_id, color_id, image) VALUES ($id, $cid, 'uploads/$fn')");
              }
            }
          }
        }
      }
    }

    // Delete colors that were removed (cascade deletes their images + files)
    if (!empty($submitted_ids)) {
      $keep = implode(',', $submitted_ids);
      $gone = mysqli_query($conn, "SELECT * FROM product_colors WHERE product_id=$id AND id NOT IN ($keep)");
      if ($gone) {
        while ($g = mysqli_fetch_assoc($gone)) {
          $img_del = mysqli_query($conn, "SELECT * FROM product_images WHERE color_id={$g['id']}");
          if ($img_del) {
            while ($gi = mysqli_fetch_assoc($img_del)) {
              if (file_exists("../" . $gi['image'])) unlink("../" . $gi['image']);
            }
          }
        }
      }
      mysqli_query($conn, "DELETE FROM product_colors WHERE product_id=$id AND id NOT IN ($keep)");
    } else {
      // All colors removed
      $gone = mysqli_query($conn, "SELECT * FROM product_colors WHERE product_id=$id");
      if ($gone) {
        while ($g = mysqli_fetch_assoc($gone)) {
          $img_del = mysqli_query($conn, "SELECT * FROM product_images WHERE color_id={$g['id']}");
          if ($img_del) {
            while ($gi = mysqli_fetch_assoc($img_del)) {
              if (file_exists("../" . $gi['image'])) unlink("../" . $gi['image']);
            }
          }
        }
      }
      mysqli_query($conn, "DELETE FROM product_colors WHERE product_id=$id");
    }

    header("Location: edit_product.php?id=$id&success=Product+updated");
    exit;
  } else {
    $error = 'Error: ' . mysqli_error($conn);
  }
}

$success = $_GET['success'] ?? '';
?>
<div class="row justify-content-center">
  <div class="col-lg-10">
    <div class="form-card">
      <?php if ($error): ?>
        <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:var(--radius-sm);padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;color:#dc2626;font-size:13px;font-weight:500;">
          <i class="bi bi-exclamation-circle" style="font-size:18px;"></i> <?php echo $error; ?>
        </div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:var(--radius-sm);padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;color:#16a34a;font-size:13px;font-weight:500;">
          <i class="bi bi-check-circle" style="font-size:18px;"></i> <?php echo $success; ?>
        </div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="product_id" value="<?php echo $id; ?>">
        <div class="row g-4">
          <div class="col-md-6">
            <label class="form-label">Product Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo $product['name']; ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select" required>
              <option value="">Select Category</option>
              <?php if ($categories): while ($c = mysqli_fetch_assoc($categories)): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo $c['id'] == $product['category_id'] ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
              <?php endwhile; endif; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Price (₹)</label>
            <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $product['price']; ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Old Price (₹)</label>
            <input type="number" step="0.01" name="old_price" class="form-control" value="<?php echo $product['old_price']; ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Stock</label>
            <input type="number" name="stock" class="form-control" value="<?php echo $product['stock']; ?>" min="0">
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="4"><?php echo $product['description']; ?></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Product Image (thumbnail)</label>
            <input type="file" name="image" class="form-control" accept="image/*">
            <?php if ($product['image']): ?>
              <div style="margin-top:8px;display:flex;align-items:center;gap:8px;">
                <img src="../<?php echo $product['image']; ?>" width="48" height="48" style="object-fit:cover;border-radius:8px;">
                <small style="color:var(--text-muted);">Current image</small>
              </div>
            <?php endif; ?>
          </div>
          <div class="col-md-6" style="display:flex;align-items:flex-end;padding-bottom:12px;gap:16px;">
            <div class="form-check">
              <input type="checkbox" name="featured" class="form-check-input" id="featured" style="border-color:var(--red);" <?php echo $product['featured'] ? 'checked' : ''; ?>>
              <label class="form-check-label" for="featured" style="font-size:14px;">Featured</label>
            </div>
            <div class="form-check">
              <input type="checkbox" name="free_delivery" class="form-check-input" id="free_delivery" style="border-color:var(--red);" <?php echo $product['free_delivery'] ? 'checked' : ''; ?>>
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
          <?php foreach ($existing_colors as $ec): $ecid = $ec['id']; $imgs = $existing_images[$ecid] ?? []; ?>
            <div class="color-row" style="background:#fafbfc;border:1px solid #eee;border-radius:8px;padding:12px;margin-bottom:12px;">
              <input type="hidden" name="color_id[]" value="<?php echo $ecid; ?>">
              <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:8px;">
                <input type="text" name="color_name[]" class="form-control" value="<?php echo $ec['color_name']; ?>" placeholder="Color name" style="flex:1;min-width:100px;">
                <input type="color" name="color_code[]" class="form-control" value="<?php echo $ec['color_code'] ?? '#cc0000'; ?>" style="width:44px;height:36px;padding:2px;flex-shrink:0;">
                <button type="button" class="btn-outline-secondary btn-sm" style="padding:5px 10px;color:#dc2626;border-color:#fecaca;flex-shrink:0;" onclick="this.closest('.color-row').remove()"><i class="bi bi-x"></i></button>
              </div>
              <?php if ($imgs): ?>
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px;">
                  <?php foreach ($imgs as $img_row): ?>
                    <div style="position:relative;width:52px;height:52px;">
                      <img src="../<?php echo $img_row['image']; ?>" style="width:52px;height:52px;object-fit:cover;border-radius:6px;border:1px solid #ddd;">
                      <a href="edit_product.php?id=<?php echo $id; ?>&delete_img=<?php echo $img_row['id']; ?>" style="position:absolute;top:-6px;right:-6px;width:18px;height:18px;background:#dc2626;color:#fff;border-radius:50%;font-size:12px;line-height:18px;text-align:center;text-decoration:none;" onclick="return confirm('Delete this image?')">&times;</a>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <div>
                <label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">Add more photos for this color</label>
                <input type="file" name="color_images[<?php echo $ecid; ?>][]" class="form-control" accept="image/*" multiple style="font-size:12px;padding:5px 10px;">
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- SIZES -->
        <hr style="border-color:var(--border-color);margin:20px 0;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
          <label class="form-label" style="margin:0;font-size:14px;font-weight:700;">Sizes <small style="font-weight:400;color:var(--text-muted);">(e.g. S, M, L, XL, XXL)</small></label>
          <button type="button" class="btn-outline-secondary btn-sm" onclick="addSize()"><i class="bi bi-plus"></i> Add Size</button>
        </div>
        <div id="sizesWrap">
          <?php foreach ($existing_sizes as $es): ?>
            <div class="size-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
              <input type="hidden" name="size_id[]" value="<?php echo $es['id']; ?>">
              <input type="text" name="size_name[]" class="form-control" value="<?php echo $es['size_name']; ?>" placeholder="Size (e.g. M)" style="max-width:200px;font-size:13px;">
              <button type="button" class="btn-outline-secondary btn-sm" style="padding:5px 10px;color:#dc2626;border-color:#fecaca;flex-shrink:0;" onclick="this.closest('.size-row').remove()"><i class="bi bi-x"></i></button>
            </div>
          <?php endforeach; ?>
        </div>

        <hr style="border-color:var(--border-color);margin:20px 0;">
        <div style="display:flex;gap:10px;">
          <button type="submit" class="btn-red"><i class="bi bi-check-lg"></i> Update Product</button>
          <a href="products.php" class="btn-outline-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var colorIndex = <?php echo count($existing_colors) + 1000; ?>;
function addColor() {
  var w = document.getElementById('colorsWrap');
  var d = document.createElement('div');
  d.className = 'color-row';
  d.style.cssText = 'background:#fafbfc;border:1px solid #eee;border-radius:8px;padding:12px;margin-bottom:12px;';
  d.innerHTML =
    '<input type="hidden" name="color_id[]" value="">' +
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
    '<input type="hidden" name="size_id[]" value="">' +
    '<input type="text" name="size_name[]" class="form-control" placeholder="Size (e.g. L)" style="max-width:200px;font-size:13px;">' +
    '<button type="button" class="btn-outline-secondary btn-sm" style="padding:5px 10px;color:#dc2626;border-color:#fecaca;flex-shrink:0;" onclick="this.closest(\'.size-row\').remove()"><i class="bi bi-x"></i></button>';
  w.appendChild(d);
}
</script>

<?php require_once 'includes/footer.php'; ?>
