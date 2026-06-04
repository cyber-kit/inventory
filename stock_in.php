<?php
session_start();
require_once 'config/database.php';

// Stock In Add
if (isset($_POST['add_stock_in'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $note = mysqli_real_escape_string($conn, trim($_POST['note']));

    if ($product_id > 0 && $quantity > 0) {
        // stock_movements এ record করো
        mysqli_query($conn, "INSERT INTO stock_movements 
            (product_id, movement_type, quantity, note) 
            VALUES ($product_id, 'in', $quantity, '$note')");

        // products table এ stock বাড়াও
        mysqli_query($conn, "UPDATE products 
            SET stock_qty = stock_qty + $quantity 
            WHERE id = $product_id");

        header("Location: stock_in.php?msg=added");
        exit();
    }
}

require_once 'includes/header.php';

// Get all products
$products = mysqli_query($conn, "SELECT * FROM products ORDER BY name ASC");
$prod_array = [];
while ($p = mysqli_fetch_assoc($products)) {
    $prod_array[] = $p;
}

// Get stock in history
$history = mysqli_query($conn, "
    SELECT sm.*, p.name as product_name, p.unit
    FROM stock_movements sm
    LEFT JOIN products p ON sm.product_id = p.id
    WHERE sm.movement_type = 'in'
    ORDER BY sm.created_at DESC
    LIMIT 20
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
        <i class="fas fa-arrow-circle-down me-2 text-success"></i> স্টক ইন
      </h5>
    </div>
  </div>

  <?php if (isset($_GET['msg'])): ?>
  <div class="alert alert-success alert-dismissible fade show">
    ✅ স্টক সফলভাবে যোগ হয়েছে!
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <div class="row g-3">
    <!-- Stock In Form -->
    <div class="col-md-4">
      <div class="card table-card p-4">
        <h6 class="fw-bold mb-3">
          <i class="fas fa-plus-circle text-success me-2"></i>নতুন স্টক যোগ করুন
        </h6>
        <form method="POST">
          <div class="mb-3">
            <label class="form-label">প্রোডাক্ট নির্বাচন করুন</label>
            <select name="product_id" class="form-select" required>
              <option value="">-- প্রোডাক্ট বেছে নিন --</option>
              <?php foreach ($prod_array as $p): ?>
              <option value="<?= $p['id'] ?>">
                <?= htmlspecialchars($p['name']) ?> 
                (বর্তমান স্টক: <?= $p['stock_qty'] ?> <?= $p['unit'] ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">পরিমাণ</label>
            <input type="number" name="quantity" class="form-control" 
              placeholder="কত পরিমাণ যোগ হবে?" min="1" required>
          </div>
          <div class="mb-3">
            <label class="form-label">নোট (ঐচ্ছিক)</label>
            <textarea name="note" class="form-control" rows="3" 
              placeholder="যেমন: সরবরাহকারীর নাম, তারিখ ইত্যাদি..."></textarea>
          </div>
          <button type="submit" name="add_stock_in" class="btn btn-success w-100">
            <i class="fas fa-plus me-1"></i> স্টক যোগ করুন
          </button>
        </form>
      </div>
    </div>

    <!-- Stock In History -->
    <div class="col-md-8">
      <div class="card table-card">
        <div class="card-header bg-white fw-bold py-3">
          <i class="fas fa-history text-success me-2"></i> স্টক ইন ইতিহাস (সর্বশেষ ২০টি)
        </div>
        <div class="card-body p-0">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>প্রোডাক্ট</th>
                <th>পরিমাণ</th>
                <th>নোট</th>
                <th>তারিখ</th>
              </tr>
            </thead>
            <tbody>
              <?php $i=1; 
              $num = mysqli_num_rows($history);
              while($row = mysqli_fetch_assoc($history)): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><strong><?= htmlspecialchars($row['product_name']) ?></strong></td>
                <td>
                  <span class="badge bg-success fs-6">
                    +<?= $row['quantity'] ?> <?= $row['unit'] ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($row['note'] ?? '-') ?></td>
                <td><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
              </tr>
              <?php endwhile; ?>

              <?php if ($num == 0): ?>
              <tr>
                <td colspan="5" class="text-center text-muted py-4">
                  কোনো স্টক ইন রেকর্ড নেই
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>
</div>