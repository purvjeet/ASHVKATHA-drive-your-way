<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Customer Registration Page (register.php)
 */
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('/customer/dashboard.php');
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name       = sanitize($_POST['full_name']);
    $email           = sanitize($_POST['email']);
    $password        = sanitize($_POST['password']);
    $phone           = sanitize($_POST['phone']);
    $driving_license = sanitize($_POST['driving_license']);
    $address         = sanitize($_POST['address']);

    if (empty($full_name) || empty($email) || empty($password) || empty($phone)) {
        $error_msg = "Please fill in all required fields.";
    } else {
        $db = get_db_connection();
        
        // Check if email already registered
        $check_stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->execute([$email]);
        if ($check_stmt->fetch()) {
            $error_msg = "An account with this email address already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $ins_stmt = $db->prepare("
                INSERT INTO users (full_name, email, password, phone, role, driving_license, address)
                VALUES (?, ?, ?, ?, 'customer', ?, ?)
            ");
            $ins_stmt->execute([$full_name, $email, $hashed_password, $phone, $driving_license, $address]);
            $new_user_id = $db->lastInsertId();

            // Auto Log-in
            $_SESSION['user_id']    = $new_user_id;
            $_SESSION['user_name']  = $full_name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role']  = 'customer';
            $_SESSION['user_phone'] = $phone;

            set_flash_message('success', "Registration successful! Welcome to Ashvkatha.");
            redirect('/customer/dashboard.php');
        }
    }
}

$page_title = "Create Customer Account — ASHVKATHA";
$extra_css  = ['forms.css'];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="auth-container">
  <div class="auth-card" style="max-width: 580px;">
    <div class="auth-header">
      <div class="brand-symbol" style="margin: 0 auto 1rem;"><i class="fa-solid fa-car-burst"></i></div>
      <h2>Create Your Account</h2>
      <p>Register to unlock instant vehicle reservations & member rates.</p>
    </div>

    <?php if (!empty($error_msg)): ?>
      <div class="alert alert-error" style="margin: 0 0 1.5rem 0;">
        <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error_msg); ?>
      </div>
    <?php endif; ?>

    <form action="<?php echo url('/register.php'); ?>" method="POST">
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-user"></i> Full Name *</label>
          <input type="text" name="full_name" class="form-control" placeholder="John Doe" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-envelope"></i> Email Address *</label>
          <input type="email" name="email" class="form-control" placeholder="name@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
        </div>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-lock"></i> Password *</label>
          <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
        </div>

        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-phone"></i> Phone Number *</label>
          <input type="text" name="phone" class="form-control" placeholder="+91 98765 43210" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label"><i class="fa-solid fa-id-card"></i> Driving License Number (Optional)</label>
        <input type="text" name="driving_license" class="form-control" placeholder="DL-GJ01-2024001" value="<?php echo htmlspecialchars($_POST['driving_license'] ?? ''); ?>">
      </div>

      <div class="form-group">
        <label class="form-label"><i class="fa-solid fa-location-dot"></i> Residential Address</label>
        <textarea name="address" class="form-control" placeholder="Enter full address..."><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
      </div>

      <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 1rem;">
        <i class="fa-solid fa-user-plus"></i> Complete Registration
      </button>
    </form>

    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--surface-border); text-align: center;">
      <p style="font-size: 0.95rem;">
        Already have an account? <a href="<?php echo url('/login.php'); ?>" style="color: var(--primary); font-weight: 700;">Sign In Here</a>
      </p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
