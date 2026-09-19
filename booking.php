<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Multi-Step Booking & Confirmation Flow (booking.php)
 */
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$db = get_db_connection();

// Process Booking Form Submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_booking') {
    require_login();
    $user = get_logged_user();

    $vehicle_id          = (int)$_POST['vehicle_id'];
    $pickup_location_id  = (int)$_POST['pickup_location_id'];
    $dropoff_location_id = (int)$_POST['dropoff_location_id'];
    $pickup_datetime     = sanitize($_POST['pickup_datetime']);
    $return_datetime     = sanitize($_POST['return_datetime']);
    $payment_method      = sanitize($_POST['payment_method']);
    $promo_code          = sanitize($_POST['promo_code'] ?? '');

    // Calculate duration & cost
    $days = calculate_days($pickup_datetime, $return_datetime);

    $v_stmt = $db->prepare("SELECT * FROM vehicles WHERE id = ?");
    $v_stmt->execute([$vehicle_id]);
    $veh = $v_stmt->fetch();

    if (!$veh) {
        set_flash_message('error', 'Vehicle not found.');
        redirect('/vehicles.php');
    }

    $base_amount = $veh['daily_rate'] * $days;
    $deposit_amount = $veh['deposit_amount'];

    // One-Way Relocation Fee if pickup != dropoff
    $extra_charges = 0.00;
    if ($pickup_location_id !== $dropoff_location_id) {
        $extra_charges = 500.00; // ₹500 One-Way Station Charge
    }

    // Apply promo discount if valid
    $discount_amount = 0.00;
    if (!empty($promo_code)) {
        $d_stmt = $db->prepare("SELECT * FROM discounts WHERE promo_code = ? AND status = 'active' AND expiry_date >= CURDATE()");
        $d_stmt->execute([$promo_code]);
        $disc = $d_stmt->fetch();

        if ($disc) {
            if ($disc['discount_type'] === 'percentage') {
                $discount_amount = ($base_amount * $disc['discount_value']) / 100;
            } else {
                $discount_amount = $disc['discount_value'];
            }
        }
    }

    $tax_amount = ($base_amount + $extra_charges - $discount_amount) * 0.18; // 18% GST
    $total_amount = ($base_amount + $extra_charges - $discount_amount) + $tax_amount + $deposit_amount;

    $delivery_method  = sanitize($_POST['delivery_method'] ?? 'Hub Self-Pickup');
    $delivery_address = sanitize($_POST['delivery_address'] ?? '');

    $booking_code = 'ASHV-' . date('Y') . '-' . rand(1000, 9999);

    // Insert Booking Record
    $b_stmt = $db->prepare("
        INSERT INTO bookings 
        (booking_code, user_id, vehicle_id, pickup_location_id, dropoff_location_id, delivery_method, delivery_address, pickup_datetime, return_datetime, total_days, base_amount, extra_charges, discount_amount, tax_amount, deposit_amount, total_amount, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')
    ");
    $b_stmt->execute([
        $booking_code, $user['id'], $vehicle_id, $pickup_location_id, $dropoff_location_id,
        $delivery_method, $delivery_address,
        $pickup_datetime, $return_datetime, $days, $base_amount, $extra_charges, $discount_amount, $tax_amount, $deposit_amount, $total_amount
    ]);
    $booking_id = $db->lastInsertId();

    // Update vehicle status to rented
    $db->prepare("UPDATE vehicles SET status = 'rented' WHERE id = ?")->execute([$vehicle_id]);

    // Insert Payment Record
    $txn_id = 'TXN-ASHV-' . rand(10000, 99999);
    $p_stmt = $db->prepare("
        INSERT INTO payments (booking_id, transaction_id, payment_method, amount, payment_status)
        VALUES (?, ?, ?, ?, 'completed')
    ");
    $p_stmt->execute([$booking_id, $txn_id, $payment_method, $total_amount]);

    set_flash_message('success', 'Reservation confirmed successfully! Your booking voucher is ready.');
    redirect("/booking.php?confirmed_id={$booking_id}");
}

// Display Confirmed Voucher Ticket if confirmed_id is set
$confirmed_booking = null;
if (isset($_GET['confirmed_id'])) {
    $cid = (int)$_GET['confirmed_id'];
    $c_stmt = $db->prepare("
        SELECT b.*, 
               COALESCE(v.brand, nm.brand, 'ASHVKATHA Special') as brand, 
               COALESCE(v.model, nm.vehicle_name, 'Custom Build') as model, 
               COALESCE(v.reg_number, 'TRACK-USE-ONLY') as reg_number, 
               COALESCE(v.image_url, nm.image_url, '') as image_url, 
               COALESCE(l1.city_name, 'Central Hub') as pickup_city, 
               COALESCE(l2.city_name, 'Private Handover') as dropoff_city,
               u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone,
               p.transaction_id, p.payment_method
        FROM bookings b
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        LEFT JOIN night_market nm ON b.vehicle_id = nm.id
        LEFT JOIN locations l1 ON b.pickup_location_id = l1.id
        LEFT JOIN locations l2 ON b.dropoff_location_id = l2.id
        JOIN users u ON b.user_id = u.id
        LEFT JOIN payments p ON p.booking_id = b.id
        WHERE b.id = ?
    ");
    $c_stmt->execute([$cid]);
    $confirmed_booking = $c_stmt->fetch();
}

// GET Parameters for Step 1 -> Step 3
$vehicle_id          = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;
$pickup_location_id  = isset($_GET['pickup_location_id']) ? (int)$_GET['pickup_location_id'] : 1;
$dropoff_location_id = isset($_GET['dropoff_location_id']) ? (int)$_GET['dropoff_location_id'] : 1;
$delivery_method     = isset($_GET['delivery_method']) ? sanitize($_GET['delivery_method']) : 'Hub Self-Pickup';
$delivery_address    = isset($_GET['delivery_address']) ? sanitize($_GET['delivery_address']) : '';
$pickup_datetime     = isset($_GET['pickup_datetime']) ? sanitize($_GET['pickup_datetime']) : date('Y-m-d H:00', strtotime('+1 hour'));
$return_datetime     = isset($_GET['return_datetime']) ? sanitize($_GET['return_datetime']) : date('Y-m-d H:00', strtotime('+3 days'));

$vehicle = null;
if ($vehicle_id > 0) {
    $v_stmt = $db->prepare("
        SELECT v.*, c.name as category_name, l.city_name 
        FROM vehicles v 
        JOIN vehicle_categories c ON v.category_id = c.id 
        JOIN locations l ON v.location_id = l.id 
        WHERE v.id = ?
    ");
    $v_stmt->execute([$vehicle_id]);
    $vehicle = $v_stmt->fetch();
}

$user = get_logged_user();
$days = calculate_days($pickup_datetime, $return_datetime);

$locations = $db->query("SELECT * FROM locations WHERE status = 'active'")->fetchAll();

$is_one_way = ($pickup_location_id !== $dropoff_location_id);
$one_way_fee = $is_one_way ? 500.00 : 0.00;

$page_title = "Reservation Checkout — ASHVKATHA";
$extra_css  = ['booking.css', 'forms.css'];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="section-padding">
  <div class="container">
    
    <?php if ($confirmed_booking): ?>
      <!-- STEP 5: CONFIRMATION VOUCHER TICKET -->
      <div class="voucher-card">
        <div class="voucher-header">
          <div>
            <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Booking Confirmed</span>
            <h2 style="font-size: 1.8rem; margin-top: 0.4rem;"><?php echo htmlspecialchars($confirmed_booking['booking_code']); ?></h2>
          </div>
          <div style="text-align: right;">
            <div style="font-family: var(--font-heading); font-weight: 800; color: var(--primary);">ASHVKATHA</div>
            <span style="font-size: 0.8rem; color: var(--text-muted);">DRIVE YOUR WAY</span>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 200px; gap: 1.5rem; margin-bottom: 2rem;">
          <div>
            <h3 style="font-size: 1.4rem; font-weight: 700;"><?php echo htmlspecialchars($confirmed_booking['brand'] . ' ' . $confirmed_booking['model']); ?></h3>
            <span class="badge badge-secondary" style="margin-top: 0.3rem;"><i class="fa-solid fa-id-card"></i> Reg: <?php echo htmlspecialchars($confirmed_booking['reg_number']); ?></span>

            <div style="margin-top: 1.5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
              <div>
                <span style="font-size: 0.8rem; color: var(--text-muted);">PICKUP LOCATION</span>
                <div style="font-weight: 700;"><?php echo htmlspecialchars($confirmed_booking['pickup_city']); ?> Hub</div>
                <div style="font-size: 0.85rem; color: var(--text-muted);"><?php echo date('d M Y, h:i A', strtotime($confirmed_booking['pickup_datetime'])); ?></div>
              </div>

              <div>
                <span style="font-size: 0.8rem; color: var(--text-muted);">RETURN LOCATION</span>
                <div style="font-weight: 700;"><?php echo htmlspecialchars($confirmed_booking['dropoff_city']); ?> Hub</div>
                <div style="font-size: 0.85rem; color: var(--text-muted);"><?php echo date('d M Y, h:i A', strtotime($confirmed_booking['return_datetime'])); ?></div>
              </div>
            </div>

            <!-- Delivery Logistics Method Display -->
            <div style="margin-top: 1.2rem; padding: 0.85rem 1rem; background: rgba(0, 122, 255, 0.05); border-radius: var(--radius-sm); border: 1px solid rgba(0, 122, 255, 0.2);">
              <div style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 0.4rem;">
                <i class="fa-solid fa-truck-plane"></i> Delivery Logistics Method
              </div>
              <div style="font-weight: 700; color: var(--text-main); margin-top: 0.25rem;">
                <?php echo htmlspecialchars($confirmed_booking['delivery_method'] ?? 'Hub Self-Pickup'); ?>
              </div>
              <?php if (!empty($confirmed_booking['delivery_address'])): ?>
                <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem;">
                  <i class="fa-solid fa-location-dot"></i> Destination: <?php echo htmlspecialchars($confirmed_booking['delivery_address']); ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div>
            <?php 
              $v_img = $confirmed_booking['image_url'];
              $v_src = (strpos($v_img, 'http') === 0) ? $v_img : url($v_img);
            ?>
            <img src="<?php echo htmlspecialchars($v_src); ?>" alt="Vehicle" style="border-radius: var(--radius-md); border: 1px solid var(--surface-border); width: 100%; height: 160px; object-fit: cover;">
          </div>
        </div>

        <div class="price-summary-card">
          <div class="price-row">
            <span>Customer Name</span>
            <span><strong><?php echo htmlspecialchars($confirmed_booking['customer_name']); ?></strong></span>
          </div>
          <div class="price-row">
            <span>Transaction ID</span>
            <span><code><?php echo htmlspecialchars($confirmed_booking['transaction_id']); ?></code></span>
          </div>
          <div class="price-row">
            <span>Payment Method</span>
            <span><?php echo htmlspecialchars($confirmed_booking['payment_method']); ?></span>
          </div>
          <div class="price-row total-row">
            <span>Total Paid</span>
            <span><?php echo format_currency($confirmed_booking['total_amount']); ?></span>
          </div>
        </div>

        <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
          <a href="<?php echo url('/customer/invoices.php?id=' . $confirmed_booking['id']); ?>" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-file-invoice"></i> Download Invoice
          </a>
          <a href="<?php echo url('/customer/dashboard.php'); ?>" class="btn btn-secondary btn-lg">
            <i class="fa-solid fa-user"></i> Go to Dashboard
          </a>
        </div>
      </div>

    <?php elseif ($vehicle): ?>
      <!-- MULTI-STEP CHECKOUT FORM -->
      <div class="section-header">
        <span class="section-subtitle">Secure Reservation</span>
        <h1 class="section-title">Complete Your Rental Booking</h1>
      </div>

      <!-- Step Indicator Bar -->
      <div class="booking-steps-bar">
        <div class="booking-step completed">
          <div class="step-num"><i class="fa-solid fa-check"></i></div>
          <span>1. Vehicle</span>
        </div>
        <div class="booking-step completed">
          <div class="step-num"><i class="fa-solid fa-check"></i></div>
          <span>2. Schedule</span>
        </div>
        <div class="booking-step active">
          <div class="step-num">3</div>
          <span>3. Details & Payment</span>
        </div>
        <div class="booking-step">
          <div class="step-num">4</div>
          <span>4. Confirmation</span>
        </div>
      </div>

      <div class="catalog-layout" style="grid-template-columns: 1fr 420px;">
        <main>
          <?php if (!$user): ?>
            <div class="alert alert-warning" style="margin: 0 0 2rem 0;">
              <div>
                <i class="fa-solid fa-user-lock"></i> <strong>Account Required:</strong> Please sign in or register to complete your reservation.
              </div>
              <div style="margin-top: 0.8rem; display: flex; gap: 0.8rem;">
                <a href="<?php echo url('/login.php'); ?>" class="btn btn-primary btn-sm">Sign In</a>
                <a href="<?php echo url('/register.php'); ?>" class="btn btn-outline btn-sm">Create Account</a>
              </div>
            </div>
          <?php endif; ?>

          <form action="<?php echo url('/booking.php'); ?>" method="POST" class="card">
            <input type="hidden" name="action" value="confirm_booking">
            <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
            <input type="hidden" name="pickup_datetime" value="<?php echo htmlspecialchars($pickup_datetime); ?>">
            <input type="hidden" name="return_datetime" value="<?php echo htmlspecialchars($return_datetime); ?>">

            <!-- Delivery & Handover Logistics Method -->
            <h3 style="font-size: 1.3rem; margin-bottom: 1rem;"><i class="fa-solid fa-truck-plane" style="color: var(--primary);"></i> Delivery & Logistics Method</h3>

            <div class="form-group">
              <label class="form-label">Selected Logistics Option</label>
              <select name="delivery_method" id="booking_delivery_method" class="form-control" onchange="toggleBookingLogistics(this.value)">
                <option value="Road Trailer Truck" <?php echo ($delivery_method === 'Road Trailer Truck') ? 'selected' : ''; ?>>By Road Trailer Truck (Doorstep Flatbed Delivery)</option>
                <option value="Air Transport" <?php echo ($delivery_method === 'Air Transport') ? 'selected' : ''; ?>>Air Transport (Cargo Plane / Nearest Airstrip)</option>
                <option value="Boat / Sea Transport" <?php echo ($delivery_method === 'Boat / Sea Transport') ? 'selected' : ''; ?>>By Boat / Sea Transport (Coastal Port Delivery)</option>
                <option value="Hub Self-Pickup" <?php echo ($delivery_method === 'Hub Self-Pickup') ? 'selected' : ''; ?>>Get from Hub (Choose from 6 Flagship Hubs)</option>
              </select>
            </div>

            <!-- Hub Pickup Option (6 Flagship Hubs) -->
            <div class="form-group" id="booking_hub_group" style="<?php echo ($delivery_method !== 'Hub Self-Pickup') ? 'display: none;' : ''; ?>">
              <label class="form-label"><i class="fa-solid fa-location-dot"></i> Handover Hub (Pickup)</label>
              <select name="pickup_location_id" class="form-control">
                <?php foreach ($locations as $loc): ?>
                  <option value="<?php echo $loc['id']; ?>" <?php echo ($loc['id'] == $pickup_location_id) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($loc['city_name']); ?> Hub (<?php echo htmlspecialchars($loc['location_address']); ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Conditional Custom Delivery Address -->
            <div class="form-group" id="booking_address_group" style="<?php echo ($delivery_method === 'Hub Self-Pickup') ? 'display: none;' : ''; ?>">
              <label class="form-label" id="booking_address_label"><i class="fa-solid fa-map-location-dot"></i> Delivery Address / Port / Airstrip Destination</label>
              <input type="text" name="delivery_address" id="booking_address_input" class="form-control" value="<?php echo htmlspecialchars($delivery_address); ?>" placeholder="Enter delivery doorstep address, airstrip, or port...">
            </div>

            <div class="form-group">
              <label class="form-label"><i class="fa-solid fa-location-arrow"></i> Return Station Hub</label>
              <select name="dropoff_location_id" class="form-control">
                <?php foreach ($locations as $loc): ?>
                  <option value="<?php echo $loc['id']; ?>" <?php echo ($loc['id'] == $dropoff_location_id) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($loc['city_name']); ?> Hub
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <script>
            function toggleBookingLogistics(val) {
              var hubGroup = document.getElementById('booking_hub_group');
              var addrGroup = document.getElementById('booking_address_group');
              var addrLabel = document.getElementById('booking_address_label');
              var addrInput = document.getElementById('booking_address_input');

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

            <h3 style="font-size: 1.3rem; margin: 2rem 0 1.5rem;"><i class="fa-solid fa-id-card" style="color: var(--primary);"></i> Driver & Contact Details</h3>

            <div class="form-grid-2">
              <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required readonly>
              </div>

              <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required readonly>
              </div>
            </div>

            <div class="form-grid-2">
              <div class="form-group">
                <label class="form-label">Contact Phone</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required readonly>
              </div>

              <div class="form-group">
                <label class="form-label">Driving License Number</label>
                <input type="text" class="form-control" placeholder="DL-XXXX-XXXXXXX" value="<?php echo htmlspecialchars($user['driving_license'] ?? 'DL-GJ01-2024991'); ?>" required>
              </div>
            </div>

            <h3 style="font-size: 1.3rem; margin: 2rem 0 1.5rem;"><i class="fa-solid fa-location-dot" style="color: var(--primary);"></i> Payment Selection</h3>

            <div class="form-group">
              <label class="form-label">Payment Option</label>
              <input type="hidden" name="payment_method" value="Pay at Rental Location">
              <div class="form-control" style="display: flex; align-items: center; gap: 0.75rem; background: #f5f5f7; font-weight: 600; padding: 0.9rem 1.2rem; border-color: rgba(0, 122, 255, 0.4);">
                <i class="fa-solid fa-store" style="color: var(--primary); font-size: 1.1rem;"></i>
                <span>Pay at Rental Location (Cash / Card / UPI at Hub Desk upon pickup)</span>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Promo / Coupon Code (Optional)</label>
              <input type="text" name="promo_code" class="form-control" placeholder="Try WELCOME10 or ASHV500">
            </div>

            <!-- Terms & Conditions Agreement -->
            <div class="form-group" style="margin-top: 1.5rem; background: rgba(0, 122, 255, 0.04); border: 1px solid rgba(0, 122, 255, 0.2); border-radius: var(--radius-md); padding: 1.25rem;">
              <label style="display: flex; gap: 0.85rem; align-items: flex-start; cursor: pointer; font-size: 0.88rem; line-height: 1.55; color: #1d1d1f;">
                <input type="checkbox" name="agree_terms" required checked style="margin-top: 0.25rem; width: 18px; height: 18px; accent-color: var(--primary); flex-shrink: 0;">
                <span>
                  <strong>I agree to the Ashvkatha Rental Terms & Cancellation Policy:</strong><br>
                  • <strong>Payment at Hub:</strong> Full rental fare and security deposit will be settled at the rental desk during vehicle inspection.<br>
                  • <strong>Cancellation & Refund:</strong> In the event this booking is cancelled, any refundable deposit or pre-authorisation will be processed and credited within <strong>2 working days</strong>.<br>
                  • <strong>Documents:</strong> Valid Original Driving License and Government ID are mandatory at pickup.<br>
                  • <strong>Fuel Policy:</strong> Same-to-same fuel level return applies across all rental fleets.
                </span>
              </label>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 1.5rem;" <?php echo (!$user) ? 'disabled' : ''; ?>>
              <i class="fa-solid fa-circle-check"></i> Confirm Reservation (Pay at Rental Location)
            </button>
          </form>
        </main>

        <!-- Right Price Summary Card -->
        <aside class="booking-box">
          <h3 style="font-size: 1.2rem; margin-bottom: 1.2rem;"><i class="fa-solid fa-receipt"></i> Rental Breakdown</h3>

          <div style="display: flex; gap: 1rem; align-items: center; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--surface-border);">
            <img src="<?php echo htmlspecialchars($vehicle['image_url']); ?>" alt="Vehicle" style="width: 90px; height: 60px; object-fit: cover; border-radius: var(--radius-sm);">
            <div>
              <div style="font-weight: 700;"><?php echo htmlspecialchars($vehicle['brand'] . ' ' . $vehicle['model']); ?></div>
              <span class="badge badge-secondary"><?php echo htmlspecialchars($vehicle['category_name']); ?></span>
            </div>
          </div>

          <div class="price-summary-card">
            <div class="price-row">
              <span>Rental Duration</span>
              <span><strong><?php echo $days; ?> Day(s)</strong></span>
            </div>
            <div class="price-row">
              <span>Daily Rate</span>
              <span><?php echo format_currency($vehicle['daily_rate']); ?></span>
            </div>
            <div class="price-row">
              <span>Subtotal Base Rent</span>
              <span><?php echo format_currency($vehicle['daily_rate'] * $days); ?></span>
            </div>
            
            <?php if ($is_one_way): ?>
              <div class="price-row">
                <span>One-Way Station Fee</span>
                <span><?php echo format_currency(500.00); ?></span>
              </div>
            <?php endif; ?>

            <div class="price-row">
              <span>GST Tax (18%)</span>
              <span><?php echo format_currency((($vehicle['daily_rate'] * $days) + $one_way_fee) * 0.18); ?></span>
            </div>
            <div class="price-row">
              <span>Refundable Security Deposit</span>
              <span><?php echo format_currency($vehicle['deposit_amount']); ?></span>
            </div>
            <div class="price-row total-row">
              <span>Total Payable</span>
              <span><?php echo format_currency((($vehicle['daily_rate'] * $days) + $one_way_fee) * 1.18 + $vehicle['deposit_amount']); ?></span>
            </div>
          </div>
        </aside>
      </div>

    <?php else: ?>
      <div class="card text-center" style="padding: 4rem 2rem;">
        <h3>No Vehicle Selected for Booking</h3>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Please browse our fleet directory and select a vehicle to reserve.</p>
        <a href="<?php echo url('/vehicles.php'); ?>" class="btn btn-primary"><i class="fa-solid fa-car"></i> Browse Fleet Directory</a>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
