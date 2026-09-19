<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * About Page (about.php)
 */
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$page_title = "About Us — ASHVKATHA Drive Your Way";
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="section-padding">
  <div class="container">
    <div class="section-header">
      <span class="section-subtitle">Our Vision & Legacy</span>
      <h1 class="section-title">Redefining Vehicle Mobility Across India</h1>
      <p class="section-desc">ASHVKATHA delivers an uncompromised standard in personal transportation, supercar experiences, and automated fleet management.</p>
    </div>

    <div class="form-grid-2" style="align-items: center; gap: 4rem; margin-bottom: 5rem;">
      <div>
        <h2 style="font-size: 2rem; margin-bottom: 1.5rem;">Precision, Reliability & Prestige</h2>
        <p style="color: var(--text-muted); margin-bottom: 1.2rem;">
          Established with a commitment to excellence, <strong>ASHVKATHA</strong> bridges the gap between premium automotive performance and accessible digital rental mobility. Whether for corporate executives, luxury weekend getaways, or cross-state road trips, our smart system ensures complete transparency.
        </p>
        <p style="color: var(--text-muted); margin-bottom: 2rem;">
          Every car and bike in our fleet undergoes stringent 50-point diagnostic checks, computerized wheel alignment, and interior sanitation before every hand-off.
        </p>
        <div style="display: flex; gap: 1rem;">
          <a href="<?php echo url('/vehicles.php'); ?>" class="btn btn-primary"><i class="fa-solid fa-car"></i> Explore Fleet</a>
          <a href="<?php echo url('/contact.php'); ?>" class="btn btn-outline"><i class="fa-solid fa-envelope"></i> Contact Us</a>
        </div>
      </div>

      <div>
        <img src="https://images.unsplash.com/photo-1555215695-3004980ad54e?auto=format&fit=crop&w=1000&q=80" alt="Ashvkatha Fleet" style="border-radius: var(--radius-lg); border: 1px solid var(--surface-border); box-shadow: var(--shadow-lg);">
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
