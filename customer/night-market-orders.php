<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Customer Portal: Underground Night Market Acquisitions (customer/night-market-orders.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$user = get_logged_user();
$db = get_db_connection();

$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$sql = "
    SELECT o.*, nm.vehicle_name, nm.brand, nm.category, nm.image_url, nm.specs_json
    FROM night_market_orders o
    LEFT JOIN night_market nm ON o.car_id = nm.id
    WHERE o.user_id = ?
";
$params = [$user['id']];

if (!empty($status_filter)) {
    $sql .= " AND o.order_status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$page_title = "My Night Market Acquisitions — ASHVKATHA";
$extra_css  = ['dashboard.css', 'forms.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/customer-sidebar.php'; ?>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div>
        <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(168, 85, 247, 0.12); border: 1px solid rgba(168, 85, 247, 0.35); padding: 0.3rem 0.8rem; border-radius: 9999px; font-size: 0.78rem; font-weight: 800; color: #a855f7; text-transform: uppercase; margin-bottom: 0.6rem;">
          <i class="fa-solid fa-gem"></i> Exclusive Garage & Acquisitions
        </div>
        <h1 class="dashboard-title">Night Market Acquisitions</h1>
        <p class="dashboard-subtitle">Track your high-performance vehicle purchases, private title allocations, and enclosed transporter deliveries.</p>
      </div>
      <a href="<?php echo url('/night-market.php'); ?>" class="btn btn-primary" style="background: linear-gradient(135deg, #7c3aed, #4f46e5); border: none;">
        <i class="fa-solid fa-magnifying-glass"></i> Explore Night Market
      </a>
    </div>

    <!-- Status Filters -->
    <div style="margin-bottom: 1.5rem; display: flex; gap: 0.6rem; flex-wrap: wrap;">
      <a href="<?php echo url('/customer/night-market-orders.php'); ?>" class="btn <?php echo empty($status_filter) ? 'btn-primary' : 'btn-outline'; ?> btn-sm">All Orders</a>
      <a href="<?php echo url('/customer/night-market-orders.php?status=confirmed'); ?>" class="btn <?php echo ($status_filter == 'confirmed') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Confirmed</a>
      <a href="<?php echo url('/customer/night-market-orders.php?status=in_transit'); ?>" class="btn <?php echo ($status_filter == 'in_transit') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">In Enclosed Transit</a>
      <a href="<?php echo url('/customer/night-market-orders.php?status=delivered'); ?>" class="btn <?php echo ($status_filter == 'delivered') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Delivered to Estate</a>
    </div>

    <?php if (empty($orders)): ?>
      <div class="card" style="text-align: center; padding: 4rem 2rem;">
        <div style="width: 80px; height: 80px; border-radius: 50%; background: rgba(168, 85, 247, 0.1); border: 1px solid rgba(168, 85, 247, 0.3); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.5rem;">
          <i class="fa-solid fa-car-tunnel" style="font-size: 2.2rem; color: #a855f7;"></i>
        </div>
        <h3 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 0.6rem;">No Night Market Acquisitions Yet</h3>
        <p style="color: var(--text-muted); max-width: 520px; margin: 0 auto 2rem; font-size: 0.95rem; line-height: 1.6;">
          You haven't acquired any underground performance vehicles yet. Discover exclusive 60% valuation supercars and track-spec machines in the Underground Night Market.
        </p>
        <a href="<?php echo url('/night-market.php'); ?>" class="btn btn-primary" style="background: linear-gradient(135deg, #7c3aed, #4f46e5); border: none; padding: 0.8rem 1.8rem;">
          <i class="fa-solid fa-gem"></i> Browse Underground Builds
        </a>
      </div>
    <?php else: ?>
      <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <?php foreach ($orders as $o): 
          $o_img = !empty($o['image_url']) ? ((strpos($o['image_url'], 'http') === 0) ? $o['image_url'] : url($o['image_url'])) : url('/assets/images/placeholder-car.jpg');
          
          $badge_class = 'badge-primary';
          $status_text = 'Confirmed Allocation';
          if ($o['order_status'] === 'in_transit') {
              $badge_class = 'badge-warning';
              $status_text = 'In Enclosed Transit';
          } elseif ($o['order_status'] === 'delivered') {
              $badge_class = 'badge-success';
              $status_text = 'Delivered to Estate';
          } elseif ($o['order_status'] === 'cancelled') {
              $badge_class = 'badge-danger';
              $status_text = 'Cancelled';
          }
        ?>
          <div class="card" style="padding: 1.5rem; border-left: 4px solid #a855f7;">
            <div style="display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap;">
              
              <!-- Vehicle Thumbnail -->
              <div style="width: 160px; height: 110px; border-radius: 12px; overflow: hidden; flex-shrink: 0; background: #0f172a;">
                <img src="<?php echo htmlspecialchars($o_img); ?>" alt="<?php echo htmlspecialchars($o['vehicle_name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
              </div>

              <!-- Order Details -->
              <div style="flex: 1; min-width: 250px;">
                <div style="display: flex; align-items: center; gap: 0.8rem; margin-bottom: 0.4rem; flex-wrap: wrap;">
                  <span class="badge <?php echo $badge_class; ?>" style="font-size: 0.8rem;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $status_text; ?>
                  </span>
                  <span style="font-family: monospace; font-weight: 700; color: #a855f7; font-size: 0.9rem;">
                    <?php echo htmlspecialchars($o['order_code']); ?>
                  </span>
                  <span style="font-size: 0.8rem; color: var(--text-muted);">
                    Ordered on <?php echo date('M d, Y', strtotime($o['created_at'])); ?>
                  </span>
                </div>

                <h3 style="font-size: 1.35rem; font-weight: 800; margin-bottom: 0.4rem;">
                  <?php echo htmlspecialchars($o['vehicle_name']); ?>
                </h3>

                <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; font-size: 0.85rem; color: var(--text-muted);">
                  <div><i class="fa-solid fa-truck" style="color: #38bdf8;"></i> <?php echo htmlspecialchars($o['delivery_option']); ?></div>
                  <div><i class="fa-solid fa-location-dot" style="color: #a855f7;"></i> <?php echo htmlspecialchars(substr($o['delivery_address'], 0, 45)) . (strlen($o['delivery_address']) > 45 ? '...' : ''); ?></div>
                  <div><i class="fa-solid fa-wallet" style="color: #10b981;"></i> <?php echo htmlspecialchars($o['payment_method']); ?></div>
                </div>
              </div>

              <!-- Price & Actions -->
              <div style="text-align: right; min-width: 180px;">
                <span style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Acquisition Amount</span>
                <div style="font-size: 1.6rem; font-weight: 900; color: #a855f7; margin-bottom: 0.8rem;">
                  <?php echo format_currency($o['total_amount']); ?>
                </div>

                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                  <a href="<?php echo url('/night-market-details.php?id=' . $o['car_id']); ?>" class="btn btn-outline-sm">
                    <i class="fa-solid fa-file-waveform"></i> View Dossier
                  </a>
                  <button onclick="window.print()" class="btn btn-primary-sm" style="background: #0f172a; border-color: rgba(168, 85, 247, 0.4); color: #c084fc;">
                    <i class="fa-solid fa-receipt"></i> Voucher
                  </button>
                </div>
              </div>

            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
