<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Vehicle Detail & Booking Calculator Page (vehicle-details.php)
 */
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$db = get_db_connection();

$vehicle_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($vehicle_id <= 0) {
    redirect('/vehicles.php');
}

// Fetch Vehicle details with category & location
$stmt = $db->prepare("
    SELECT v.*, c.name as category_name, l.city_name, l.location_address 
    FROM vehicles v 
    JOIN vehicle_categories c ON v.category_id = c.id 
    JOIN locations l ON v.location_id = l.id 
    WHERE v.id = ?
");
$stmt->execute([$vehicle_id]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    redirect('/vehicles.php');
}

// Fetch all pickup/dropoff location hubs
$locations = $db->query("SELECT * FROM locations WHERE status = 'active' ORDER BY city_name ASC")->fetchAll();

/**
 * Helper to structure fleet vehicle features into prominent spec-box cards
 */
function get_fleet_feature_boxes($features_str) {
    if (empty($features_str)) {
        return [
            ['label' => 'Climate System', 'value' => 'Air Conditioning', 'icon' => 'fa-solid fa-snowflake'],
            ['label' => 'Safety System', 'value' => 'Dual Front Airbags', 'icon' => 'fa-solid fa-shield-halved'],
            ['label' => 'Audio System', 'value' => 'Bluetooth Infotainment', 'icon' => 'fa-solid fa-music'],
            ['label' => 'Braking Tech', 'value' => 'ABS with EBD', 'icon' => 'fa-solid fa-circle-stop'],
            ['label' => 'Convenience', 'value' => 'Power Steering & Windows', 'icon' => 'fa-solid fa-bolt']
        ];
    }

    $raw_items = explode(',', $features_str);
    $boxes = [];

    foreach ($raw_items as $item) {
        $clean = trim($item);
        if (empty($clean)) continue;

        $lower = strtolower($clean);
        $icon = 'fa-solid fa-circle-check';
        $label = 'Premium Feature';

        if (strpos($lower, 'airbag') !== false || strpos($lower, 'ncap') !== false || strpos($lower, 'abs') !== false || strpos($lower, 'safety') !== false || strpos($lower, 'esp') !== false) {
            $icon = 'fa-solid fa-shield-halved';
            $label = 'Safety & Security';
        } elseif (strpos($lower, 'sunroof') !== false || strpos($lower, 'moonroof') !== false) {
            $icon = 'fa-solid fa-sun';
            $label = 'Cabin Roof';
        } elseif (strpos($lower, 'audio') !== false || strpos($lower, 'sound') !== false || strpos($lower, 'bose') !== false || strpos($lower, 'harman') !== false || strpos($lower, 'speaker') !== false) {
            $icon = 'fa-solid fa-music';
            $label = 'Acoustic Sound';
        } elseif (strpos($lower, 'touchscreen') !== false || strpos($lower, 'display') !== false || strpos($lower, 'smartplay') !== false || strpos($lower, 'cluster') !== false) {
            $icon = 'fa-solid fa-mobile-screen-button';
            $label = 'Smart Infotainment';
        } elseif (strpos($lower, 'camera') !== false || strpos($lower, '360') !== false || strpos($lower, 'sensor') !== false) {
            $icon = 'fa-solid fa-video';
            $label = 'Driver Assist';
        } elseif (strpos($lower, 'keyless') !== false || strpos($lower, 'push button') !== false || strpos($lower, 'smart key') !== false) {
            $icon = 'fa-solid fa-key';
            $label = 'Access & Entry';
        } elseif (strpos($lower, 'climate') !== false || strpos($lower, 'ac') !== false || strpos($lower, 'cooling') !== false) {
            $icon = 'fa-solid fa-snowflake';
            $label = 'Climate System';
        } elseif (strpos($lower, 'cruise') !== false) {
            $icon = 'fa-solid fa-gauge-simple-high';
            $label = 'Cruise Control';
        } elseif (strpos($lower, 'charger') !== false || strpos($lower, 'wireless') !== false) {
            $icon = 'fa-solid fa-charging-station';
            $label = 'Device Charging';
        } elseif (strpos($lower, 'leather') !== false || strpos($lower, 'seat') !== false) {
            $icon = 'fa-solid fa-couch';
            $label = 'Seating Comfort';
        } elseif (strpos($lower, 'ambient') !== false || strpos($lower, 'lighting') !== false || strpos($lower, 'led') !== false) {
            $icon = 'fa-solid fa-lightbulb';
            $label = 'Optics & Lighting';
        }

        $boxes[] = [
            'label' => $label,
            'value' => $clean,
            'icon'  => $icon
        ];
    }

    return $boxes;
}

$page_title = htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']) . " Rental — ASHVKATHA";
$extra_css  = ['vehicles.css', 'forms.css'];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="section-padding">
  <div class="container">
    <div style="margin-bottom: 2rem;">
      <a href="<?php echo url('/vehicles.php'); ?>" class="btn btn-outline-sm"><i class="fa-solid fa-arrow-left"></i> Back to Fleet Directory</a>
    </div>

    <div class="vehicle-detail-layout">
      <!-- Main Details & Gallery -->
      <main class="detail-main">
        <div class="main-image-frame">
          <?php $v_img = (strpos($vehicle['image_url'], 'http') === 0) ? $vehicle['image_url'] : url($vehicle['image_url']); ?>
          <img src="<?php echo htmlspecialchars($v_img); ?>" alt="<?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?>">
        </div>

        <div style="margin-top: 2rem;">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
            <div>
              <span class="badge badge-secondary" style="margin-bottom: 0.5rem;"><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($vehicle['category_name']); ?></span>
              <h1 style="font-size: 2.5rem; font-weight: 800;"><?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?> <span style="font-size: 1.5rem; color: var(--text-muted);">(<?php echo htmlspecialchars($vehicle['year']); ?>)</span></h1>
            </div>
            <div>
              <?php echo get_status_badge($vehicle['status']); ?>
            </div>
          </div>

          <p style="color: var(--text-muted); font-size: 1.1rem; margin-bottom: 2rem;">
            <?php echo htmlspecialchars($vehicle['description']); ?>
          </p>

          <!-- Specifications Grid -->
          <h3 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 1rem;">Vehicle Specifications</h3>
          <div class="detail-specs-grid">
            <div class="spec-box">
              <i class="fa-solid fa-gas-pump"></i>
              <div class="spec-box-label">Fuel Type</div>
              <div class="spec-box-value"><?php echo htmlspecialchars($vehicle['fuel_type']); ?></div>
            </div>

            <div class="spec-box">
              <i class="fa-solid fa-gear"></i>
              <div class="spec-box-label">Transmission</div>
              <div class="spec-box-value"><?php echo htmlspecialchars($vehicle['transmission']); ?></div>
            </div>

            <div class="spec-box">
              <i class="fa-solid fa-users"></i>
              <div class="spec-box-label">Seating Capacity</div>
              <div class="spec-box-value"><?php echo htmlspecialchars($vehicle['seating_capacity']); ?> Persons</div>
            </div>

            <div class="spec-box">
              <i class="fa-solid fa-gauge-high"></i>
              <div class="spec-box-label">Mileage / Range</div>
              <div class="spec-box-value"><?php echo htmlspecialchars($vehicle['mileage']); ?></div>
            </div>

            <div class="spec-box">
              <i class="fa-solid fa-location-dot"></i>
              <div class="spec-box-label">Home Hub Location</div>
              <div class="spec-box-value"><?php echo htmlspecialchars($vehicle['city_name']); ?></div>
            </div>

            <div class="spec-box">
              <i class="fa-solid fa-shield-halved"></i>
              <div class="spec-box-label">Security Deposit</div>
              <div class="spec-box-value"><?php echo format_currency($vehicle['deposit_amount']); ?></div>
            </div>
          </div>

          <!-- Vehicle Features & Equipment (Structured in identical prominent spec boxes) -->
          <h3 style="font-size: 1.4rem; font-weight: 700; margin: 2.5rem 0 1rem;"><i class="fa-solid fa-screwdriver-wrench" style="color: var(--primary);"></i> Vehicle Features & Equipment</h3>
          <div class="detail-specs-grid">
            <?php 
              $feature_boxes = get_fleet_feature_boxes($vehicle['features']);
              foreach ($feature_boxes as $fb):
            ?>
              <div class="spec-box spec-box-feature">
                <i class="<?php echo $fb['icon']; ?>"></i>
                <div class="spec-box-label"><?php echo htmlspecialchars($fb['label']); ?></div>
                <div class="spec-box-value"><?php echo htmlspecialchars($fb['value']); ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Rental Policy -->
          <div class="card" style="margin-top: 3rem;">
            <h4 style="font-size: 1.2rem; margin-bottom: 1rem;"><i class="fa-solid fa-file-contract" style="color: var(--primary);"></i> Ashvkatha Rental Terms</h4>
            <ul style="color: var(--text-muted); font-size: 0.95rem; display: flex; flex-direction: column; gap: 0.5rem;">
              <li><i class="fa-solid fa-angle-right"></i> Valid Original Driving License and Government ID mandatory at pickup.</li>
              <li><i class="fa-solid fa-angle-right"></i> Refundable security deposit of <?php echo format_currency($vehicle['deposit_amount']); ?> collected upon check-in.</li>
              <li><i class="fa-solid fa-angle-right"></i> Fuel Policy: Same-to-same fuel level return.</li>
              <li><i class="fa-solid fa-angle-right"></i> 24/7 Roadside assistance included free of charge across all highways.</li>
            </ul>
          </div>
        </div>
      </main>

      <!-- Sticky Booking Form Box -->
      <aside class="booking-box">
        <div class="booking-box-header">
          <div>
            <span style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase;">Daily Rate</span>
            <div style="font-size: 2rem; font-weight: 800; color: var(--primary);"><?php echo format_currency($vehicle['daily_rate']); ?> <span style="font-size: 0.9rem; color: var(--text-muted);">/ day</span></div>
          </div>
          <div style="text-align: right;">
            <span style="font-size: 0.8rem; color: var(--text-muted);">Hourly: <?php echo format_currency($vehicle['hourly_rate']); ?></span>
          </div>
        </div>

        <?php if ($vehicle['status'] !== 'available'): ?>
          <div class="alert alert-warning" style="margin: 0 0 1.5rem 0;">
            <i class="fa-solid fa-triangle-exclamation"></i> This vehicle is currently <?php echo strtoupper($vehicle['status']); ?>.
          </div>
        <?php else: ?>
          <form action="<?php echo url('/booking.php'); ?>" method="GET">
            <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">

            <!-- Delivery Logistics Method -->
            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-truck-plane"></i> Delivery Logistics Method</label>
              <select name="delivery_method" id="browse_delivery_method" class="form-control" onchange="toggleBrowseDelivery(this.value)">
                <option value="Road Trailer Truck">By Road Trailer Truck (Doorstep Flatbed Delivery)</option>
                <option value="Air Transport">Air Transport (Cargo Plane / Nearest Airstrip)</option>
                <option value="Boat / Sea Transport">By Boat / Sea Transport (Coastal Port Delivery)</option>
                <option value="Hub Self-Pickup" selected>Get from Hub (Choose from 6 Flagship Hubs)</option>
              </select>
            </div>

            <!-- Hub Pickup Option (6 Flagship Hubs) -->
            <div class="form-group" id="browse_hub_group">
              <label class="form-label"><i class="fa-solid fa-location-dot"></i> Choose Flagship Pickup Hub</label>
              <select name="pickup_location_id" class="form-control" required>
                <?php foreach ($locations as $loc): ?>
                  <option value="<?php echo $loc['id']; ?>" <?php echo ($loc['id'] == $vehicle['location_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($loc['city_name']); ?> Hub
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Conditional Custom Delivery Address -->
            <div class="form-group" id="browse_address_group" style="display: none;">
              <label class="form-label" id="browse_address_label"><i class="fa-solid fa-map-location-dot"></i> Delivery Address / Port / Airstrip</label>
              <input type="text" name="delivery_address" id="browse_address_input" class="form-control" placeholder="Enter doorstep residence, airstrip, or port address...">
            </div>

            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-location-arrow"></i> Return Hub Location</label>
              <select name="dropoff_location_id" class="form-control" required>
                <?php foreach ($locations as $loc): ?>
                  <option value="<?php echo $loc['id']; ?>" <?php echo ($loc['id'] == $vehicle['location_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($loc['city_name']); ?> Hub
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-grid-2">
              <div class="form-group">
                <label class="form-label"><i class="fa-solid fa-calendar-day"></i> Pickup Date & Time</label>
                <input type="datetime-local" name="pickup_datetime" class="form-control" value="<?php echo date('Y-m-d\TH:00', strtotime('+1 hour')); ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label"><i class="fa-solid fa-calendar-check"></i> Return Date & Time</label>
                <input type="datetime-local" name="return_datetime" class="form-control" value="<?php echo date('Y-m-d\TH:00', strtotime('+3 days')); ?>" required>
              </div>
            </div>

            <script>
            function toggleBrowseDelivery(val) {
              var hubGroup = document.getElementById('browse_hub_group');
              var addrGroup = document.getElementById('browse_address_group');
              var addrLabel = document.getElementById('browse_address_label');
              var addrInput = document.getElementById('browse_address_input');

              if (val === 'Hub Self-Pickup') {
                hubGroup.style.display = 'block';
                addrGroup.style.display = 'none';
              } else {
                hubGroup.style.display = 'none';
                addrGroup.style.display = 'block';
                if (val === 'Air Transport') {
                  addrLabel.innerHTML = '<i class="fa-solid fa-plane-arrival"></i> Airstrip / Airport Cargo Terminal';
                  addrInput.placeholder = 'e.g. SVPIA Airport / Mundra Airstrip / Private Hangar';
                } else if (val === 'Boat / Sea Transport') {
                  addrLabel.innerHTML = '<i class="fa-solid fa-ship"></i> Coastal Port / Marine Terminal';
                  addrInput.placeholder = 'e.g. Kandla Marine Berth / Mundra Port / Mandvi Marina';
                } else {
                  addrLabel.innerHTML = '<i class="fa-solid fa-truck"></i> Road Flatbed Delivery Address';
                  addrInput.placeholder = 'e.g. Home, Office, Hotel, or Estate Address in Gujarat';
                }
              }
            }
            </script>
            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 1rem;">
              <i class="fa-solid fa-bolt"></i> Proceed to Reservation
            </button>
          </form>
        <?php endif; ?>
      </aside>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
