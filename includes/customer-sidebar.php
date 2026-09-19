<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Customer Portal Navigation Sidebar
 */
require_once __DIR__ . '/functions.php';
$customer_page = basename($_SERVER['PHP_SELF']);
$user = get_logged_user();
?>
<aside class="customer-sidebar">
  <div class="sidebar-user">
    <div class="user-avatar-large"><i class="fa-solid fa-user"></i></div>
    <div class="user-details">
      <span class="user-name"><?php echo htmlspecialchars($user['full_name'] ?? 'Customer'); ?></span>
      <span class="user-email"><?php echo htmlspecialchars($user['email'] ?? ''); ?></span>
      <span class="license-status badge badge-success"><i class="fa-solid fa-id-card"></i> Verified License</span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <ul>
      <li class="<?php echo ($customer_page == 'dashboard.php') ? 'active' : ''; ?>">
        <a href="<?php echo url('/customer/dashboard.php'); ?>"><i class="fa-solid fa-gauge-high"></i> Dashboard Overview</a>
      </li>
      <li class="<?php echo ($customer_page == 'bookings.php' || $customer_page == 'booking-details.php') ? 'active' : ''; ?>">
        <a href="<?php echo url('/customer/bookings.php'); ?>"><i class="fa-solid fa-calendar-check"></i> My Rental Bookings</a>
      </li>
      <li class="<?php echo ($customer_page == 'night-market-orders.php') ? 'active' : ''; ?>">
        <a href="<?php echo url('/customer/night-market-orders.php'); ?>"><i class="fa-solid fa-gem" style="color: #a855f7;"></i> Night Market Acquisitions</a>
      </li>
      <li>
        <a href="<?php echo url('/vehicles.php'); ?>"><i class="fa-solid fa-car-side"></i> Rent a New Vehicle</a>
      </li>
      <li class="<?php echo ($customer_page == 'payments.php') ? 'active' : ''; ?>">
        <a href="<?php echo url('/customer/payments.php'); ?>"><i class="fa-solid fa-receipt"></i> Payment Records</a>
      </li>
      <li class="<?php echo ($customer_page == 'invoices.php') ? 'active' : ''; ?>">
        <a href="<?php echo url('/customer/invoices.php'); ?>"><i class="fa-solid fa-file-invoice"></i> Download Invoices</a>
      </li>
      <li class="<?php echo ($customer_page == 'profile.php') ? 'active' : ''; ?>">
        <a href="<?php echo url('/customer/profile.php'); ?>"><i class="fa-solid fa-id-badge"></i> Profile & Driving License</a>
      </li>
      <li>
        <a href="<?php echo url('/logout.php'); ?>" class="nav-logout"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
      </li>
    </ul>
  </nav>
</aside>
