<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Add Vehicle to Fleet Form (admin/add-vehicle.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$db = get_db_connection();

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
    $insurance_expiry = sanitize($_POST['insurance_expiry']);
    $puc_expiry       = sanitize($_POST['puc_expiry']);
    $image_url        = sanitize($_POST['image_url']);
    $mileage          = sanitize($_POST['mileage']);
    $description      = sanitize($_POST['description']);
    $features         = sanitize($_POST['features']);

    $stmt = $db->prepare("
        INSERT INTO vehicles 
        (category_id, location_id, brand, model, year, reg_number, fuel_type, transmission, seating_capacity, daily_rate, hourly_rate, deposit_amount, status, insurance_expiry, puc_expiry, image_url, mileage, description, features)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available', ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $category_id, $location_id, $brand, $model, $year, $reg_number, $fuel_type, $transmission,
        $seating_capacity, $daily_rate, $hourly_rate, $deposit_amount, $insurance_expiry, $puc_expiry, $image_url, $mileage, $description, $features
    ]);

    set_flash_message('success', 'New vehicle added to fleet successfully.');
    redirect('/admin/vehicles.php');
}

$categories = $db->query("SELECT * FROM vehicle_categories ORDER BY name ASC")->fetchAll();
$locations  = $db->query("SELECT * FROM locations WHERE status = 'active' ORDER BY city_name ASC")->fetchAll();

$page_title = "Add Vehicle — ASHVKATHA Admin";
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
      <h2 style="font-size: 1.8rem; margin-bottom: 1.5rem;"><i class="fa-solid fa-car-side" style="color: var(--primary);"></i> Add Vehicle to Fleet</h2>

      <form action="<?php echo url('/admin/add-vehicle.php'); ?>" method="POST">
        <!-- Section 1: Basic Information -->
        <h3 style="font-size: 1.1rem; color: var(--text-muted); margin-bottom: 1rem; text-transform: uppercase;">1. Basic Identification</h3>
        <div class="form-grid-3">
          <div class="form-group">
            <label class="form-label">Brand / Make *</label>
            <input type="text" name="brand" class="form-control" placeholder="e.g. BMW, Porsche" required>
          </div>

          <div class="form-group">
            <label class="form-label">Model Name *</label>
            <input type="text" name="model" class="form-control" placeholder="e.g. 5 Series, 911" required>
          </div>

          <div class="form-group">
            <label class="form-label">Model Year *</label>
            <input type="number" name="year" class="form-control" value="2025" required>
          </div>
        </div>

        <div class="form-grid-3">
          <div class="form-group">
            <label class="form-label">Registration Plate Number *</label>
            <input type="text" name="reg_number" class="form-control" placeholder="GJ-01-AB-1234" required>
          </div>

          <div class="form-group">
            <label class="form-label">Fleet Category *</label>
            <select name="category_id" class="form-control" required>
              <?php foreach ($categories as $c): ?>
                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Home Hub Location *</label>
            <select name="location_id" class="form-control" required>
              <?php foreach ($locations as $l): ?>
                <option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['city_name']); ?> Hub</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Section 2: Technical Specifications & Compliance -->
        <h3 style="font-size: 1.1rem; color: var(--text-muted); margin: 2rem 0 1rem; text-transform: uppercase;">2. Specifications & Compliance</h3>
        <div class="form-grid-4">
          <div class="form-group">
            <label class="form-label">Fuel Type</label>
            <select name="fuel_type" class="form-control" required>
              <option value="Petrol">Petrol</option>
              <option value="Diesel">Diesel</option>
              <option value="Electric">Electric</option>
              <option value="Hybrid">Hybrid</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Transmission</label>
            <select name="transmission" class="form-control" required>
              <option value="Automatic">Automatic</option>
              <option value="Manual">Manual</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Seating Capacity</label>
            <input type="number" name="seating_capacity" class="form-control" value="5" required>
          </div>

          <div class="form-group">
            <label class="form-label">Mileage / Range</label>
            <input type="text" name="mileage" class="form-control" placeholder="18.5 kmpl or 600 km" required>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label"><i class="fa-solid fa-file-contract"></i> Insurance Expiry Date *</label>
            <input type="date" name="insurance_expiry" class="form-control" value="<?php echo date('Y-12-31'); ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label"><i class="fa-solid fa-smog"></i> PUC Expiry Date *</label>
            <input type="date" name="puc_expiry" class="form-control" value="<?php echo date('Y-12-31'); ?>" required>
          </div>
        </div>

        <!-- Section 3: Pricing & Rates -->
        <h3 style="font-size: 1.1rem; color: var(--text-muted); margin: 2rem 0 1rem; text-transform: uppercase;">3. Pricing & Security Deposit</h3>
        <div class="form-grid-3">
          <div class="form-group">
            <label class="form-label">Daily Rate (₹) *</label>
            <input type="number" step="0.01" name="daily_rate" class="form-control" placeholder="2500.00" required>
          </div>

          <div class="form-group">
            <label class="form-label">Hourly Rate (₹) *</label>
            <input type="number" step="0.01" name="hourly_rate" class="form-control" placeholder="300.00" required>
          </div>

          <div class="form-group">
            <label class="form-label">Security Deposit (₹) *</label>
            <input type="number" step="0.01" name="deposit_amount" class="form-control" placeholder="5000.00" required>
          </div>
        </div>

        <!-- Section 4: Media & Details -->
        <h3 style="font-size: 1.1rem; color: var(--text-muted); margin: 2rem 0 1rem; text-transform: uppercase;">4. Image URL & Features</h3>
        <div class="form-group">
          <label class="form-label">Image URL *</label>
          <input type="url" name="image_url" class="form-control" placeholder="https://images.unsplash.com/..." required>
        </div>

        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" placeholder="Provide vehicle summary..." required></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Features (Comma-separated)</label>
          <input type="text" name="features" class="form-control" placeholder="Sunroof, ADAS, Leather Seats, Bose Audio" required>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="margin-top: 1.5rem;">
          <i class="fa-solid fa-plus"></i> Save & Publish Vehicle
        </button>
      </form>
    </div>
  </main>
</div>

</body>
</html>
