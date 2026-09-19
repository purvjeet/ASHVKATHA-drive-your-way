<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Customer Portal Dashboard (customer/dashboard.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$user = get_logged_user();
$db = get_db_connection();

// Customer statistics
$c_active = $db->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status IN ('confirmed', 'active')");
$c_active->execute([$user['id']]);
$active_count = $c_active->fetchColumn();

$c_completed = $db->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status = 'completed'");
$c_completed->execute([$user['id']]);
$completed_count = $c_completed->fetchColumn();

$c_spent = $db->prepare("SELECT SUM(total_amount) FROM bookings WHERE user_id = ? AND status != 'cancelled'");
$c_spent->execute([$user['id']]);
$total_spent = $c_spent->fetchColumn() ?: 0.00;

// Night Market statistics
$c_acq = $db->prepare("SELECT COUNT(*) FROM night_market_orders WHERE user_id = ?");
$c_acq->execute([$user['id']]);
$acq_count = $c_acq->fetchColumn();

// Fetch Recent Bookings
$b_stmt = $db->prepare("
    SELECT b.*, v.brand, v.model, v.image_url, l1.city_name as pickup_city, l2.city_name as dropoff_city
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN locations l1 ON b.pickup_location_id = l1.id
    JOIN locations l2 ON b.dropoff_location_id = l2.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC LIMIT 5
");
$b_stmt->execute([$user['id']]);
$recent_bookings = $b_stmt->fetchAll();

// Fetch Recent Night Market Acquisitions
$nm_stmt = $db->prepare("
    SELECT o.*, nm.vehicle_name, nm.brand, nm.image_url
    FROM night_market_orders o
    JOIN night_market nm ON o.car_id = nm.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC LIMIT 3
");
$nm_stmt->execute([$user['id']]);
$recent_acquisitions = $nm_stmt->fetchAll();

$page_title = "My Dashboard — ASHVKATHA";
$extra_css  = ['dashboard.css', 'forms.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/customer-sidebar.php'; ?>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
        <p class="dashboard-subtitle">Manage your active vehicle reservations, invoices, and rental history.</p>
      </div>
      <a href="<?php echo url('/vehicles.php'); ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Rent a New Vehicle</a>
    </div>

    <!-- Stat Cards Grid -->
    <div class="stats-cards-grid">
      <div class="stat-card">
        <div class="stat-icon info"><i class="fa-solid fa-car"></i></div>
        <div class="stat-data">
          <span class="stat-value"><?php echo $active_count; ?></span>
          <span class="stat-label">Active Bookings</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon success"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-data">
          <span class="stat-value"><?php echo $completed_count; ?></span>
          <span class="stat-label">Completed Rentals</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon primary"><i class="fa-solid fa-indian-rupee-sign"></i></div>
        <div class="stat-data">
          <span class="stat-value"><?php echo format_currency($total_spent); ?></span>
          <span class="stat-label">Total Rental Spent</span>
        </div>
      </div>

      <div class="stat-card" style="border-left: 3px solid #a855f7;">
        <div class="stat-icon" style="background: rgba(168, 85, 247, 0.15); color: #a855f7;"><i class="fa-solid fa-gem"></i></div>
        <div class="stat-data">
          <span class="stat-value" style="color: #a855f7;"><?php echo $acq_count; ?></span>
          <span class="stat-label">Market Acquisitions</span>
        </div>
      </div>
    </div>

    <!-- Recent Night Market Acquisitions Section (Separate from Rentals) -->
    <?php if (!empty($recent_acquisitions)): ?>
      <div class="card" style="margin-bottom: 2rem; border-left: 4px solid #a855f7;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
          <div>
            <span style="font-size: 0.75rem; font-weight: 800; color: #a855f7; text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 0.2rem;">Exclusive Allocations</span>
            <h3 style="font-size: 1.3rem;"><i class="fa-solid fa-gem" style="color: #a855f7;"></i> Night Market Acquisitions</h3>
          </div>
          <a href="<?php echo url('/customer/night-market-orders.php'); ?>" class="btn btn-outline-sm">View All Acquisitions</a>
        </div>

        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Order Code</th>
                <th>Vehicle Build</th>
                <th>Delivery Logistics</th>
                <th>Acquisition Price</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent_acquisitions as $acq): 
                $a_img = (strpos($acq['image_url'], 'http') === 0) ? $acq['image_url'] : url($acq['image_url']);
              ?>
                <tr>
                  <td><strong style="color: #a855f7;"><?php echo htmlspecialchars($acq['order_code']); ?></strong></td>
                  <td>
                    <div style="display: flex; align-items: center; gap: 0.8rem;">
                      <img src="<?php echo htmlspecialchars($a_img); ?>" alt="Car" style="width: 45px; height: 30px; object-fit: cover; border-radius: var(--radius-sm);">
                      <span><strong><?php echo htmlspecialchars($acq['vehicle_name']); ?></strong></span>
                    </div>
                  </td>
                  <td><i class="fa-solid fa-truck" style="color: #38bdf8;"></i> <?php echo htmlspecialchars($acq['delivery_option']); ?></td>
                  <td><strong style="color: #a855f7;"><?php echo format_currency($acq['total_amount']); ?></strong></td>
                  <td>
                    <span class="badge badge-primary" style="background: rgba(168, 85, 247, 0.15); color: #a855f7; border: 1px solid rgba(168, 85, 247, 0.4);">
                      <?php echo strtoupper(htmlspecialchars($acq['order_status'])); ?>
                    </span>
                  </td>
                  <td>
                    <a href="<?php echo url('/night-market-details.php?id=' . $acq['car_id']); ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-file-waveform"></i> Dossier</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>

    <!-- Recent Rental Reservations Table (Standard Fleet Only) -->
    <div class="card">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
          <span style="font-size: 0.75rem; font-weight: 800; color: var(--primary); text-transform: uppercase; letter-spacing: 0.05em; display: block; margin-bottom: 0.2rem;">Fleet Rentals</span>
          <h3 style="font-size: 1.3rem;"><i class="fa-solid fa-calendar-days" style="color: var(--primary);"></i> Recent Rental Reservations</h3>
        </div>
        <a href="<?php echo url('/customer/bookings.php'); ?>" class="btn btn-outline-sm">View All Bookings</a>
      </div>

      <?php if (empty($recent_bookings)): ?>
        <p style="color: var(--text-muted); text-align: center; padding: 2rem 0;">You have no active or previous vehicle reservations.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Booking Code</th>
                <th>Vehicle</th>
                <th>Pickup Location</th>
                <th>Pickup Date</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recent_bookings as $b): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($b['booking_code']); ?></strong></td>
                  <td>
                    <div style="display: flex; align-items: center; gap: 0.8rem;">
                      <img src="<?php echo htmlspecialchars($b['image_url']); ?>" alt="Car" style="width: 45px; height: 30px; object-fit: cover; border-radius: var(--radius-sm);">
                      <span><?php echo htmlspecialchars($b['brand'] . ' ' . $b['model']); ?></span>
                    </div>
                  </td>
                  <td>
                    <div><strong><?php echo htmlspecialchars($b['pickup_city']); ?> Hub</strong></div>
                    <div style="font-size: 0.76rem; color: var(--primary);"><i class="fa-solid fa-truck-plane"></i> <?php echo htmlspecialchars($b['delivery_method'] ?? 'Hub Self-Pickup'); ?></div>
                  </td>
                  <td><?php echo date('d M Y, h:i A', strtotime($b['pickup_datetime'])); ?></td>
                  <td><strong><?php echo format_currency($b['total_amount']); ?></strong></td>
                  <td><?php echo get_status_badge($b['status']); ?></td>
                  <td>
                    <a href="<?php echo url('/customer/booking-details.php?id=' . $b['id']); ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-eye"></i> View</a>
                    <a href="<?php echo url('/customer/invoices.php?id=' . $b['id']); ?>" class="btn btn-outline-sm btn-sm"><i class="fa-solid fa-file-invoice"></i> Voucher</a>
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
