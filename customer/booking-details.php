<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Customer Booking Details & Voucher View (customer/booking-details.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$user = get_logged_user();
$db = get_db_connection();

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $db->prepare("
    SELECT b.*, v.brand, v.model, v.reg_number, v.fuel_type, v.transmission, v.image_url, 
           l1.city_name as pickup_city, l1.location_address as pickup_addr,
           l2.city_name as dropoff_city, l2.location_address as dropoff_addr,
           p.transaction_id, p.payment_method, p.payment_status, p.payment_date
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN locations l1 ON b.pickup_location_id = l1.id
    JOIN locations l2 ON b.dropoff_location_id = l2.id
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->execute([$booking_id, $user['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    redirect('/customer/bookings.php');
}

$page_title = "Voucher #" . $booking['booking_code'] . " — ASHVKATHA";
$extra_css  = ['dashboard.css', 'booking.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/customer-sidebar.php'; ?>

  <main class="dashboard-content">
    <div style="margin-bottom: 2rem;">
      <a href="<?php echo url('/customer/bookings.php'); ?>" class="btn btn-outline-sm"><i class="fa-solid fa-arrow-left"></i> Back to My Bookings</a>
    </div>

    <div class="voucher-card">
      <div class="voucher-header">
        <div>
          <?php echo get_status_badge($booking['status']); ?>
          <h2 style="font-size: 1.8rem; margin-top: 0.4rem;"><?php echo htmlspecialchars($booking['booking_code']); ?></h2>
          <span style="font-size: 0.85rem; color: var(--text-muted);">Booked on <?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?></span>
        </div>
        <div style="text-align: right;">
          <div style="font-family: var(--font-heading); font-weight: 800; color: var(--primary);">ASHVKATHA</div>
          <span style="font-size: 0.8rem; color: var(--text-muted);">DRIVE YOUR WAY</span>
        </div>
      </div>

      <div class="form-grid-2" style="margin-bottom: 2rem;">
        <div>
          <h3 style="font-size: 1.4rem; font-weight: 700;"><?php echo htmlspecialchars($booking['brand'] . ' ' . $booking['model']); ?></h3>
          <p style="color: var(--text-muted); font-size: 0.9rem;">Registration: <strong><?php echo htmlspecialchars($booking['reg_number']); ?></strong> (<?php echo htmlspecialchars($booking['fuel_type']); ?>, <?php echo htmlspecialchars($booking['transmission']); ?>)</p>

          <div style="margin-top: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
            <div>
              <span style="font-size: 0.8rem; color: var(--text-muted);">PICKUP LOCATION & TIME</span>
              <div style="font-weight: 700;"><?php echo htmlspecialchars($booking['pickup_city']); ?> Hub</div>
              <div style="font-size: 0.85rem; color: var(--text-muted);"><?php echo htmlspecialchars($booking['pickup_addr']); ?></div>
              <div style="font-size: 0.85rem; color: var(--primary); font-weight: 600;"><?php echo date('d M Y, h:i A', strtotime($booking['pickup_datetime'])); ?></div>
            </div>

            <div>
              <span style="font-size: 0.8rem; color: var(--text-muted);">RETURN LOCATION & TIME</span>
              <div style="font-weight: 700;"><?php echo htmlspecialchars($booking['dropoff_city']); ?> Hub</div>
              <div style="font-size: 0.85rem; color: var(--text-muted);"><?php echo htmlspecialchars($booking['dropoff_addr']); ?></div>
              <div style="font-size: 0.85rem; color: var(--primary); font-weight: 600;"><?php echo date('d M Y, h:i A', strtotime($booking['return_datetime'])); ?></div>
            </div>
          </div>
        </div>

        <div>
          <img src="<?php echo htmlspecialchars($booking['image_url']); ?>" alt="Car" style="border-radius: var(--radius-md); border: 1px solid var(--surface-border);">
        </div>
      </div>

      <div class="price-summary-card">
        <div class="price-row">
          <span>Customer</span>
          <span><strong><?php echo htmlspecialchars($user['full_name']); ?></strong></span>
        </div>
        <div class="price-row">
          <span>Rental Duration</span>
          <span><?php echo $booking['total_days']; ?> Day(s)</span>
        </div>
        <div class="price-row">
          <span>Base Rent</span>
          <span><?php echo format_currency($booking['base_amount']); ?></span>
        </div>
        <div class="price-row">
          <span>Discount Applied</span>
          <span>- <?php echo format_currency($booking['discount_amount']); ?></span>
        </div>
        <div class="price-row">
          <span>GST Tax</span>
          <span><?php echo format_currency($booking['tax_amount']); ?></span>
        </div>
        <div class="price-row">
          <span>Security Deposit</span>
          <span><?php echo format_currency($booking['deposit_amount']); ?></span>
        </div>
        <div class="price-row total-row">
          <span>Total Amount</span>
          <span><?php echo format_currency($booking['total_amount']); ?></span>
        </div>
      </div>

      <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
        <a href="<?php echo url('/customer/invoices.php?id=' . $booking['id']); ?>" class="btn btn-primary">
          <i class="fa-solid fa-file-invoice"></i> Download Invoice
        </a>
      </div>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
