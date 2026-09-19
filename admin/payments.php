<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Admin Financial Ledger & Payments (admin/payments.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$db = get_db_connection();

$payments = $db->query("
    SELECT p.*, b.booking_code, u.full_name as customer_name, v.brand, v.model
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN users u ON b.user_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    ORDER BY p.payment_date DESC
")->fetchAll();

$total_revenue = $db->query("SELECT SUM(amount) FROM payments WHERE payment_status = 'completed'")->fetchColumn() ?: 0.00;

$page_title = "Payment Ledger — ASHVKATHA Admin";
$extra_css  = ['dashboard.css', 'admin.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">Revenue & Payment Records</h1>
        <p class="dashboard-subtitle">Monitor transaction IDs, payment gateway logs, and security deposit holdings.</p>
      </div>
    </div>

    <!-- Revenue Metric Card -->
    <div class="card" style="margin-bottom: 2rem; background: linear-gradient(135deg, var(--surface) 0%, var(--surface-alt) 100%);">
      <div style="display: flex; align-items: center; justify-content: space-between;">
        <div>
          <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Total System Revenue</span>
          <div style="font-size: 2.8rem; font-weight: 800; color: var(--success); font-family: var(--font-heading);"><?php echo format_currency($total_revenue); ?></div>
        </div>
        <div style="font-size: 3rem; color: var(--success-bg);"><i class="fa-solid fa-vault"></i></div>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Txn ID</th>
              <th>Booking Code</th>
              <th>Customer</th>
              <th>Vehicle</th>
              <th>Method</th>
              <th>Amount</th>
              <th>Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($payments as $p): ?>
              <tr>
                <td><code><?php echo htmlspecialchars($p['transaction_id']); ?></code></td>
                <td><strong><?php echo htmlspecialchars($p['booking_code']); ?></strong></td>
                <td><?php echo htmlspecialchars($p['customer_name']); ?></td>
                <td><?php echo htmlspecialchars($p['brand'] . ' ' . $p['model']); ?></td>
                <td><?php echo htmlspecialchars($p['payment_method']); ?></td>
                <td><strong><?php echo format_currency($p['amount']); ?></strong></td>
                <td><?php echo date('d M Y, h:i A', strtotime($p['payment_date'])); ?></td>
                <td><?php echo get_status_badge($p['payment_status']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

</body>
</html>
