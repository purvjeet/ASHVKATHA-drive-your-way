<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Admin Control Panel Dashboard (admin/dashboard.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$db = get_db_connection();

// Fleet Statistics
$total_vehicles  = $db->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$available_count = $db->query("SELECT COUNT(*) FROM vehicles WHERE status = 'available'")->fetchColumn();
$rented_count    = $db->query("SELECT COUNT(*) FROM vehicles WHERE status IN ('rented', 'reserved')")->fetchColumn();
$maint_count     = $db->query("SELECT COUNT(*) FROM vehicles WHERE status = 'maintenance'")->fetchColumn();

// Financial & Booking Stats
$total_revenue  = $db->query("SELECT SUM(amount) FROM payments WHERE payment_status = 'completed'")->fetchColumn() ?: 0.00;
$total_bookings = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();

// Underground Night Market Stats & Recent Acquisitions (Separated from Fleet Rentals)
$nm_orders_count  = $db->query("SELECT COUNT(*) FROM night_market_orders WHERE order_status != 'cancelled'")->fetchColumn();
$nm_revenue       = $db->query("SELECT SUM(total_amount) FROM night_market_orders WHERE order_status != 'cancelled'")->fetchColumn() ?: 0.00;
$nm_cancelled_vol = $db->query("SELECT SUM(total_amount) FROM night_market_orders WHERE order_status = 'cancelled'")->fetchColumn() ?: 0.00;

$recent_nm_orders = $db->query("
    SELECT o.*, nm.vehicle_name, nm.brand, nm.category, nm.image_url
    FROM night_market_orders o
    JOIN night_market nm ON o.car_id = nm.id
    ORDER BY o.created_at DESC LIMIT 5
")->fetchAll();

// Fetch Recent Reservations
$recent_bookings = $db->query("
    SELECT b.*, v.brand, v.model, u.full_name as customer_name, l.city_name
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    JOIN users u ON b.user_id = u.id
    JOIN locations l ON b.pickup_location_id = l.id
    ORDER BY b.created_at DESC LIMIT 6
")->fetchAll();

$page_title = "Admin Control Dashboard — ASHVKATHA";
$extra_css  = ['dashboard.css', 'admin.css', 'forms.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">Fleet Command Center</h1>
        <p class="dashboard-subtitle">Real-time overview of fleet availability, rental reservations, and revenue analytics.</p>
      </div>
      <div style="display: flex; gap: 0.8rem;">
        <a href="<?php echo url('/admin/add-vehicle.php'); ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add New Vehicle</a>
        <a href="<?php echo url('/admin/reports.php'); ?>" class="btn btn-secondary"><i class="fa-solid fa-chart-pie"></i> View Reports</a>
      </div>
    </div>

    <!-- Admin Metric Cards Grid -->
    <div class="stats-cards-grid">
      <div class="stat-card">
        <div class="stat-icon primary"><i class="fa-solid fa-car"></i></div>
        <div class="stat-data">
          <span class="stat-value"><?php echo $total_vehicles; ?></span>
          <span class="stat-label">Total Fleet</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon success"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-data">
          <span class="stat-value"><?php echo $available_count; ?></span>
          <span class="stat-label">Available Ready</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon info"><i class="fa-solid fa-key"></i></div>
        <div class="stat-data">
          <span class="stat-value"><?php echo $rented_count; ?></span>
          <span class="stat-label">Currently Rented</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon warning"><i class="fa-solid fa-wrench"></i></div>
        <div class="stat-data">
          <span class="stat-value"><?php echo $maint_count; ?></span>
          <span class="stat-label">In Maintenance</span>
        </div>
      </div>
    </div>

    <!-- Visual Analytics & Utilization Progress Bar Section -->
    <div class="form-grid-2" style="margin-bottom: 2.5rem; gap: 2rem;">
      <div class="card">
        <h3 style="font-size: 1.2rem; margin-bottom: 1rem;"><i class="fa-solid fa-chart-line" style="color: #3b82f6;"></i> Fleet Utilization & Health</h3>
        
        <div class="chart-bar-container">
          <div class="chart-bar-item">
            <div class="chart-bar-label">
              <span>Fleet Active Rate</span>
              <span><?php echo ($total_vehicles > 0) ? round(($rented_count / $total_vehicles) * 100) : 0; ?>%</span>
            </div>
            <div class="chart-progress-track">
              <div class="chart-progress-fill" style="width: <?php echo ($total_vehicles > 0) ? round(($rented_count / $total_vehicles) * 100) : 0; ?>%;"></div>
            </div>
          </div>

          <div class="chart-bar-item">
            <div class="chart-bar-label">
              <span>Ready for Rental Rate</span>
              <span><?php echo ($total_vehicles > 0) ? round(($available_count / $total_vehicles) * 100) : 0; ?>%</span>
            </div>
            <div class="chart-progress-track">
              <div class="chart-progress-fill green" style="width: <?php echo ($total_vehicles > 0) ? round(($available_count / $total_vehicles) * 100) : 0; ?>%;"></div>
            </div>
          </div>

          <div class="chart-bar-item">
            <div class="chart-bar-label">
              <span>Maintenance Overhead Rate</span>
              <span><?php echo ($total_vehicles > 0) ? round(($maint_count / $total_vehicles) * 100) : 0; ?>%</span>
            </div>
            <div class="chart-progress-track">
              <div class="chart-progress-fill blue" style="width: <?php echo ($total_vehicles > 0) ? round(($maint_count / $total_vehicles) * 100) : 0; ?>%;"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; background: linear-gradient(135deg, var(--surface) 0%, var(--surface-alt) 100%);">
        <div>
          <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">FLEET RENTAL REVENUE</span>
          <div style="font-size: 2.2rem; font-weight: 800; color: var(--success); margin: 0.3rem 0; font-family: var(--font-heading);"><?php echo format_currency($total_revenue); ?></div>
          <p style="color: var(--text-muted); font-size: 0.88rem;">Processed across <?php echo $total_bookings; ?> fleet reservations.</p>
        </div>
        <div style="margin-top: 1rem; padding-top: 0.8rem; border-top: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
          <div>
            <span style="font-size: 0.72rem; color: #a855f7; text-transform: uppercase; font-weight: 700;">NIGHT MARKET ACQUISITIONS</span>
            <div style="font-size: 1.3rem; font-weight: 800; color: #a855f7;"><?php echo format_currency($nm_revenue); ?></div>
            <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $nm_orders_count; ?> active deals</span>
            <?php if ($nm_cancelled_vol > 0): ?>
              <div style="font-size: 0.7rem; color: #ef4444; font-weight: 700; margin-top: 0.2rem;">
                <i class="fa-solid fa-circle-minus"></i> -<?php echo format_currency($nm_cancelled_vol); ?> Cancelled
              </div>
            <?php endif; ?>
          </div>
          <a href="<?php echo url('/admin/night-market-orders.php'); ?>" class="btn btn-outline-sm" style="border-color: #a855f7; color: #a855f7;">
            <i class="fa-solid fa-gem"></i> Market Orders
          </a>
        </div>
      </div>
    </div>

    <!-- Recent Reservations Table (Fleet Rentals) -->
    <div class="card" style="margin-bottom: 2rem;">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <h3 style="font-size: 1.25rem;"><i class="fa-solid fa-calendar-check" style="color: var(--primary);"></i> Recent Fleet Rental Reservations</h3>
        <a href="<?php echo url('/admin/bookings.php'); ?>" class="btn btn-outline-sm">Manage All Reservations</a>
      </div>

      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Code</th>
              <th>Customer</th>
              <th>Vehicle</th>
              <th>Pickup Hub</th>
              <th>Pickup Date</th>
              <th>Total</th>
              <th>Status</th>
              <th>Quick Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recent_bookings)): ?>
              <tr>
                <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">No fleet rental reservations found.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($recent_bookings as $rb): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($rb['booking_code']); ?></strong></td>
                  <td><?php echo htmlspecialchars($rb['customer_name']); ?></td>
                  <td><?php echo htmlspecialchars($rb['brand'] . ' ' . $rb['model']); ?></td>
                  <td><?php echo htmlspecialchars($rb['city_name']); ?> Hub</td>
                  <td><?php echo date('d M, h:i A', strtotime($rb['pickup_datetime'])); ?></td>
                  <td><strong><?php echo format_currency($rb['total_amount']); ?></strong></td>
                  <td><?php echo get_status_badge($rb['status']); ?></td>
                  <td>
                    <a href="<?php echo url('/admin/bookings.php'); ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-sliders"></i> Manage</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Underground Night Market Recent Acquisitions Table (Separated) -->
    <div class="card" style="border: 1px solid rgba(168, 85, 247, 0.25);">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <div>
          <h3 style="font-size: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
            <i class="fa-solid fa-gem" style="color: #a855f7;"></i>
            Recent Underground Night Market Acquisitions
          </h3>
          <p style="font-size: 0.82rem; color: var(--text-muted); margin: 0.2rem 0 0 0;">Dedicated transaction register for 60% valuation supercar purchases.</p>
        </div>
        <a href="<?php echo url('/admin/night-market-orders.php'); ?>" class="btn btn-outline-sm" style="border-color: #a855f7; color: #a855f7;">
          <i class="fa-solid fa-gem"></i> View All Acquisitions (<?php echo $nm_orders_count; ?>)
        </a>
      </div>

      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>Order Code</th>
              <th>Buyer</th>
              <th>Supercar Build</th>
              <th>Category</th>
              <th>Total (with Tax)</th>
              <th>Logistics Status</th>
              <th style="text-align: right;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recent_nm_orders)): ?>
              <tr>
                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No Night Market acquisitions recorded yet.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($recent_nm_orders as $nmo): 
                $nm_img = (strpos($nmo['image_url'], 'http') === 0) ? $nmo['image_url'] : url($nmo['image_url']);
              ?>
                <tr>
                  <td>
                    <span style="font-family: monospace; font-weight: 700; color: #a855f7;">
                      <?php echo htmlspecialchars($nmo['order_code']); ?>
                    </span>
                    <div style="font-size: 0.72rem; color: var(--text-muted);">
                      <?php echo date('d M Y', strtotime($nmo['created_at'])); ?>
                    </div>
                  </td>
                  <td>
                    <div style="font-weight: 600; color: var(--text-main);">
                      <?php echo htmlspecialchars($nmo['buyer_name']); ?>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                      <?php echo htmlspecialchars($nmo['buyer_phone']); ?>
                    </div>
                  </td>
                  <td>
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                      <img src="<?php echo htmlspecialchars($nm_img); ?>" alt="" style="width: 44px; height: 32px; object-fit: cover; border-radius: 4px; border: 1px solid rgba(168, 85, 247, 0.3);">
                      <div>
                        <div style="font-weight: 600; font-size: 0.88rem; color: var(--text-main);">
                          <?php echo htmlspecialchars($nmo['vehicle_name']); ?>
                        </div>
                        <div style="font-size: 0.72rem; color: var(--text-muted);">
                          <?php echo htmlspecialchars($nmo['brand']); ?>
                        </div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span style="display: inline-block; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.72rem; font-weight: 700; background: rgba(168, 85, 247, 0.12); color: #a855f7;">
                      <?php echo htmlspecialchars($nmo['category']); ?>
                    </span>
                  </td>
                  <td>
                    <strong style="color: #10b981; font-size: 0.95rem;"><?php echo format_currency($nmo['total_amount']); ?></strong>
                    <div style="font-size: 0.72rem; color: var(--text-muted);">Acquisition Escrow</div>
                  </td>
                  <td>
                    <?php
                    $status_colors = [
                        'pending'    => 'warning',
                        'confirmed'  => 'info',
                        'in_transit' => 'primary',
                        'delivered'  => 'success',
                        'cancelled'  => 'danger'
                    ];
                    $badge_class = $status_colors[$nmo['order_status']] ?? 'secondary';
                    ?>
                    <span class="badge badge-<?php echo $badge_class; ?>">
                      <?php echo strtoupper(str_replace('_', ' ', $nmo['order_status'])); ?>
                    </span>
                  </td>
                  <td style="text-align: right;">
                    <a href="<?php echo url('/admin/night-market-orders.php?status=' . urlencode($nmo['order_status'])); ?>" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 0.3rem;">
                      <i class="fa-solid fa-sliders"></i> Manage
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

</body>
</html>
