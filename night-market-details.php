<?php
/**
 * ASHVKATHA — Underground Night Market
 * Exclusive Vehicle Dossier & Acquisition Checkout (night-market-details.php)
 * UI/UX mirrors vehicle-details.php structured layout with Night Market theme & palette
 */
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$db = get_db_connection();

$car_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($car_id <= 0) {
    redirect('/night-market.php');
}

// Fetch Night Market Vehicle
$stmt = $db->prepare("SELECT * FROM night_market WHERE id = ?");
$stmt->execute([$car_id]);
$car = $stmt->fetch();

if (!$car) {
    set_flash_message('error', 'The requested Night Market vehicle build could not be located.');
    redirect('/night-market.php');
}

$specs = json_decode($car['specs_json'], true) ?: [];
$user  = get_logged_user();

/**
 * Helper to structure custom upgrades into prominent spec-like cards
 */
function get_custom_upgrades_list($features_str) {
    if (empty($features_str)) {
        return [
            ['label' => 'Engine & ECU Calibration', 'value' => 'Track-only tune', 'icon' => 'fa-solid fa-microchip'],
            ['label' => 'Aerodynamics & Carbon', 'value' => 'Widebody carbon aero', 'icon' => 'fa-solid fa-wind'],
            ['label' => 'Exhaust Architecture', 'value' => 'Akrapovic titanium exhaust', 'icon' => 'fa-solid fa-fire'],
            ['label' => 'Wheels & Unsprung Mass', 'value' => 'Forged monoblock rims', 'icon' => 'fa-solid fa-circle-dot'],
            ['label' => 'Chassis Rigidity & Safety', 'value' => 'Roll cage', 'icon' => 'fa-solid fa-shield-halved']
        ];
    }

    $raw_items = preg_split('/[,;\.\n]+/', $features_str);
    $upgrades = [];

    foreach ($raw_items as $item) {
        $clean = trim($item);
        if (empty($clean) || strlen($clean) < 3) continue;

        $lower = strtolower($clean);
        $icon = 'fa-solid fa-wrench';
        $label = 'Bespoke Upgrade';

        if (strpos($lower, 'tune') !== false || strpos($lower, 'ecu') !== false || strpos($lower, 'stage') !== false || strpos($lower, 'mapping') !== false) {
            $icon = 'fa-solid fa-microchip';
            $label = 'Engine & ECU Calibration';
        } elseif (strpos($lower, 'aero') !== false || strpos($lower, 'carbon') !== false || strpos($lower, 'wing') !== false || strpos($lower, 'splitter') !== false || strpos($lower, 'widebody') !== false || strpos($lower, 'body') !== false || strpos($lower, 'wrap') !== false || strpos($lower, 'armor') !== false) {
            $icon = 'fa-solid fa-wind';
            $label = 'Aerodynamics & Carbon Aero';
        } elseif (strpos($lower, 'exhaust') !== false || strpos($lower, 'titanium') !== false || strpos($lower, 'akrapovic') !== false || strpos($lower, 'pipe') !== false || strpos($lower, 'downpipe') !== false) {
            $icon = 'fa-solid fa-fire';
            $label = 'Exhaust Architecture';
        } elseif (strpos($lower, 'rim') !== false || strpos($lower, 'wheel') !== false || strpos($lower, 'monoblock') !== false || strpos($lower, 'forged') !== false || strpos($lower, 'tire') !== false || strpos($lower, 'alloy') !== false) {
            $icon = 'fa-solid fa-circle-dot';
            $label = 'Wheels & Unsprung Mass';
        } elseif (strpos($lower, 'cage') !== false || strpos($lower, 'roll') !== false || strpos($lower, 'harness') !== false || strpos($lower, 'bucket') !== false || strpos($lower, 'chassis') !== false || strpos($lower, 'brace') !== false) {
            $icon = 'fa-solid fa-shield-halved';
            $label = 'Chassis Rigidity & Safety';
        } elseif (strpos($lower, 'suspension') !== false || strpos($lower, 'coilovers') !== false || strpos($lower, 'lift') !== false || strpos($lower, 'damper') !== false || strpos($lower, 'air') !== false) {
            $icon = 'fa-solid fa-compress';
            $label = 'Track Suspension Setup';
        } elseif (strpos($lower, 'brake') !== false || strpos($lower, 'caliper') !== false || strpos($lower, 'ceramic') !== false || strpos($lower, 'rotor') !== false) {
            $icon = 'fa-solid fa-circle-stop';
            $label = 'Braking & Thermal Control';
        } elseif (strpos($lower, 'glass') !== false || strpos($lower, 'tint') !== false || strpos($lower, 'privacy') !== false || strpos($lower, 'light') !== false || strpos($lower, 'blackout') !== false) {
            $icon = 'fa-solid fa-eye-slash';
            $label = 'Aesthetics & Blackout Spec';
        }

        $upgrades[] = [
            'label' => $label,
            'value' => $clean,
            'icon'  => $icon
        ];
    }

    if (empty($upgrades)) {
        $upgrades = [
            ['label' => 'Engine & ECU Calibration', 'value' => 'Track-only tune', 'icon' => 'fa-solid fa-microchip'],
            ['label' => 'Aerodynamics & Carbon', 'value' => 'Widebody carbon aero', 'icon' => 'fa-solid fa-wind'],
            ['label' => 'Exhaust Architecture', 'value' => 'Akrapovic titanium exhaust', 'icon' => 'fa-solid fa-fire'],
            ['label' => 'Wheels & Unsprung Mass', 'value' => 'Forged monoblock rims', 'icon' => 'fa-solid fa-circle-dot'],
            ['label' => 'Chassis Rigidity & Safety', 'value' => 'Roll cage', 'icon' => 'fa-solid fa-shield-halved']
        ];
    }

    return $upgrades;
}

$custom_upgrades = get_custom_upgrades_list($car['features']);

// Handle Acquisition Form Submission (POST) - Saves directly to night_market_orders
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_acquisition') {
    // Exclusivity Lock Check: Only available builds can be acquired
    if ($car['status'] !== 'available') {
        set_flash_message('error', 'This vehicle build has already been acquired and is currently locked out. Only one collector can hold this allocation.');
        redirect("/night-market-details.php?id={$car_id}");
    }

    $full_name        = sanitize($_POST['full_name'] ?? '');
    $email            = sanitize($_POST['email'] ?? '');
    $phone            = sanitize($_POST['phone'] ?? '');
    $delivery_method  = sanitize($_POST['delivery_method'] ?? 'road_trailer');
    $hub_selection    = sanitize($_POST['hub_selection'] ?? 'Ahmedabad Hub');
    $custom_address   = sanitize($_POST['delivery_address'] ?? '');
    $payment_method   = sanitize($_POST['payment_method'] ?? 'Crypto Multi-Sig Escrow (USDT / BTC / ETH Cold Storage)');
    $terms_agreed     = isset($_POST['terms_agreement']) && $_POST['terms_agreement'] === '1';

    // Format delivery option and address based on chosen method
    if ($delivery_method === 'hub_pickup') {
        $delivery_option  = "Get from Hub ({$hub_selection})";
        $delivery_address = "{$hub_selection} — Ashvkatha Flagship Hub Handover Desk";
    } elseif ($delivery_method === 'air_transport') {
        $delivery_option  = "Air Transport (Cargo Plane / Nearest Airstrip)";
        $delivery_address = !empty($custom_address) ? $custom_address : 'Nearest Airport / Private Hangar Terminal';
    } elseif ($delivery_method === 'boat_sea') {
        $delivery_option  = "By Boat / Sea Transport (Port / Marina Delivery)";
        $delivery_address = !empty($custom_address) ? $custom_address : 'Nearest Coastal Port / Marina Berth';
    } else {
        $delivery_option  = "By Road Trailer Truck (Enclosed Transporter)";
        $delivery_address = !empty($custom_address) ? $custom_address : 'Private Estate / Secure Garage Handover';
    }

    if (empty($full_name) || empty($email) || empty($phone)) {
        set_flash_message('error', 'Please fill in all buyer contact details.');
        redirect("/night-market-details.php?id={$car_id}");
    }

    if (!$terms_agreed) {
        set_flash_message('error', 'You must agree to the Underground Night Market Acquisition & Escrow Protocol.');
        redirect("/night-market-details.php?id={$car_id}");
    }

    // Determine or establish user account
    if (is_logged_in()) {
        $buyer_id = $user['id'];
    } else {
        $u_stmt = $db->prepare("SELECT id, full_name, email, phone FROM users WHERE email = ?");
        $u_stmt->execute([$email]);
        $existing = $u_stmt->fetch();
        if ($existing) {
            $buyer_id = $existing['id'];
            $_SESSION['user_id']    = $existing['id'];
            $_SESSION['user_name']  = $existing['full_name'];
            $_SESSION['user_email'] = $existing['email'];
            $_SESSION['user_role']  = 'customer';
            $_SESSION['user_phone'] = $existing['phone'];
        } else {
            $pass_hash = password_hash('Ashvkatha@123', PASSWORD_DEFAULT);
            $ins = $db->prepare("INSERT INTO users (full_name, email, password, phone, role) VALUES (?, ?, ?, ?, 'customer')");
            $ins->execute([$full_name, $email, $pass_hash, $phone]);
            $buyer_id = $db->lastInsertId();
            $_SESSION['user_id']    = $buyer_id;
            $_SESSION['user_name']  = $full_name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role']  = 'customer';
            $_SESSION['user_phone'] = $phone;
        }
    }

    // Pricing calculation (Acquisition price is daily_rate)
    $acq_price    = (float)$car['daily_rate'];
    $tax_amount   = round($acq_price * 0.18, 2); // 18% GST
    $total_amount = $acq_price + $tax_amount;

    $order_code   = 'NM-' . date('Y') . '-' . rand(1000, 9999);
    $txn_id       = 'TXN-NM-' . rand(10000, 99999);

    // Insert into separate night_market_orders table
    $order_stmt = $db->prepare("
        INSERT INTO night_market_orders 
        (order_code, user_id, car_id, buyer_name, buyer_email, buyer_phone, delivery_option, delivery_address, acquisition_price, tax_amount, total_amount, payment_method, payment_status, order_status, transaction_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', 'confirmed', ?)
    ");
    $order_stmt->execute([
        $order_code,
        $buyer_id,
        $car['id'],
        $full_name,
        $email,
        $phone,
        $delivery_option,
        $delivery_address,
        $acq_price,
        $tax_amount,
        $total_amount,
        $payment_method,
        $txn_id
    ]);
    $order_id = $db->lastInsertId();

    // EXCLUSIVITY LOCK: Immediately set vehicle status to sold_out so no one else can buy it
    $lock_stmt = $db->prepare("UPDATE night_market SET status = 'sold_out' WHERE id = ?");
    $lock_stmt->execute([$car['id']]);

    set_flash_message('success', "Acquisition allocation confirmed! Order {$order_code} has been registered to your account.");
    redirect('/customer/night-market-orders.php?order_id=' . $order_id);
}

$page_title = htmlspecialchars($car['vehicle_name']) . " — Underground Night Market Exclusive";
$body_class = 'night-market-body';
$extra_css  = ['night-market.css', 'forms.css'];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';

$img_src = (strpos($car['image_url'], 'http') === 0) ? $car['image_url'] : url($car['image_url']);
?>

<div class="nm-details-wrapper section-padding" style="padding-top: 6.5rem; min-height: 90vh; background-color: #060b19;">
  <div class="container">

    <!-- Top Navigation / Back Button (Matches vehicle-details.php UI) -->
    <div style="margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between;">
      <a href="<?php echo url('/night-market.php'); ?>" class="btn-nm-back">
        <i class="fa-solid fa-arrow-left"></i> Back to Underground Night Market
      </a>
      <div style="font-size: 0.85rem; color: #94a3b8; display: flex; align-items: center; gap: 0.5rem;">
        <?php if ($car['status'] === 'sold_out'): ?>
          <span class="nm-live-dot" style="background: #ef4444; box-shadow: 0 0 10px #ef4444;"></span> <span style="color: #ef4444; font-weight: 700; letter-spacing: 0.05em;">BUILD ALLOCATED / OFF-MARKET</span>
        <?php else: ?>
          <span class="nm-live-dot"></span> <span>UNDERGROUND ALLOCATION ACTIVE</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- 2-Column Layout (Identical UI structure to vehicle-details.php) -->
    <div class="nm-detail-layout">

      <!-- Main Column: Image, Specs & Dossier -->
      <main class="nm-detail-main">

        <!-- Vehicle Stage Image Frame -->
        <div class="nm-main-image-frame">
          <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($car['vehicle_name']); ?>">
          <div class="nm-frame-badge">
            <i class="fa-solid fa-certificate"></i>
            <span><?php echo htmlspecialchars($car['badge_type'] ?: 'Non-Road-Legal Track Machine'); ?></span>
          </div>
          <div class="nm-frame-discount-pill">
            <i class="fa-solid fa-bolt"></i> 60% Acquisition Price
          </div>
        </div>

        <div style="margin-top: 2rem;">
          <!-- Header: Category, Title & Status -->
          <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1.5rem; margin-bottom: 1.2rem; flex-wrap: wrap;">
            <div>
              <span class="nm-badge-category" style="margin-bottom: 0.6rem; display: inline-block;">
                <i class="fa-solid fa-shield-halved"></i> <?php echo htmlspecialchars($car['category']); ?>
              </span>
              <h1 class="nm-detail-title">
                <?php echo htmlspecialchars($car['vehicle_name']); ?>
              </h1>
              <div class="nm-detail-subtitle">
                <i class="fa-solid fa-tag" style="color: #a855f7;"></i> <?php echo htmlspecialchars($car['subtitle']); ?> 
                <?php if (!empty($car['brand'])): ?>
                  &bull; <span style="color: #cbd5e1;"><?php echo htmlspecialchars($car['brand']); ?> Performance</span>
                <?php endif; ?>
              </div>
            </div>
            <div>
              <span class="nm-badge-status">
                <i class="fa-solid fa-circle-check"></i> Available for Acquisition
              </span>
            </div>
          </div>

          <!-- Build Dossier & Engineering Notes -->
          <div class="nm-dossier-card">
            <h3 class="nm-card-title"><i class="fa-solid fa-file-waveform" style="color: #38bdf8;"></i> Vehicle Build Dossier & Provenance</h3>
            <p class="nm-card-text">
              <?php echo !empty($car['features']) ? htmlspecialchars($car['features']) : 'Engineered strictly for closed-course track days, private tarmac estates, and VIP collectors. Features authentic competition tuning, race-spec cooling, and bespoke lightweight components.'; ?>
            </p>
          </div>

          <!-- Specifications Grid (Matches vehicle-details.php detail-specs-grid, styled in Night Market theme) -->
          <h3 class="nm-section-heading"><i class="fa-solid fa-microchip" style="color: #a855f7;"></i> Engineering & Dimensional Specifications</h3>
          
          <div class="nm-specs-grid">
            <div class="nm-spec-box">
              <i class="fa-solid fa-fire"></i>
              <div class="nm-spec-label">Engine Configuration</div>
              <div class="nm-spec-value"><?php echo htmlspecialchars($specs['engine'] ?? '4.4L Twin-Turbo V8'); ?></div>
            </div>

            <div class="nm-spec-box">
              <i class="fa-solid fa-gauge-high"></i>
              <div class="nm-spec-label">Peak Power Output</div>
              <div class="nm-spec-value"><?php echo htmlspecialchars($specs['power'] ?? '626 HP @ 6,500 RPM'); ?></div>
            </div>

            <div class="nm-spec-box">
              <i class="fa-solid fa-bolt"></i>
              <div class="nm-spec-label">Peak Torque</div>
              <div class="nm-spec-value"><?php echo htmlspecialchars($specs['torque'] ?? '750 Nm @ 1,800 RPM'); ?></div>
            </div>

            <div class="nm-spec-box">
              <i class="fa-solid fa-stopwatch"></i>
              <div class="nm-spec-label">0 - 100 km/h Sprint</div>
              <div class="nm-spec-value"><?php echo htmlspecialchars($specs['acceleration'] ?? '0-100 km/h in 3.6s'); ?></div>
            </div>

            <div class="nm-spec-box">
              <i class="fa-solid fa-gauge"></i>
              <div class="nm-spec-label">Maximum Velocity</div>
              <div class="nm-spec-value"><?php echo htmlspecialchars($specs['top_speed'] ?? '290 km/h'); ?></div>
            </div>

            <div class="nm-spec-box">
              <i class="fa-solid fa-car-side"></i>
              <div class="nm-spec-label">Drivetrain & Traction</div>
              <div class="nm-spec-value"><?php echo htmlspecialchars($specs['drivetrain'] ?? 'Active AWD with Torque Vectoring'); ?></div>
            </div>

            <div class="nm-spec-box">
              <i class="fa-solid fa-gear"></i>
              <div class="nm-spec-label">Transmission</div>
              <div class="nm-spec-value"><?php echo htmlspecialchars($specs['transmission'] ?? '8-Speed Sport Shift'); ?></div>
            </div>

            <div class="nm-spec-box">
              <i class="fa-solid fa-weight-hanging"></i>
              <div class="nm-spec-label">Curb Weight</div>
              <div class="nm-spec-value"><?php echo htmlspecialchars($specs['weight'] ?? '2,485 kg'); ?></div>
            </div>

            <div class="nm-spec-box">
              <i class="fa-solid fa-road"></i>
              <div class="nm-spec-label">Ground Clearance</div>
              <div class="nm-spec-value"><?php echo htmlspecialchars($specs['ground_clearance'] ?? '216 mm (Track Stance)'); ?></div>
            </div>
          </div>

          <!-- Custom Upgrades & Build Features (Formatted in identical high-impact structured spec boxes) -->
          <h3 class="nm-section-heading" style="margin-top: 2.5rem;"><i class="fa-solid fa-screwdriver-wrench" style="color: #38bdf8;"></i> Custom Upgrades & Build Features</h3>
          
          <div class="nm-specs-grid">
            <?php foreach ($custom_upgrades as $upg): ?>
              <div class="nm-spec-box nm-spec-box-upgrade">
                <i class="<?php echo $upg['icon']; ?>"></i>
                <div class="nm-spec-label"><?php echo htmlspecialchars($upg['label']); ?></div>
                <div class="nm-spec-value"><?php echo htmlspecialchars($upg['value']); ?></div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Underground Acquisition & Escrow Protocol (Matches vehicle-details.php terms card) -->
          <div class="nm-terms-card">
            <h4 class="nm-terms-title"><i class="fa-solid fa-shield-halved" style="color: #a855f7;"></i> Underground Night Market Acquisition Terms</h4>
            <ul class="nm-terms-list">
              <li><i class="fa-solid fa-angle-right"></i> <strong>Exclusivity & Provenance:</strong> Direct acquisition at 60% valuation with authentic chassis verification and dyno certification.</li>
              <li><i class="fa-solid fa-angle-right"></i> <strong>Private Enclosed Transit:</strong> Hydraulic enclosed multi-car or single transporter direct to buyer's private estate or hangar across India.</li>
              <li><i class="fa-solid fa-angle-right"></i> <strong>48-Hour Escrow Protection:</strong> Funds remain secured in transaction escrow until physical handover and buyer inspection sign-off.</li>
              <li><i class="fa-solid fa-angle-right"></i> <strong>Non-Road-Legal Advisory:</strong> Configured strictly for closed-course motorsport, private collectors, and private circuits. Non-registered track machinery.</li>
            </ul>
          </div>

        </div>
      </main>

      <!-- Right Column: Sticky Acquisition Checkout Box (Matches vehicle-details.php booking-box) -->
      <aside class="nm-acquisition-box">
        <div class="nm-acquisition-header">
          <div>
            <span class="nm-header-label">60% Direct Acquisition Price</span>
            <div class="nm-header-price"><?php echo format_currency($car['daily_rate']); ?></div>
          </div>
          <div style="text-align: right;">
            <?php if ($car['status'] === 'sold_out'): ?>
              <span class="nm-badge-discount" style="background: rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.4); color: #f87171;">LOCKED ALLOCATION</span>
            <?php else: ?>
              <span class="nm-badge-discount">-40% OFF RETAIL</span>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($car['status'] === 'sold_out'): ?>
          <!-- Single-Buyer Lockout Display -->
          <div style="margin-top: 1.5rem; padding: 1.8rem 1.4rem; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 12px; text-align: center;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.2rem; color: #ef4444; font-size: 1.5rem;">
              <i class="fa-solid fa-lock"></i>
            </div>
            <div style="display: inline-block; background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171; font-size: 0.72rem; font-weight: 800; padding: 3px 10px; border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.8rem;">
              1 of 1 Exclusivity Lock
            </div>
            <h3 style="color: #ffffff; font-size: 1.35rem; font-weight: 800; margin-bottom: 0.6rem;">Allocation Acquired / Sold Out</h3>
            <p style="color: #94a3b8; font-size: 0.86rem; line-height: 1.6; margin-bottom: 1.5rem;">
              This unique build has already been acquired by a collector. Only one owner can hold this allocation at a time. It remains locked and cannot be purchased by anyone else unless the deal is cancelled by administrators or re-stocked.
            </p>
            <div style="padding: 1rem; background: rgba(15, 23, 42, 0.8); border-radius: 8px; border: 1px solid rgba(255,255,255,0.08); margin-bottom: 1.5rem; text-align: left;">
              <div style="display: flex; justify-content: space-between; align-items: center;">
                <span style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; font-weight: 700;">Status</span>
                <span style="font-size: 0.78rem; font-weight: 700; color: #ef4444;"><i class="fa-solid fa-circle-xmark"></i> Sold to Private Buyer</span>
              </div>
              <div style="font-size: 0.76rem; color: #64748b; margin-top: 0.4rem; line-height: 1.4;">
                To acquire another vehicle, please explore active allocations in our Underground Night Market roster.
              </div>
            </div>
            <a href="<?php echo url('/night-market.php'); ?>" class="btn-nm-acquire" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; text-decoration: none; text-align: center;">
              <i class="fa-solid fa-arrow-left"></i> View Available Builds
            </a>
          </div>

        <?php else: ?>

          <div class="nm-tax-notice">
            <i class="fa-solid fa-receipt"></i> + 18% GST applicable upon transfer documentation
          </div>

          <form action="<?php echo url('/night-market-details.php?id=' . $car['id']); ?>" method="POST" class="nm-checkout-form">
            <input type="hidden" name="action" value="confirm_acquisition">

            <div class="form-group">
              <label class="nm-form-label"><i class="fa-solid fa-user"></i> Buyer Full Legal Name</label>
              <input type="text" name="full_name" class="form-control nm-form-input" required 
                     value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" placeholder="Enter legal buyer name">
            </div>

            <div class="form-group">
              <label class="nm-form-label"><i class="fa-solid fa-envelope"></i> Buyer Email Address</label>
              <input type="email" name="email" class="form-control nm-form-input" required 
                     value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="buyer@domain.com">
            </div>

            <div class="form-group">
              <label class="nm-form-label"><i class="fa-solid fa-phone"></i> Direct Contact Phone</label>
              <input type="tel" name="phone" class="form-control nm-form-input" required 
                     value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+91 98765 43210">
            </div>

            <!-- Practical Delivery Logistics Method -->
            <div class="form-group">
              <label class="nm-form-label"><i class="fa-solid fa-truck-plane"></i> Delivery Logistics Method</label>
              <select name="delivery_method" id="nm_delivery_method" class="form-control nm-form-input" onchange="toggleNmLogistics(this.value)">
                <option value="road_trailer" selected>By Road Trailer Truck (Enclosed Transporter)</option>
                <option value="air_transport">Air Transport (Cargo Plane / Nearest Airstrip)</option>
                <option value="boat_sea">By Boat / Sea Transport (Ocean Vessel / Port Delivery)</option>
                <option value="hub_pickup">Get from Hub (Choose from 6 Flagship Hubs)</option>
              </select>
            </div>

            <!-- Conditional Hub Selection (6 Exclusive Hubs) -->
            <div class="form-group" id="nm_hub_group" style="display: none;">
              <label class="nm-form-label"><i class="fa-solid fa-location-dot"></i> Choose Flagship Handover Hub</label>
              <select name="hub_selection" class="form-control nm-form-input">
                <option value="Ahmedabad Hub">Ahmedabad (Amdavad) — SG Highway Premium Lounge</option>
                <option value="Rajkot Hub">Rajkot — Airport Road Terminal</option>
                <option value="Gandhinagar Hub">Gandhinagar — Infocity Capital Hub</option>
                <option value="Junagadh Hub">Junagadh — Ashvkatha Central Hub, Gir Road</option>
                <option value="Amreli Hub">Amreli — Station Road Hub, Near Circle</option>
                <option value="Surendranagar Hub">Surendranagar — Highway Express Hub</option>
              </select>
            </div>

            <!-- Conditional Destination Address -->
            <div class="form-group" id="nm_address_group">
              <label class="nm-form-label" id="nm_address_label"><i class="fa-solid fa-map-location-dot"></i> Delivery Destination Address / Estate / Garage</label>
              <textarea name="delivery_address" id="nm_address_input" class="form-control nm-form-input" rows="2" placeholder="Private estate, residence, or secure garage address in Gujarat..."></textarea>
            </div>

            <!-- Payment & Escrow Method: Crypto, Physical Handover by Enclosed Transporter at Delivery, Wire/RTGS -->
            <div class="form-group">
              <label class="nm-form-label"><i class="fa-solid fa-shield-halved" style="color: #38bdf8;"></i> Payment & Escrow Method</label>
              <select name="payment_method" id="nm_payment_method" class="form-control nm-form-input" onchange="toggleNmPaymentNotes(this.value)">
                <option value="Crypto Multi-Sig Escrow (USDT / BTC / ETH Cold Storage)" selected>Crypto Multi-Sig Escrow (USDT / BTC / ETH Cold Storage)</option>
                <option value="Physical Handover by Enclosed Transporter (Payment at Delivery)">Physical Handover by Enclosed Transporter (Payment at Delivery)</option>
                <option value="Bank Wire Escrow / RTGS NetBanking">Bank Wire Escrow / RTGS NetBanking</option>
              </select>

              <!-- Dynamic Payment Guidance Notes -->
              <div id="pm_note_crypto" style="margin-top: 0.6rem; padding: 0.75rem; background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.25); border-radius: 8px; font-size: 0.8rem; color: #bae6fd; line-height: 1.45;">
                <i class="fa-brands fa-bitcoin" style="color: #38bdf8;"></i> <strong>Crypto Escrow Protocol:</strong> Smart contract multi-sig custody wallet (USDT TRC20/ERC20, BTC, ETH) with 48h settlement freeze until inspection.
              </div>
              <div id="pm_note_physical" style="display: none; margin-top: 0.6rem; padding: 0.75rem; background: rgba(168, 85, 247, 0.08); border: 1px solid rgba(168, 85, 247, 0.25); border-radius: 8px; font-size: 0.8rem; color: #e9d5ff; line-height: 1.45;">
                <i class="fa-solid fa-truck-ramp-box" style="color: #c084fc;"></i> <strong>Physical Handover at Delivery:</strong> Settle payment directly upon arrival with our enclosed transporter crew after unlading and walkaround inspection.
              </div>
              <div id="pm_note_wire" style="display: none; margin-top: 0.6rem; padding: 0.75rem; background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 8px; font-size: 0.8rem; color: #a7f3d0; line-height: 1.45;">
                <i class="fa-solid fa-building-columns" style="color: #34d399;"></i> <strong>Bank Wire / RTGS:</strong> Official RTGS escrow credentials provided on voucher. Funds remain protected until digital receipt sign-off.
              </div>
            </div>

            <div class="nm-agreement-group">
              <label class="nm-checkbox-label">
                <input type="checkbox" name="terms_agreement" value="1" required>
                <span>I agree to the Underground Night Market Acquisition Protocol, 48-Hour Escrow Handover, and closed-course terms.</span>
              </label>
            </div>

            <button type="submit" class="btn-nm-acquire">
              <i class="fa-solid fa-gem"></i> Confirm & Secure Allocation
            </button>
          </form>

          <script>
          function toggleNmLogistics(val) {
            var hubGroup = document.getElementById('nm_hub_group');
            var addrGroup = document.getElementById('nm_address_group');
            var addrLabel = document.getElementById('nm_address_label');
            var addrInput = document.getElementById('nm_address_input');

            if (val === 'hub_pickup') {
              hubGroup.style.display = 'block';
              addrGroup.style.display = 'none';
            } else {
              hubGroup.style.display = 'none';
              addrGroup.style.display = 'block';
              if (val === 'air_transport') {
                addrLabel.innerHTML = '<i class="fa-solid fa-plane-arrival"></i> Airstrip / Private Airport / Hangar Details';
                addrInput.placeholder = 'e.g. SVPIA Cargo Terminal / Mundra Private Airstrip / Hangar #4';
              } else if (val === 'boat_sea') {
                addrLabel.innerHTML = '<i class="fa-solid fa-ship"></i> Coastal Port / Marine Terminal / Marina Berth';
                addrInput.placeholder = 'e.g. Kandla Port Cargo Berth / Mundra Marine Terminal / Mandvi Marina';
              } else {
                addrLabel.innerHTML = '<i class="fa-solid fa-truck"></i> Road Trailer Delivery Address / Estate / Garage';
                addrInput.placeholder = 'e.g. Private Estate, Farmhouse, or Secure Garage Address...';
              }
            }
          }

          function toggleNmPaymentNotes(val) {
            var nCrypto = document.getElementById('pm_note_crypto');
            var nPhys = document.getElementById('pm_note_physical');
            var nWire = document.getElementById('pm_note_wire');

            nCrypto.style.display = 'none';
            nPhys.style.display = 'none';
            nWire.style.display = 'none';

            if (val.indexOf('Crypto') !== -1) {
              nCrypto.style.display = 'block';
            } else if (val.indexOf('Physical') !== -1) {
              nPhys.style.display = 'block';
            } else {
              nWire.style.display = 'block';
            }
          }
          </script>

        <?php endif; ?>

        <div class="nm-guarantee-footer">
          <div class="nm-guarantee-item">
            <i class="fa-solid fa-lock" style="color: #38bdf8;"></i>
            <span>100% Escrow Secured</span>
          </div>
          <div class="nm-guarantee-item">
            <i class="fa-solid fa-shield-check" style="color: #a855f7;"></i>
            <span>Chassis Authenticity Guaranteed</span>
          </div>
          <div class="nm-guarantee-item">
            <i class="fa-solid fa-truck" style="color: #38bdf8;"></i>
            <span>Enclosed Pan-India Transit</span>
          </div>
        </div>

      </aside>

    </div>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
