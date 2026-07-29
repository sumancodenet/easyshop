<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$host = 'sql302.infinityfree.com';
$user = 'if0_42526826';
$pass = 'LkI4E94e3iBFsOR';
$db   = 'if0_42526826_easyshop';

// $host = 'localhost';
// $user = 'root';
// $pass = '';
// $db   = 'easyshop';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
  die("Connection failed: " . mysqli_connect_error());
}

define('SITE_URL', 'http://localhost/easyshop');
define('ADMIN_URL', SITE_URL . '/admin');
?>
