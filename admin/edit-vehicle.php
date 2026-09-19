<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Edit Vehicle Details Form (admin/edit-vehicle.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$db = get_db_connection();

$v_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $db->prepare("SELECT * FROM vehicles WHERE id = ?");
$stmt->execute([$v_id]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    redirect('admin/vehicles.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id      = (int)$_POST['category_id'];
    $location_id      = (int)$_POST['location_id'];
    $brand            = sanitize($_POST['brand']);
    $model            = sanitize($_POST['model']);
    $year             = (int)$_POST['year'];
    $reg_number       = sanitize($_POST['reg_number']);
    $fuel_type        = sanitize($_POST['fuel_type']);
    $transmission     = sanitize($_POST['transmission']);
    $seating_capacity = (int)$_POST['seating_capacity'];
    $daily_rate       = (float)$_POST['daily_rate'];
    $hourly_rate      = (float)$_POST['hourly_rate'];
    $deposit_amount   = (float)$_POST['deposit_amount'];
    $status           = sanitize($_POST['status']);
    $insurance_expiry = sanitize($_POST['insurance_expiry']);
    $puc_expiry       = sanitize($_POST['puc_expiry']);
    $image_url        = sanitize($_POST['image_url']);
    $mileage          = sanitize($_POST['mileage']);
    $description      = sanitize($_POST['description']);
    $features         = sanitize($_POST['features']);

    $up = $db->prepare("
        UPDATE vehicles SET 
        category_id = ?, location_id = ?, brand = ?, model = ?, year = ?, reg_number = ?, fuel_type = ?, 
        transmission = ?, seating_capacity = ?, daily_rate = ?, hourly_rate = ?, deposit_amount = ?, 
        status = ?, insurance_expiry = ?, puc_expiry = ?, image_url = ?, mileage = ?, description = ?, features = ?
        WHERE id = ?
    ");
    $up->execute([
        $category_id, $location_id, $brand, $model, $year, $reg_number, $fuel_type, $transmission,
        $seating_capacity, $daily_rate, $hourly_rate, $deposit_amount, $status, $insurance_expiry, $puc_expiry,
        $image_url, $mileage, $description, $features, $v_id
    ]);

    set_flash_message('success', 'Vehicle details updated successfully.');
    redirect('admin/vehicles.php');
}

$categories = $db->query("SELECT * FROM vehicle_categories ORDER BY name ASC")->fetchAll();
$locations  = $db->query("SELECT * FROM locations WHERE status = 'active' ORDER BY city_name ASC")->fetchAll();

$page_title = "Edit Vehicle #" . $vehicle['id'] . " — ASHVKATHA Admin";
$extra_css  = ['dashboard.css', 'admin.css', 'forms.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="dashboard-content">
    <div style="margin-bottom: 2rem;">
      <a href="<?php echo url('/admin/vehicles.php'); ?>" class="btn btn-outline-sm"><i class="fa-solid fa-arrow-left"></i> Back to Fleet Directory</a>
    </div>

    <div class="card" style="max-width: 900px;">
      <h2 style="font-size: 1.8rem; margin-bottom: 1.5rem;"><i class="fa-solid fa-pen-to-square" style="color: var(--primary);"></i> Edit Vehicle #<?php echo $vehicle['id']; ?> (<?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?>)</h2>

      <form action="<?php echo url('/admin/edit-vehicle.php?id=' . $vehicle['id']); ?>" method="POST">
        <div class="form-grid-3">
          <div class="form-group">
            <label class="form-label">Brand</label>
            <input type="text" name="brand" class="form-control" value="<?php echo htmlspecialchars($vehicle['brand']); ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Model</label>
            <input type="text" name="model" class="form-control" value="<?php echo htmlspecialchars($vehicle['model']); ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Year</label>
            <input type="number" name="year" class="form-control" value="<?php echo htmlspecialchars($vehicle['year']); ?>" required>
          </div>
        </div>

        <div class="form-grid-3">
          <div class="form-group">
            <label class="form-label">Registration Number</label>
            <input type="text" name="reg_number" class="form-control" value="<?php echo htmlspecialchars($vehicle['reg_number']); ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-control" required>
              <?php foreach ($categories as $c): ?>
                <option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $vehicle['category_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Location Hub</label>
            <select name="location_id" class="form-control" required>
              <?php foreach ($locations as $l): ?>
                <option value="<?php echo $l['id']; ?>" <?php echo ($l['id'] == $vehicle['location_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($l['city_name']); ?> Hub</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-grid-4">
          <div class="form-group">
            <label class="form-label">Status</label>
            <select name="status" class="form-control" required>
              <option value="available" <?php echo ($vehicle['status'] == 'available') ? 'selected' : ''; ?>>Available</option>
              <option value="reserved" <?php echo ($vehicle['status'] == 'reserved') ? 'selected' : ''; ?>>Reserved</option>
              <option value="rented" <?php echo ($vehicle['status'] == 'rented') ? 'selected' : ''; ?>>Rented</option>
              <option value="maintenance" <?php echo ($vehicle['status'] == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Daily Rate (₹)</label>
            <input type="number" step="0.01" name="daily_rate" class="form-control" value="<?php echo htmlspecialchars($vehicle['daily_rate']); ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Hourly Rate (₹)</label>
            <input type="number" step="0.01" name="hourly_rate" class="form-control" value="<?php echo htmlspecialchars($vehicle['hourly_rate']); ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Security Deposit (₹)</label>
            <input type="number" step="0.01" name="deposit_amount" class="form-control" value="<?php echo htmlspecialchars($vehicle['deposit_amount']); ?>" required>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label"><i class="fa-solid fa-file-contract"></i> Insurance Expiry Date</label>
            <input type="date" name="insurance_expiry" class="form-control" value="<?php echo htmlspecialchars($vehicle['insurance_expiry'] ?? '2026-12-31'); ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label"><i class="fa-solid fa-smog"></i> PUC Expiry Date</label>
            <input type="date" name="puc_expiry" class="form-control" value="<?php echo htmlspecialchars($vehicle['puc_expiry'] ?? '2026-12-31'); ?>" required>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Fuel Type</label>
            <select name="fuel_type" class="form-control" required>
              <option value="Petrol" <?php echo ($vehicle['fuel_type'] == 'Petrol') ? 'selected' : ''; ?>>Petrol</option>
              <option value="Diesel" <?php echo ($vehicle['fuel_type'] == 'Diesel') ? 'selected' : ''; ?>>Diesel</option>
              <option value="Electric" <?php echo ($vehicle['fuel_type'] == 'Electric') ? 'selected' : ''; ?>>Electric</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Transmission</label>
            <select name="transmission" class="form-control" required>
              <option value="Automatic" <?php echo ($vehicle['transmission'] == 'Automatic') ? 'selected' : ''; ?>>Automatic</option>
              <option value="Manual" <?php echo ($vehicle['transmission'] == 'Manual') ? 'selected' : ''; ?>>Manual</option>
            </select>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label">Seating Capacity</label>
            <input type="number" name="seating_capacity" class="form-control" value="<?php echo htmlspecialchars($vehicle['seating_capacity']); ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Mileage / Range</label>
            <input type="text" name="mileage" class="form-control" value="<?php echo htmlspecialchars($vehicle['mileage']); ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Image URL</label>
          <input type="url" name="image_url" class="form-control" value="<?php echo htmlspecialchars($vehicle['image_url']); ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" required><?php echo htmlspecialchars($vehicle['description']); ?></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Features Tags</label>
          <input type="text" name="features" class="form-control" value="<?php echo htmlspecialchars($vehicle['features']); ?>" required>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="margin-top: 1.5rem;">
          <i class="fa-solid fa-floppy-disk"></i> Update Vehicle Details
        </button>
      </form>
    </div>
  </main>
</div>

</body>
</html>
