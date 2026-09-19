<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Tax Invoice Document Generator (customer/invoices.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$user = get_logged_user();
$db = get_db_connection();

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$invoice_booking = null;
if ($booking_id > 0) {
    $stmt = $db->prepare("
        SELECT b.*, 
               COALESCE(v.brand, nm.brand, 'ASHVKATHA Special') as brand, 
               COALESCE(v.model, nm.vehicle_name, 'Exclusive Machine') as model, 
               COALESCE(v.reg_number, 'TRACK-USE-ONLY') as reg_number, 
               COALESCE(v.daily_rate, b.base_amount) as daily_rate,
               COALESCE(l1.city_name, 'HQ Hub') as pickup_city, 
               COALESCE(l2.city_name, 'Private Handover') as dropoff_city,
               p.transaction_id, p.payment_method, p.payment_date
        FROM bookings b
        LEFT JOIN vehicles v ON b.vehicle_id = v.id
        LEFT JOIN night_market nm ON b.vehicle_id = nm.id
        LEFT JOIN locations l1 ON b.pickup_location_id = l1.id
        LEFT JOIN locations l2 ON b.dropoff_location_id = l2.id
        LEFT JOIN payments p ON p.booking_id = b.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$booking_id, $user['id']]);
    $invoice_booking = $stmt->fetch();
}

// Fetch all bookings for invoice list if no specific ID selected
$all_stmt = $db->prepare("
    SELECT b.*, 
           COALESCE(v.brand, nm.brand, 'ASHVKATHA Special') as brand, 
           COALESCE(v.model, nm.vehicle_name, 'Exclusive Machine') as model
    FROM bookings b
    LEFT JOIN vehicles v ON b.vehicle_id = v.id
    LEFT JOIN night_market nm ON b.vehicle_id = nm.id
    WHERE b.user_id = ? ORDER BY b.created_at DESC
");
$all_stmt->execute([$user['id']]);
$user_bookings = $all_stmt->fetchAll();

$page_title = "Tax Invoices — ASHVKATHA";
$extra_css  = ['dashboard.css', 'booking.css'];
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<style>
@media print {
  body * { visibility: hidden; }
  .printable-invoice, .printable-invoice * { visibility: visible; }
  .printable-invoice { position: absolute; left: 0; top: 0; width: 100%; }
  .navbar-header, .customer-sidebar, .footer-site, .no-print { display: none !important; }
}
</style>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/customer-sidebar.php'; ?>

  <main class="dashboard-content">
    <?php if ($invoice_booking): ?>
      <div class="no-print" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <a href="<?php echo url('/customer/invoices.php'); ?>" class="btn btn-outline-sm"><i class="fa-solid fa-arrow-left"></i> All Invoices</a>
        <button onclick="window.print();" class="btn btn-primary"><i class="fa-solid fa-print"></i> Print / Save PDF Invoice</button>
      </div>

      <!-- Formal Printable Tax Invoice -->
      <div class="card printable-invoice" style="max-width: 800px; margin: 0 auto; padding: 3rem; background: #fff; color: #0f172a; border: 1px solid #cbd5e1;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 2rem; border-bottom: 2px solid #e2e8f0;">
          <div>
            <h1 style="font-family: var(--font-heading); color: var(--primary); font-size: 2.2rem; font-weight: 800;">ASHVKATHA</h1>
            <p style="color: #64748b; font-size: 0.85rem; letter-spacing: 0.15em; font-weight: 700;">DRIVE YOUR WAY</p>
            <p style="color: #475569; font-size: 0.9rem; margin-top: 0.5rem;">Ashvkatha Tower, Gir Road, Junagadh, Gujarat<br>GSTIN: 24AAACA0000A1Z5</p>
          </div>

          <div style="text-align: right;">
            <h2 style="font-size: 1.6rem; color: #1e293b;">TAX INVOICE</h2>
            <p style="font-size: 0.95rem; font-weight: 700; color: #0f172a;">Invoice #: INV-<?php echo htmlspecialchars($invoice_booking['booking_code']); ?></p>
            <p style="font-size: 0.85rem; color: #64748b;">Date: <?php echo date('d M Y', strtotime($invoice_booking['created_at'])); ?></p>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin: 2rem 0;">
          <div>
            <h4 style="font-size: 0.85rem; color: #64748b; text-transform: uppercase;">BILLED TO:</h4>
            <div style="font-size: 1.1rem; font-weight: 700; color: #0f172a;"><?php echo htmlspecialchars($user['full_name']); ?></div>
            <p style="font-size: 0.9rem; color: #475569;"><?php echo htmlspecialchars($user['email']); ?><br><?php echo htmlspecialchars($user['phone']); ?></p>
          </div>

          <div>
            <h4 style="font-size: 0.85rem; color: #64748b; text-transform: uppercase;">RENTAL DETAILS:</h4>
            <p style="font-size: 0.9rem; color: #0f172a;">
              <strong>Vehicle:</strong> <?php echo htmlspecialchars($invoice_booking['brand'] . ' ' . $invoice_booking['model']); ?> (<?php echo htmlspecialchars($invoice_booking['reg_number']); ?>)<br>
              <strong>Duration:</strong> <?php echo $invoice_booking['total_days']; ?> Days (<?php echo date('d M Y', strtotime($invoice_booking['pickup_datetime'])); ?> - <?php echo date('d M Y', strtotime($invoice_booking['return_datetime'])); ?>)<br>
              <strong>Logistics:</strong> <?php echo htmlspecialchars($invoice_booking['delivery_method'] ?? 'Hub Self-Pickup'); ?>
              <?php if (!empty($invoice_booking['delivery_address'])): ?>
                — <?php echo htmlspecialchars($invoice_booking['delivery_address']); ?>
              <?php endif; ?>
            </p>
          </div>
        </div>

        <!-- Itemized Table -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
          <thead>
            <tr style="background: #f1f5f9; color: #475569; text-align: left; font-size: 0.85rem; text-transform: uppercase;">
              <th style="padding: 0.8rem; border-bottom: 2px solid #cbd5e1;">Description</th>
              <th style="padding: 0.8rem; border-bottom: 2px solid #cbd5e1;">Rate / Day</th>
              <th style="padding: 0.8rem; border-bottom: 2px solid #cbd5e1;">Days</th>
              <th style="padding: 0.8rem; border-bottom: 2px solid #cbd5e1; text-align: right;">Amount</th>
            </tr>
          </thead>
          <tbody style="font-size: 0.95rem; color: #1e293b;">
            <tr>
              <td style="padding: 1rem 0.8rem; border-bottom: 1px solid #e2e8f0;">Vehicle Rental Charges - <?php echo htmlspecialchars($invoice_booking['brand'] . ' ' . $invoice_booking['model']); ?></td>
              <td style="padding: 1rem 0.8rem; border-bottom: 1px solid #e2e8f0;"><?php echo format_currency($invoice_booking['daily_rate']); ?></td>
              <td style="padding: 1rem 0.8rem; border-bottom: 1px solid #e2e8f0;"><?php echo $invoice_booking['total_days']; ?></td>
              <td style="padding: 1rem 0.8rem; border-bottom: 1px solid #e2e8f0; text-align: right;"><?php echo format_currency($invoice_booking['base_amount']); ?></td>
            </tr>
            <?php if ($invoice_booking['discount_amount'] > 0): ?>
              <tr>
                <td colspan="3" style="padding: 0.8rem; border-bottom: 1px solid #e2e8f0; color: #16a34a;">Promo Discount Applied</td>
                <td style="padding: 0.8rem; border-bottom: 1px solid #e2e8f0; text-align: right; color: #16a34a;">- <?php echo format_currency($invoice_booking['discount_amount']); ?></td>
              </tr>
            <?php endif; ?>
            <tr>
              <td colspan="3" style="padding: 0.8rem; border-bottom: 1px solid #e2e8f0;">GST Tax (18%)</td>
              <td style="padding: 0.8rem; border-bottom: 1px solid #e2e8f0; text-align: right;"><?php echo format_currency($invoice_booking['tax_amount']); ?></td>
            </tr>
            <tr>
              <td colspan="3" style="padding: 0.8rem; border-bottom: 1px solid #e2e8f0;">Refundable Security Deposit</td>
              <td style="padding: 0.8rem; border-bottom: 1px solid #e2e8f0; text-align: right;"><?php echo format_currency($invoice_booking['deposit_amount']); ?></td>
            </tr>
            <tr style="font-weight: 800; font-size: 1.1rem; background: #f8fafc;">
              <td colspan="3" style="padding: 1rem 0.8rem;">TOTAL AMOUNT PAID</td>
              <td style="padding: 1rem 0.8rem; text-align: right; color: var(--primary);"><?php echo format_currency($invoice_booking['total_amount']); ?></td>
            </tr>
          </tbody>
        </table>

        <div style="font-size: 0.85rem; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 1.5rem; text-align: center;">
          This is a computer-generated tax invoice. Thank you for choosing Ashvkatha!
        </div>
      </div>

    <?php else: ?>
      <div class="dashboard-header">
        <div>
          <h1 class="dashboard-title">Rental Invoices & Receipts</h1>
          <p class="dashboard-subtitle">Download or print official GST tax invoices for your bookings.</p>
        </div>
      </div>

      <div class="card">
        <?php if (empty($user_bookings)): ?>
          <p style="text-align: center; color: var(--text-muted); padding: 3rem 0;">No invoices available.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Invoice Code</th>
                  <th>Vehicle</th>
                  <th>Booking Date</th>
                  <th>Total Amount</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($user_bookings as $ub): ?>
                  <tr>
                    <td><strong>INV-<?php echo htmlspecialchars($ub['booking_code']); ?></strong></td>
                    <td><?php echo htmlspecialchars($ub['brand'] . ' ' . $ub['model']); ?></td>
                    <td><?php echo date('d M Y', strtotime($ub['created_at'])); ?></td>
                    <td><strong><?php echo format_currency($ub['total_amount']); ?></strong></td>
                    <td>
                      <a href="<?php echo url('/customer/invoices.php?id=' . $ub['id']); ?>" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-file-invoice"></i> View / Print Invoice
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
