<?php
session_start();
require_once 'config/database.php';

// Stock Out Add
if (isset($_POST['add_stock_out'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $note = mysqli_real_escape_string($conn, trim($_POST['note']));

    if ($product_id > 0 && $quantity > 0) {
        // বর্তমান স্টক চেক করো
        $current = mysqli_fetch_assoc(mysqli_query($conn, 
            "SELECT stock_qty FROM products WHERE id=$product_id"));
        
        if ($current['stock_qty'] >= $quantity) {
            // stock_movements এ record করো
            mysqli_query($conn, "INSERT INTO stock_movements 
                (product_id, movement_type, quantity, note) 
                VALUES ($product_id, 'out', $quantity, '$note')");

            // products table এ stock কমাও
            mysqli_query($conn, "UPDATE products 
                SET stock_qty = stock_qty - $quantity 
                WHERE id = $product_id");

            header("Location: stock_out.php?msg=success");
            exit();
        } else {
            header("Location: stock_out.php?msg=error");
            exit();
        }
    }
}

require_once 'includes/header.php';

// Get all products
$products = mysqli_query($conn, "SELECT * FROM products ORDER BY name ASC");
$prod_array = [];
while ($p = mysqli_fetch_assoc($products)) {
    $prod_array[] = $p;
}

// Get stock out history
$history = mysqli_query($conn, "
    SELECT sm.*, p.name as product_name, p.unit
    FROM stock_movements sm
    LEFT JOIN products p ON sm.product_id = p.id
    WHERE sm.movement_type = 'out'
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
        <i class="fas fa-arrow-circle-up me-2 text-warning"></i> স্টক আউট
      </h5>
    </div>
  </div>

  <?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] == 'success'): ?>
    <div class="alert alert-success alert-dismissible fade show">
      ✅ স্টক সফলভাবে বের করা হয়েছে!
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php elseif ($_GET['msg'] == 'error'): ?>
    <div class="alert alert-danger alert-dismissible fade show">
      ❌ পর্যাপ্ত স্টক নেই! স্টক আউট করা সম্ভব হয়নি।
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="row g-3">
    <!-- Stock Out Form -->
    <div class="col-md-4">
      <div class="card table-card p-4">
        <h6 class="fw-bold mb-3">
          <i class="fas fa-minus-circle text-warning me-2"></i>স্টক বের করুন
        </h6>
        <form method="POST">
          <div class="mb-3">
            <label class="form-label">প্রোডাক্ট নির্বাচন করুন</label>
            <select name="product_id" class="form-select" required 
              onchange="updateStock(this)">
              <option value="">-- প্রোডাক্ট বেছে নিন --</option>
              <?php foreach ($prod_array as $p): ?>
              <option value="<?= $p['id'] ?>" 
                data-stock="<?= $p['stock_qty'] ?>"
                data-unit="<?= $p['unit'] ?>">
                <?= htmlspecialchars($p['name']) ?> 
                (স্টক: <?= $p['stock_qty'] ?> <?= $p['unit'] ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Current Stock Display -->
          <div class="mb-3" id="stockInfo" style="display:none;">
            <div class="alert alert-info py-2 mb-0">
              বর্তমান স্টক: <strong id="currentStock">0</strong> 
              <span id="currentUnit"></span>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">পরিমাণ</label>
            <input type="number" name="quantity" class="form-control" 
              placeholder="কত পরিমাণ বের হবে?" min="1" required>
          </div>
          <div class="mb-3">
            <label class="form-label">নোট (ঐচ্ছিক)</label>
            <textarea name="note" class="form-control" rows="3" 
              placeholder="যেমন: কারণ, ব্যবহারকারীর নাম ইত্যাদি..."></textarea>
          </div>
          <button type="submit" name="add_stock_out" class="btn btn-warning w-100 fw-bold">
            <i class="fas fa-minus me-1"></i> স্টক আউট করুন
          </button>
        </form>
      </div>
    </div>

    <!-- Stock Out History -->
    <div class="col-md-8">
      <div class="card table-card">
        <div class="card-header bg-white fw-bold py-3">
          <i class="fas fa-history text-warning me-2"></i> স্টক আউট ইতিহাস (সর্বশেষ ২০টি)
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
                  <span class="badge bg-warning text-dark fs-6">
                    -<?= $row['quantity'] ?> <?= $row['unit'] ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($row['note'] ?? '-') ?></td>
                <td><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
              </tr>
              <?php endwhile; ?>

              <?php if ($num == 0): ?>
              <tr>
                <td colspan="5" class="text-center text-muted py-4">
                  কোনো স্টক আউট রেকর্ড নেই
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

<script>
function updateStock(select) {
    var option = select.options[select.selectedIndex];
    var stock = option.getAttribute('data-stock');
    var unit = option.getAttribute('data-unit');
    
    if (stock !== null) {
        document.getElementById('currentStock').textContent = stock;
        document.getElementById('currentUnit').textContent = unit;
        document.getElementById('stockInfo').style.display = 'block';
    } else {
        document.getElementById('stockInfo').style.display = 'none';
    }
}
</script>