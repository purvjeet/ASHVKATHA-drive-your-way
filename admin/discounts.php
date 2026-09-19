<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Admin Promo Codes & Discount Management (admin/discounts.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$db = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_discount') {
    $promo_code         = strtoupper(sanitize($_POST['promo_code']));
    $discount_type      = sanitize($_POST['discount_type']);
    $discount_value     = (float)$_POST['discount_value'];
    $min_booking_amount = (float)$_POST['min_booking_amount'];
    $expiry_date        = sanitize($_POST['expiry_date']);

    $ins = $db->prepare("
        INSERT INTO discounts (promo_code, discount_type, discount_value, min_booking_amount, expiry_date, status)
        VALUES (?, ?, ?, ?, ?, 'active')
    ");
    $ins->execute([$promo_code, $discount_type, $discount_value, $min_booking_amount, $expiry_date]);

    set_flash_message('success', 'Promo coupon code created successfully.');
    redirect('admin/discounts.php');
}

$discounts = $db->query("SELECT * FROM discounts ORDER BY id DESC")->fetchAll();

$page_title = "Promo Codes & Discounts — ASHVKATHA Admin";
$extra_css  = ['dashboard.css', 'admin.css', 'forms.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">Promo Coupons & Discounts</h1>
        <p class="dashboard-subtitle">Create seasonal promotional discount codes and percentage vouchers.</p>
      </div>
    </div>

    <div class="catalog-layout" style="grid-template-columns: 340px 1fr;">
      <!-- Add Promo Code Form -->
      <div class="card">
        <h3 style="font-size: 1.2rem; margin-bottom: 1.2rem;"><i class="fa-solid fa-ticket" style="color: var(--primary);"></i> Create Promo Code</h3>

        <form action="<?php echo url('/admin/discounts.php'); ?>" method="POST">
          <input type="hidden" name="action" value="add_discount">

          <div class="form-group">
            <label class="form-label">Promo Code *</label>
            <input type="text" name="promo_code" class="form-control" placeholder="e.g. FESTIVE20" required>
          </div>

          <div class="form-group">
            <label class="form-label">Discount Type *</label>
            <select name="discount_type" class="form-control" required>
              <option value="percentage">Percentage (%)</option>
              <option value="fixed">Fixed Amount (₹)</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Discount Value *</label>
            <input type="number" step="0.01" name="discount_value" class="form-control" placeholder="10 or 500" required>
          </div>

          <div class="form-group">
            <label class="form-label">Min Booking Amount (₹)</label>
            <input type="number" step="0.01" name="min_booking_amount" class="form-control" value="0.00" required>
          </div>

          <div class="form-group">
            <label class="form-label">Expiry Date *</label>
            <input type="date" name="expiry_date" class="form-control" value="<?php echo date('Y-12-31'); ?>" required>
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fa-solid fa-plus"></i> Save Promo Code</button>
        </form>
      </div>

      <!-- Promo Codes Table -->
      <div class="card">
        <h3 style="font-size: 1.2rem; margin-bottom: 1.2rem;"><i class="fa-solid fa-list"></i> Active Promo Coupons</h3>

        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Type</th>
                <th>Value</th>
                <th>Min Amount</th>
                <th>Expiry</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($discounts as $d): ?>
                <tr>
                  <td><code><?php echo htmlspecialchars($d['promo_code']); ?></code></td>
                  <td><?php echo ucfirst($d['discount_type']); ?></td>
                  <td>
                    <strong>
                      <?php echo ($d['discount_type'] === 'percentage') ? $d['discount_value'] . '%' : format_currency($d['discount_value']); ?>
                    </strong>
                  </td>
                  <td><?php echo format_currency($d['min_booking_amount']); ?></td>
                  <td><?php echo date('d M Y', strtotime($d['expiry_date'])); ?></td>
                  <td><?php echo get_status_badge($d['status']); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

</body>
</html>
