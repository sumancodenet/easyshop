</main>

<?php
// Determine page skeleton type
$skel_type = basename($_SERVER['PHP_SELF']);
if (!isset($page_skel)) $page_skel = $skel_type;
?>
<!-- PAGE SKELETON LOADER -->
<div id="pageSkel" style="display:none;">
<?php if (in_array($page_skel, ['index.php','shop.php','wishlist.php'])): ?>
  <div class="container py-4 py-md-5">
    <div class="skel skel-line" style="width:200px;height:24px;margin-bottom:24px;"></div>
    <div class="row g-3 g-md-4">
      <?php for ($i=0;$i<8;$i++): ?>
      <div class="col-6 col-md-3"><div class="skel" style="height:280px;border-radius:12px;"></div></div>
      <?php endfor; ?>
    </div>
  </div>
<?php elseif (in_array($page_skel, ['login.php'])): ?>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="skel" style="height:400px;border-radius:16px;"></div>
      </div>
    </div>
  </div>
<?php elseif (in_array($page_skel, ['cart.php','checkout.php'])): ?>
  <div class="container py-4 py-md-5">
    <div class="skel skel-line" style="width:160px;height:24px;margin-bottom:24px;"></div>
    <div class="row g-4">
      <div class="col-lg-7"><div class="skel" style="height:300px;border-radius:12px;"></div></div>
      <div class="col-lg-5"><div class="skel" style="height:200px;border-radius:12px;"></div></div>
    </div>
  </div>
<?php elseif (in_array($page_skel, ['product-detail.php'])): ?>
  <div class="container py-4 py-md-5">
    <div class="row g-4">
      <div class="col-md-5"><div class="skel" style="height:450px;border-radius:12px;"></div></div>
      <div class="col-md-7">
        <div class="skel skel-line" style="width:120px;height:14px;margin-bottom:8px;"></div>
        <div class="skel skel-line" style="width:80%;height:28px;margin-bottom:12px;"></div>
        <div class="skel skel-line" style="width:40%;height:32px;margin-bottom:20px;"></div>
        <div class="skel skel-line" style="width:100%;height:14px;margin-bottom:6px;"></div>
        <div class="skel skel-line" style="width:70%;height:14px;margin-bottom:20px;"></div>
        <div class="skel skel-line" style="width:50%;height:40px;margin-bottom:12px;"></div>
        <div class="skel skel-line" style="width:60%;height:20px;"></div>
      </div>
    </div>
  </div>
<?php elseif (in_array($page_skel, ['account.php'])): ?>
  <div class="container py-3 py-md-5">
    <div class="skel skel-line" style="width:180px;height:24px;margin-bottom:24px;"></div>
    <div class="row g-4 g-md-5">
      <div class="col-md-4 col-lg-3">
        <div class="skel" style="height:320px;border-radius:12px;"></div>
      </div>
      <div class="col-md-8 col-lg-9">
        <div class="skel" style="height:300px;border-radius:12px;"></div>
      </div>
    </div>
  </div>
<?php elseif (in_array($page_skel, ['orders.php'])): ?>
  <div class="container py-4 py-md-5">
    <div class="skel skel-line" style="width:160px;height:24px;margin-bottom:24px;"></div>
    <div class="skel" style="height:400px;border-radius:12px;"></div>
  </div>
<?php else: ?>
  <div class="container py-4 py-md-5">
    <div class="row g-3 g-md-4">
      <?php for ($i=0;$i<4;$i++): ?>
      <div class="col-6 col-md-3"><div class="skel" style="height:250px;border-radius:12px;"></div></div>
      <?php endfor; ?>
    </div>
  </div>
<?php endif; ?>
</div>

<script>
(function() {
  var skel = document.getElementById('pageSkel');
  var main = document.querySelector('main');
  var ptype = '<?php echo addslashes($page_skel); ?>';
  if (skel && main && ptype !== 'none') {
    if (ptype === 'account.php') {
      document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
          skel.style.transition = 'opacity 0.35s ease';
          skel.style.opacity = '0';
          setTimeout(function() {
            skel.style.display = 'none';
            main.style.display = 'block';
            main.style.animation = 'fadeIn 0.5s ease';
          }, 350);
        }, 600);
      });
    } else {
      skel.style.display = 'block';
      main.style.display = 'none';
      setTimeout(function() {
        skel.style.transition = 'opacity 0.35s ease';
        skel.style.opacity = '0';
        setTimeout(function() {
          skel.style.display = 'none';
          main.style.display = 'block';
          main.style.animation = 'fadeIn 0.5s ease';
        }, 350);
      }, 300);
    }
  }
})();
</script>

<!-- APP BOTTOM NAV (sticky footer with 5 options) -->
<nav class="app-bottom-nav">
  <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
    <i class="bi bi-house-door"></i>
    <span>Home</span>
  </a>
  <a href="shop.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'shop.php' ? 'active' : ''; ?>">
    <i class="bi bi-grid"></i>
    <span>Categories</span>
  </a>
  <a href="orders.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
    <i class="bi bi-box"></i>
    <span>Orders</span>
  </a>
  <a href="cart.php" class="cart-wrap <?php echo basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'active' : ''; ?>">
    <i class="bi bi-bag"></i>
    <small class="nav-cart-count"><?php $cart = $_SESSION['cart'] ?? []; echo array_sum(array_column($cart, 'qty')); ?></small>
    <span>Cart</span>
  </a>
  <?php $prof_page = basename($_SERVER['PHP_SELF']); ?>
  <a href="<?php echo isset($_SESSION['user']) ? 'account.php' : 'login.php'; ?>" class="<?php echo in_array($prof_page, ['login.php','account.php']) ? 'active' : ''; ?>">
    <i class="bi bi-person"></i>
    <span>Profile</span>
  </a>
</nav>

<!-- SITE INFO FOOTER (lightweight) -->
<!-- <footer class="site-footer">
  <div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center py-3 gap-2">
      <div class="small text-muted">
        &copy; <?php // echo date('Y'); ?> EasyShop. All rights reserved.
      </div>
      <div class="d-flex gap-3 small text-muted">
        <a href="#" class="text-muted text-decoration-none">Privacy</a>
        <a href="#" class="text-muted text-decoration-none">Terms</a>
        <a href="#" class="text-muted text-decoration-none">Contact</a>
      </div>
    </div>
  </div>
</footer> -->

<div class="toast-container" id="toastContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSearch() {
  var el = document.getElementById('searchOverlay');
  var showing = el.classList.toggle('show');
  if (showing) setTimeout(function(){ el.querySelector('.search-overlay-input').focus(); }, 100);
}
function showToast(type, title, message, duration) {
  duration = duration || 3500;
  var container = document.getElementById('toastContainer');
  var icons = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', warning: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };
  var toast = document.createElement('div');
  toast.className = 'toast toast-' + type;
  toast.innerHTML =
    '<i class="bi ' + (icons[type] || icons.info) + ' toast-icon"></i>' +
    '<div class="toast-content">' +
      '<div class="toast-title">' + title + '</div>' +
      '<div class="toast-msg">' + message + '</div>' +
    '</div>' +
    '<button class="toast-close" onclick="this.parentElement.classList.add(\'removing\');setTimeout(function(){this.parentElement.remove()}.bind(this),300)"><i class="bi bi-x"></i></button>';
  container.appendChild(toast);
  setTimeout(function() {
    if (toast.parentNode) { toast.classList.add('removing'); setTimeout(function(){ if(toast.parentNode) toast.remove(); }, 300); }
  }, duration);
}
</script>
</body>
</html>
