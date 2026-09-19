<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Main Homepage Page (index.php)
 */
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$db = get_db_connection();

// Fetch locations for smart search dropdown
$locations = $db->query("SELECT * FROM locations WHERE status = 'active' ORDER BY city_name ASC")->fetchAll();

// Fetch vehicle categories
$categories = $db->query("SELECT * FROM vehicle_categories ORDER BY id ASC")->fetchAll();

// Fetch featured vehicles (limit 6)
$featured_vehicles = $db->query("
    SELECT v.*, c.name as category_name, l.city_name 
    FROM vehicles v 
    JOIN vehicle_categories c ON v.category_id = c.id 
    JOIN locations l ON v.location_id = l.id 
    ORDER BY v.daily_rate DESC LIMIT 6
")->fetchAll();

// Dynamic Statistics from DB
$stat_vehicles = $db->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$stat_locations = $db->query("SELECT COUNT(*) FROM locations WHERE status = 'active'")->fetchColumn();
$stat_bookings = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$stat_customers = $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();

$page_title = "ASHVKATHA — Smart Vehicle Rental & Fleet Management | Drive Your Way";
$extra_css = ['home.css'];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<!-- Hero Section -->
<section class="hero-section">
  <div class="hero-content">
    <h1 class="hero-title">Your Journey. Your Vehicle. <span>Your Way.</span></h1>
    <p class="hero-subtitle">
      Experience the freedom of premium luxury sedans, 4x4 offroad SUVs, high-performance speedsters, and eco-friendly electric vehicles. Book in seconds with zero hidden charges.
    </p>
    <div class="hero-buttons">
      <a href="<?php echo url('/vehicles.php'); ?>" class="btn btn-primary btn-lg">
        <i class="fa-solid fa-car"></i> Browse Fleet
      </a>
      <a href="#locations" class="btn btn-outline btn-lg">
        <i class="fa-solid fa-location-dot"></i> Explore Locations
      </a>
    </div>
  </div>
</section>

<!-- Smart Rental Search / Booking Bar -->
<div class="search-widget-container">
  <div class="search-widget">
    <form action="<?php echo url('/vehicles.php'); ?>" method="GET" class="search-form">
      <div class="search-field">
        <label><i class="fa-solid fa-location-dot"></i> Pickup Location</label>
        <select name="location_id" class="form-control" required>
          <option value="">Select Pickup Location</option>
          <?php foreach ($locations as $loc): ?>
            <option value="<?php echo $loc['id']; ?>"><?php echo htmlspecialchars($loc['city_name']); ?> (<?php echo htmlspecialchars($loc['location_address']); ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="search-field">
        <label><i class="fa-solid fa-calendar-day"></i> Pickup Date</label>
        <input type="date" name="pickup_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
      </div>

      <div class="search-field">
        <label><i class="fa-solid fa-calendar-check"></i> Return Date</label>
        <input type="date" name="return_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" required>
      </div>

      <div class="search-field">
        <label><i class="fa-solid fa-car-side"></i> Vehicle Type</label>
        <select name="category" class="form-control">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo htmlspecialchars($cat['slug']); ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="search-field search-action">
        <label class="search-action-label">&nbsp;</label>
        <button type="submit" class="btn btn-primary btn-search-submit">
          <i class="fa-solid fa-magnifying-glass"></i> Search Vehicles
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Vehicle Categories -->
<section class="section-padding">
  <div class="container">
    <div class="section-header">
      <span class="section-subtitle">Curated Fleet Categories</span>
      <h2 class="section-title">Explore Vehicles By Category</h2>
      <p class="section-desc">From executive business sedans to roaring supercars, select your preferred driving experience.</p>
    </div>

    <div class="category-grid">
      <?php foreach ($categories as $cat): ?>
        <a href="<?php echo url('/vehicles.php?category=' . $cat['slug']); ?>" class="category-card">
          <div class="category-icon">
            <i class="fa-solid fa-<?php echo htmlspecialchars($cat['icon']); ?>"></i>
          </div>
          <h3 class="category-name"><?php echo htmlspecialchars($cat['name']); ?></h3>
          <span class="badge badge-secondary">Explore Fleet <i class="fa-solid fa-arrow-right"></i></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Featured Vehicles Grid -->
<section class="section-padding" style="background-color: var(--surface);">
  <div class="container">
    <div class="section-header">
      <span class="section-subtitle">Handpicked Selections</span>
      <h2 class="section-title">Featured Fleet Vehicles</h2>
      <p class="section-desc">Discover our top-rated luxury and performance vehicles available for immediate reservation.</p>
    </div>

    <div class="vehicles-grid">
      <?php foreach ($featured_vehicles as $veh): ?>
        <div class="vehicle-card">
          <div class="vehicle-thumb">
            <?php $v_img = (strpos($veh['image_url'], 'http') === 0) ? $veh['image_url'] : url($veh['image_url']); ?>
            <img src="<?php echo htmlspecialchars($v_img); ?>" alt="<?php echo htmlspecialchars($veh['brand'] . ' ' . $veh['model']); ?>">
            <div class="vehicle-status-badge">
              <?php echo get_status_badge($veh['status']); ?>
            </div>
          </div>

          <div class="vehicle-details-body">
            <h3 class="vehicle-title"><?php echo htmlspecialchars($veh['brand'] . ' ' . $veh['model']); ?></h3>
            <span class="badge badge-secondary"><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($veh['category_name']); ?></span>

            <div class="vehicle-specs-tags">
              <span class="spec-tag"><i class="fa-solid fa-gas-pump"></i> <?php echo htmlspecialchars($veh['fuel_type']); ?></span>
              <span class="spec-tag"><i class="fa-solid fa-gear"></i> <?php echo htmlspecialchars($veh['transmission']); ?></span>
              <span class="spec-tag"><i class="fa-solid fa-users"></i> <?php echo htmlspecialchars($veh['seating_capacity']); ?> Seats</span>
              <span class="spec-tag"><i class="fa-solid fa-gauge-high"></i> <?php echo htmlspecialchars($veh['mileage']); ?></span>
            </div>

            <div class="vehicle-pricing-footer">
              <div class="price-box">
                <span class="price-amount"><?php echo format_currency($veh['daily_rate']); ?></span>
                <span class="price-unit">per day</span>
              </div>
              <a href="<?php echo url('/vehicle-details.php?id=' . $veh['id']); ?>" class="btn btn-primary">
                View & Book <i class="fa-solid fa-chevron-right"></i>
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="text-center" style="margin-top: 3.5rem;">
      <a href="<?php echo url('/vehicles.php'); ?>" class="btn btn-outline btn-lg">
        <i class="fa-solid fa-car-side"></i> View Entire Fleet Directory
      </a>
    </div>
  </div>
</section>

<!-- Why Choose Ashvkatha -->
<section class="section-padding">
  <div class="container">
    <div class="section-header">
      <span class="section-subtitle">Why Drive With Us</span>
      <h2 class="section-title">The Ashvkatha Distinction</h2>
      <p class="section-desc">Unmatched quality, transparent pricing, and 24/7 VIP assistance across Gujarat.</p>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon-wrapper"><i class="fa-solid fa-certificate"></i></div>
        <h3>100% Verified Fleet</h3>
        <p>Every vehicle undergoes a 50-point diagnostic inspection and complete sterilization before delivery.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-wrapper"><i class="fa-solid fa-truck-ramp-box"></i></div>
        <h3>Doorstep White-Glove Delivery</h3>
        <p>Get your reserved car or bike delivered right to your airport terminal, office, or residential address.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-wrapper"><i class="fa-solid fa-receipt"></i></div>
        <h3>Zero Hidden Charges</h3>
        <p>Transparent pricing with insurance, roadside assistance, and standard taxes included upfront.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-wrapper"><i class="fa-solid fa-headset"></i></div>
        <h3>24/7 Concierge Support</h3>
        <p>Our dedicated support team is available round-the-clock for navigation, roadside emergencies, or extensions.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-wrapper"><i class="fa-solid fa-calendar-xmark"></i></div>
        <h3>Flexible Cancellation</h3>
        <p>Plans change. Enjoy full refunds on cancellations made up to 24 hours prior to pickup time.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon-wrapper"><i class="fa-solid fa-shield-halved"></i></div>
        <h3>Comprehensive Insurance</h3>
        <p>Drive with peace of mind. All vehicles are covered with loss damage waivers and third-party liability insurance.</p>
      </div>
    </div>
  </div>
</section>

<!-- How It Works Section -->
<section class="section-padding" style="background-color: var(--surface);">
  <div class="container">
    <div class="section-header">
      <span class="section-subtitle">Simple 4-Step Process</span>
      <h2 class="section-title">How To Rent Your Vehicle</h2>
      <p class="section-desc">Book your dream ride in less than 2 minutes through our server-rendered platform.</p>
    </div>

    <div class="steps-grid">
      <div class="step-card">
        <div class="step-number">1</div>
        <h3>Choose Vehicle</h3>
        <p>Select from sedans, SUVs, supercars, or electric performance vehicles.</p>
      </div>

      <div class="step-card">
        <div class="step-number">2</div>
        <h3>Select Schedule</h3>
        <p>Pick your preferred pickup location, pickup date, and return schedule.</p>
      </div>

      <div class="step-card">
        <div class="step-number">3</div>
        <h3>Confirm Booking</h3>
        <p>Enter driver details and process demo payment securely.</p>
      </div>

      <div class="step-card">
        <div class="step-number">4</div>
        <h3>Drive Away!</h3>
        <p>Collect your keys at our location hub or receive doorstep delivery.</p>
      </div>
    </div>
  </div>
</section>

<!-- Locations Section -->
<section id="locations" class="section-padding">
  <div class="container">
    <div class="section-header">
      <span class="section-subtitle">Operating Hubs</span>
      <h2 class="section-title">Ashvkatha Rental Locations</h2>
      <p class="section-desc">Pickup and drop-off available across major commercial and travel hubs in Gujarat.</p>
    </div>

    <div class="vehicles-grid">
      <?php foreach ($locations as $loc): ?>
        <div class="card" style="display: flex; gap: 1rem; align-items: center;">
          <div class="feature-icon-wrapper" style="margin-bottom: 0;"><i class="fa-solid fa-building-flag"></i></div>
          <div>
            <h3 style="font-size: 1.3rem; font-weight: 700;"><?php echo htmlspecialchars($loc['city_name']); ?> Hub</h3>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.2rem;"><?php echo htmlspecialchars($loc['location_address']); ?></p>
            <span class="badge badge-success" style="margin-top: 0.5rem;"><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($loc['contact_phone']); ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Real-Time Statistics Strip -->
<div class="stats-strip">
  <div class="container">
    <div class="stats-grid">
      <div class="stat-item">
        <div class="stat-number"><?php echo $stat_vehicles; ?>+</div>
        <div class="stat-label">Active Fleet Vehicles</div>
      </div>
      <div class="stat-item">
        <div class="stat-number"><?php echo $stat_locations; ?></div>
        <div class="stat-label">Rental Hub Locations</div>
      </div>
      <div class="stat-item">
        <div class="stat-number"><?php echo number_format($stat_bookings); ?>+</div>
        <div class="stat-label">Successful Bookings</div>
      </div>
      <div class="stat-item">
        <div class="stat-number"><?php echo number_format($stat_customers); ?>+</div>
        <div class="stat-label">Verified Customers</div>
      </div>
    </div>
  </div>
</div>

<!-- Final CTA Section -->
<section class="section-padding text-center" style="background: linear-gradient(180deg, var(--surface) 0%, var(--background) 100%);">
  <div class="container">
    <h2 class="section-title" style="font-size: 3rem; margin-bottom: 1rem;">Ready to Hit the Road?</h2>
    <p class="section-desc" style="margin-bottom: 2rem;">Experience superior luxury, reliability, and service with Ashvkatha.</p>
    <a href="<?php echo url('/vehicles.php'); ?>" class="btn btn-primary btn-lg">
      <i class="fa-solid fa-key"></i> Reserve Your Vehicle Now
    </a>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
