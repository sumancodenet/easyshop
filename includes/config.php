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

define('SITE_URL', 'http://localhost/easyshop');
define('ADMIN_URL', SITE_URL . '/admin');

// EasyPay Payment Gateway
// API credentials - generate from EasyPay dashboard under API Credentials
define('EASYPAY_API_KEY', 'EP_3DD153A79412422343EC4816FE3167EBBED955DEEB96C1BB');      // starts with EP_
define('EASYPAY_SECRET_KEY', 'SEC_ED03111933D7C73530C60FC4A003765A5803C9447B7D16BA1621E8C709CFFBEE');   // starts with SEC_
// Base URL is the ROOT domain - the SDK and API helpers append /api/... themselves
define('EASYPAY_BASE_URL', 'http://localhost:8080');
define('EASYPAY_SDK_URL', 'http://localhost:8080/sdk/easypay-sdk.js');
?>
