<?php
require_once 'includes/config.php';

header('Content-Type: application/json');

$pincode = trim($_POST['pincode'] ?? '');
if (!preg_match('/^\d{6}$/', $pincode)) {
  echo json_encode(['success' => false, 'message' => 'Enter a valid 6-digit PIN code']);
  exit;
}

$q = mysqli_query($conn, "SELECT * FROM pincodes WHERE pincode='$pincode' AND status=1");
$row = $q ? mysqli_fetch_assoc($q) : null;

if (!$row) {
  echo json_encode(['success' => false, 'message' => 'Delivery not available to this PIN code']);
  exit;
}

$days = (int)$row['delivery_days'];
$date = date('d M', strtotime("+$days days"));

echo json_encode([
  'success' => true,
  'delivery_days' => $days,
  'estimated_date' => $date,
  'cod_available' => (bool)$row['cod_available']
]);
