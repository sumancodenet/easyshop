<?php require_once 'includes/config.php';
$error = $success = '';
$is_register = isset($_GET['register']);

if (isset($_SESSION['user'])) {
  header('Location: account.php');
  exit;
}

// Login
if (isset($_POST['login'])) {
  $email_phone = mysqli_real_escape_string($conn, $_POST['email_phone']);
  $password = $_POST['password'];
  $remember = !empty($_POST['remember']);
  $q = mysqli_query($conn, "SELECT * FROM users WHERE email='$email_phone' OR phone='$email_phone'");
  if ($q && $u = mysqli_fetch_assoc($q)) {
    if (password_verify($password, $u['password'])) {
      $_SESSION['user'] = $u;
      if ($remember) {
        $token = bin2hex(random_bytes(32));
        mysqli_query($conn, "UPDATE users SET remember_token='$token' WHERE id={$u['id']}");
        setcookie('easyshop_remember', $token, time() + 30 * 24 * 3600, '/', '', false, true);
      }
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
        $success = 'Account created successfully! Please sign in.';
        $is_register = false;
      } else {
        $error = 'Registration failed: ' . mysqli_error($conn);
      }
    }
  }
}

include 'includes/header.php'; ?>
<div class="auth-page">
  <div class="auth-card">
    <div class="auth-header">
      <a href="index.php" class="brand">Easy<span>Shop</span></a>
      <p><?php echo $is_register ? 'Create your account to get started' : 'Welcome back! Sign in to your account'; ?></p>
    </div>
    <div class="auth-body">
      <div class="auth-tabs">
        <a href="login.php" class="<?php echo !$is_register ? 'active' : ''; ?>"><i class="bi bi-box-arrow-in-right me-1"></i>Sign In</a>
        <a href="login.php?register=1" class="<?php echo $is_register ? 'active' : ''; ?>"><i class="bi bi-person-plus me-1"></i>Register</a>
      </div>

      <?php if ($error): ?>
        <div class="auth-alert error"><i class="bi bi-exclamation-circle"></i> <?php echo $error; ?></div>
      <?php endif; ?>
      <?php if ($success): ?>
        <div class="auth-alert success"><i class="bi bi-check-circle"></i> <?php echo $success; ?></div>
      <?php endif; ?>

      <?php if (!$is_register): ?>
        <!-- LOGIN FORM -->
        <form method="POST">
          <div class="auth-field">
            <input type="text" name="email_phone" class="form-control" required placeholder="Email or phone number">
            <i class="bi bi-envelope input-icon"></i>
          </div>
          <div class="auth-field">
            <input type="password" name="password" id="loginPass" class="form-control" required placeholder="Password">
            <i class="bi bi-lock input-icon"></i>
            <button type="button" class="toggle-pass" onclick="togglePass('loginPass',this)"><i class="bi bi-eye"></i></button>
          </div>
          <div style="display:flex;align-items:center;justify-content:space-between;margin:4px 2px 14px;font-size:13px;">
            <label style="display:flex;align-items:center;gap:6px;color:#555;cursor:pointer;">
              <input type="checkbox" name="remember" style="accent-color:var(--red);width:16px;height:16px;cursor:pointer;" checked>
              Remember me
            </label>
          </div>
          <button type="submit" name="login" class="btn-auth"><i class="bi bi-box-arrow-in-right"></i> Sign In</button>
        </form>
        <div class="auth-divider">or continue with</div>
        <div class="d-flex gap-2">
          <button class="btn btn-outline-secondary flex-fill" style="border-radius:12px;height:44px;font-size:14px;font-weight:600;" disabled><i class="bi bi-google"></i> Google</button>
          <button class="btn btn-outline-secondary flex-fill" style="border-radius:12px;height:44px;font-size:14px;font-weight:600;" disabled><i class="bi bi-facebook"></i> Facebook</button>
        </div>
        <p class="text-center mt-3 mb-0" style="font-size:13px;color:#999;">
          Don't have an account? <a href="login.php?register=1" style="color:var(--red);font-weight:600;">Register</a>
        </p>

      <?php else: ?>
        <!-- REGISTER FORM -->
        <form method="POST">
          <div class="auth-field">
            <input type="text" name="name" class="form-control" required placeholder="Full name">
            <i class="bi bi-person input-icon"></i>
          </div>
          <div class="auth-field">
            <input type="email" name="email" class="form-control" required placeholder="Email address">
            <i class="bi bi-envelope input-icon"></i>
          </div>
          <div class="auth-field">
            <input type="text" name="phone" class="form-control" placeholder="Phone number (optional)">
            <i class="bi bi-telephone input-icon"></i>
          </div>
          <div class="auth-field">
            <input type="password" name="password" id="regPass" class="form-control" required placeholder="Password (min 6 characters)">
            <i class="bi bi-lock input-icon"></i>
            <button type="button" class="toggle-pass" onclick="togglePass('regPass',this)"><i class="bi bi-eye"></i></button>
          </div>
          <div class="auth-field">
            <input type="password" name="cpassword" id="regCPass" class="form-control" required placeholder="Confirm password">
            <i class="bi bi-lock-fill input-icon"></i>
            <button type="button" class="toggle-pass" onclick="togglePass('regCPass',this)"><i class="bi bi-eye"></i></button>
          </div>
          <button type="submit" name="register" class="btn-auth"><i class="bi bi-person-plus"></i> Create Account</button>
        </form>
        <p class="text-center mt-3 mb-0" style="font-size:13px;color:#999;">
          Already have an account? <a href="login.php" style="color:var(--red);font-weight:600;">Sign In</a>
        </p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function togglePass(id, btn) {
  var inp = document.getElementById(id);
  if (inp.type === 'password') {
    inp.type = 'text';
    btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
  } else {
    inp.type = 'password';
    btn.innerHTML = '<i class="bi bi-eye"></i>';
  }
}
</script>

<?php include 'includes/footer.php'; ?>
