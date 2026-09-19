<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Admin Control Panel: Underground Night Market Exclusive Inventory Management (admin/night-market-vehicles.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$db = get_db_connection();

// Handle Status Toggle (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $vehicle_id = (int)$_POST['vehicle_id'];
    $new_status = sanitize($_POST['status'] ?? 'available');

    if (in_array($new_status, ['available', 'coming_soon', 'sold_out'])) {
        $stmt = $db->prepare("UPDATE night_market SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $vehicle_id]);
        set_flash_message('success', "Night Market vehicle #{$vehicle_id} status updated to " . strtoupper(str_replace('_', ' ', $new_status)) . ".");
    }
    redirect('/admin/night-market-vehicles.php');
}

// Filters
$category_filter = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$status_filter   = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search_query    = isset($_GET['q']) ? sanitize($_GET['q']) : '';

$sql = "SELECT * FROM night_market WHERE 1=1";
$params = [];

if (!empty($category_filter)) {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}

if (!empty($search_query)) {
    $sql .= " AND (vehicle_name LIKE ? OR brand LIKE ? OR subtitle LIKE ?)";
    $term = "%{$search_query}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY id ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$vehicles = $stmt->fetchAll();

// Metrics
$total_nm_count    = $db->query("SELECT COUNT(*) FROM night_market")->fetchColumn();
$available_count   = $db->query("SELECT COUNT(*) FROM night_market WHERE status = 'available'")->fetchColumn();
$sold_out_count    = $db->query("SELECT COUNT(*) FROM night_market WHERE status = 'sold_out'")->fetchColumn();
$total_acq_value   = $db->query("SELECT SUM(daily_rate) FROM night_market")->fetchColumn() ?: 0.00;

// Distinct categories for filter
$categories = $db->query("SELECT DISTINCT category FROM night_market WHERE category IS NOT NULL AND category != '' ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);

$page_title = "Exclusive Inventory — Underground Night Market Admin";
$extra_css  = ['dashboard.css', 'admin.css', 'forms.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div>
        <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(168, 85, 247, 0.12); border: 1px solid rgba(168, 85, 247, 0.35); padding: 0.3rem 0.8rem; border-radius: 9999px; font-size: 0.78rem; font-weight: 800; color: #a855f7; text-transform: uppercase; margin-bottom: 0.6rem;">
          <i class="fa-solid fa-moon"></i> Underground Inventory
        </div>
        <h1 class="dashboard-title">Exclusive Night Market Catalog</h1>
        <p class="dashboard-subtitle">Manage high-performance track builds, 60% acquisition pricing, and live availability statuses.</p>
      </div>
      <div style="display: flex; gap: 0.8rem;">
        <a href="<?php echo url('/admin/night-market-orders.php'); ?>" class="btn btn-secondary">
          <i class="fa-solid fa-gem" style="color: #a855f7;"></i> View Acquisitions
        </a>
        <a href="<?php echo url('/night-market.php'); ?>" target="_blank" class="btn btn-primary" style="background: linear-gradient(135deg, #7c3aed, #4f46e5); border: none;">
          <i class="fa-solid fa-arrow-up-right-from-square"></i> Open Live Showcase
        </a>
      </div>
    </div>

    <!-- Inventory Stat Cards -->
    <div class="stats-cards-grid" style="margin-bottom: 2rem;">
      <div class="stat-card" style="border-left: 3px solid #a855f7;">
        <div class="stat-icon" style="background: rgba(168, 85, 247, 0.15); color: #a855f7;"><i class="fa-solid fa-car-rear"></i></div>
        <div class="stat-data">
          <span class="stat-value"><?php echo $total_nm_count; ?></span>
          <span class="stat-label">Total Night Market Builds</span>
        </div>
      </div>

      <div class="stat-card" style="border-left: 3px solid #10b981;">
        <div class="stat-icon success"><i class="fa-solid fa-circle-check"></i></div>
        <div class="stat-data">
          <span class="stat-value"><?php echo $available_count; ?></span>
          <span class="stat-label">Ready for Acquisition</span>
        </div>
      </div>

      <div class="stat-card" style="border-left: 3px solid #ef4444;">
        <div class="stat-icon danger"><i class="fa-solid fa-ban"></i></div>
        <div class="stat-data">
          <span class="stat-value"><?php echo $sold_out_count; ?></span>
          <span class="stat-label">Allocated / Sold Out</span>
        </div>
      </div>

      <div class="stat-card" style="border-left: 3px solid #38bdf8;">
        <div class="stat-icon info" style="background: rgba(56, 189, 248, 0.15); color: #0284c7;"><i class="fa-solid fa-vault"></i></div>
        <div class="stat-data">
          <span class="stat-value"><?php echo format_currency($total_acq_value); ?></span>
          <span class="stat-label">Total Inventory Valuation</span>
        </div>
      </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem 1.5rem;">
      <form method="GET" action="<?php echo url('/admin/night-market-vehicles.php'); ?>" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
        
        <div style="flex: 2; min-width: 220px;">
          <label style="font-size: 0.78rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem; display: block;">Search Supercars</label>
          <input type="text" name="q" class="form-control" placeholder="Search by name, brand, spec..." value="<?php echo htmlspecialchars($search_query); ?>">
        </div>

        <div style="flex: 1; min-width: 180px;">
          <label style="font-size: 0.78rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem; display: block;">Category</label>
          <select name="category" class="form-control">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($category_filter === $cat) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="flex: 1; min-width: 160px;">
          <label style="font-size: 0.78rem; text-transform: uppercase; font-weight: 700; color: var(--text-muted); margin-bottom: 0.35rem; display: block;">Stock Status</label>
          <select name="status" class="form-control">
            <option value="">All Statuses</option>
            <option value="available" <?php echo ($status_filter === 'available') ? 'selected' : ''; ?>>Available</option>
            <option value="coming_soon" <?php echo ($status_filter === 'coming_soon') ? 'selected' : ''; ?>>Coming Soon</option>
            <option value="sold_out" <?php echo ($status_filter === 'sold_out') ? 'selected' : ''; ?>>Sold Out</option>
          </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
          <button type="submit" class="btn btn-primary" style="height: 42px; display: inline-flex; align-items: center; gap: 0.4rem;">
            <i class="fa-solid fa-filter"></i> Filter
          </button>
          <a href="<?php echo url('/admin/night-market-vehicles.php'); ?>" class="btn btn-secondary" style="height: 42px; display: inline-flex; align-items: center; gap: 0.4rem;">
            <i class="fa-solid fa-rotate-left"></i> Reset
          </a>
        </div>
      </form>
    </div>

    <!-- Vehicles Table Card -->
    <div class="card">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
        <h3 style="font-size: 1.15rem; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
          <i class="fa-solid fa-car-tunnel" style="color: #a855f7;"></i>
          Catalog Records (<?php echo count($vehicles); ?> of <?php echo $total_nm_count; ?>)
        </h3>
      </div>

      <?php if (empty($vehicles)): ?>
        <div style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
          <i class="fa-solid fa-car" style="font-size: 2.5rem; color: #a855f7; margin-bottom: 0.8rem; display: block;"></i>
          <h4 style="color: var(--text-main);">No Night Market builds match your search.</h4>
          <p>Try clearing filters or search terms.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th style="width: 70px;">Preview</th>
                <th>Vehicle & Dossier</th>
                <th>Category</th>
                <th>Acquisition Price (60%)</th>
                <th>Status</th>
                <th style="text-align: right;">Quick Status & Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($vehicles as $v): 
                $v_specs = json_decode($v['specs_json'], true) ?: [];
                $img_url = (strpos($v['image_url'], 'http') === 0) ? $v['image_url'] : url($v['image_url']);
              ?>
                <tr>
                  <td>
                    <img src="<?php echo htmlspecialchars($img_url); ?>" 
                         alt="<?php echo htmlspecialchars($v['vehicle_name']); ?>"
                         style="width: 60px; height: 42px; object-fit: cover; border-radius: 6px; border: 1px solid rgba(168, 85, 247, 0.3);">
                  </td>
                  <td>
                    <div style="font-weight: 700; color: var(--text-main); font-size: 0.95rem;">
                      <?php echo htmlspecialchars($v['vehicle_name']); ?>
                    </div>
                    <div style="font-size: 0.78rem; color: var(--text-muted);">
                      <?php echo htmlspecialchars($v['subtitle']); ?>
                    </div>
                    <div style="font-size: 0.72rem; color: #38bdf8; margin-top: 0.2rem; display: flex; gap: 0.6rem;">
                      <?php if (!empty($v_specs['engine'])): ?>
                        <span><i class="fa-solid fa-fire"></i> <?php echo htmlspecialchars($v_specs['engine']); ?></span>
                      <?php endif; ?>
                      <?php if (!empty($v_specs['power'])): ?>
                        <span><i class="fa-solid fa-gauge-high"></i> <?php echo htmlspecialchars($v_specs['power']); ?></span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td>
                    <span style="display: inline-block; padding: 0.25rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700; background: rgba(168, 85, 247, 0.12); color: #a855f7; border: 1px solid rgba(168, 85, 247, 0.3);">
                      <?php echo htmlspecialchars($v['category']); ?>
                    </span>
                  </td>
                  <td>
                    <div style="font-weight: 800; font-size: 1.05rem; color: #10b981;">
                      <?php echo format_currency($v['daily_rate']); ?>
                    </div>
                    <div style="font-size: 0.72rem; color: var(--text-muted);">
                      Direct Underground Price
                    </div>
                  </td>
                  <td>
                    <?php if ($v['status'] === 'available'): ?>
                      <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Available</span>
                    <?php elseif ($v['status'] === 'coming_soon'): ?>
                      <span class="badge badge-warning"><i class="fa-solid fa-clock"></i> Coming Soon</span>
                    <?php else: ?>
                      <span class="badge badge-danger"><i class="fa-solid fa-lock"></i> Sold Out</span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align: right;">
                    <div style="display: inline-flex; align-items: center; gap: 0.5rem;">
                      <!-- Quick Status Toggle Form -->
                      <form method="POST" action="<?php echo url('/admin/night-market-vehicles.php'); ?>" style="display: inline-block; margin: 0;">
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="vehicle_id" value="<?php echo $v['id']; ?>">
                        <select name="status" onchange="this.form.submit()" style="padding: 0.35rem 0.6rem; font-size: 0.8rem; border-radius: 6px; background: var(--surface); color: var(--text-main); border: 1px solid var(--border);">
                          <option value="available" <?php echo ($v['status'] === 'available') ? 'selected' : ''; ?>>Set Available</option>
                          <option value="coming_soon" <?php echo ($v['status'] === 'coming_soon') ? 'selected' : ''; ?>>Set Coming Soon</option>
                          <option value="sold_out" <?php echo ($v['status'] === 'sold_out') ? 'selected' : ''; ?>>Set Sold Out</option>
                        </select>
                      </form>

                      <!-- Public Dossier View Link -->
                      <a href="<?php echo url('/night-market-details.php?id=' . $v['id']); ?>" 
                         target="_blank" 
                         class="btn btn-outline-sm" 
                         title="View Public Dossier" 
                         style="padding: 0.35rem 0.65rem;">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>

</body>
</html>
