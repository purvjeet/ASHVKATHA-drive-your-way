<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Admin System Settings (admin/settings.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    set_flash_message('success', 'System configuration settings updated.');
    redirect('admin/settings.php');
}

$page_title = "System Settings — ASHVKATHA Admin";
$extra_css  = ['dashboard.css', 'admin.css', 'forms.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">System Settings & Defaults</h1>
        <p class="dashboard-subtitle">Configure site identity, default tax rates, currency symbol, and platform defaults.</p>
      </div>
    </div>

    <div class="card" style="max-width: 750px;">
      <form action="<?php echo url('/admin/settings.php'); ?>" method="POST">
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Platform Brand Name</label>
            <input type="text" class="form-control" value="ASHVKATHA" required>
          </div>

          <div class="form-group">
            <label class="form-label">Brand Tagline</label>
            <input type="text" class="form-control" value="Drive Your Way" required>
          </div>
        </div>

        <div class="form-grid-3">
          <div class="form-group">
            <label class="form-label">Currency Symbol</label>
            <input type="text" class="form-control" value="₹" required>
          </div>

          <div class="form-group">
            <label class="form-label">Default GST Tax Rate (%)</label>
            <input type="number" step="0.01" class="form-control" value="18.00" required>
          </div>

          <div class="form-group">
            <label class="form-label">Support Email</label>
            <input type="email" class="form-control" value="support@ashvkatha.com" required>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="margin-top: 1rem;">
          <i class="fa-solid fa-floppy-disk"></i> Save Configuration
        </button>
      </form>
    </div>
  </main>
</div>

</body>
</html>
