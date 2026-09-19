<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Customer Bookings History & Rental Extension (customer/bookings.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$user = get_logged_user();
$db = get_db_connection();

// Process Cancel Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    $cancel_id = (int)$_POST['booking_id'];
    $stmt = $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('pending', 'confirmed')");
    $stmt->execute([$cancel_id, $user['id']]);

    // Restore vehicle status
    $v_stmt = $db->prepare("SELECT vehicle_id FROM bookings WHERE id = ?");
    $v_stmt->execute([$cancel_id]);
    $v_id = $v_stmt->fetchColumn();
    if ($v_id) {
        $db->prepare("UPDATE vehicles SET status = 'available' WHERE id = ?")->execute([$v_id]);
    }

    set_flash_message('success', 'Booking cancelled successfully.');
    redirect('/customer/bookings.php');
}

// Process Rental Extension Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'extend_booking') {
    $b_id       = (int)$_POST['booking_id'];
    $extra_days = (int)$_POST['extra_days'];

    if ($extra_days > 0 && $extra_days <= 14) {
        $b_stmt = $db->prepare("
            SELECT b.*, v.daily_rate 
            FROM bookings b 
            JOIN vehicles v ON b.vehicle_id = v.id 
            WHERE b.id = ? AND b.user_id = ? AND b.status IN ('confirmed', 'active')
        ");
        $b_stmt->execute([$b_id, $user['id']]);
        $b_data = $b_stmt->fetch();

        if ($b_data) {
            $new_return = date('Y-m-d H:i:s', strtotime($b_data['return_datetime'] . " + {$extra_days} days"));
            $new_total_days = $b_data['total_days'] + $extra_days;
            $add_base = $b_data['daily_rate'] * $extra_days;
            $add_tax  = $add_base * 0.18;
            $add_total = $add_base + $add_tax;

            $new_base = $b_data['base_amount'] + $add_base;
            $new_tax  = $b_data['tax_amount'] + $add_tax;
            $new_total = $b_data['total_amount'] + $add_total;

            $up = $db->prepare("
                UPDATE bookings 
                SET return_datetime = ?, total_days = ?, base_amount = ?, tax_amount = ?, total_amount = ? 
                WHERE id = ?
            ");
            $up->execute([$new_return, $new_total_days, $new_base, $new_tax, $new_total, $b_id]);

            // Insert Extension Payment Record
            $txn_id = 'TXN-EXT-' . rand(10000, 99999);
            $p_stmt = $db->prepare("
                INSERT INTO payments (booking_id, transaction_id, payment_method, amount, payment_status)
                VALUES (?, ?, 'Credit Card', ?, 'completed')
            ");
            $p_stmt->execute([$b_id, $txn_id, $add_total]);

            set_flash_message('success', "Rental extended by {$extra_days} day(s) successfully! Added amount: " . format_currency($add_total));
        }
    }

    redirect('/customer/bookings.php');
}

$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$sql = "
    SELECT b.*, v.brand, v.model, v.image_url, v.reg_number, l1.city_name as pickup_city, l2.city_name as dropoff_city
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN locations l1 ON b.pickup_location_id = l1.id
    JOIN locations l2 ON b.dropoff_location_id = l2.id
    WHERE b.user_id = ?
";
$params = [$user['id']];

if (!empty($status_filter)) {
    $sql .= " AND b.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$page_title = "My Reservations — ASHVKATHA";
$extra_css  = ['dashboard.css', 'forms.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/customer-sidebar.php'; ?>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">My Vehicle Reservations</h1>
        <p class="dashboard-subtitle">Track, manage, extend, and download vouchers for your active & past rentals.</p>
      </div>
    </div>

    <!-- Status Filters -->
    <div style="margin-bottom: 2rem; display: flex; gap: 0.8rem; flex-wrap: wrap;">
      <a href="<?php echo url('/customer/bookings.php'); ?>" class="btn <?php echo empty($status_filter) ? 'btn-primary' : 'btn-outline'; ?> btn-sm">All Bookings</a>
      <a href="<?php echo url('/customer/bookings.php?status=confirmed'); ?>" class="btn <?php echo ($status_filter == 'confirmed') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Confirmed</a>
      <a href="<?php echo url('/customer/bookings.php?status=active'); ?>" class="btn <?php echo ($status_filter == 'active') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Active On-Road</a>
      <a href="<?php echo url('/customer/bookings.php?status=completed'); ?>" class="btn <?php echo ($status_filter == 'completed') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Completed</a>
      <a href="<?php echo url('/customer/bookings.php?status=cancelled'); ?>" class="btn <?php echo ($status_filter == 'cancelled') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Cancelled</a>
    </div>

    <div class="card">
      <?php if (empty($bookings)): ?>
        <div class="text-center" style="padding: 3rem 0;">
          <i class="fa-solid fa-calendar-xmark" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
          <h3>No Bookings Found</h3>
          <p style="color: var(--text-muted); margin-bottom: 1.5rem;">You currently have no reservations matching this filter.</p>
          <a href="<?php echo url('/vehicles.php'); ?>" class="btn btn-primary"><i class="fa-solid fa-car"></i> Reserve a Vehicle</a>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Booking Code</th>
                <th>Vehicle Info</th>
                <th>Pickup / Return Hub</th>
                <th>Return Schedule</th>
                <th>Total Paid</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($bookings as $b): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($b['booking_code']); ?></strong></td>
                  <td>
                    <div style="display: flex; align-items: center; gap: 0.8rem;">
                      <img src="<?php echo htmlspecialchars($b['image_url']); ?>" alt="Car" style="width: 50px; height: 32px; object-fit: cover; border-radius: var(--radius-sm);">
                      <div>
                        <div style="font-weight: 700;"><?php echo htmlspecialchars($b['brand'] . ' ' . $b['model']); ?></div>
                        <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($b['reg_number']); ?></span>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div style="font-size: 0.85rem;">
                      <i class="fa-solid fa-location-dot" style="color: var(--primary);"></i> <?php echo htmlspecialchars($b['pickup_city']); ?><br>
                      <i class="fa-solid fa-location-arrow" style="color: var(--info);"></i> <?php echo htmlspecialchars($b['dropoff_city']); ?>
                    </div>
                  </td>
                  <td>
                    <div style="font-size: 0.85rem;">
                      <strong><?php echo date('d M Y, h:i A', strtotime($b['return_datetime'])); ?></strong><br>
                      <small style="color: var(--text-muted);"><?php echo $b['total_days']; ?> Day(s) Total</small>
                    </div>
                  </td>
                  <td><strong><?php echo format_currency($b['total_amount']); ?></strong></td>
                  <td><?php echo get_status_badge($b['status']); ?></td>
                  <td>
                    <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                      <a href="<?php echo url('/customer/booking-details.php?id=' . $b['id']); ?>" class="btn btn-secondary btn-sm" title="View Voucher"><i class="fa-solid fa-eye"></i></a>
                      <a href="<?php echo url('/customer/invoices.php?id=' . $b['id']); ?>" class="btn btn-outline-sm btn-sm" title="Invoice"><i class="fa-solid fa-file-invoice"></i></a>
                      
                      <?php if (in_array($b['status'], ['confirmed', 'active'])): ?>
                        <!-- Extend Rental Modal Form -->
                        <form action="<?php echo url('/customer/bookings.php'); ?>" method="POST" style="display: inline;">
                          <input type="hidden" name="action" value="extend_booking">
                          <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                          <select name="extra_days" onchange="if(confirm('Extend rental by ' + this.value + ' day(s)?')) this.form.submit();" class="form-control" style="padding: 0.25rem 0.4rem; font-size: 0.78rem; width: auto; display: inline-block;">
                            <option value="">+ Extend</option>
                            <option value="1">+1 Day</option>
                            <option value="2">+2 Days</option>
                            <option value="3">+3 Days</option>
                            <option value="5">+5 Days</option>
                            <option value="7">+7 Days</option>
                          </select>
                        </form>

                        <form action="<?php echo url('/customer/bookings.php'); ?>" method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking?');" style="display: inline;">
                          <input type="hidden" name="action" value="cancel_booking">
                          <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                          <button type="submit" class="btn btn-danger-outline btn-sm" title="Cancel Booking"><i class="fa-solid fa-ban"></i></button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
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
