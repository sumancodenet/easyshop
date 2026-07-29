</main>

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
