<?php
require_once 'includes/config.php';
if (isset($_SESSION['user']) && !empty($_COOKIE['easyshop_remember'])) {
  $token = substr(preg_replace('/[^a-zA-Z0-9]/', '', $_COOKIE['easyshop_remember']), 0, 64);
  if (strlen($token) === 64) {
    $token = mysqli_real_escape_string($conn, $token);
    mysqli_query($conn, "UPDATE users SET remember_token=NULL WHERE remember_token='$token'");
  }
}
unset($_SESSION['user']);
setcookie('easyshop_remember', '', time() - 3600, '/');
header('Location: login.php');
exit;
