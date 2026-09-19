<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Main Ashvkatha Centered Pill Navigation Header with External Top-Right RGB Auth Button
 */
require_once __DIR__ . '/functions.php';
$user = get_logged_user();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<header class="nav-header-container">
  <!-- Centered Floating Translucent Pill Navbar -->
  <nav class="nav-pill">
    <a href="<?php echo url('/vehicles.php'); ?>" class="nav-link <?php echo ($current_page == 'vehicles.php' || $current_page == 'vehicle-details.php') ? 'active' : ''; ?>">Browse</a>
    <a href="<?php echo url('/night-market.php'); ?>" class="nav-link <?php echo ($current_page == 'night-market.php') ? 'active' : ''; ?>">Night Market</a>

    <a href="<?php echo url('/index.php'); ?>" class="nav-logo" title="ASHVKATHA — Drive Your Way" onclick="if(window.location.pathname.endsWith('index.php') || window.location.pathname.endsWith('/ashvkatha/') || window.location.pathname.endsWith('/ashvkatha') || window.location.pathname === '/' || window.location.pathname === '<?php echo parse_url(url('/'), PHP_URL_PATH); ?>'){ window.scrollTo({top: 0, left: 0, behavior: 'instant'}); window.location.reload(); return false; }">
      <span class="nav-logo-main">A S H V K A T H A</span>
      <span class="nav-logo-sub">Drive Your Way</span>
    </a>

    <a href="<?php echo url('/about.php'); ?>" class="nav-link <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">About</a>
    <a href="<?php echo url('/contact.php'); ?>" class="nav-link <?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>">Contact Us</a>
  </nav>
</header>

<!-- Fixed Top-Right Corner Auth Button (Side Top Right of Window) -->
<div class="top-right-auth">
  <?php if ($user): ?>
    <?php if (is_admin()): ?>
      <a href="<?php echo url('/admin/dashboard.php'); ?>" class="rgb-login-btn"><i class="fa-solid fa-gauge-high"></i> Admin</a>
    <?php else: ?>
      <a href="<?php echo url('/customer/dashboard.php'); ?>" class="rgb-login-btn"><i class="fa-solid fa-user"></i> Account</a>
    <?php endif; ?>
  <?php else: ?>
    <a href="<?php echo url('/login.php'); ?>" class="rgb-login-btn"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
  <?php endif; ?>
</div>
