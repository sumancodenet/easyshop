<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// $host = 'sql110.infinityfree.com';
// $user = 'if0_42551967';
// $pass = '21bZIf0zSibwnz';
// $db   = 'if0_42551967_easyshopy';

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'easyshop';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
  die("Connection failed: " . mysqli_connect_error());
}

// Remember me - auto login from cookie if no active session
if (empty($_SESSION['user']) && !empty($_COOKIE['easyshop_remember'])) {
  $token = substr(preg_replace('/[^a-zA-Z0-9]/', '', $_COOKIE['easyshop_remember']), 0, 64);
  if (strlen($token) === 64) {
    $token = mysqli_real_escape_string($conn, $token);
    $q = mysqli_query($conn, "SELECT * FROM users WHERE remember_token='$token' LIMIT 1");
    if ($q && $u = mysqli_fetch_assoc($q)) {
      $_SESSION['user'] = $u;
      // Refresh cookie so it doesn't expire while the user is active
      setcookie('easyshop_remember', $token, time() + 30 * 24 * 3600, '/', '', false, true);
    }
  }
}

// Site base URL - derived from the current request so it works on any domain
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$site_root = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
define('SITE_URL', $https . '://' . $_SERVER['HTTP_HOST'] . $site_root);
define('ADMIN_URL', SITE_URL . '/admin');

// EasyPay Payment Gateway
// API credentials - generate from EasyPay dashboard under API Credentials
define('EASYPAY_API_KEY', 'EP_586A90193F9B317882701E958187ECCE60EB19017F38D1A4');      // starts with EP_
define('EASYPAY_SECRET_KEY', 'SEC_6114B2CCB7111863830FD991E4B568D5C591C929B853BFAC540BAE21C80455DE');   // starts with SEC_
// Base URL is the ROOT domain - the SDK and API helpers append /api/... themselves
define('EASYPAY_BASE_URL', 'https://easypay-sigma-two.vercel.app');
define('EASYPAY_SDK_URL', 'https://easypay-sigma-two.vercel.app/sdk/easypay-sdk.js');
?>
