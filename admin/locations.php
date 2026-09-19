<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Admin Rental Locations Management (admin/locations.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$db = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_location') {
    $city_name        = sanitize($_POST['city_name']);
    $location_address = sanitize($_POST['location_address']);
    $contact_phone    = sanitize($_POST['contact_phone']);

    $ins = $db->prepare("INSERT INTO locations (city_name, location_address, contact_phone, status) VALUES (?, ?, ?, 'active')");
    $ins->execute([$city_name, $location_address, $contact_phone]);
    set_flash_message('success', 'New location hub added successfully.');
    redirect('admin/locations.php');
}

$locations = $db->query("
    SELECT l.*, COUNT(v.id) as vehicle_count
    FROM locations l
    LEFT JOIN vehicles v ON l.id = v.location_id
    GROUP BY l.id ORDER BY l.city_name ASC
")->fetchAll();

$page_title = "Rental Locations — ASHVKATHA Admin";
$extra_css  = ['dashboard.css', 'admin.css', 'forms.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">Rental Hub Locations</h1>
        <p class="dashboard-subtitle">Manage regional pickup & dropoff stations across Gujarat.</p>
      </div>
    </div>

    <div class="catalog-layout" style="grid-template-columns: 340px 1fr;">
      <!-- Add Location Form -->
      <div class="card">
        <h3 style="font-size: 1.2rem; margin-bottom: 1.2rem;"><i class="fa-solid fa-plus-circle" style="color: var(--primary);"></i> Add Location Hub</h3>

        <form action="<?php echo url('/admin/locations.php'); ?>" method="POST">
          <input type="hidden" name="action" value="add_location">

          <div class="form-group">
            <label class="form-label">City Name *</label>
            <input type="text" name="city_name" class="form-control" placeholder="e.g. Bhavnagar" required>
          </div>

          <div class="form-group">
            <label class="form-label">Station Address *</label>
            <textarea name="location_address" class="form-control" placeholder="Full station address..." required></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Contact Phone *</label>
            <input type="text" name="contact_phone" class="form-control" placeholder="+91 281 2200999" required>
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fa-solid fa-building"></i> Save Location Hub</button>
        </form>
      </div>

      <!-- Locations List Table -->
      <div class="card">
        <h3 style="font-size: 1.2rem; margin-bottom: 1.2rem;"><i class="fa-solid fa-location-dot"></i> Operating Station Hubs</h3>

        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>City Hub</th>
                <th>Station Address</th>
                <th>Contact Phone</th>
                <th>Assigned Vehicles</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($locations as $loc): ?>
                <tr>
                  <td>#<?php echo $loc['id']; ?></td>
                  <td><strong><?php echo htmlspecialchars($loc['city_name']); ?></strong></td>
                  <td><?php echo htmlspecialchars($loc['location_address']); ?></td>
                  <td><?php echo htmlspecialchars($loc['contact_phone']); ?></td>
                  <td><strong><?php echo $loc['vehicle_count']; ?> Vehicles</strong></td>
                  <td><?php echo get_status_badge($loc['status']); ?></td>
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
