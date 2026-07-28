<?php
require_once 'includes/config.php';

$pid = (int)($_POST['id'] ?? 0);
if ($pid < 1) { echo json_encode(['success'=>false]); exit; }

if (!isset($_SESSION['wishlist'])) {
  $_SESSION['wishlist'] = [];
}

$key = array_search($pid, $_SESSION['wishlist']);
if ($key !== false) {
  unset($_SESSION['wishlist'][$key]);
  $_SESSION['wishlist'] = array_values($_SESSION['wishlist']);
  echo json_encode(['success'=>true, 'action'=>'removed']);
} else {
  $_SESSION['wishlist'][] = $pid;
  echo json_encode(['success'=>true, 'action'=>'added']);
}
