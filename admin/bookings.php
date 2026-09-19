<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Admin Bookings Management (admin/bookings.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$db = get_db_connection();

// Process Status Update Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_booking_status') {
    $b_id   = (int)$_POST['booking_id'];
    $status = sanitize($_POST['status']);

    $db->prepare("UPDATE bookings SET status = ? WHERE id = ?")->execute([$status, $b_id]);

    // Update associated vehicle status
    $v_stmt = $db->prepare("SELECT vehicle_id FROM bookings WHERE id = ?");
    $v_stmt->execute([$b_id]);
    $v_id = $v_stmt->fetchColumn();

    if ($v_id) {
        if ($status === 'completed' || $status === 'cancelled') {
            $db->prepare("UPDATE vehicles SET status = 'available' WHERE id = ?")->execute([$v_id]);
        } elseif ($status === 'active' || $status === 'confirmed') {
            $db->prepare("UPDATE vehicles SET status = 'rented' WHERE id = ?")->execute([$v_id]);
        }
    }

    set_flash_message('success', 'Booking status updated successfully.');
    redirect('admin/bookings.php');
}

$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$sql = "
    SELECT b.*, v.brand, v.model, v.reg_number, u.full_name as customer_name, u.email as customer_email,
           l1.city_name as pickup_city, l2.city_name as dropoff_city
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN users u ON b.user_id = u.id
    JOIN locations l1 ON b.pickup_location_id = l1.id
    JOIN locations l2 ON b.dropoff_location_id = l2.id
    WHERE 1=1
";
$params = [];

if (!empty($status_filter)) {
    $sql .= " AND b.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$page_title = "Reservation Management — ASHVKATHA Admin";
$extra_css  = ['dashboard.css', 'admin.css', 'forms.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">Reservations & Rental Orders</h1>
        <p class="dashboard-subtitle">Control rental order statuses, check-ins, returns, and cancellations.</p>
      </div>
    </div>

    <!-- Filter Buttons -->
    <div style="margin-bottom: 1.5rem; display: flex; gap: 0.6rem;">
      <a href="<?php echo url('/admin/bookings.php'); ?>" class="btn <?php echo empty($status_filter) ? 'btn-primary' : 'btn-outline'; ?> btn-sm">All Bookings</a>
      <a href="<?php echo url('/admin/bookings.php?status=confirmed'); ?>" class="btn <?php echo ($status_filter == 'confirmed') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Confirmed</a>
      <a href="<?php echo url('/admin/bookings.php?status=active'); ?>" class="btn <?php echo ($status_filter == 'active') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Active On-Road</a>
      <a href="<?php echo url('/admin/bookings.php?status=completed'); ?>" class="btn <?php echo ($status_filter == 'completed') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Completed</a>
      <a href="<?php echo url('/admin/bookings.php?status=cancelled'); ?>" class="btn <?php echo ($status_filter == 'cancelled') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Cancelled</a>
    </div>

    <div class="card">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Booking Code</th>
              <th>Customer</th>
              <th>Vehicle</th>
              <th>Pickup Hub</th>
              <th>Duration</th>
              <th>Total Amount</th>
              <th>Status</th>
              <th>Status Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $b): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($b['booking_code']); ?></strong></td>
                <td>
                  <strong><?php echo htmlspecialchars($b['customer_name']); ?></strong>
                  <span style="display: block; font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($b['customer_email']); ?></span>
                </td>
                <td>
                  <div><strong><?php echo htmlspecialchars($b['pickup_city']); ?> Hub</strong></div>
                  <div style="font-size: 0.74rem; color: var(--primary);"><i class="fa-solid fa-truck-plane"></i> <?php echo htmlspecialchars($b['delivery_method'] ?? 'Hub Self-Pickup'); ?></div>
                </td>
                <td><?php echo $b['total_days']; ?> Days</td>
                <td><strong><?php echo format_currency($b['total_amount']); ?></strong></td>
                <td><?php echo get_status_badge($b['status']); ?></td>
                <td>
                  <form action="<?php echo url('/admin/bookings.php'); ?>" method="POST" style="display: inline-block;">
                    <input type="hidden" name="action" value="update_booking_status">
                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                    <select name="status" onchange="this.form.submit()" class="form-control" style="padding: 0.3rem 0.5rem; font-size: 0.8rem; width: auto;">
                      <option value="confirmed" <?php echo ($b['status'] == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                      <option value="active" <?php echo ($b['status'] == 'active') ? 'selected' : ''; ?>>Active On-Road</option>
                      <option value="completed" <?php echo ($b['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                      <option value="cancelled" <?php echo ($b['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                  </form>
                </td>
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
