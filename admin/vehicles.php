<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Admin Vehicle Fleet Management Page (admin/vehicles.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$db = get_db_connection();

// Process Delete Request
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    $del_stmt = $db->prepare("DELETE FROM vehicles WHERE id = ?");
    $del_stmt->execute([$del_id]);
    set_flash_message('success', 'Vehicle removed from fleet successfully.');
    redirect('/admin/vehicles.php');
}

// Process Quick Status Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $v_id   = (int)$_POST['vehicle_id'];
    $status = sanitize($_POST['status']);
    $db->prepare("UPDATE vehicles SET status = ? WHERE id = ?")->execute([$status, $v_id]);
    set_flash_message('success', 'Vehicle status updated successfully.');
    redirect('/admin/vehicles.php');
}

// Fetch all vehicles with category and location
$sql = "
    SELECT v.*, c.name as category_name, l.city_name 
    FROM vehicles v 
    JOIN vehicle_categories c ON v.category_id = c.id 
    JOIN locations l ON v.location_id = l.id 
    ORDER BY v.id DESC
";
$vehicles = $db->query($sql)->fetchAll();

$today = date('Y-m-d');

$page_title = "Fleet Management — ASHVKATHA Admin";
$extra_css  = ['dashboard.css', 'admin.css', 'forms.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">Fleet Directory & Compliance Control</h1>
        <p class="dashboard-subtitle">Manage vehicle specs, daily rates, availability statuses, PUC & Insurance compliance.</p>
      </div>
      <a href="<?php echo url('/admin/add-vehicle.php'); ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add New Vehicle</a>
    </div>

    <div class="card">
      <div class="table-responsive">
        <table class="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Vehicle</th>
              <th>Registration</th>
              <th>Category</th>
              <th>Location Hub</th>
              <th>Insurance / PUC</th>
              <th>Daily Rate</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($vehicles as $v): 
              $ins_expired = (isset($v['insurance_expiry']) && $v['insurance_expiry'] < $today);
              $puc_expired = (isset($v['puc_expiry']) && $v['puc_expiry'] < $today);
              $maint_req   = ($v['status'] === 'maintenance' || $ins_expired || $puc_expired);
            ?>
              <tr style="<?php echo $maint_req ? 'background: rgba(245, 158, 11, 0.05);' : ''; ?>">
                <td>#<?php echo $v['id']; ?></td>
                <td>
                  <div style="display: flex; align-items: center; gap: 0.8rem;">
                    <img src="<?php echo htmlspecialchars($v['image_url']); ?>" alt="Car" style="width: 50px; height: 35px; object-fit: cover; border-radius: var(--radius-sm);">
                    <div>
                      <strong style="display: block;"><?php echo htmlspecialchars($v['brand'] . ' ' . $v['model']); ?></strong>
                      <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($v['fuel_type']); ?> • <?php echo htmlspecialchars($v['transmission']); ?></span>
                    </div>
                  </div>
                </td>
                <td><code><?php echo htmlspecialchars($v['reg_number']); ?></code></td>
                <td><?php echo htmlspecialchars($v['category_name']); ?></td>
                <td><?php echo htmlspecialchars($v['city_name']); ?></td>
                <td>
                  <div style="display: flex; flex-direction: column; gap: 0.2rem; font-size: 0.78rem;">
                    <span style="color: <?php echo $ins_expired ? 'var(--danger)' : 'var(--success)'; ?>;">
                      <i class="fa-solid fa-shield-halved"></i> Ins: <?php echo isset($v['insurance_expiry']) ? date('d M Y', strtotime($v['insurance_expiry'])) : 'Valid'; ?>
                    </span>
                    <span style="color: <?php echo $puc_expired ? 'var(--danger)' : 'var(--success)'; ?>;">
                      <i class="fa-solid fa-smog"></i> PUC: <?php echo isset($v['puc_expiry']) ? date('d M Y', strtotime($v['puc_expiry'])) : 'Valid'; ?>
                    </span>
                  </div>
                </td>
                <td><strong><?php echo format_currency($v['daily_rate']); ?></strong></td>
                <td>
                  <div style="display: flex; flex-direction: column; gap: 0.4rem;">
                    <?php if ($maint_req): ?>
                      <span class="badge badge-warning"><i class="fa-solid fa-triangle-exclamation"></i> Maint. Required</span>
                    <?php endif; ?>

                    <form action="<?php echo url('/admin/vehicles.php'); ?>" method="POST" style="display: inline-block;">
                      <input type="hidden" name="action" value="update_status">
                      <input type="hidden" name="vehicle_id" value="<?php echo $v['id']; ?>">
                      <select name="status" onchange="this.form.submit()" class="form-control" style="padding: 0.3rem 0.5rem; font-size: 0.8rem; width: auto;">
                        <option value="available" <?php echo ($v['status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                        <option value="reserved" <?php echo ($v['status'] == 'reserved') ? 'selected' : ''; ?>>Reserved</option>
                        <option value="rented" <?php echo ($v['status'] == 'rented') ? 'selected' : ''; ?>>Rented</option>
                        <option value="maintenance" <?php echo ($v['status'] == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                      </select>
                    </form>
                  </div>
                </td>
                <td>
                  <div style="display: flex; gap: 0.4rem;">
                    <a href="<?php echo url('/admin/edit-vehicle.php?id=' . $v['id']); ?>" class="btn btn-secondary btn-sm" title="Edit Vehicle"><i class="fa-solid fa-pen-to-square"></i></a>
                    <a href="<?php echo url('/admin/vehicles.php?action=delete&id=' . $v['id']); ?>" class="btn btn-danger-outline btn-sm" onclick="return confirm('Are you sure you want to delete this vehicle?');" title="Delete"><i class="fa-solid fa-trash"></i></a>
                  </div>
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
