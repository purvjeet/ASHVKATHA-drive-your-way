<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Admin Fleet Maintenance Module (admin/maintenance.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$db = get_db_connection();

// Process New Maintenance Log Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_maintenance') {
    $vehicle_id        = (int)$_POST['vehicle_id'];
    $service_type      = sanitize($_POST['service_type']);
    $service_date      = sanitize($_POST['service_date']);
    $next_service_date = sanitize($_POST['next_service_date']);
    $cost              = (float)$_POST['cost'];
    $description       = sanitize($_POST['description']);
    $status            = sanitize($_POST['status']);

    $ins = $db->prepare("
        INSERT INTO maintenance_records (vehicle_id, service_type, service_date, next_service_date, cost, description, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $ins->execute([$vehicle_id, $service_type, $service_date, $next_service_date, $cost, $description, $status]);

    // Update vehicle status to maintenance if status in_progress
    if ($status === 'in_progress') {
        $db->prepare("UPDATE vehicles SET status = 'maintenance' WHERE id = ?")->execute([$vehicle_id]);
    }

    set_flash_message('success', 'Maintenance record created successfully.');
    redirect('admin/maintenance.php');
}

$records = $db->query("
    SELECT m.*, v.brand, v.model, v.reg_number 
    FROM maintenance_records m
    JOIN vehicles v ON m.vehicle_id = v.id
    ORDER BY m.service_date DESC
")->fetchAll();

$all_vehicles = $db->query("SELECT id, brand, model, reg_number FROM vehicles ORDER BY brand ASC")->fetchAll();

$total_maint_cost = $db->query("SELECT SUM(cost) FROM maintenance_records")->fetchColumn() ?: 0.00;

$page_title = "Fleet Maintenance — ASHVKATHA Admin";
$extra_css  = ['dashboard.css', 'admin.css', 'forms.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">Fleet Maintenance & Diagnostics</h1>
        <p class="dashboard-subtitle">Track periodic service logs, oil changes, brake pads, and maintenance costs.</p>
      </div>
    </div>

    <!-- Maintenance Overview Card -->
    <div class="card" style="margin-bottom: 2rem;">
      <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
          <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 700;">TOTAL MAINTENANCE EXPENSE</span>
          <div style="font-size: 2.2rem; font-weight: 800; color: var(--warning);"><?php echo format_currency($total_maint_cost); ?></div>
        </div>
        <span class="badge badge-warning" style="font-size: 1rem;"><i class="fa-solid fa-wrench"></i> Active Maintenance Logs</span>
      </div>
    </div>

    <!-- New Log Form & Records List Grid -->
    <div class="catalog-layout" style="grid-template-columns: 360px 1fr;">
      <!-- Log Form -->
      <div class="card">
        <h3 style="font-size: 1.2rem; margin-bottom: 1.2rem;"><i class="fa-solid fa-plus-circle" style="color: var(--primary);"></i> Log Service Activity</h3>

        <form action="<?php echo url('/admin/maintenance.php'); ?>" method="POST">
          <input type="hidden" name="action" value="add_maintenance">

          <div class="form-group">
            <label class="form-label">Select Vehicle</label>
            <select name="vehicle_id" class="form-control" required>
              <?php foreach ($all_vehicles as $av): ?>
                <option value="<?php echo $av['id']; ?>"><?php echo htmlspecialchars($av['brand'] . ' ' . $av['model']); ?> (<?php echo htmlspecialchars($av['reg_number']); ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Service Type</label>
            <input type="text" name="service_type" class="form-control" placeholder="Periodic Service, Oil Change..." required>
          </div>

          <div class="form-group">
            <label class="form-label">Service Date</label>
            <input type="date" name="service_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Next Service Date</label>
            <input type="date" name="next_service_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+3 months')); ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Cost (₹)</label>
            <input type="number" step="0.01" name="cost" class="form-control" placeholder="4500.00" required>
          </div>

          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control" required>
              <option value="in_progress">In Progress</option>
              <option value="scheduled">Scheduled</option>
              <option value="completed">Completed</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Description / Work Done</label>
            <textarea name="description" class="form-control" placeholder="Work summary..." required></textarea>
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;"><i class="fa-solid fa-floppy-disk"></i> Log Record</button>
        </form>
      </div>

      <!-- Log Records Table -->
      <div class="card">
        <h3 style="font-size: 1.2rem; margin-bottom: 1.2rem;"><i class="fa-solid fa-clock-rotate-left"></i> Maintenance Records History</h3>

        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Vehicle</th>
                <th>Service Type</th>
                <th>Service Date</th>
                <th>Next Service</th>
                <th>Cost</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($records as $r): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($r['brand'] . ' ' . $r['model']); ?></strong><br><small style="color: var(--text-muted);"><?php echo htmlspecialchars($r['reg_number']); ?></small></td>
                  <td><?php echo htmlspecialchars($r['service_type']); ?></td>
                  <td><?php echo date('d M Y', strtotime($r['service_date'])); ?></td>
                  <td><?php echo date('d M Y', strtotime($r['next_service_date'])); ?></td>
                  <td><strong><?php echo format_currency($r['cost']); ?></strong></td>
                  <td><?php echo get_status_badge($r['status']); ?></td>
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
