<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Admin Analytics & Financial Reports (admin/reports.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$db = get_db_connection();

// Aggregate Data for Reports
$total_revenue  = $db->query("SELECT SUM(amount) FROM payments WHERE payment_status = 'completed'")->fetchColumn() ?: 0.00;
$total_bookings = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$total_vehicles = $db->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$total_maint    = $db->query("SELECT SUM(cost) FROM maintenance_records")->fetchColumn() ?: 0.00;

// Category Breakdown
$cat_reports = $db->query("
    SELECT c.name, COUNT(v.id) as total_v, COALESCE(SUM(b.total_amount), 0) as cat_revenue
    FROM vehicle_categories c
    LEFT JOIN vehicles v ON v.category_id = c.id
    LEFT JOIN bookings b ON b.vehicle_id = v.id AND b.status != 'cancelled'
    GROUP BY c.id
")->fetchAll();

$page_title = "Analytics & Reports — ASHVKATHA Admin";
$extra_css  = ['dashboard.css', 'admin.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">Fleet & Financial Analytics</h1>
        <p class="dashboard-subtitle">Revenue breakdowns, category performance, and expense analytics.</p>
      </div>
      <button onclick="window.print();" class="btn btn-primary"><i class="fa-solid fa-print"></i> Export Analytics Report</button>
    </div>

    <!-- Overview Metrics -->
    <div class="stats-cards-grid">
      <div class="stat-card">
        <div class="stat-icon success"><i class="fa-solid fa-indian-rupee-sign"></i></div>
        <div class="stat-data">
          <span class="stat-value"><?php echo format_currency($total_revenue); ?></span>
          <span class="stat-label">Gross Revenue</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon info"><i class="fa-solid fa-calendar-check"></i></div>
        <div class="stat-data">
          <span class="stat-value"><?php echo $total_bookings; ?></span>
          <span class="stat-label">Total Reservations</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon primary"><i class="fa-solid fa-car"></i></div>
        <div class="stat-data">
          <span class="stat-value"><?php echo $total_vehicles; ?></span>
          <span class="stat-label">Managed Vehicles</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-icon warning"><i class="fa-solid fa-wrench"></i></div>
        <div class="stat-data">
          <span class="stat-value"><?php echo format_currency($total_maint); ?></span>
          <span class="stat-label">Maintenance Expenses</span>
        </div>
      </div>
    </div>

    <!-- Category Revenue Visual Chart Bar Section -->
    <div class="card" style="margin-bottom: 2.5rem;">
      <h3 style="font-size: 1.3rem; margin-bottom: 1.5rem;"><i class="fa-solid fa-chart-bar" style="color: var(--primary);"></i> Revenue Share By Fleet Category</h3>

      <div class="chart-bar-container">
        <?php foreach ($cat_reports as $cat): 
          $pct = ($total_revenue > 0) ? round(($cat['cat_revenue'] / $total_revenue) * 100) : 0;
        ?>
          <div class="chart-bar-item">
            <div class="chart-bar-label">
              <span><strong><?php echo htmlspecialchars($cat['name']); ?></strong> (<?php echo $cat['total_v']; ?> vehicles)</span>
              <span><strong><?php echo format_currency($cat['cat_revenue']); ?></strong> (<?php echo $pct; ?>%)</span>
            </div>
            <div class="chart-progress-track">
              <div class="chart-progress-fill" style="width: <?php echo max($pct, 5); ?>%;"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </main>
</div>

</body>
</html>
