<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Customer Profile Management (customer/profile.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$user = get_logged_user();
$db = get_db_connection();

// Fetch current user row
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);
$u_data = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name       = sanitize($_POST['full_name']);
    $phone           = sanitize($_POST['phone']);
    $driving_license = sanitize($_POST['driving_license']);
    $address         = sanitize($_POST['address']);
    $new_password    = sanitize($_POST['new_password'] ?? '');

    if (!empty($new_password)) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $up_stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ?, driving_license = ?, address = ?, password = ? WHERE id = ?");
        $up_stmt->execute([$full_name, $phone, $driving_license, $address, $hashed, $user['id']]);
    } else {
        $up_stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ?, driving_license = ?, address = ? WHERE id = ?");
        $up_stmt->execute([$full_name, $phone, $driving_license, $address, $user['id']]);
    }

    $_SESSION['user_name']  = $full_name;
    $_SESSION['user_phone'] = $phone;

    set_flash_message('success', 'Profile updated successfully.');
    redirect('customer/profile.php');
}

$page_title = "My Profile & License — ASHVKATHA";
$extra_css  = ['dashboard.css', 'forms.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/customer-sidebar.php'; ?>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">Profile & Driving Credentials</h1>
        <p class="dashboard-subtitle">Manage your personal information, phone number, and driving license details.</p>
      </div>
    </div>

    <div class="card" style="max-width: 700px;">
      <form action="<?php echo url('/customer/profile.php'); ?>" method="POST">
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label"><i class="fa-solid fa-user"></i> Full Name</label>
            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($u_data['full_name']); ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label"><i class="fa-solid fa-envelope"></i> Email Address (Locked)</label>
            <input type="email" class="form-control" value="<?php echo htmlspecialchars($u_data['email']); ?>" readonly disabled>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label"><i class="fa-solid fa-phone"></i> Contact Phone</label>
            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($u_data['phone']); ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label"><i class="fa-solid fa-id-card"></i> Driving License Number</label>
            <input type="text" name="driving_license" class="form-control" value="<?php echo htmlspecialchars($u_data['driving_license'] ?? ''); ?>" placeholder="DL-GJ01-2024001" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-location-dot"></i> Residential Address</label>
          <textarea name="address" class="form-control" required><?php echo htmlspecialchars($u_data['address'] ?? ''); ?></textarea>
        </div>

        <div class="form-group" style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--surface-border);">
          <label class="form-label"><i class="fa-solid fa-key"></i> New Password (Leave blank to keep unchanged)</label>
          <input type="password" name="new_password" class="form-control" placeholder="••••••••">
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="margin-top: 1rem;">
          <i class="fa-solid fa-floppy-disk"></i> Update Profile Settings
        </button>
      </form>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
