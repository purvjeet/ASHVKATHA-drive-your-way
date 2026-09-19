<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Admin Control Panel: Underground Night Market Acquisitions Management (admin/night-market-orders.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$db = get_db_connection();

// Process Status Update Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order_status') {
    $order_id = (int)$_POST['order_id'];
    $status   = sanitize($_POST['status']);

    if (in_array($status, ['pending', 'confirmed', 'in_transit', 'delivered', 'cancelled'])) {
        $c_stmt = $db->prepare("SELECT car_id FROM night_market_orders WHERE id = ?");
        $c_stmt->execute([$order_id]);
        $car_id = $c_stmt->fetchColumn();

        if ($status === 'cancelled') {
            // Cancel order, mark payment as refunded, and deduct from active volume
            $db->prepare("UPDATE night_market_orders SET order_status = 'cancelled', payment_status = 'refunded' WHERE id = ?")->execute([$order_id]);
            
            // Revert vehicle inventory status to available if it was sold out
            if ($car_id) {
                $db->prepare("UPDATE night_market SET status = 'available' WHERE id = ?")->execute([$car_id]);
            }
            set_flash_message('success', "Night Market order #{$order_id} has been CANCELLED. Deal amount has been deducted (-MINS) from Acquisition Volume and vehicle inventory restored to Available.");
        } else {
            // If restoring or updating order, re-lock car as sold_out
            if ($car_id) {
                $db->prepare("UPDATE night_market SET status = 'sold_out' WHERE id = ?")->execute([$car_id]);
            }
            $db->prepare("UPDATE night_market_orders SET order_status = ?, payment_status = 'completed' WHERE id = ?")->execute([$status, $order_id]);
            set_flash_message('success', "Night Market order #{$order_id} status successfully updated to " . strtoupper($status) . ".");
        }
    }
    redirect('/admin/night-market-orders.php');
}

$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$sql = "
    SELECT o.*, nm.vehicle_name, nm.brand, nm.category, nm.image_url
    FROM night_market_orders o
    LEFT JOIN night_market nm ON o.car_id = nm.id
    WHERE 1=1
";
$params = [];

if (!empty($status_filter)) {
    $sql .= " AND o.order_status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Statistics - Accurately deducts any cancelled deals
$active_acq_count = $db->query("SELECT COUNT(*) FROM night_market_orders WHERE order_status != 'cancelled'")->fetchColumn();
$total_orders     = $db->query("SELECT COUNT(*) FROM night_market_orders")->fetchColumn();
$total_acq_vol    = $db->query("SELECT SUM(total_amount) FROM night_market_orders WHERE order_status != 'cancelled'")->fetchColumn() ?: 0.00;
$cancelled_count  = $db->query("SELECT COUNT(*) FROM night_market_orders WHERE order_status = 'cancelled'")->fetchColumn();
$cancelled_vol    = $db->query("SELECT SUM(total_amount) FROM night_market_orders WHERE order_status = 'cancelled'")->fetchColumn() ?: 0.00;
$transit_count    = $db->query("SELECT COUNT(*) FROM night_market_orders WHERE order_status = 'in_transit'")->fetchColumn();
$delivered_count  = $db->query("SELECT COUNT(*) FROM night_market_orders WHERE order_status = 'delivered'")->fetchColumn();

$page_title = "Night Market Acquisitions — ASHVKATHA Admin";
$extra_css  = ['dashboard.css', 'admin.css', 'forms.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div>
        <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(168, 85, 247, 0.12); border: 1px solid rgba(168, 85, 247, 0.35); padding: 0.3rem 0.8rem; border-radius: 9999px; font-size: 0.78rem; font-weight: 800; color: #a855f7; text-transform: uppercase; margin-bottom: 0.6rem;">
          <i class="fa-solid fa-moon"></i> Underground Operations
        </div>
        <h1 class="dashboard-title">Night Market Acquisitions & Orders</h1>
        <p class="dashboard-subtitle">Manage exclusive non-road-legal supercar acquisitions, escrow transactions, and enclosed logistics handovers.</p>
      </div>
      <div style="display: flex; gap: 0.8rem;">
        <a href="<?php echo url('/admin/night-market-vehicles.php'); ?>" class="btn btn-secondary">
          <i class="fa-solid fa-car-rear"></i> View Exclusive Inventory
        </a>
        <a href="<?php echo url('/night-market.php'); ?>" target="_blank" class="btn btn-primary" style="background: linear-gradient(135deg, #7c3aed, #4f46e5); border: none;">
          <i class="fa-solid fa-arrow-up-right-from-square"></i> Open Live Market
        </a>
      </div>
    </div>

    <!-- Night Market KPI Stat Cards -->
    <div class="stats-cards-grid" style="margin-bottom: 2rem;">
      <div class="stat-card" style="border-left: 3px solid #a855f7;">
        <div class="stat-icon" style="background: rgba(168, 85, 247, 0.15); color: #a855f7;"><i class="fa-solid fa-file-invoice-dollar"></i></div>
        <div class="stat-data">
          <span class="stat-value"><?php echo $active_acq_count; ?></span>
          <span class="stat-label">Active Acquisitions</span>
        </div>
      </div>

      <div class="stat-card" style="border-left: 3px solid #10b981;">
        <div class="stat-icon success"><i class="fa-solid fa-indian-rupee-sign"></i></div>
        <div class="stat-data">
          <span class="stat-value"><?php echo format_currency($total_acq_vol); ?></span>
          <span class="stat-label">Acquisition Volume</span>
          <?php if ($cancelled_vol > 0): ?>
            <div style="font-size: 0.72rem; color: #ef4444; font-weight: 700; margin-top: 0.25rem;">
              <i class="fa-solid fa-circle-minus"></i> -<?php echo format_currency($cancelled_vol); ?> Cancelled / Deducted
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="stat-card" style="border-left: 3px solid #f59e0b;">
        <div class="stat-icon warning"><i class="fa-solid fa-truck-fast"></i></div>
        <div class="stat-data">
          <span class="stat-value"><?php echo $transit_count; ?></span>
          <span class="stat-label">In Enclosed Transit</span>
        </div>
      </div>

      <div class="stat-card" style="border-left: 3px solid #ef4444;">
        <div class="stat-icon danger"><i class="fa-solid fa-ban"></i></div>
        <div class="stat-data">
          <span class="stat-value"><?php echo $cancelled_count; ?></span>
          <span class="stat-label">Cancelled Deals</span>
        </div>
      </div>
    </div>

    <!-- Filter Buttons -->
    <div style="margin-bottom: 1.5rem; display: flex; gap: 0.6rem; flex-wrap: wrap;">
      <a href="<?php echo url('/admin/night-market-orders.php'); ?>" class="btn <?php echo empty($status_filter) ? 'btn-primary' : 'btn-outline'; ?> btn-sm">All Orders (<?php echo $total_orders; ?>)</a>
      <a href="<?php echo url('/admin/night-market-orders.php?status=confirmed'); ?>" class="btn <?php echo ($status_filter == 'confirmed') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Confirmed</a>
      <a href="<?php echo url('/admin/night-market-orders.php?status=in_transit'); ?>" class="btn <?php echo ($status_filter == 'in_transit') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">In Transit</a>
      <a href="<?php echo url('/admin/night-market-orders.php?status=delivered'); ?>" class="btn <?php echo ($status_filter == 'delivered') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Delivered</a>
      <a href="<?php echo url('/admin/night-market-orders.php?status=cancelled'); ?>" class="btn <?php echo ($status_filter == 'cancelled') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Cancelled</a>
    </div>

    <div class="card">
      <?php if (empty($orders)): ?>
        <p style="text-align: center; color: var(--text-muted); padding: 3rem 0;">No Night Market orders found matching your criteria.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Order Code</th>
                <th>Buyer Information</th>
                <th>Vehicle Build</th>
                <th>Transit Logistics</th>
                <th>Acquisition Price</th>
                <th>Status</th>
                <th style="min-width: 170px;">Update Order Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $o): 
                $o_img = !empty($o['image_url']) ? ((strpos($o['image_url'], 'http') === 0) ? $o['image_url'] : url($o['image_url'])) : url('/assets/images/placeholder-car.jpg');
              ?>
                <tr>
                  <td>
                    <strong style="color: #a855f7; font-family: monospace; font-size: 0.95rem;"><?php echo htmlspecialchars($o['order_code']); ?></strong>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;">
                      <?php echo date('d M Y, h:i A', strtotime($o['created_at'])); ?>
                    </div>
                  </td>
                  <td>
                    <strong><?php echo htmlspecialchars($o['buyer_name']); ?></strong>
                    <div style="font-size: 0.78rem; color: var(--text-muted);"><?php echo htmlspecialchars($o['buyer_email']); ?></div>
                    <div style="font-size: 0.78rem; color: #3b82f6;"><?php echo htmlspecialchars($o['buyer_phone']); ?></div>
                  </td>
                  <td>
                    <div style="display: flex; align-items: center; gap: 0.8rem;">
                      <img src="<?php echo htmlspecialchars($o_img); ?>" alt="Car" style="width: 50px; height: 34px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid rgba(168, 85, 247, 0.3);">
                      <div>
                        <strong><?php echo htmlspecialchars($o['vehicle_name']); ?></strong>
                        <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo htmlspecialchars($o['category']); ?></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <div style="font-size: 0.82rem; font-weight: 600;"><i class="fa-solid fa-truck" style="color: #38bdf8;"></i> <?php echo htmlspecialchars($o['delivery_option']); ?></div>
                    <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.2rem;" title="<?php echo htmlspecialchars($o['delivery_address']); ?>">
                      <i class="fa-solid fa-location-dot" style="color: #a855f7;"></i> <?php echo htmlspecialchars(substr($o['delivery_address'], 0, 35)) . (strlen($o['delivery_address']) > 35 ? '...' : ''); ?>
                    </div>
                  </td>
                  <td>
                    <div style="font-size: 1.05rem; font-weight: 900; color: #a855f7;">
                      <?php echo format_currency($o['total_amount']); ?>
                    </div>
                    <div style="font-size: 0.75rem; color: var(--text-muted);">
                      <?php echo htmlspecialchars($o['payment_method']); ?>
                    </div>
                  </td>
                  <td>
                    <?php if ($o['order_status'] === 'confirmed'): ?>
                      <span class="badge badge-primary" style="background: rgba(168, 85, 247, 0.15); color: #a855f7; border: 1px solid rgba(168, 85, 247, 0.4);">CONFIRMED</span>
                    <?php elseif ($o['order_status'] === 'in_transit'): ?>
                      <span class="badge badge-warning">IN TRANSIT</span>
                    <?php elseif ($o['order_status'] === 'delivered'): ?>
                      <span class="badge badge-success">DELIVERED</span>
                    <?php else: ?>
                      <span class="badge badge-danger"><?php echo strtoupper($o['order_status']); ?></span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <form action="<?php echo url('/admin/night-market-orders.php'); ?>" method="POST" style="display: flex; gap: 0.4rem; align-items: center;">
                      <input type="hidden" name="action" value="update_order_status">
                      <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                      <select name="status" class="form-control" style="font-size: 0.78rem; padding: 0.35rem 0.6rem; height: 32px; width: 110px;">
                        <option value="confirmed" <?php echo ($o['order_status'] == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="in_transit" <?php echo ($o['order_status'] == 'in_transit') ? 'selected' : ''; ?>>In Transit</option>
                        <option value="delivered" <?php echo ($o['order_status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo ($o['order_status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                      </select>
                      <button type="submit" class="btn btn-primary-sm btn-sm" style="padding: 0.35rem 0.6rem; height: 32px;" title="Save Status">
                        <i class="fa-solid fa-floppy-disk"></i>
                      </button>
                    </form>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
