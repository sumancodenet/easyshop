<?php
require_once 'includes/config.php';

$pid = (int)($_POST['id'] ?? 0);
$size_id = (int)($_POST['size_id'] ?? 0);
$qty = max(1, (int)($_POST['qty'] ?? 1));

if ($pid < 1) { echo json_encode(['success'=>false]); exit; }

if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

$cart_key = $pid . '_' . $size_id;

if (isset($_SESSION['cart'][$cart_key])) {
  $_SESSION['cart'][$cart_key]['qty'] += $qty;
} else {
  $_SESSION['cart'][$cart_key] = ['product_id' => $pid, 'size_id' => $size_id, 'qty' => $qty];
}

$total_items = array_sum(array_column($_SESSION['cart'], 'qty'));
echo json_encode(['success'=>true, 'total_items'=>$total_items]);
