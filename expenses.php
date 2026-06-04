<?php
session_start();
require_once 'config/database.php';

// Add Expense
if (isset($_POST['add_expense'])) {
    $category_id = (int)$_POST['category_id'];
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $amount = (float)$_POST['amount'];
    $expense_date = mysqli_real_escape_string($conn, $_POST['expense_date']);
    $added_by = $_SESSION['user_id'];

    mysqli_query($conn, "INSERT INTO expenses 
        (category_id, description, amount, expense_date, added_by) 
        VALUES ($category_id, '$description', $amount, '$expense_date', $added_by)");

    header("Location: expenses.php?msg=added");
    exit();
}

// Add Expense Category
if (isset($_POST['add_category'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    if (!empty($name)) {
        mysqli_query($conn, "INSERT INTO expense_categories (name) VALUES ('$name')");
        header("Location: expenses.php?msg=cat_added");
        exit();
    }
}

// Delete Expense
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM expenses WHERE id=$id");
    header("Location: expenses.php?msg=deleted");
    exit();
}

// Delete Category
if (isset($_GET['delete_cat'])) {
    $id = (int)$_GET['delete_cat'];
    mysqli_query($conn, "DELETE FROM expense_categories WHERE id=$id");
    header("Location: expenses.php?msg=cat_deleted");
    exit();
}

require_once 'includes/header.php';

// Filter
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$cat_filter = (int)($_GET['cat_filter'] ?? 0);

$conditions = ["DATE(e.expense_date) BETWEEN '$date_from' AND '$date_to'"];
if ($cat_filter > 0) $conditions[] = "e.category_id = $cat_filter";
$where = "WHERE " . implode(" AND ", $conditions);

// Get expenses
$expenses = mysqli_query($conn, "
    SELECT e.*, ec.name as category_name, u.full_name as added_by_name
    FROM expenses e
    LEFT JOIN expense_categories ec ON e.category_id = ec.id
    LEFT JOIN users u ON e.added_by = u.id
    $where
    ORDER BY e.expense_date DESC, e.created_at DESC
");

// Get categories
$categories = mysqli_query($conn, "SELECT * FROM expense_categories ORDER BY name ASC");
$cat_array = [];
while ($c = mysqli_fetch_assoc($categories)) {
    $cat_array[] = $c;
}

// Summary
$summary = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        SUM(e.amount) as total,
        COUNT(*) as count
    FROM expenses e
    $where
"));

// Category wise summary
$cat_summary = mysqli_query($conn, "
    SELECT 
        ec.name as category_name,
        SUM(e.amount) as total,
        COUNT(*) as count
    FROM expenses e
    LEFT JOIN expense_categories ec ON e.category_id = ec.id
    $where
    GROUP BY e.category_id
    ORDER BY total DESC
");
?>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-2">
      <button class="hamburger" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
      </button>
      <h5 class="mb-0 fw-bold">
        <i class="fas fa-receipt me-2 text-danger"></i> খরচ ট্র্যাকিং
      </h5>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-primary btn-sm"
        data-bs-toggle="modal" data-bs-target="#addCatModal">
        <i class="fas fa-plus me-1"></i> নতুন ক্যাটাগরি
      </button>
      <button class="btn btn-danger btn-sm"
        data-bs-toggle="modal" data-bs-target="#addExpenseModal">
        <i class="fas fa-plus me-1"></i> নতুন খরচ
      </button>
    </div>
  </div>

  <?php if (isset($_GET['msg'])): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>
    <?php
    if ($_GET['msg'] == 'added') echo "খরচ সফলভাবে যোগ হয়েছে!";
    if ($_GET['msg'] == 'deleted') echo "খরচ ডিলিট হয়েছে!";
    if ($_GET['msg'] == 'cat_added') echo "ক্যাটাগরি যোগ হয়েছে!";
    if ($_GET['msg'] == 'cat_deleted') echo "ক্যাটাগরি ডিলিট হয়েছে!";
    ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <!-- Filter -->
  <div class="card table-card mb-3 p-3">
    <form method="GET">
      <div class="row g-2 align-items-end">
        <div class="col-md-3 col-6">
          <label class="form-label small">শুরুর তারিখ</label>
          <input type="date" name="date_from" class="form-control form-control-sm"
            value="<?= $date_from ?>">
        </div>
        <div class="col-md-3 col-6">
          <label class="form-label small">শেষ তারিখ</label>
          <input type="date" name="date_to" class="form-control form-control-sm"
            value="<?= $date_to ?>">
        </div>
        <div class="col-md-3 col-8">
          <label class="form-label small">ক্যাটাগরি</label>
          <select name="cat_filter" class="form-select form-select-sm">
            <option value="0">সব ক্যাটাগরি</option>
            <?php foreach ($cat_array as $c): ?>
            <option value="<?= $c['id'] ?>" 
              <?= $cat_filter == $c['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-1 col-2">
          <button type="submit" class="btn btn-primary btn-sm w-100">
            <i class="fas fa-search"></i>
          </button>
        </div>
        <div class="col-md-2 col-2">
          <a href="expenses.php" class="btn btn-secondary btn-sm w-100">
            <i class="fas fa-times"></i>
          </a>
        </div>
      </div>
    </form>
  </div>

  <!-- Summary Cards -->
  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <div class="card border-0 p-3 text-center"
        style="background:linear-gradient(135deg,#e74c3c,#c0392b);border-radius:12px;">
        <div class="text-white">
          <div class="small opacity-75">মোট খরচ</div>
          <h3 class="mb-0 fw-bold">
            ৳<?= number_format($summary['total'] ?? 0, 2) ?>
          </h3>
          <small class="opacity-75"><?= $summary['count'] ?>টি খরচ</small>
        </div>
      </div>
    </div>
    <div class="col-md-8">
      <div class="card table-card p-3">
        <h6 class="fw-bold mb-2">ক্যাটাগরি অনুযায়ী খরচ</h6>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead class="table-light">
              <tr>
                <th>ক্যাটাগরি</th>
                <th>সংখ্যা</th>
                <th>মোট খরচ</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $num_cs = mysqli_num_rows($cat_summary);
              while($cs = mysqli_fetch_assoc($cat_summary)): ?>
              <tr>
                <td><?= htmlspecialchars($cs['category_name'] ?? 'অন্যান্য') ?></td>
                <td><?= $cs['count'] ?></td>
                <td class="fw-bold text-danger">
                  ৳<?= number_format($cs['total'], 2) ?>
                </td>
              </tr>
              <?php endwhile; ?>
              <?php if ($num_cs == 0): ?>
              <tr>
                <td colspan="3" class="text-center text-muted">কোনো খরচ নেই</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Action Buttons -->
  <div class="d-flex gap-2 mb-3 flex-wrap">
    <button onclick="window.print()" class="btn btn-sm btn-outline-dark">
      <i class="fas fa-print me-1"></i>প্রিন্ট
    </button>
    <button onclick="downloadCSV('expenseTable', 'expenses')" 
      class="btn btn-sm btn-outline-success">
      <i class="fas fa-download me-1"></i>CSV ডাউনলোড
    </button>
  </div>

  <!-- Expenses Table -->
  <div class="card table-card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0" id="expenseTable">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>তারিখ</th>
              <th>ক্যাটাগরি</th>
              <th>বিবরণ</th>
              <th>পরিমাণ</th>
              <th>যোগ করেছেন</th>
              <th class="no-print">অ্যাকশন</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $i = 1;
            $num = mysqli_num_rows($expenses);
            while($row = mysqli_fetch_assoc($expenses)): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><?= date('d M Y', strtotime($row['expense_date'])) ?></td>
              <td>
                <span class="badge bg-secondary">
                  <?= htmlspecialchars($row['category_name'] ?? 'অন্যান্য') ?>
                </span>
              </td>
              <td><?= htmlspecialchars($row['description'] ?? '-') ?></td>
              <td>
                <strong class="text-danger">
                  ৳<?= number_format($row['amount'], 2) ?>
                </strong>
              </td>
              <td><?= htmlspecialchars($row['added_by_name'] ?? '-') ?></td>
              <td class="no-print">
                <a href="expenses.php?delete=<?= $row['id'] ?>"
                  class="btn btn-sm btn-outline-danger"
                  onclick="return confirm('এই খরচ ডিলিট করবেন?')">
                  <i class="fas fa-trash"></i>
                </a>
              </td>
            </tr>
            <?php endwhile; ?>
            <?php if ($num == 0): ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">
                কোনো খরচের রেকর্ড নেই
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
          <?php if ($num > 0): ?>
          <tfoot class="table-light">
            <tr>
              <td colspan="4" class="text-end fw-bold">মোট:</td>
              <td class="fw-bold text-danger">
                ৳<?= number_format($summary['total'] ?? 0, 2) ?>
              </td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-plus me-2"></i>নতুন খরচ যোগ করুন
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">খরচের ক্যাটাগরি</label>
            <select name="category_id" class="form-select" required>
              <option value="">-- ক্যাটাগরি বেছে নিন --</option>
              <?php foreach ($cat_array as $c): ?>
              <option value="<?= $c['id'] ?>">
                <?= htmlspecialchars($c['name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">বিবরণ</label>
            <textarea name="description" class="form-control" rows="2"
              placeholder="খরচের বিস্তারিত বিবরণ..."></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">পরিমাণ (৳)</label>
            <input type="number" name="amount" class="form-control"
              placeholder="0.00" step="0.01" min="0.01" required>
          </div>
          <div class="mb-3">
            <label class="form-label">তারিখ</label>
            <input type="date" name="expense_date" class="form-control"
              value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary"
            data-bs-dismiss="modal">বাতিল</button>
          <button type="submit" name="add_expense" class="btn btn-danger">
            <i class="fas fa-save me-1"></i>সেভ করুন
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCatModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">নতুন ক্যাটাগরি</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">ক্যাটাগরির নাম</label>
            <input type="text" name="name" class="form-control"
              placeholder="যেমন: অফিস খরচ" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary"
            data-bs-dismiss="modal">বাতিল</button>
          <button type="submit" name="add_category" class="btn btn-primary">
            <i class="fas fa-save me-1"></i>সেভ
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
@media print {
  .sidebar, .hamburger, .sidebar-overlay,
  .topbar button, .no-print,
  .modal, .card.table-card.mb-3.p-3 { display: none !important; }
  .main-content { margin-left: 0 !important; }
  .card { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>