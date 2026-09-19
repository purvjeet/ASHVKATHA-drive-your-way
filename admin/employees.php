<?php
/**
 * ASHVKATHA — Smart Vehicle Rental & Fleet Management System
 * Admin Staff & Employee Management (admin/employees.php)
 */
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();
$db = get_db_connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_employee') {
    $full_name = sanitize($_POST['full_name']);
    $email     = sanitize($_POST['email']);
    $password  = password_hash(sanitize($_POST['password']), PASSWORD_DEFAULT);
    $phone     = sanitize($_POST['phone']);
    $role      = sanitize($_POST['role']);

    $ins = $db->prepare("INSERT INTO users (full_name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
    $ins->execute([$full_name, $email, $password, $phone, $role]);

    set_flash_message('success', 'New staff member added successfully.');
    redirect('admin/employees.php');
}

$staff = $db->query("SELECT * FROM users WHERE role IN ('admin', 'manager', 'staff') ORDER BY id ASC")->fetchAll();

$page_title = "Staff & Employees — ASHVKATHA Admin";
$extra_css  = ['dashboard.css', 'admin.css', 'forms.css'];
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
  <?php include __DIR__ . '/../includes/admin-sidebar.php'; ?>

  <main class="dashboard-content">
    <div class="dashboard-header">
      <div>
        <h1 class="dashboard-title">Staff & Employee Roles</h1>
        <p class="dashboard-subtitle">Manage system administrators, fleet managers, and station staff.</p>
      </div>
    </div>

    <div class="catalog-layout" style="grid-template-columns: 340px 1fr;">
      <!-- Add Staff Form -->
      <div class="card">
        <h3 style="font-size: 1.2rem; margin-bottom: 1.2rem;"><i class="fa-solid fa-user-plus" style="color: var(--primary);"></i> Add Staff Member</h3>

        <form action="<?php echo url('/admin/employees.php'); ?>" method="POST">
          <input type="hidden" name="action" value="add_employee">

          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="full_name" class="form-control" required>
          </div>

          <div class="form-group">
            <label class="form-label">Email Address *</label>
            <input type="email" name="email" class="form-control" required>
          </div>

          <div class="form-group">
            <label class="form-label">Password *</label>
            <input type="password" name="password" class="form-control" required>
          </div>

          <div class="form-group">
            <label class="form-label">Phone Number *</label>
            <input type="text" name="phone" class="form-control" required>
          </div>

          <div class="form-group">
            <label class="form-label">System Role *</label>
            <select name="role" class="form-control" required>
              <option value="manager">Fleet Manager</option>
              <option value="staff">Station Staff</option>
              <option value="admin">System Administrator</option>
            </select>
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fa-solid fa-shield-halved"></i> Create Staff Account</button>
        </form>
      </div>

      <!-- Staff List Table -->
      <div class="card">
        <h3 style="font-size: 1.2rem; margin-bottom: 1.2rem;"><i class="fa-solid fa-users-gear"></i> Active Employee Directory</h3>

        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Added Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($staff as $s): ?>
                <tr>
                  <td>#<?php echo $s['id']; ?></td>
                  <td><strong><?php echo htmlspecialchars($s['full_name']); ?></strong></td>
                  <td><?php echo htmlspecialchars($s['email']); ?></td>
                  <td><?php echo htmlspecialchars($s['phone']); ?></td>
                  <td><span class="badge badge-primary"><?php echo strtoupper(htmlspecialchars($s['role'])); ?></span></td>
                  <td><?php echo date('d M Y', strtotime($s['created_at'])); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

</body>
</html>
