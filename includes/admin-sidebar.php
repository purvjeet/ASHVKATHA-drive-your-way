<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Admin Sidebar Navigation Component
 */
require_once __DIR__ . '/functions.php';
$admin_page = basename($_SERVER['PHP_SELF']);
$user = get_logged_user();
?>
<aside class="admin-sidebar">
  <div class="sidebar-header">
    <a href="<?php echo url('/admin/dashboard.php'); ?>" class="sidebar-brand">
      <i class="fa-solid fa-car-burst"></i>
      <div>
        <span class="brand-title">ASHVKATHA</span>
        <span class="brand-sub">ADMIN CONTROL</span>
      </div>
    </a>
  </div>

  <div class="sidebar-user">
    <div class="user-avatar"><i class="fa-solid fa-user-gear"></i></div>
    <div class="user-info">
      <span class="user-name"><?php echo htmlspecialchars($user['full_name'] ?? 'Admin'); ?></span>
      <span class="user-role badge badge-primary"><?php echo strtoupper(htmlspecialchars($user['role'] ?? 'ADMIN')); ?></span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-title">MAIN OPERATING MENU</div>
    <ul>
      <li class="<?php echo ($admin_page == 'dashboard.php') ? 'active' : ''; ?>">
        <a href="<?php echo url('/admin/dashboard.php'); ?>"><i class="fa-solid fa-chart-line"></i> Dashboard</a>
      </li>
      <li class="<?php echo ($admin_page == 'vehicles.php' || $admin_page == 'add-vehicle.php' || $admin_page == 'edit-vehicle.php') ? 'active' : ''; ?>">
        <a href="<?php echo url('/admin/vehicles.php'); ?>"><i class="fa-solid fa-car"></i> Fleet Management</a>
      </li>
      <li class="<?php echo ($admin_page == 'bookings.php') ? 'active' : ''; ?>">
        <a href="<?php echo url('/admin/bookings.php'); ?>"><i class="fa-solid fa-calendar-check"></i> Reservations & Bookings</a>
      </li>
      <li class="<?php echo ($admin_page == 'customers.php') ? 'active' : ''; ?>">
        <a href="<?php echo url('/admin/customers.php'); ?>"><i class="fa-solid fa-users"></i> Customer Directory</a>
      </li>
      <li class="<?php echo ($admin_page == 'payments.php') ? 'active' : ''; ?>">
        <a href="<?php echo url('/admin/payments.php'); ?>"><i class="fa-solid fa-credit-card"></i> Revenue & Payments</a>
      </li>

      <div class="nav-section-title" style="color: #a855f7; display: flex; align-items: center; gap: 0.4rem;"><i class="fa-solid fa-moon"></i> UNDERGROUND NIGHT MARKET</div>
      <li class="<?php echo ($admin_page == 'night-market-orders.php') ? 'active' : ''; ?>">
        <a href="<?php echo url('/admin/night-market-orders.php'); ?>"><i class="fa-solid fa-gem" style="color: #a855f7;"></i> Market Acquisitions</a>
      </li>
      <li class="<?php echo ($admin_page == 'night-market-vehicles.php') ? 'active' : ''; ?>">
        <a href="<?php echo url('/admin/night-market-vehicles.php'); ?>"><i class="fa-solid fa-fire" style="color: #38bdf8;"></i> Exclusive Inventory</a>
      </li>

      <div class="nav-section-title">FLEET & MAINTENANCE</div>
      <li class="<?php echo ($admin_page == 'maintenance.php') ? 'active' : ''; ?>">
        <a href="<?php echo url('/admin/maintenance.php'); ?>"><i class="fa-solid fa-wrench"></i> Fleet Maintenance</a>
      </li>
      <li class="<?php echo ($admin_page == 'locations.php') ? 'active' : ''; ?>">
        <a href="<?php echo url('/admin/locations.php'); ?>"><i class="fa-solid fa-location-dot"></i> Rental Hubs & Locations</a>
      </li>
      <li class="<?php echo ($admin_page == 'employees.php') ? 'active' : ''; ?>">
        <a href="<?php echo url('/admin/employees.php'); ?>"><i class="fa-solid fa-user-shield"></i> Staff & Employees</a>
      </li>
      <li class="<?php echo ($admin_page == 'discounts.php') ? 'active' : ''; ?>">
        <a href="<?php echo url('/admin/discounts.php'); ?>"><i class="fa-solid fa-ticket"></i> Promo Codes & Discounts</a>
      </li>

      <div class="nav-section-title">REPORTS & SYSTEM</div>
      <li class="<?php echo ($admin_page == 'reports.php') ? 'active' : ''; ?>">
        <a href="<?php echo url('/admin/reports.php'); ?>"><i class="fa-solid fa-chart-pie"></i> Analytics & Reports</a>
      </li>
      <li class="<?php echo ($admin_page == 'settings.php') ? 'active' : ''; ?>">
        <a href="<?php echo url('/admin/settings.php'); ?>"><i class="fa-solid fa-sliders"></i> System Settings</a>
      </li>
    </ul>
  </nav>

  <div class="sidebar-footer">
    <a href="<?php echo url('/index.php'); ?>" class="btn btn-outline-sm"><i class="fa-solid fa-globe"></i> View Website</a>
    <a href="<?php echo url('/logout.php'); ?>" class="btn btn-danger-sm"><i class="fa-solid fa-power-off"></i> Logout</a>
  </div>
</aside>
