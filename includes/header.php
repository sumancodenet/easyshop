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
      <div class="col-auto">
        <a href="index.php" class="top-brand">Easy<span>Shop</span></a>
      </div>
      <div class="col">
        <div class="top-search">
          <form action="shop.php" method="GET" class="d-flex">
            <input type="text" name="search" placeholder="Search for products..." autocomplete="off">
            <button type="submit"><i class="bi bi-search"></i></button>
          </form>
        </div>
      </div>
      <div class="col-auto">
        <a href="wishlist.php" class="top-icon-link">
          <i class="bi bi-heart"></i>
          <span>Wishlist</span>
        </a>
      </div>
    </div>
  </div>
</header>

<main>
