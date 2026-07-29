<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EasyShop - India's Fashion Destination</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header class="top-header">
  <div class="container">
    <div class="row align-items-center gx-2" style="min-height:56px;">
      <div class="col text-start d-lg-none">
        <button class="search-btn-mobile" onclick="toggleSearch()" aria-label="Search">
          <i class="bi bi-search"></i>
        </button>
      </div>
      <div class="col text-center">
        <a href="index.php" class="top-brand">Easy<span>Shop</span></a>
      </div>
      <div class="col text-end d-flex align-items-center justify-content-end gap-1">
        <button class="search-btn-desktop" onclick="toggleSearch()" aria-label="Search">
          <i class="bi bi-search"></i>
        </button>
        <a href="wishlist.php" class="top-icon-link">
          <i class="bi bi-heart"></i>
          <span>Wishlist</span>
        </a>
      </div>
    </div>
  </div>
  <!-- Search Bar (hidden by default) -->
  <div class="search-overlay" id="searchOverlay">
    <div class="container">
      <form action="shop.php" method="GET" class="search-overlay-form">
        <i class="bi bi-search search-overlay-icon"></i>
        <input type="text" name="search" class="search-overlay-input" placeholder="Search for products..." autocomplete="off" autofocus>
        <button type="button" class="search-overlay-close" onclick="toggleSearch()"><i class="bi bi-x-lg"></i></button>
      </form>
    </div>
  </div>
</header>

<main>
