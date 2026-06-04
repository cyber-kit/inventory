<?php
session_start();
require_once 'config/database.php';

// Only admin can access
if ($_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Add Role
if (isset($_POST['add_role'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $perms = [
        'dashboard' => isset($_POST['perm_dashboard']) ? 1 : 0,
        'products' => isset($_POST['perm_products']) ? 1 : 0,
        'categories' => isset($_POST['perm_categories']) ? 1 : 0,
        'stock_in' => isset($_POST['perm_stock_in']) ? 1 : 0,
        'stock_out' => isset($_POST['perm_stock_out']) ? 1 : 0,
        'stock_adjust' => isset($_POST['perm_stock_adjust']) ? 1 : 0,
        'sales' => isset($_POST['perm_sales']) ? 1 : 0,
        'expenses' => isset($_POST['perm_expenses']) ? 1 : 0,
        'reports' => isset($_POST['perm_reports']) ? 1 : 0,
        'users' => isset($_POST['perm_users']) ? 1 : 0,
        'roles' => isset($_POST['perm_roles']) ? 1 : 0,
    ];
    $perms_json = mysqli_real_escape_string($conn, json_encode($perms));
    mysqli_query($conn, "INSERT INTO roles (name, permissions) 
        VALUES ('$name', '$perms_json')");
    header("Location: roles.php?msg=added");
    exit();
}

// Edit Role
if (isset($_POST['edit_role'])) {
    $id = (int)$_POST['id'];
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $perms = [
        'dashboard' => isset($_POST['perm_dashboard']) ? 1 : 0,
        'products' => isset($_POST['perm_products']) ? 1 : 0,
        'categories' => isset($_POST['perm_categories']) ? 1 : 0,
        'stock_in' => isset($_POST['perm_stock_in']) ? 1 : 0,
        'stock_out' => isset($_POST['perm_stock_out']) ? 1 : 0,
        'stock_adjust' => isset($_POST['perm_stock_adjust']) ? 1 : 0,
        'sales' => isset($_POST['perm_sales']) ? 1 : 0,
        'expenses' => isset($_POST['perm_expenses']) ? 1 : 0,
        'reports' => isset($_POST['perm_reports']) ? 1 : 0,
        'users' => isset($_POST['perm_users']) ? 1 : 0,
        'roles' => isset($_POST['perm_roles']) ? 1 : 0,
    ];
    $perms_json = mysqli_real_escape_string($conn, json_encode($perms));
    mysqli_query($conn, "UPDATE roles SET name='$name', 
        permissions='$perms_json' WHERE id=$id");
    header("Location: roles.php?msg=updated");
    exit();
}

// Delete Role
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id != 1) { // admin role delete করা যাবে না
        mysqli_query($conn, "DELETE FROM roles WHERE id=$id");
        header("Location: roles.php?msg=deleted");
        exit();
    }
}

require_once 'includes/header.php';

$roles = mysqli_query($conn, "SELECT * FROM roles ORDER BY id ASC");

$all_perms = [
    'dashboard' => 'ড্যাশবোর্ড',
    'products' => 'প্রোডাক্ট',
    'categories' => 'ক্যাটাগরি',
    'stock_in' => 'স্টক ইন',
    'stock_out' => 'স্টক আউট',
    'stock_adjust' => 'স্টক সমন্বয়',
    'sales' => 'বিক্রয়',
    'expenses' => 'খরচ',
    'reports' => 'রিপোর্ট',
    'users' => 'ইউজার',
    'roles' => 'রোল',
];
?>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-2">
      <button class="hamburger" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
      </button>
      <h5 class="mb-0 fw-bold">
        <i class="fas fa-user-shield me-2 text-primary"></i> রোল ম্যানেজমেন্ট
      </h5>
    </div>
    <button class="btn btn-primary btn-sm"
      data-bs-toggle="modal" data-bs-target="#addRoleModal">
      <i class="fas fa-plus me-1"></i> নতুন রোল
    </button>
  </div>

  <?php if (isset($_GET['msg'])): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>
    <?php
    if ($_GET['msg'] == 'added') echo "রোল সফলভাবে যোগ হয়েছে!";
    if ($_GET['msg'] == 'updated') echo "রোল আপডেট হয়েছে!";
    if ($_GET['msg'] == 'deleted') echo "রোল ডিলিট হয়েছে!";
    ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <div class="row g-3">
    <?php while($role = mysqli_fetch_assoc($roles)):
      $perms = json_decode($role['permissions'], true) ?? [];
      $user_count = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT COUNT(*) as total FROM users WHERE role_id={$role['id']}"))['total'];
    ?>
    <div class="col-md-6 col-lg-4">
      <div class="card table-card h-100">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <div>
            <h6 class="fw-bold mb-0">
              <i class="fas fa-user-shield text-primary me-2"></i>
              <?= htmlspecialchars($role['name']) ?>
            </h6>
            <small class="text-muted"><?= $user_count ?>জন ইউজার</small>
          </div>
          <div class="d-flex gap-1">
            <button class="btn btn-sm btn-outline-primary"
              data-bs-toggle="modal"
              data-bs-target="#editModal<?= $role['id'] ?>">
              <i class="fas fa-edit"></i>
            </button>
            <?php if ($role['id'] != 1): ?>
            <a href="roles.php?delete=<?= $role['id'] ?>"
              class="btn btn-sm btn-outline-danger"
              onclick="return confirm('এই রোল ডিলিট করবেন?')">
              <i class="fas fa-trash"></i>
            </a>
            <?php endif; ?>
          </div>
        </div>
        <div class="card-body">
          <div class="row g-2">
            <?php foreach ($all_perms as $key => $label): ?>
            <div class="col-6">
              <?php if (isset($perms[$key]) && $perms[$key]): ?>
              <span class="badge bg-success w-100 text-start">
                <i class="fas fa-check me-1"></i><?= $label ?>
              </span>
              <?php else: ?>
              <span class="badge bg-light text-muted w-100 text-start">
                <i class="fas fa-times me-1"></i><?= $label ?>
              </span>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal<?= $role['id'] ?>" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form method="POST">
            <div class="modal-header">
              <h5 class="modal-title">রোল এডিট: <?= htmlspecialchars($role['name']) ?></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="id" value="<?= $role['id'] ?>">
              <div class="mb-3">
                <label class="form-label">রোলের নাম</label>
                <input type="text" name="name" class="form-control"
                  value="<?= htmlspecialchars($role['name']) ?>"
                  <?= $role['id'] == 1 ? 'readonly' : '' ?> required>
              </div>
              <label class="form-label fw-bold">পারমিশন সেট করুন:</label>
              <div class="row g-2">
                <?php foreach ($all_perms as $key => $label): ?>
                <div class="col-6">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox"
                      name="perm_<?= $key ?>" id="edit_<?= $role['id'] ?>_<?= $key ?>"
                      <?= (isset($perms[$key]) && $perms[$key]) ? 'checked' : '' ?>
                      <?= ($role['id'] == 1) ? 'disabled checked' : '' ?>>
                    <label class="form-check-label"
                      for="edit_<?= $role['id'] ?>_<?= $key ?>">
                      <?= $label ?>
                    </label>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <?php if ($role['id'] == 1): ?>
              <!-- Admin role এর hidden inputs -->
              <?php foreach ($all_perms as $key => $label): ?>
              <input type="hidden" name="perm_<?= $key ?>" value="1">
              <?php endforeach; ?>
              <?php endif; ?>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary"
                data-bs-dismiss="modal">বাতিল</button>
              <button type="submit" name="edit_role" class="btn btn-primary">
                আপডেট করুন
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>

  <?php include 'includes/footer.php'; ?>
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-plus me-2"></i>নতুন রোল তৈরি করুন
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">রোলের নাম</label>
            <input type="text" name="name" class="form-control"
              placeholder="যেমন: Cashier, Accountant" required>
          </div>
          <label class="form-label fw-bold">পারমিশন সেট করুন:</label>
          <div class="row g-2">
            <?php foreach ($all_perms as $key => $label): ?>
            <div class="col-6">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox"
                  name="perm_<?= $key ?>" id="add_<?= $key ?>">
                <label class="form-check-label" for="add_<?= $key ?>">
                  <?= $label ?>
                </label>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary"
            data-bs-dismiss="modal">বাতিল</button>
          <button type="submit" name="add_role" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>সেভ করুন
          </button>
        </div>
      </form>
    </div>
  </div>
</div>