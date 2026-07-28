<?php
require_once 'includes/config.php';
unset($_SESSION['user']);
header('Location: login.php');
exit;
