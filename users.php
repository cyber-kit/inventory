<?php
session_start();
require_once 'config/database.php';

// Only admin can access
if ($_SESSION['role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Update last_active
mysqli_query($conn, "UPDATE users SET last_active=NOW() WHERE id={$_SESSION['user_id']}");

// User Add
if (isset($_POST['add_user'])) {
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
    $role_id = (int)$_POST['role_id'];

    // Role info নিয়ে আসো
    $role_data = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM roles WHERE id=$role_id"));
    $role_name = $role_data ? $role_data['name'] : 'staff';
    $permissions = $role_data ? $role_data['permissions'] : '{}';
    $permissions_safe = mysqli_real_escape_string($conn, $permissions);

    $check = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id FROM users WHERE username='$username'"));

    if ($check) {
        $error = "এই username টি আগেই ব্যবহার হয়েছে!";
    } else {
        mysqli_query($conn, "INSERT INTO users 
            (full_name, username, password, role, role_id, permissions, is_active) 
            VALUES ('$full_name', '$username', '$password', '$role_name', 
                    $role_id, '$permissions_safe', 1)");
        header("Location: users.php?msg=added");
        exit();
    }
}

// User Edit
if (isset($_POST['edit_user'])) {
    $id = (int)$_POST['id'];
    $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $role_id = (int)$_POST['role_id'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $role_data = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM roles WHERE id=$role_id"));
    $role_name = $role_data ? $role_data['name'] : 'staff';
    $permissions = $role_data ? $role_data['permissions'] : '{}';
    $permissions_safe = mysqli_real_escape_string($conn, $permissions);

    mysqli_query($conn, "UPDATE users SET 
        full_name='$full_name', role='$role_name', 
        role_id=$role_id, permissions='$permissions_safe',
        is_active=$is_active
        WHERE id=$id");

    if (!empty($_POST['new_password'])) {
        $new_pass = password_hash(trim($_POST['new_password']), PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password='$new_pass' WHERE id=$id");
    }

    header("Location: users.php?msg=updated");
    exit();
}

// User Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id != $_SESSION['user_id']) {
        mysqli_query($conn, "DELETE FROM users WHERE id=$id");
        header("Location: users.php?msg=deleted");
        exit();
    }
}

// Toggle Active
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    if ($id != $_SESSION['user_id']) {
        mysqli_query($conn, "UPDATE users SET 
            is_active = CASE WHEN is_active=1 THEN 0 ELSE 1 END 
            WHERE id=$id");
        header("Location: users.php?msg=updated");
        exit();
    }
}

require_once 'includes/header.php';

$users = mysqli_query($conn, "
    SELECT u.*, r.name as role_name 
    FROM users u 
    LEFT JOIN roles r ON u.role_id = r.id 
    ORDER BY u.created_at DESC
");

$roles = mysqli_query($conn, "SELECT * FROM roles ORDER BY id ASC");
$roles_array = [];
while ($r = mysqli_fetch_assoc($roles)) {
    $roles_array[] = $r;
}

// Online threshold: 5 minutes
$online_threshold = date('Y-m-d H:i:s', strtotime('-5 minutes'));
?>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-2">
      <button class="hamburger" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
      </button>
      <h5 class="mb-0 fw-bold">
        <i class="fas fa-users me-2 text-primary"></i> ইউজার ম্যানেজমেন্ট
      </h5>
    </div>
    <button class="btn btn-primary btn-sm"
      data-bs-toggle="modal" data-bs-target="#addModal">
      <i class="fas fa-plus me-1"></i> নতুন ইউজার
    </button>
  </div>

  <?php if (isset($error)): ?>
  <div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-times-circle me-2"></i><?= $error ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <?php if (isset($_GET['msg'])): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>
    <?php
    if ($_GET['msg'] == 'added') echo "ইউজার সফলভাবে যোগ হয়েছে!";
    if ($_GET['msg'] == 'updated') echo "ইউজার আপডেট হয়েছে!";
    if ($_GET['msg'] == 'deleted') echo "ইউজার ডিলিট হয়েছে!";
    ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <div class="card table-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>নাম</th>
              <th>Username</th>
              <th>রোল</th>
              <th>অবস্থা</th>
              <th>সর্বশেষ সক্রিয়</th>
              <th>অ্যাকাউন্ট</th>
              <th>অ্যাকশন</th>
            </tr>
          </thead>
          <tbody>
            <?php $i=1; while($row = mysqli_fetch_assoc($users)):
              $is_online = $row['last_active'] && $row['last_active'] > $online_threshold;
            ?>
            <tr>
              <td><?= $i++ ?></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="position-relative">
                    <div class="rounded-circle text-white d-flex align-items-center 
                      justify-content-center fw-bold"
                      style="width:38px;height:38px;font-size:1rem;
                      background:<?= $is_online ? '#27ae60' : '#95a5a6' ?>">
                      <?= strtoupper(substr($row['full_name'], 0, 1)) ?>
                    </div>
                    <!-- Online indicator -->
                    <span class="position-absolute bottom-0 end-0 rounded-circle border border-white"
                      style="width:10px;height:10px;
                      background:<?= $is_online ? '#27ae60' : '#95a5a6' ?>">
                    </span>
                  </div>
                  <div>
                    <div class="fw-bold"><?= htmlspecialchars($row['full_name']) ?></div>
                    <?php if ($row['id'] == $_SESSION['user_id']): ?>
                    <small class="text-primary">(আপনি)</small>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td><code><?= htmlspecialchars($row['username']) ?></code></td>
              <td>
                <span class="badge <?= $row['role']=='admin'?'bg-danger':'bg-info' ?>">
                  <?= htmlspecialchars($row['role_name'] ?? $row['role']) ?>
                </span>
              </td>
              <td>
                <?php if ($is_online): ?>
                <span class="badge bg-success">
                  <i class="fas fa-circle me-1" style="font-size:0.5rem"></i>
                  অনলাইন
                </span>
                <?php else: ?>
                <span class="badge bg-secondary">
                  <i class="fas fa-circle me-1" style="font-size:0.5rem"></i>
                  অফলাইন
                </span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($row['last_active']): ?>
                <small class="text-muted">
                  <?php
                  $diff = time() - strtotime($row['last_active']);
                  if ($diff < 60) echo "এইমাত্র";
                  elseif ($diff < 3600) echo round($diff/60) . " মিনিট আগে";
                  elseif ($diff < 86400) echo round($diff/3600) . " ঘন্টা আগে";
                  else echo date('d M Y, h:i A', strtotime($row['last_active']));
                  ?>
                </small>
                <?php else: ?>
                <small class="text-muted">কখনো লগিন করেনি</small>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($row['is_active']): ?>
                <span class="badge bg-success">সক্রিয়</span>
                <?php else: ?>
                <span class="badge bg-danger">নিষ্ক্রিয়</span>
                <?php endif; ?>
              </td>
              <td>
                <div class="d-flex gap-1 flex-wrap">
                  <button class="btn btn-sm btn-outline-primary"
                    data-bs-toggle="modal"
                    data-bs-target="#editModal<?= $row['id'] ?>">
                    <i class="fas fa-edit"></i>
                  </button>
                  <?php if ($row['id'] != $_SESSION['user_id']): ?>
                  <a href="users.php?toggle=<?= $row['id'] ?>"
                    class="btn btn-sm <?= $row['is_active']?'btn-outline-warning':'btn-outline-success' ?>"
                    title="<?= $row['is_active']?'নিষ্ক্রিয় করুন':'সক্রিয় করুন' ?>">
                    <i class="fas fa-<?= $row['is_active']?'ban':'check' ?>"></i>
                  </a>
                  <a href="users.php?delete=<?= $row['id'] ?>"
                    class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('এই ইউজার ডিলিট করবেন?')">
                    <i class="fas fa-trash"></i>
                  </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>

            <!-- Edit Modal -->
            <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <form method="POST">
                    <div class="modal-header">
                      <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>ইউজার এডিট
                      </h5>
                      <button type="button" class="btn-close"
                        data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <input type="hidden" name="id" value="<?= $row['id'] ?>">
                      <div class="mb-3">
                        <label class="form-label">পূর্ণ নাম</label>
                        <input type="text" name="full_name" class="form-control"
                          value="<?= htmlspecialchars($row['full_name']) ?>" required>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control"
                          value="<?= htmlspecialchars($row['username']) ?>" disabled>
                        <small class="text-muted">Username পরিবর্তন করা যাবে না</small>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">রোল</label>
                        <select name="role_id" class="form-select">
                          <?php foreach ($roles_array as $r): ?>
                          <option value="<?= $r['id'] ?>"
                            <?= $r['id'] == $row['role_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r['name']) ?>
                          </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label class="form-label">নতুন পাসওয়ার্ড (ঐচ্ছিক)</label>
                        <input type="password" name="new_password" class="form-control"
                          placeholder="পরিবর্তন না করলে খালি রাখুন">
                      </div>
                      <?php if ($row['id'] != $_SESSION['user_id']): ?>
                      <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox"
                          name="is_active" id="active<?= $row['id'] ?>"
                          <?= $row['is_active'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="active<?= $row['id'] ?>">
                          অ্যাকাউন্ট সক্রিয়
                        </label>
                      </div>
                      <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">বাতিল</button>
                      <button type="submit" name="edit_user" class="btn btn-primary">
                        আপডেট করুন
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-user-plus me-2"></i>নতুন ইউজার যোগ করুন
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">পূর্ণ নাম</label>
            <input type="text" name="full_name" class="form-control"
              placeholder="যেমন: রহিম উদ্দিন" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control"
              placeholder="যেমন: rahim123" required>
          </div>
          <div class="mb-3">
            <label class="form-label">পাসওয়ার্ড</label>
            <input type="password" name="password" class="form-control"
              placeholder="কমপক্ষে ৬ অক্ষর" required>
          </div>
          <div class="mb-3">
            <label class="form-label">রোল নির্বাচন করুন</label>
            <select name="role_id" class="form-select" required>
              <?php foreach ($roles_array as $r): ?>
              <option value="<?= $r['id'] ?>">
                <?= htmlspecialchars($r['name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary"
            data-bs-dismiss="modal">বাতিল</button>
          <button type="submit" name="add_user" class="btn btn-primary">
            <i class="fas fa-save me-1"></i> সেভ করুন
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Auto refresh every 60 seconds for activity status
setTimeout(function() {
  window.location.reload();
}, 60000);
</script>