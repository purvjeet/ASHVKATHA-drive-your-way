<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Admin Customer Management Directory (admin/customers.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$db = get_db_connection();

$customers = $db->query("
    SELECT u.*, COUNT(b.id) as total_bookings, COALESCE(SUM(b.total_amount), 0) as total_spent
    FROM users u
    LEFT JOIN bookings b ON u.id = b.user_id AND b.status != 'cancelled'
    WHERE u.role = 'customer'
    GROUP BY u.id
    ORDER BY u.id DESC
")->fetchAll();

$page_title = "Customer Directory — ASHVKATHA Admin";
$extra_css  = ['dashboard.css', 'admin.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">Customer Directory</h1>
        <p class="dashboard-subtitle">Manage customer accounts, driving license verifications, and spending history.</p>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Customer Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Driving License</th>
              <th>Total Bookings</th>
              <th>Total Spent</th>
              <th>Registered Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($customers as $c): ?>
              <tr>
                <td>#<?php echo $c['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($c['full_name']); ?></strong></td>
                <td><?php echo htmlspecialchars($c['email']); ?></td>
                <td><?php echo htmlspecialchars($c['phone']); ?></td>
                <td>
                  <?php if (!empty($c['driving_license'])): ?>
                    <span class="badge badge-success"><i class="fa-solid fa-id-card"></i> <?php echo htmlspecialchars($c['driving_license']); ?></span>
                  <?php else: ?>
                    <span class="badge badge-warning">Pending DL</span>
                  <?php endif; ?>
                </td>
                <td><strong><?php echo $c['total_bookings']; ?></strong></td>
                <td><strong><?php echo format_currency($c['total_spent']); ?></strong></td>
                <td><?php echo date('d M Y', strtotime($c['created_at'])); ?></td>
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
