<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'easyshop';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
  die("Connection failed: " . mysqli_connect_error());
}

define('SITE_URL', 'http://localhost/easyshop');
define('ADMIN_URL', SITE_URL . '/admin');
?>
