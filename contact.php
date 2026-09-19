<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Contact & Support Page (contact.php)
 */
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$message_sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    set_flash_message('success', 'Thank you! Your message has been received. Our VIP support team will contact you shortly.');
    $message_sent = true;
}

$page_title = "Contact Support & VIP Assistance — ASHVKATHA";
$extra_css  = ['forms.css'];
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>

<div class="section-padding">
  <div class="container">
    <div class="section-header">
      <span class="section-subtitle">24/7 Support Hub</span>
      <h1 class="section-title">Get In Touch With Ashvkatha</h1>
      <p class="section-desc">Have questions about fleet reservations, corporate accounts, or roadside assistance?</p>
    </div>

    <div class="form-grid-2" style="gap: 3rem; align-items: start;">
      <div class="card">
        <h3 style="font-size: 1.5rem; margin-bottom: 1.5rem;"><i class="fa-solid fa-paper-plane" style="color: var(--primary);"></i> Send an Inquiry</h3>
        
        <form action="<?php echo url('/contact.php'); ?>" method="POST">
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" placeholder="Enter your name" required>
          </div>

          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
          </div>

          <div class="form-group">
            <label class="form-label">Subject</label>
            <select name="subject" class="form-control" required>
              <option value="General Inquiry">General Fleet Inquiry</option>
              <option value="Booking Modification">Booking Modification / Extension</option>
              <option value="Corporate Fleet">Corporate Partnership</option>
              <option value="Roadside Support">Emergency Roadside Support</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Message</label>
            <textarea name="message" class="form-control" placeholder="Describe your inquiry..." required></textarea>
          </div>

          <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
            <i class="fa-solid fa-paper-plane"></i> Submit Message
          </button>
        </form>
      </div>

      <div>
        <div class="card" style="margin-bottom: 1.5rem;">
          <h3 style="font-size: 1.3rem; margin-bottom: 1rem;"><i class="fa-solid fa-building" style="color: var(--primary);"></i> Headquarters & Central Operations</h3>
          <p style="color: var(--text-muted); margin-bottom: 0.8rem;">
            <i class="fa-solid fa-location-dot"></i> Ashvkatha Tower, Gir Road, Junagadh, Gujarat 362001
          </p>
          <p style="color: var(--text-muted); margin-bottom: 0.8rem;">
            <i class="fa-solid fa-phone"></i> +91 285 2200111 / +91 98765 43210
          </p>
          <p style="color: var(--text-muted);">
            <i class="fa-solid fa-envelope"></i> support@ashvkatha.com
          </p>
        </div>

        <div class="card">
          <h3 style="font-size: 1.3rem; margin-bottom: 1rem;"><i class="fa-solid fa-headset" style="color: var(--success);"></i> 24/7 Emergency Assistance</h3>
          <p style="color: var(--text-muted); font-size: 0.95rem;">
            If you encounter mechanical trouble or require emergency breakdown towing anywhere in Gujarat, call our 24/7 hotline directly:
          </p>
          <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary); margin-top: 0.8rem;">
            <i class="fa-solid fa-phone-volume"></i> 1800-ASHV-HELP
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
