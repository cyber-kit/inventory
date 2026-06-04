<?php
$base = '/~techcamp/inventory';
$current = basename($_SERVER['PHP_SELF']);

// Permission check function
function hasPermission($perm) {
    if (!isset($_SESSION['role_id'])) return true;
    if ($_SESSION['role'] == 'admin') return true;
    $perms = json_decode($_SESSION['permissions'] ?? '{}', true);
    return isset($perms[$perm]) && $perms[$perm] == 1;
}
?>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="sidebar" id="sidebar">
  <div class="brand">
    <span class="brand-text">
      <i class="fas fa-boxes me-2"></i> Inventory
    </span>
    <!-- Desktop Collapse Button -->
    <button class="collapse-btn d-none d-md-block" 
      onclick="toggleCollapse()" id="collapseBtn"
      title="Collapse/Expand">
      <i class="fas fa-bars"></i>
    </button>
    <!-- Mobile Close Button -->
    <button onclick="closeSidebar()" 
      class="collapse-btn d-md-none">
      <i class="fas fa-times"></i>
    </button>
  </div>

  <nav class="mt-2">
    <?php if (hasPermission('dashboard')): ?>
    <a href="<?= $base ?>/dashboard.php"
       class="nav-link <?= $current=='dashboard.php'?'active':'' ?>"
       title="ড্যাশবোর্ড">
      <i class="fas fa-tachometer-alt"></i>
      <span class="nav-text">ড্যাশবোর্ড</span>
    </a>
    <?php endif; ?>

    <?php if (hasPermission('categories')): ?>
    <a href="<?= $base ?>/categories.php"
       class="nav-link <?= $current=='categories.php'?'active':'' ?>"
       title="ক্যাটাগরি">
      <i class="fas fa-tags"></i>
      <span class="nav-text">ক্যাটাগরি</span>
    </a>
    <?php endif; ?>

    <?php if (hasPermission('products')): ?>
    <a href="<?= $base ?>/products.php"
       class="nav-link <?= $current=='products.php'?'active':'' ?>"
       title="প্রোডাক্ট">
      <i class="fas fa-box"></i>
      <span class="nav-text">প্রোডাক্ট</span>
    </a>
    <?php endif; ?>

    <?php if (hasPermission('stock_in')): ?>
    <a href="<?= $base ?>/stock_in.php"
       class="nav-link <?= $current=='stock_in.php'?'active':'' ?>"
       title="স্টক ইন">
      <i class="fas fa-arrow-circle-down text-success"></i>
      <span class="nav-text">স্টক ইন</span>
    </a>
    <?php endif; ?>

    <?php if (hasPermission('stock_out')): ?>
    <a href="<?= $base ?>/stock_out.php"
       class="nav-link <?= $current=='stock_out.php'?'active':'' ?>"
       title="স্টক আউট">
      <i class="fas fa-arrow-circle-up text-warning"></i>
      <span class="nav-text">স্টক আউট</span>
    </a>
    <?php endif; ?>

    <?php if (hasPermission('stock_adjust')): ?>
    <a href="<?= $base ?>/stock_adjust.php"
       class="nav-link <?= $current=='stock_adjust.php'?'active':'' ?>"
       title="স্টক সমন্বয়">
      <i class="fas fa-sliders-h text-info"></i>
      <span class="nav-text">স্টক সমন্বয়</span>
    </a>
    <?php endif; ?>

    <?php if (hasPermission('sales')): ?>
    <a href="<?= $base ?>/sales.php"
       class="nav-link <?= $current=='sales.php'?'active':'' ?>"
       title="বিক্রয়">
      <i class="fas fa-shopping-cart"></i>
      <span class="nav-text">বিক্রয়</span>
    </a>
    <?php endif; ?>

    <?php if (hasPermission('expenses')): ?>
    <a href="<?= $base ?>/expenses.php"
       class="nav-link <?= $current=='expenses.php'?'active':'' ?>"
       title="খরচ">
      <i class="fas fa-receipt text-danger"></i>
      <span class="nav-text">খরচ ট্র্যাকিং</span>
    </a>
    <?php endif; ?>

    <?php if (hasPermission('reports')): ?>
    <a href="<?= $base ?>/reports.php"
       class="nav-link <?= $current=='reports.php'?'active':'' ?>"
       title="রিপোর্ট">
      <i class="fas fa-chart-bar"></i>
      <span class="nav-text">রিপোর্ট</span>
    </a>
    <?php endif; ?>

    <hr style="border-color:rgba(255,255,255,0.1); margin:10px 20px;">

    <?php if (hasPermission('users')): ?>
    <a href="<?= $base ?>/users.php"
       class="nav-link <?= $current=='users.php'?'active':'' ?>"
       title="ইউজার">
      <i class="fas fa-users"></i>
      <span class="nav-text">ইউজার</span>
    </a>
    <?php endif; ?>

    <?php if (hasPermission('roles')): ?>
    <a href="<?= $base ?>/roles.php"
       class="nav-link <?= $current=='roles.php'?'active':'' ?>"
       title="রোল">
      <i class="fas fa-user-shield"></i>
      <span class="nav-text">রোল ম্যানেজমেন্ট</span>
    </a>
    <?php endif; ?>

    <a href="<?= $base ?>/change_password.php"
       class="nav-link <?= $current=='change_password.php'?'active':'' ?>"
       title="পাসওয়ার্ড পরিবর্তন">
      <i class="fas fa-key"></i>
      <span class="nav-text">পাসওয়ার্ড পরিবর্তন</span>
    </a>

    <a href="<?= $base ?>/logout.php"
       class="nav-link" style="color:#ff6b6b;"
       title="লগআউট">
      <i class="fas fa-sign-out-alt"></i>
      <span class="nav-text">লগআউট</span>
    </a>
  </nav>
</div>