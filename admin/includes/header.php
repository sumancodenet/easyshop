<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
  header('Location: index.php');
  exit;
}
require_once dirname(__DIR__, 2) . '/includes/config.php';
/** @var \mysqli $conn */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - EasyShop</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="admin-sidebar" id="adminSidebar">
  <div class="brand">
    <div class="brand-icon"><i class="bi bi-shop"></i></div>
    <span class="brand-text">Easy<span>Shop</span></span>
  </div>

  <div class="nav-wrap">
    <div class="nav-label">Main Menu</div>

    <div class="nav-item">
      <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
        <i class="bi bi-grid-1x2-fill"></i>
        <span>Dashboard</span>
      </a>
    </div>

    <div class="nav-item">
      <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'category') !== false ? 'active' : ''; ?>" href="categories.php">
        <i class="bi bi-tags-fill"></i>
        <span>Categories</span>
      </a>
    </div>

    <div class="nav-item">
      <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'product') !== false ? 'active' : ''; ?>" href="products.php">
        <i class="bi bi-box-seam-fill"></i>
        <span>Products</span>
      </a>
    </div>

    <div class="nav-label">Management</div>

    <div class="nav-item">
      <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" href="orders.php">
        <i class="bi bi-truck"></i>
        <span>Orders</span>
      </a>
    </div>

    <div class="nav-item">
      <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
        <i class="bi bi-people-fill"></i>
        <span>Users</span>
      </a>
    </div>

    <div class="nav-item">
      <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>" href="reviews.php">
        <i class="bi bi-star-fill"></i>
        <span>Reviews</span>
      </a>
    </div>

    <div class="nav-label">System</div>

    <div class="nav-item">
      <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
        <i class="bi bi-gear-fill"></i>
        <span>Settings</span>
      </a>
    </div>
  </div>

  <div class="sidebar-footer">
    <div class="nav-item">
      <a class="nav-link" href="logout.php">
        <i class="bi bi-box-arrow-left"></i>
        <span>Sign Out</span>
      </a>
    </div>
  </div>
</div>

<div class="admin-topbar" id="adminTopbar">
  <div class="topbar-left">
    <button class="sidebar-toggle" id="sidebarToggle"><i class="bi bi-list"></i></button>
    <h4><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h4>
  </div>
  <div class="topbar-right">
    <a href="../index.php" class="btn-icon" title="View Site"><i class="bi bi-eye"></i></a>
    <div class="admin-profile dropdown" data-bs-toggle="dropdown">
      <div class="avatar"><?php echo strtoupper(substr($_SESSION['admin_name'], 0, 1)); ?></div>
      <div class="info d-none d-sm-block">
        <div class="name"><?php echo $_SESSION['admin_name']; ?></div>
        <div class="role">Administrator</div>
      </div>
    </div>
    <ul class="dropdown-menu dropdown-menu-end">
      <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-left me-2"></i>Sign Out</a></li>
    </ul>
  </div>
</div>

<div class="admin-content">
