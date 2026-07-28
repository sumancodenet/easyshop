<?php require_once 'includes/config.php'; include 'includes/header.php';

$error = $success = '';

// Redirect if already logged in
if (isset($_SESSION['user'])) {
  header('Location: account.php');
  exit;
}

// Login
if (isset($_POST['login'])) {
  $email_phone = mysqli_real_escape_string($conn, $_POST['email_phone']);
  $password = $_POST['password'];
  $q = mysqli_query($conn, "SELECT * FROM users WHERE email='$email_phone' OR phone='$email_phone'");
  if ($q && $u = mysqli_fetch_assoc($q)) {
    if (password_verify($password, $u['password'])) {
      $_SESSION['user'] = $u;
      $redir = $_SESSION['redirect_after_login'] ?? 'account.php';
      unset($_SESSION['redirect_after_login']);
      header("Location: $redir");
      exit;
    } else {
      $error = 'Invalid email/phone or password';
    }
  } else {
    $error = 'Invalid email/phone or password';
  }
}

// Register
if (isset($_POST['register'])) {
  $name = mysqli_real_escape_string($conn, $_POST['name']);
  $email = mysqli_real_escape_string($conn, $_POST['email']);
  $phone = mysqli_real_escape_string($conn, $_POST['phone']);
  $password = $_POST['password'];
  $cpassword = $_POST['cpassword'];

  if ($password !== $cpassword) {
    $error = 'Passwords do not match';
  } elseif (strlen($password) < 6) {
    $error = 'Password must be at least 6 characters';
  } else {
    $check = mysqli_query($conn, "SELECT id FROM users WHERE email='$email'");
    if ($check && mysqli_num_rows($check) > 0) {
      $error = 'Email already registered';
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $q = mysqli_query($conn, "INSERT INTO users (name, email, phone, password) VALUES ('$name', '$email', '$phone', '$hash')");
      if ($q) {
        $success = 'Account created! Please login.';
      } else {
        $error = 'Registration failed: ' . mysqli_error($conn);
      }
    }
  }
}
?>
<div class="container py-4" style="max-width:480px;">
  <h4 style="font-weight:800;margin-bottom:20px;text-align:center;"><?php echo isset($_GET['register']) ? 'Create Account' : 'Sign In'; ?></h4>

  <?php if ($error): ?>
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:var(--radius-sm);padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;gap:10px;color:#dc2626;font-size:13px;font-weight:500;"><i class="bi bi-exclamation-circle" style="font-size:18px;"></i> <?php echo $error; ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:var(--radius-sm);padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;gap:10px;color:#16a34a;font-size:13px;font-weight:500;"><i class="bi bi-check-circle" style="font-size:18px;"></i> <?php echo $success; ?></div>
  <?php endif; ?>

  <?php if (!isset($_GET['register'])): ?>
    <!-- LOGIN FORM -->
    <div style="background:#fff;border:1px solid #eee;border-radius:12px;padding:24px;">
      <form method="POST">
        <div style="margin-bottom:14px;">
          <label style="font-size:13px;font-weight:600;color:#555;margin-bottom:4px;display:block;">Email or Phone</label>
          <input type="text" name="email_phone" class="form-control" required placeholder="your@email.com or 9876543210">
        </div>
        <div style="margin-bottom:14px;">
          <label style="font-size:13px;font-weight:600;color:#555;margin-bottom:4px;display:block;">Password</label>
          <input type="password" name="password" class="form-control" required placeholder="••••••">
        </div>
        <button type="submit" name="login" class="btn-red" style="width:100%;padding:12px;font-size:15px;"><i class="bi bi-box-arrow-in-right"></i> Sign In</button>
      </form>
      <div style="text-align:center;margin-top:16px;font-size:13px;color:#999;">
        Don't have an account? <a href="login.php?register=1" style="color:var(--red);font-weight:600;">Register</a>
      </div>
    </div>
  <?php else: ?>
    <!-- REGISTER FORM -->
    <div style="background:#fff;border:1px solid #eee;border-radius:12px;padding:24px;">
      <form method="POST">
        <div style="margin-bottom:14px;">
          <label style="font-size:13px;font-weight:600;color:#555;margin-bottom:4px;display:block;">Full Name</label>
          <input type="text" name="name" class="form-control" required placeholder="John Doe">
        </div>
        <div style="margin-bottom:14px;">
          <label style="font-size:13px;font-weight:600;color:#555;margin-bottom:4px;display:block;">Email</label>
          <input type="email" name="email" class="form-control" required placeholder="your@email.com">
        </div>
        <div style="margin-bottom:14px;">
          <label style="font-size:13px;font-weight:600;color:#555;margin-bottom:4px;display:block;">Phone</label>
          <input type="text" name="phone" class="form-control" placeholder="+91 98765 43210">
        </div>
        <div style="margin-bottom:14px;">
          <label style="font-size:13px;font-weight:600;color:#555;margin-bottom:4px;display:block;">Password</label>
          <input type="password" name="password" class="form-control" required placeholder="Min 6 characters">
        </div>
        <div style="margin-bottom:14px;">
          <label style="font-size:13px;font-weight:600;color:#555;margin-bottom:4px;display:block;">Confirm Password</label>
          <input type="password" name="cpassword" class="form-control" required placeholder="Repeat password">
        </div>
        <small style="color:#999;display:block;margin-bottom:14px;">You can add your address later in your account settings.</small>
        <button type="submit" name="register" class="btn-red" style="width:100%;padding:12px;font-size:15px;"><i class="bi bi-person-plus"></i> Create Account</button>
      </form>
      <div style="text-align:center;margin-top:16px;font-size:13px;color:#999;">
        Already have an account? <a href="login.php" style="color:var(--red);font-weight:600;">Sign In</a>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
