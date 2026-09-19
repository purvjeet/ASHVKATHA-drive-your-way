<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Vehicle Catalog & Discovery Page (vehicles.php)
 * Updated layout: Horizontal Top Filter Bar & 6 Cars Display Grid per Page
 */
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$db = get_db_connection();

// GET Filter Parameters
$search_query  = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category_val  = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$location_id   = isset($_GET['location_id']) ? (int)$_GET['location_id'] : (isset($_GET['location']) ? (int)$_GET['location'] : 0);
$fuel_type     = isset($_GET['fuel_type']) ? sanitize($_GET['fuel_type']) : '';
$transmission  = isset($_GET['transmission']) ? sanitize($_GET['transmission']) : '';
$sort_by       = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'rate_asc';
$page_num      = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page      = 6; // Exactly 6 cars per page as requested

// Base SQL Query condition
$where_sql = " FROM vehicles v 
    JOIN vehicle_categories c ON v.category_id = c.id 
    JOIN locations l ON v.location_id = l.id 
    WHERE 1=1";
$params = [];

if (!empty($search_query)) {
    $where_sql .= " AND (v.brand LIKE ? OR v.model LIKE ? OR v.reg_number LIKE ?)";
    $params[] = "%{$search_query}%";
    $params[] = "%{$search_query}%";
    $params[] = "%{$search_query}%";
}

if (!empty($category_val)) {
    if (is_numeric($category_val)) {
        $where_sql .= " AND c.id = ?";
        $params[] = (int)$category_val;
    } else {
        $where_sql .= " AND c.slug = ?";
        $params[] = $category_val;
    }
}

if ($location_id > 0) {
    $where_sql .= " AND v.location_id = ?";
    $params[] = $location_id;
}

if (!empty($fuel_type)) {
    $where_sql .= " AND v.fuel_type = ?";
    $params[] = $fuel_type;
}

if (!empty($transmission)) {
    $where_sql .= " AND v.transmission = ?";
    $params[] = $transmission;
}

// 1. Count total matching vehicles
$count_stmt = $db->prepare("SELECT COUNT(*)" . $where_sql);
$count_stmt->execute($params);
$total_records = (int)$count_stmt->fetchColumn();
$total_pages   = max(1, ceil($total_records / $per_page));

if ($page_num > $total_pages) {
    $page_num = $total_pages;
}
$offset = ($page_num - 1) * $per_page;

// 2. Build full vehicle query
$sql = "SELECT v.*, c.name as category_name, c.slug as category_slug, l.city_name" . $where_sql;

switch ($sort_by) {
    case 'rate_desc':
        $sql .= " ORDER BY v.daily_rate DESC";
        break;
    case 'year_desc':
        $sql .= " ORDER BY v.year DESC";
        break;
    case 'rate_asc':
    default:
        $sql .= " ORDER BY v.daily_rate ASC";
        break;
}

$sql .= " LIMIT $per_page OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

// Fetch filter dropdown options
$categories = $db->query("SELECT * FROM vehicle_categories ORDER BY id ASC")->fetchAll();
$locations  = $db->query("SELECT * FROM locations WHERE status = 'active' ORDER BY city_name ASC")->fetchAll();

// Helper function to build page link with preserved GET query string
function get_page_url($page) {
    $params = $_GET;
    $params['page'] = $page;
    return url('/vehicles.php?' . http_build_query($params));
}

$page_title = "Browse Fleet Directory — ASHVKATHA";
$extra_css  = ['vehicles.css', 'forms.css'];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="section-padding" style="padding-top: 4.5rem;">
  <div class="container">
    <div class="browse-header-flex">
      <div>
        <span class="section-subtitle">FLEET DIRECTORY</span>
        <h1 class="section-title" style="margin-bottom: 0.4rem;">Browse Our Vehicles</h1>
        <p class="section-desc" style="margin: 0;">Verified luxury sedans, 4x4 SUVs, performance supercars, and electric vehicles.</p>
      </div>
      <div class="catalog-count-pill">
        Showing <strong><?php echo min($per_page, count($vehicles)); ?></strong> of <strong><?php echo $total_records; ?></strong> cars
      </div>
    </div>

    <!-- Horizontal Top Filter Toolbar -->
    <div class="horizontal-filter-toolbar">
      <form action="<?php echo url('/vehicles.php'); ?>" method="GET" class="filter-form-horizontal">
        <input type="hidden" name="page" value="1">
        
        <div class="filter-item filter-search">
          <i class="fa-solid fa-magnifying-glass filter-icon"></i>
          <input type="text" name="search" class="form-control filter-input" placeholder="Search brand or model..." value="<?php echo htmlspecialchars($search_query); ?>">
        </div>

        <div class="filter-item">
          <select name="category" class="form-control filter-select" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo htmlspecialchars($cat['slug']); ?>" <?php echo ($category_val == $cat['slug'] || $category_val == $cat['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-item">
          <select name="location_id" class="form-control filter-select" onchange="this.form.submit()">
            <option value="0">All Hubs</option>
            <?php foreach ($locations as $loc): ?>
              <option value="<?php echo $loc['id']; ?>" <?php echo ($location_id == $loc['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($loc['city_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="filter-item">
          <select name="fuel_type" class="form-control filter-select" onchange="this.form.submit()">
            <option value="">Fuel Type</option>
            <option value="Petrol" <?php echo ($fuel_type == 'Petrol') ? 'selected' : ''; ?>>Petrol</option>
            <option value="Diesel" <?php echo ($fuel_type == 'Diesel') ? 'selected' : ''; ?>>Diesel</option>
            <option value="Electric" <?php echo ($fuel_type == 'Electric') ? 'selected' : ''; ?>>Electric</option>
            <option value="Hybrid" <?php echo ($fuel_type == 'Hybrid') ? 'selected' : ''; ?>>Hybrid</option>
          </select>
        </div>

        <div class="filter-item">
          <select name="transmission" class="form-control filter-select" onchange="this.form.submit()">
            <option value="">Transmission</option>
            <option value="Automatic" <?php echo ($transmission == 'Automatic') ? 'selected' : ''; ?>>Automatic</option>
            <option value="Manual" <?php echo ($transmission == 'Manual') ? 'selected' : ''; ?>>Manual</option>
          </select>
        </div>

        <div class="filter-item">
          <select name="sort" class="form-control filter-select" onchange="this.form.submit()">
            <option value="rate_asc" <?php echo ($sort_by == 'rate_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
            <option value="rate_desc" <?php echo ($sort_by == 'rate_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
            <option value="year_desc" <?php echo ($sort_by == 'year_desc') ? 'selected' : ''; ?>>Model Year: Newest</option>
          </select>
        </div>

        <div class="filter-actions">
          <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Apply</button>
          <a href="<?php echo url('/vehicles.php'); ?>" class="btn btn-danger-outline btn-sm"><i class="fa-solid fa-rotate-left"></i> Reset</a>
        </div>
      </form>
    </div>

    <!-- Vehicle Results Section (6 Cars Grid) -->
    <main class="catalog-results-full">
      <?php if (empty($vehicles)): ?>
        <div class="card text-center" style="padding: 4rem 2rem; margin-top: 2rem;">
          <i class="fa-solid fa-car-side" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 1rem;"></i>
          <h3>No Vehicles Match Your Filter Criteria</h3>
          <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Adjust your filter selections or reset all filters to view our available fleet.</p>
          <a href="<?php echo url('/vehicles.php'); ?>" class="btn btn-primary"><i class="fa-solid fa-rotate-left"></i> Reset All Filters</a>
        </div>
      <?php else: ?>
        <!-- 3x2 Grid Display (6 vehicles max per page) -->
        <div class="vehicles-grid-6">
          <?php foreach ($vehicles as $veh): ?>
            <div class="vehicle-card">
              <div class="vehicle-thumb">
                <?php 
                  $v_img = (strpos($veh['image_url'], 'http') === 0) ? $veh['image_url'] : url($veh['image_url']);
                ?>
                <img src="<?php echo htmlspecialchars($v_img); ?>" alt="<?php echo htmlspecialchars($veh['brand'] . ' ' . $veh['model']); ?>" loading="lazy">
                <div class="vehicle-status-badge">
                  <?php echo get_status_badge($veh['status']); ?>
                </div>
              </div>

              <div class="vehicle-details-body">
                <h3 class="vehicle-title"><?php echo htmlspecialchars($veh['brand'] . ' ' . $veh['model']); ?></h3>
                <div style="display: flex; gap: 0.5rem; align-items: center; margin-bottom: 0.8rem;">
                  <span class="badge badge-secondary"><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($veh['category_name']); ?></span>
                  <span class="badge badge-secondary"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($veh['city_name']); ?></span>
                </div>

                <div class="vehicle-specs-tags">
                  <span class="spec-tag"><i class="fa-solid fa-gas-pump"></i> <?php echo htmlspecialchars($veh['fuel_type']); ?></span>
                  <span class="spec-tag"><i class="fa-solid fa-gear"></i> <?php echo htmlspecialchars($veh['transmission']); ?></span>
                  <span class="spec-tag"><i class="fa-solid fa-users"></i> <?php echo htmlspecialchars($veh['seating_capacity']); ?> Seats</span>
                  <span class="spec-tag"><i class="fa-solid fa-calendar"></i> <?php echo htmlspecialchars($veh['year']); ?></span>
                </div>

                <div class="vehicle-pricing-footer">
                  <div class="price-box">
                    <span class="price-amount"><?php echo format_currency($veh['daily_rate']); ?></span>
                    <span class="price-unit">per day</span>
                  </div>
                  <a href="<?php echo url('/vehicle-details.php?id=' . $veh['id']); ?>" class="btn btn-primary btn-sm">
                    Book <i class="fa-solid fa-chevron-right"></i>
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Pagination Controls (6 cars per page) -->
        <?php if ($total_pages > 1): ?>
          <div class="pagination-wrapper">
            <div class="pagination-container">
              <!-- Previous Page -->
              <?php if ($page_num > 1): ?>
                <a href="<?php echo get_page_url($page_num - 1); ?>" class="pagination-btn"><i class="fa-solid fa-chevron-left"></i> Prev</a>
              <?php else: ?>
                <span class="pagination-btn disabled"><i class="fa-solid fa-chevron-left"></i> Prev</span>
              <?php endif; ?>

              <!-- Page Numbers -->
              <div class="pagination-numbers">
                <?php 
                $start_p = max(1, $page_num - 2);
                $end_p   = min($total_pages, $page_num + 2);
                if ($start_p > 1): ?>
                  <a href="<?php echo get_page_url(1); ?>" class="page-num">1</a>
                  <?php if ($start_p > 2): ?><span class="page-dots">...</span><?php endif; ?>
                <?php endif; ?>

                <?php for ($p = $start_p; $p <= $end_p; $p++): ?>
                  <a href="<?php echo get_page_url($p); ?>" class="page-num <?php echo ($p == $page_num) ? 'active' : ''; ?>">
                    <?php echo $p; ?>
                  </a>
                <?php endfor; ?>

                <?php if ($end_p < $total_pages): ?>
                  <?php if ($end_p < $total_pages - 1): ?><span class="page-dots">...</span><?php endif; ?>
                  <a href="<?php echo get_page_url($total_pages); ?>" class="page-num"><?php echo $total_pages; ?></a>
                <?php endif; ?>
              </div>

              <!-- Next Page -->
              <?php if ($page_num < $total_pages): ?>
                <a href="<?php echo get_page_url($page_num + 1); ?>" class="pagination-btn">Next <i class="fa-solid fa-chevron-right"></i></a>
              <?php else: ?>
                <span class="pagination-btn disabled">Next <i class="fa-solid fa-chevron-right"></i></span>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

      <?php endif; ?>
    </main>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
