<?php
session_start();
if (isset($_SESSION['admin_id'])) {
  header('Location: dashboard.php');
  exit;
}
require_once '../includes/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $username = mysqli_real_escape_string($conn, $_POST['username']);
  $password = $_POST['password'];

  $q_r = mysqli_query($conn, "SELECT * FROM admin WHERE username='$username'");
  $q = $q_r ? $q_r : false;
  if ($q && mysqli_num_rows($q) == 1) {
    $admin = mysqli_fetch_assoc($q);
    if (password_verify($password, $admin['password'])) {
      $_SESSION['admin_id'] = $admin['id'];
      $_SESSION['admin_name'] = $admin['username'];
      header('Location: dashboard.php');
      exit;
    } else {
      $error = 'Invalid password!';
    }
  } else {
    $error = 'Username not found!';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login - EasyShop</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #f8f9fa 0%, #fff5f5 50%, #f8f9fa 100%);
      position: relative;
      overflow: hidden;
    }

    /* Animated background shapes */
    .bg-shapes {
      position: fixed;
      inset: 0;
      z-index: 0;
      overflow: hidden;
    }
    .bg-shapes .shape {
      position: absolute;
      border-radius: 50%;
      animation: floatShape 20s infinite ease-in-out;
    }
    .bg-shapes .shape:nth-child(1) {
      width: 500px; height: 500px;
      background: radial-gradient(circle, rgba(139,0,0,0.12), transparent 70%);
      top: -200px; left: -100px;
      animation-delay: 0s;
    }
    .bg-shapes .shape:nth-child(2) {
      width: 400px; height: 400px;
      background: radial-gradient(circle, rgba(193,18,31,0.1), transparent 70%);
      bottom: -150px; right: -100px;
      animation-delay: -7s;
    }
    .bg-shapes .shape:nth-child(3) {
      width: 300px; height: 300px;
      background: radial-gradient(circle, rgba(139,0,0,0.08), transparent 70%);
      top: 40%; right: -50px;
      animation-delay: -14s;
    }

    @keyframes floatShape {
      0%, 100% { transform: translate(0, 0) scale(1); }
      33% { transform: translate(40px, -30px) scale(1.1); }
      66% { transform: translate(-30px, 20px) scale(0.9); }
    }

    /* Dot pattern overlay */
    .dot-overlay {
      position: fixed;
      inset: 0;
      z-index: 0;
      opacity: 0.3;
      background-image: radial-gradient(#8B0000 1px, transparent 1px);
      background-size: 40px 40px;
    }

    /* Container */
    .login-container {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 440px;
      padding: 20px;
      animation: fadeUp 0.8s ease;
    }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(40px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Card */
    .login-card {
      background: rgba(255,255,255,0.97);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      padding: 44px 36px 36px;
      box-shadow:
        0 0 0 1px rgba(255,255,255,0.1),
        0 20px 60px rgba(0,0,0,0.4),
        0 0 80px rgba(139,0,0,0.05);
      position: relative;
      overflow: hidden;
    }

    .login-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #8B0000, #c1121f, #8B0000);
      background-size: 200% 100%;
      animation: shimmer 3s infinite linear;
    }

    @keyframes shimmer {
      0% { background-position: 200% 0; }
      100% { background-position: -200% 0; }
    }

    .login-card .brand {
      text-align: center;
      margin-bottom: 6px;
    }

    .login-card .brand .logo-icon {
      width: 56px;
      height: 56px;
      background: linear-gradient(135deg, #8B0000, #c1121f);
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      color: #fff;
      font-size: 28px;
      box-shadow: 0 8px 24px rgba(139,0,0,0.25);
      animation: pulseIcon 2s ease-in-out infinite;
    }

    @keyframes pulseIcon {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    .login-card .brand h2 {
      font-size: 24px;
      font-weight: 800;
      color: #8B0000;
      letter-spacing: -0.5px;
    }

    .login-card .brand h2 span { color: #1a1a2e; }

    .login-card .brand p {
      color: #888;
      font-size: 14px;
      margin-top: 4px;
    }

    /* Form */
    .login-card .form-group {
      margin-bottom: 20px;
    }

    .login-card .form-group label {
      font-size: 12px;
      font-weight: 600;
      color: #555;
      margin-bottom: 6px;
      display: block;
    }

    .login-card .form-group .input-wrap {
      position: relative;
    }

    .login-card .form-group .input-wrap .input-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: #bbb;
      font-size: 18px;
      transition: 0.3s;
      pointer-events: none;
      z-index: 2;
    }

    .login-card .form-group .input-wrap input {
      width: 100%;
      padding: 13px 14px 13px 44px;
      border: 2px solid #eee;
      border-radius: 14px;
      font-size: 14px;
      font-family: inherit;
      outline: none;
      background: #f8f9fa;
      transition: all 0.3s ease;
    }

    .login-card .form-group .input-wrap input:focus {
      border-color: #8B0000;
      background: #fff;
      box-shadow: 0 0 0 4px rgba(139,0,0,0.08);
    }

    .login-card .form-group .input-wrap input:focus ~ .input-icon {
      color: #8B0000;
    }

    .login-card .form-group input::placeholder {
      color: #ccc;
    }

    /* Button */
    .login-card .btn-login {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #8B0000, #c1121f);
      color: #fff;
      border: none;
      border-radius: 14px;
      font-size: 15px;
      font-weight: 700;
      font-family: inherit;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .login-card .btn-login:hover {
      transform: translateY(-3px);
      box-shadow: 0 12px 32px rgba(139,0,0,0.3);
    }

    .login-card .btn-login:active {
      transform: translateY(0);
    }

    .login-card .btn-login .btn-shimmer {
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
      animation: btnShimmer 3s infinite;
    }

    @keyframes btnShimmer {
      0% { left: -100%; }
      100% { left: 100%; }
    }

    /* Error */
    .login-card .error-msg {
      background: #fff0f0;
      border: 1px solid #ffd7d7;
      border-radius: 12px;
      padding: 10px 14px;
      font-size: 13px;
      color: #c1121f;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
      animation: shake 0.4s ease;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-6px); }
      75% { transform: translateX(6px); }
    }

    /* Footer link */
    .login-card .back-link {
      text-align: center;
      margin-top: 20px;
    }

    .login-card .back-link a {
      color: #999;
      font-size: 13px;
      text-decoration: none;
      transition: 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .login-card .back-link a:hover {
      color: #8B0000;
    }

    /* Decorative dots */
    .login-card .dots {
      position: absolute;
      top: 20px;
      right: 20px;
      display: flex;
      gap: 6px;
    }

    .login-card .dots span {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #eee;
    }

    .login-card .dots span:nth-child(1) { background: #8B0000; }
    .login-card .dots span:nth-child(2) { background: #c1121f; }
    .login-card .dots span:nth-child(3) { background: #e63946; }

    /* Responsive */
    @media (max-width: 576px) {
      .login-container { padding: 12px; }
      .login-card { padding: 28px 18px 24px; border-radius: 18px; }
      .login-card .brand .logo-icon { width: 46px; height: 46px; font-size: 22px; margin-bottom: 12px; }
      .login-card .brand h2 { font-size: 20px; }
      .login-card .brand p { font-size: 13px; }
      .login-card .form-group { margin-bottom: 16px; }
      .login-card .form-group input { padding: 11px 12px 11px 38px; font-size: 14px; border-radius: 11px; }
      .login-card .form-group .input-wrap .input-icon { left: 11px; font-size: 15px; }
      .login-card .form-group label { font-size: 11px; }
      .login-card .btn-login { padding: 12px; font-size: 14px; border-radius: 11px; }
    }

    @media (max-width: 380px) {
      .login-card { padding: 22px 14px 20px; }
      .login-card .brand .logo-icon { width: 40px; height: 40px; font-size: 18px; }
      .login-card .brand h2 { font-size: 18px; }
      .login-card .form-group .input-wrap input { padding: 10px 10px 10px 32px; font-size: 13px; }
      .login-card .form-group .input-wrap .input-icon { left: 9px; font-size: 13px; }
    }
  </style>
</head>
<body>

<div class="bg-shapes">
  <div class="shape"></div>
  <div class="shape"></div>
  <div class="shape"></div>
</div>
<div class="dot-overlay"></div>

<div class="login-container">
  <div class="login-card">
    <div class="dots">
      <span></span><span></span><span></span>
    </div>

    <div class="brand">
      <div class="logo-icon"><i class="bi bi-shop"></i></div>
      <h2>Easy<span>Shop</span></h2>
      <p>Admin Panel Login</p>
    </div>

    <?php if ($error): ?>
      <div class="error-msg">
        <i class="bi bi-exclamation-circle"></i>
        <?php echo $error; ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Username</label>
        <div class="input-wrap">
          <input type="text" name="username" placeholder="Enter your username" required autofocus>
          <i class="bi bi-person input-icon"></i>
        </div>
      </div>

      <div class="form-group">
        <label>Password</label>
        <div class="input-wrap">
          <input type="password" name="password" placeholder="Enter your password" required>
          <i class="bi bi-lock input-icon"></i>
        </div>
      </div>

      <button type="submit" class="btn-login">
        <span class="btn-shimmer"></span>
        <i class="bi bi-box-arrow-in-right"></i>
        Sign In
      </button>
    </form>

    <div class="back-link">
      <a href="../index.php"><i class="bi bi-arrow-left"></i> Back to Website</a>
    </div>
  </div>
</div>

</body>
</html>
