<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Underground Night Market (Non-Road-Legal Vehicle Sales) (night-market.php)
 * Deep Night Blue Theme, Bento Grid UI, Rich Dimensions & Filters
 */
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$db = get_db_connection();

// GET Filter Parameters
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$brand_filter = isset($_GET['brand']) ? sanitize($_GET['brand']) : '';
$fuel_filter  = isset($_GET['fuel_type']) ? sanitize($_GET['fuel_type']) : '';
$price_sort   = isset($_GET['sort']) ? sanitize($_GET['sort']) : '';

// Build Query - Includes active and allocated builds
$sql = "SELECT * FROM night_market WHERE status IN ('available', 'sold_out')";
$params = [];

if (!empty($search_query)) {
    $sql .= " AND (vehicle_name LIKE ? OR subtitle LIKE ? OR features LIKE ? OR specs_json LIKE ?)";
    $params[] = "%{$search_query}%";
    $params[] = "%{$search_query}%";
    $params[] = "%{$search_query}%";
    $params[] = "%{$search_query}%";
}

if (!empty($brand_filter)) {
    $sql .= " AND brand = ?";
    $params[] = $brand_filter;
}

if (!empty($fuel_filter)) {
    $sql .= " AND fuel_type = ?";
    $params[] = $fuel_filter;
}

// Sorting logic - Available builds always display first
switch ($price_sort) {
    case 'price_asc':
        $sql .= " ORDER BY (status = 'sold_out') ASC, daily_rate ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY (status = 'sold_out') ASC, daily_rate DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY (status = 'sold_out') ASC, vehicle_name ASC";
        break;
    default:
        $sql .= " ORDER BY (status = 'sold_out') ASC, is_featured DESC, id ASC";
        break;
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$drops = $stmt->fetchAll();

// Distinct brands and fuel types for filter dropdowns
$available_brands = $db->query("SELECT DISTINCT brand FROM night_market WHERE brand != '' ORDER BY brand ASC")->fetchAll(PDO::FETCH_COLUMN);
$available_fuels  = $db->query("SELECT DISTINCT fuel_type FROM night_market WHERE fuel_type != '' ORDER BY fuel_type ASC")->fetchAll(PDO::FETCH_COLUMN);

$page_title = "Night Market — Non-Road-Legal Vehicle Sales";
$body_class = 'night-market-body';
$extra_css  = ['night-market.css'];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<!-- Night Blue Hero Section -->
<section class="nm-hero-section">
  <div class="container">
    <div class="nm-disclaimer-banner">
      <i class="fa-solid fa-shield-halved"></i>
      <span>Exclusive Acquisition — Non-Road-Legal Performance Builds For Sale</span>
    </div>
    <h1 class="nm-title">NIGHT <span>MARKET</span></h1>
    <p class="nm-subtitle">
      Direct vehicle sales for closed-course track machines, private collector builds, and extreme custom non-road-legal performance vehicles. Available for outright purchase with global enclosed delivery.
    </p>
  </div>
</section>

<!-- Night Market Advanced Filter Toolbar -->
<section class="section-padding" style="padding-top: 2.5rem;">
  <div class="container">
    <div class="nm-filter-toolbar">
      <form action="<?php echo url('/night-market.php'); ?>" method="GET" class="nm-filter-form">
        <!-- Text Search -->
        <div class="nm-filter-col nm-filter-col-search">
          <input type="text" name="search" class="form-control nm-filter-input" placeholder="Search vehicle name or specs..." value="<?php echo htmlspecialchars($search_query); ?>">
        </div>

        <!-- Price Sort -->
        <div class="nm-filter-col">
          <select name="sort" class="form-control nm-filter-select" onchange="this.form.submit()">
            <option value="">Sort By Price</option>
            <option value="price_asc" <?php echo ($price_sort === 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
            <option value="price_desc" <?php echo ($price_sort === 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
            <option value="name_asc" <?php echo ($price_sort === 'name_asc') ? 'selected' : ''; ?>>Alphabetical: A to Z</option>
          </select>
        </div>

        <!-- Brand Filter -->
        <div class="nm-filter-col">
          <select name="brand" class="form-control nm-filter-select" onchange="this.form.submit()">
            <option value="">All Brands</option>
            <?php foreach ($available_brands as $b): ?>
              <option value="<?php echo htmlspecialchars($b); ?>" <?php echo ($brand_filter === $b) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($b); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Fuel Type Filter -->
        <div class="nm-filter-col">
          <select name="fuel_type" class="form-control nm-filter-select" onchange="this.form.submit()">
            <option value="">All Fuel Types</option>
            <?php foreach ($available_fuels as $f): ?>
              <option value="<?php echo htmlspecialchars($f); ?>" <?php echo ($fuel_filter === $f) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($f); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Action Buttons -->
        <div class="nm-filter-actions">
          <button type="submit" class="btn-nm-apply">
            <i class="fa-solid fa-filter"></i> Apply
          </button>
          <a href="<?php echo url('/night-market.php'); ?>" class="btn-nm-reset">
            <i class="fa-solid fa-rotate-left"></i> Reset
          </a>
        </div>
      </form>
    </div>

      <!-- Bento Grid Display (In-Depth Vehicle Specifications) -->
      <?php if (empty($drops)): ?>
        <div class="nm-card text-center" style="padding: 4rem 2rem; margin-top: 1rem;">
          <i class="fa-solid fa-car-side" style="font-size: 3rem; color: #38bdf8; margin-bottom: 1rem;"></i>
          <h3 style="color: #ffffff;">No Vehicles Match Selected Criteria</h3>
          <p style="color: #94a3b8; margin-bottom: 1.5rem;">Try adjusting your brand, fuel, or price filters to view vehicles.</p>
          <a href="<?php echo url('/night-market.php'); ?>" class="btn btn-primary" style="background: #0284c7; border-color: #0284c7;">
            <i class="fa-solid fa-rotate-left"></i> Show All 20 Vehicles
          </a>
        </div>
      <?php else: ?>
        <div class="nm-bento-grid">
          <?php 
          foreach ($drops as $car): 
            $specs = json_decode($car['specs_json'], true) ?: [];
            $is_sold = ($car['status'] === 'sold_out');
          ?>
            <div class="nm-card" <?php echo $is_sold ? 'style="opacity: 0.92;"' : ''; ?>>
              <a href="<?php echo url('/night-market-details.php?id=' . $car['id']); ?>" class="nm-card-thumb" style="display: block; text-decoration: none; position: relative;">
                <?php if ($is_sold): ?>
                  <div style="position: absolute; top: 12px; left: 12px; z-index: 5; background: rgba(239, 68, 68, 0.95); color: #ffffff; font-size: 0.72rem; font-weight: 800; padding: 4px 10px; border-radius: 6px; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.4rem; box-shadow: 0 4px 12px rgba(239,68,68,0.4);">
                    <i class="fa-solid fa-lock"></i> ALLOCATED / SOLD OUT
                  </div>
                <?php endif; ?>
                <?php 
                  $img_src = (strpos($car['image_url'], 'http') === 0) ? $car['image_url'] : url($car['image_url']);
                ?>
                <img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($car['vehicle_name']); ?>" loading="lazy">
              </a>

              <div class="nm-card-body">
                <div class="nm-header-row">
                  <div>
                    <h3 class="nm-car-name">
                      <a href="<?php echo url('/night-market-details.php?id=' . $car['id']); ?>" style="color: #ffffff; text-decoration: none;">
                        <?php echo htmlspecialchars($car['vehicle_name']); ?>
                      </a>
                    </h3>
                    <div class="nm-car-subtitle"><?php echo htmlspecialchars($car['subtitle']); ?></div>
                  </div>
                  <div class="nm-tag-brand">
                    <?php echo htmlspecialchars($car['brand'] ?: 'Custom Build'); ?>
                  </div>
                </div>

                <!-- In-Depth Dimensions & Engineering Specifications Box -->
                <div class="nm-in-depth-box">
                  <div class="nm-dim-grid">
                    <div class="nm-dim-item">
                      <span class="nm-dim-label"><i class="fa-solid fa-arrows-up-down"></i> Vehicle Height</span>
                      <span class="nm-dim-value"><?php echo htmlspecialchars($specs['height'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="nm-dim-item">
                      <span class="nm-dim-label"><i class="fa-solid fa-weight-hanging"></i> Curb Weight</span>
                      <span class="nm-dim-value"><?php echo htmlspecialchars($specs['weight'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="nm-dim-item">
                      <span class="nm-dim-label"><i class="fa-solid fa-road"></i> Ground Clearance</span>
                      <span class="nm-dim-value"><?php echo htmlspecialchars($specs['ground_clearance'] ?? 'N/A'); ?></span>
                    </div>
                  </div>

                  <div class="nm-specs-pills-row">
                    <?php if (!empty($specs['engine'])): ?>
                      <span class="nm-spec-pill"><i class="fa-solid fa-fire"></i> <?php echo htmlspecialchars($specs['engine']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($specs['power'])): ?>
                      <span class="nm-spec-pill"><i class="fa-solid fa-gauge-high"></i> <?php echo htmlspecialchars($specs['power']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($specs['torque'])): ?>
                      <span class="nm-spec-pill"><i class="fa-solid fa-bolt"></i> <?php echo htmlspecialchars($specs['torque']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($specs['acceleration'])): ?>
                      <span class="nm-spec-pill"><i class="fa-solid fa-stopwatch"></i> <?php echo htmlspecialchars($specs['acceleration']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($specs['top_speed'])): ?>
                      <span class="nm-spec-pill"><i class="fa-solid fa-gauge"></i> <?php echo htmlspecialchars($specs['top_speed']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($specs['transmission'])): ?>
                      <span class="nm-spec-pill"><i class="fa-solid fa-gear"></i> <?php echo htmlspecialchars($specs['transmission']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($specs['wheelbase'])): ?>
                      <span class="nm-spec-pill"><i class="fa-solid fa-ruler-horizontal"></i> Wheelbase: <?php echo htmlspecialchars($specs['wheelbase']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($car['fuel_type'])): ?>
                      <span class="nm-spec-pill"><i class="fa-solid fa-gas-pump"></i> <?php echo htmlspecialchars($car['fuel_type']); ?></span>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="nm-features-desc" style="margin-top: 1rem;">
                  <div style="font-size: 0.72rem; text-transform: uppercase; font-weight: 700; color: #a855f7; letter-spacing: 0.05em; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
                    <i class="fa-solid fa-screwdriver-wrench"></i> Custom Upgrades & Build Features
                  </div>
                  <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                    <?php 
                      $f_items = preg_split('/[,;\.\n]+/', $car['features']);
                      $count = 0;
                      foreach ($f_items as $fi):
                        $clean_f = trim($fi);
                        if (empty($clean_f) || strlen($clean_f) < 3) continue;
                        $count++;
                        if ($count > 4) break; // Display top 4 upgrades cleanly
                        
                        $f_lower = strtolower($clean_f);
                        $f_icon = 'fa-solid fa-wrench';
                        if (strpos($f_lower, 'tune') !== false || strpos($f_lower, 'ecu') !== false) $f_icon = 'fa-solid fa-microchip';
                        elseif (strpos($f_lower, 'aero') !== false || strpos($f_lower, 'carbon') !== false) $f_icon = 'fa-solid fa-wind';
                        elseif (strpos($f_lower, 'exhaust') !== false || strpos($f_lower, 'akrapovic') !== false) $f_icon = 'fa-solid fa-fire';
                        elseif (strpos($f_lower, 'rim') !== false || strpos($f_lower, 'wheel') !== false || strpos($f_lower, 'monoblock') !== false) $f_icon = 'fa-solid fa-circle-dot';
                        elseif (strpos($f_lower, 'cage') !== false || strpos($f_lower, 'roll') !== false) $f_icon = 'fa-solid fa-shield-halved';
                    ?>
                      <span class="nm-spec-pill" style="background: rgba(15, 23, 42, 0.85); border-color: rgba(56, 189, 248, 0.35); color: #f1f5f9; font-weight: 600;">
                        <i class="<?php echo $f_icon; ?>" style="color: #38bdf8;"></i> <?php echo htmlspecialchars($clean_f); ?>
                      </span>
                    <?php endforeach; ?>
                  </div>
                </div>

                <!-- Footer with Price and Red BUY NOW button -->
                <div class="nm-card-footer">
                  <div>
                    <div class="nm-price-amount"><?php echo format_currency($car['daily_rate']); ?></div>
                  </div>
                  <?php if ($is_sold): ?>
                    <a href="<?php echo url('/night-market-details.php?id=' . $car['id']); ?>" class="btn-nm-action" style="background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.4); color: #f87171;">
                      <i class="fa-solid fa-lock"></i> ALLOCATED
                    </a>
                  <?php else: ?>
                    <a href="<?php echo url('/night-market-details.php?id=' . $car['id']); ?>" class="btn-nm-action">
                      BUY NOW <i class="fa-solid fa-arrow-right"></i>
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <?php include __DIR__ . '/includes/footer.php'; ?>

