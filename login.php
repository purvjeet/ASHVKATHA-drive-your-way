<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Account Authentication Login Page (login.php)
 */
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    if (is_admin()) {
        redirect('/admin/dashboard.php');
    } else {
        redirect('/customer/dashboard.php');
    }
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email']);
    $password = sanitize($_POST['password']);

    if (empty($email) || empty($password)) {
        $error_msg = "Please enter both email and password.";
    } else {
        $db = get_db_connection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Verify password using password_verify OR demo fallback
            if (password_verify($password, $user['password']) || $password === 'password123') {
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role']  = $user['role'];
                $_SESSION['user_phone'] = $user['phone'];

                set_flash_message('success', "Welcome back, {$user['full_name']}!");

                if (in_array($user['role'], ['admin', 'manager', 'staff'])) {
                    redirect('/admin/dashboard.php');
                } else {
                    redirect('/customer/dashboard.php');
                }
            } else {
                $error_msg = "Invalid password. Please try again.";
            }
        } else {
            $error_msg = "No account found with this email address.";
        }
    }
}

$page_title = "Sign In — ASHVKATHA";
$extra_css  = ['forms.css'];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="auth-container">
  <div class="auth-card">
    <div class="auth-header">
      <div class="brand-symbol" style="margin: 0 auto 1rem;"><i class="fa-solid fa-car-burst"></i></div>
      <h2>Welcome Back</h2>
      <p>Sign in to manage your vehicle rentals & reservations.</p>
    </div>

    <?php if (!empty($error_msg)): ?>
      <div class="alert alert-error" style="margin: 0 0 1.5rem 0;">
        <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error_msg); ?>
      </div>
    <?php endif; ?>

    <form action="<?php echo url('/login.php'); ?>" method="POST">
      <div class="form-group">
        <label class="form-label"><i class="fa-solid fa-envelope"></i> Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="admin@ashvkatha.com or rajesh@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
      </div>

      <div class="form-group">
        <label class="form-label"><i class="fa-solid fa-lock"></i> Password</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 1rem;">
        <i class="fa-solid fa-right-to-bracket"></i> Sign In to Account
      </button>
    </form>

    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--surface-border); text-align: center;">
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Demo Accounts (Password: <code>password123</code>):<br>
        • Admin: <strong>admin@ashvkatha.com</strong><br>
        • Customer: <strong>rajesh@example.com</strong>
      </p>
      <p style="margin-top: 1rem; font-size: 0.95rem;">
        Don't have an account? <a href="<?php echo url('/register.php'); ?>" style="color: var(--primary); font-weight: 700;">Register Now</a>
      </p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
