<?php
session_start();
require_once 'config/database.php';

// Product Add
if (isset($_POST['add_product'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $sku = mysqli_real_escape_string($conn, trim($_POST['sku']));
    $category_id = (int)$_POST['category_id'];
    $purchase_price = (float)$_POST['purchase_price'];
    $sale_price = (float)$_POST['sale_price'];
    $stock_qty = (int)$_POST['stock_qty'];
    $alert_qty = (int)$_POST['alert_qty'];
    $unit = mysqli_real_escape_string($conn, trim($_POST['unit']));

    mysqli_query($conn, "INSERT INTO products 
        (name, sku, category_id, purchase_price, sale_price, stock_qty, alert_qty, unit) 
        VALUES 
        ('$name', '$sku', $category_id, $purchase_price, $sale_price, $stock_qty, $alert_qty, '$unit')");

    header("Location: products.php?msg=added");
    exit();
}

// Product Edit
if (isset($_POST['edit_product'])) {
    $id = (int)$_POST['id'];
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $sku = mysqli_real_escape_string($conn, trim($_POST['sku']));
    $category_id = (int)$_POST['category_id'];
    $purchase_price = (float)$_POST['purchase_price'];
    $sale_price = (float)$_POST['sale_price'];
    $alert_qty = (int)$_POST['alert_qty'];
    $unit = mysqli_real_escape_string($conn, trim($_POST['unit']));

    mysqli_query($conn, "UPDATE products SET 
        name='$name', sku='$sku', category_id=$category_id,
        purchase_price=$purchase_price, sale_price=$sale_price,
        alert_qty=$alert_qty, unit='$unit'
        WHERE id=$id");

    header("Location: products.php?msg=updated");
    exit();
}

// Product Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM products WHERE id=$id");
    header("Location: products.php?msg=deleted");
    exit();
}

require_once 'includes/header.php';

// Get categories for dropdown
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name ASC");
$cat_array = [];
while ($c = mysqli_fetch_assoc($categories)) {
    $cat_array[] = $c;
}

// Search
$search = '';
$where = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where = "WHERE p.name LIKE '%$search%' OR p.sku LIKE '%$search%'";
}

// Get all products
$products = mysqli_query($conn, "
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    $where
    ORDER BY p.created_at DESC
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
        <i class="fas fa-box me-2 text-primary"></i> প্রোডাক্ট ম্যানেজমেন্ট
      </h5>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
      <i class="fas fa-plus me-1"></i> নতুন প্রোডাক্ট
    </button>
  </div>

  <?php if (isset($_GET['msg'])): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <?php
    if ($_GET['msg'] == 'added') echo "✅ প্রোডাক্ট সফলভাবে যোগ হয়েছে!";
    if ($_GET['msg'] == 'updated') echo "✅ প্রোডাক্ট আপডেট হয়েছে!";
    if ($_GET['msg'] == 'deleted') echo "✅ প্রোডাক্ট ডিলিট হয়েছে!";
    ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <!-- Search Bar -->
  <div class="card table-card mb-3 p-3">
    <form method="GET" class="d-flex gap-2">
      <input type="text" name="search" class="form-control" 
        placeholder="প্রোডাক্ট নাম বা SKU দিয়ে খুঁজুন..." 
        value="<?= htmlspecialchars($search) ?>">
      <button type="submit" class="btn btn-primary">
        <i class="fas fa-search"></i>
      </button>
      <?php if ($search): ?>
      <a href="products.php" class="btn btn-secondary">
        <i class="fas fa-times"></i>
      </a>
      <?php endif; ?>
    </form>
  </div>

  <div class="card table-card">
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>প্রোডাক্ট নাম</th>
            <th>SKU</th>
            <th>ক্যাটাগরি</th>
            <th>ক্রয় মূল্য</th>
            <th>বিক্রয় মূল্য</th>
            <th>স্টক</th>
            <th>একক</th>
            <th>অ্যাকশন</th>
          </tr>
        </thead>
        <tbody>
          <?php $i=1; 
          $num_rows = mysqli_num_rows($products);
          while($row = mysqli_fetch_assoc($products)): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
            <td><code><?= htmlspecialchars($row['sku']) ?></code></td>
            <td><?= htmlspecialchars($row['category_name'] ?? '-') ?></td>
            <td>৳<?= number_format($row['purchase_price'], 2) ?></td>
            <td>৳<?= number_format($row['sale_price'], 2) ?></td>
            <td>
              <?php if ($row['stock_qty'] <= $row['alert_qty']): ?>
                <span class="badge bg-danger"><?= $row['stock_qty'] ?></span>
                <small class="text-danger">কম!</small>
              <?php else: ?>
                <span class="badge bg-success"><?= $row['stock_qty'] ?></span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($row['unit']) ?></td>
            <td>
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                data-bs-target="#editModal<?= $row['id'] ?>">
                <i class="fas fa-edit"></i>
              </button>
              <a href="products.php?delete=<?= $row['id'] ?>"
                class="btn btn-sm btn-outline-danger"
                onclick="return confirm('এই প্রোডাক্ট ডিলিট করবেন?')">
                <i class="fas fa-trash"></i>
              </a>
            </td>
          </tr>

          <!-- Edit Modal -->
          <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <form method="POST">
                  <div class="modal-header">
                    <h5 class="modal-title">প্রোডাক্ট এডিট</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label">প্রোডাক্ট নাম</label>
                        <input type="text" name="name" class="form-control"
                          value="<?= htmlspecialchars($row['name']) ?>" required>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">SKU কোড</label>
                        <input type="text" name="sku" class="form-control"
                          value="<?= htmlspecialchars($row['sku']) ?>" required>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">ক্যাটাগরি</label>
                        <select name="category_id" class="form-select">
                          <option value="0">-- ক্যাটাগরি নির্বাচন --</option>
                          <?php foreach ($cat_array as $cat): ?>
                          <option value="<?= $cat['id'] ?>" 
                            <?= $cat['id'] == $row['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                          </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">একক (Unit)</label>
                        <select name="unit" class="form-select">
                          <option <?= $row['unit']=='pcs'?'selected':'' ?>>pcs</option>
                          <option <?= $row['unit']=='kg'?'selected':'' ?>>kg</option>
                          <option <?= $row['unit']=='ltr'?'selected':'' ?>>ltr</option>
                          <option <?= $row['unit']=='box'?'selected':'' ?>>box</option>
                          <option <?= $row['unit']=='dozen'?'selected':'' ?>>dozen</option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">ক্রয় মূল্য (৳)</label>
                        <input type="number" name="purchase_price" class="form-control"
                          value="<?= $row['purchase_price'] ?>" step="0.01" required>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">বিক্রয় মূল্য (৳)</label>
                        <input type="number" name="sale_price" class="form-control"
                          value="<?= $row['sale_price'] ?>" step="0.01" required>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label">Alert পরিমাণ</label>
                        <input type="number" name="alert_qty" class="form-control"
                          value="<?= $row['alert_qty'] ?>" required>
                      </div>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                    <button type="submit" name="edit_product" class="btn btn-primary">আপডেট</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <?php endwhile; ?>

          <?php if ($num_rows == 0): ?>
          <tr>
            <td colspan="9" class="text-center text-muted py-4">
              কোনো প্রোডাক্ট নেই। নতুন যোগ করুন!
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-plus me-2"></i>নতুন প্রোডাক্ট</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">প্রোডাক্ট নাম</label>
              <input type="text" name="name" class="form-control" 
                placeholder="যেমন: Samsung TV" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">SKU কোড</label>
              <input type="text" name="sku" class="form-control" 
                placeholder="যেমন: SAM-TV-001" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">ক্যাটাগরি</label>
              <select name="category_id" class="form-select">
                <option value="0">-- ক্যাটাগরি নির্বাচন --</option>
                <?php foreach ($cat_array as $cat): ?>
                <option value="<?= $cat['id'] ?>">
                  <?= htmlspecialchars($cat['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">একক (Unit)</label>
              <select name="unit" class="form-select">
                <option value="pcs">pcs</option>
                <option value="kg">kg</option>
                <option value="ltr">ltr</option>
                <option value="box">box</option>
                <option value="dozen">dozen</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">ক্রয় মূল্য (৳)</label>
              <input type="number" name="purchase_price" class="form-control" 
                placeholder="0.00" step="0.01" value="0" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">বিক্রয় মূল্য (৳)</label>
              <input type="number" name="sale_price" class="form-control" 
                placeholder="0.00" step="0.01" value="0" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Alert পরিমাণ</label>
              <input type="number" name="alert_qty" class="form-control" 
                placeholder="5" value="5" required>
            </div>
            <div class="col-md-12">
              <label class="form-label">প্রাথমিক স্টক পরিমাণ</label>
              <input type="number" name="stock_qty" class="form-control" 
                placeholder="0" value="0" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
          <button type="submit" name="add_product" class="btn btn-primary">
            <i class="fas fa-save me-1"></i> সেভ করুন
          </button>
        </div>
      </form>
    </div>
  </div>
</div>