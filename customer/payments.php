<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Customer Payment History Ledger (customer/payments.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$user = get_logged_user();
$db = get_db_connection();

$stmt = $db->prepare("
    SELECT p.*, b.booking_code, v.brand, v.model
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.user_id = ?
    ORDER BY p.payment_date DESC
");
$stmt->execute([$user['id']]);
$payments = $stmt->fetchAll();

$page_title = "Payment History — ASHVKATHA";
$extra_css  = ['dashboard.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/customer-sidebar.php'; ?>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">Payment Transaction Records</h1>
        <p class="dashboard-subtitle">Complete ledger of all payments and security deposits processed.</p>
      </div>
    </div>

    <div class="card">
      <?php if (empty($payments)): ?>
        <p style="text-align: center; color: var(--text-muted); padding: 3rem 0;">No payment transactions recorded.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Transaction ID</th>
                <th>Booking Code</th>
                <th>Vehicle</th>
                <th>Payment Method</th>
                <th>Date & Time</th>
                <th>Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($payments as $p): ?>
                <tr>
                  <td><code><?php echo htmlspecialchars($p['transaction_id']); ?></code></td>
                  <td><strong><?php echo htmlspecialchars($p['booking_code']); ?></strong></td>
                  <td><?php echo htmlspecialchars($p['brand'] . ' ' . $p['model']); ?></td>
                  <td><?php echo htmlspecialchars($p['payment_method']); ?></td>
                  <td><?php echo date('d M Y, h:i A', strtotime($p['payment_date'])); ?></td>
                  <td><strong><?php echo format_currency($p['amount']); ?></strong></td>
                  <td><?php echo get_status_badge($p['payment_status']); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
