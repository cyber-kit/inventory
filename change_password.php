<?php
session_start();
require_once 'config/database.php';

if (isset($_POST['change_password'])) {
    $user_id = $_SESSION['user_id'];
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Current user data নিয়ে আসো
    $user = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT * FROM users WHERE id=$user_id"));

    if (!password_verify($current_password, $user['password'])) {
        $error = "বর্তমান পাসওয়ার্ড ভুল!";
    } elseif (strlen($new_password) < 6) {
        $error = "নতুন পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে!";
    } elseif ($new_password !== $confirm_password) {
        $error = "নতুন পাসওয়ার্ড দুটো মিলছে না!";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password='$hashed' WHERE id=$user_id");
        $success = "পাসওয়ার্ড সফলভাবে পরিবর্তন হয়েছে!";
    }
}

require_once 'includes/header.php';
?>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-2">
      <button class="hamburger" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
      </button>
      <h5 class="mb-0 fw-bold">
        <i class="fas fa-key me-2 text-primary"></i> পাসওয়ার্ড পরিবর্তন
      </h5>
    </div>
  </div>

  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card table-card p-4">
        <h6 class="fw-bold mb-4">
          <i class="fas fa-lock text-primary me-2"></i>
          পাসওয়ার্ড পরিবর্তন করুন
        </h6>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger">
          <i class="fas fa-times-circle me-2"></i><?= $error ?>
        </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle me-2"></i><?= $success ?>
        </div>
        <?php endif; ?>

        <form method="POST">
          <div class="mb-3">
            <label class="form-label">বর্তমান পাসওয়ার্ড</label>
            <div class="input-group">
              <input type="password" name="current_password" 
                class="form-control" id="currentPass"
                placeholder="বর্তমান পাসওয়ার্ড দিন" required>
              <button class="btn btn-outline-secondary" type="button"
                onclick="togglePass('currentPass')">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">নতুন পাসওয়ার্ড</label>
            <div class="input-group">
              <input type="password" name="new_password" 
                class="form-control" id="newPass"
                placeholder="নতুন পাসওয়ার্ড দিন (কমপক্ষে ৬ অক্ষর)" required>
              <button class="btn btn-outline-secondary" type="button"
                onclick="togglePass('newPass')">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label">নতুন পাসওয়ার্ড নিশ্চিত করুন</label>
            <div class="input-group">
              <input type="password" name="confirm_password" 
                class="form-control" id="confirmPass"
                placeholder="আবার নতুন পাসওয়ার্ড দিন" required>
              <button class="btn btn-outline-secondary" type="button"
                onclick="togglePass('confirmPass')">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>

          <div class="d-grid">
            <button type="submit" name="change_password" class="btn btn-primary py-2">
              <i class="fas fa-save me-2"></i> পাসওয়ার্ড পরিবর্তন করুন
            </button>
          </div>
        </form>

        <hr>
        <div class="text-center">
          <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>
            লগইন করা আছেন: <strong><?= $_SESSION['full_name'] ?></strong>
            (<?= $_SESSION['username'] ?>)
          </small>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>
</div>

<script>
function togglePass(id) {
  var input = document.getElementById(id);
  input.type = input.type === 'password' ? 'text' : 'password';
}
</script>