<?php
session_start();
require_once 'config/database.php';

// Stock Adjust
if (isset($_POST['adjust_stock'])) {
    $product_id = (int)$_POST['product_id'];
    $new_qty = (int)$_POST['new_qty'];
    $note = mysqli_real_escape_string($conn, trim($_POST['note']));

    if ($product_id > 0 && $new_qty >= 0) {
        // বর্তমান স্টক নিয়ে আসো
        $current = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT stock_qty, name FROM products WHERE id=$product_id"));
        
        $old_qty = $current['stock_qty'];
        $diff = $new_qty - $old_qty;

        // products table আপডেট করো
        mysqli_query($conn, "UPDATE products 
            SET stock_qty=$new_qty WHERE id=$product_id");

        // stock_movements এ record করো
        $movement_note = "স্টক সমন্বয়: $old_qty থেকে $new_qty তে পরিবর্তন। কারণ: $note";
        $movement_note = mysqli_real_escape_string($conn, $movement_note);
        mysqli_query($conn, "INSERT INTO stock_movements 
            (product_id, movement_type, quantity, note) 
            VALUES ($product_id, 'adjust', ABS($diff), '$movement_note')");

        header("Location: stock_adjust.php?msg=success");
        exit();
    }
}

require_once 'includes/header.php';

// Get all products
$products = mysqli_query($conn, "
    SELECT p.*, c.name as category_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.name ASC
");
$prod_array = [];
while ($p = mysqli_fetch_assoc($products)) {
    $prod_array[] = $p;
}

// Get adjustment history
$history = mysqli_query($conn, "
    SELECT sm.*, p.name as product_name, p.unit
    FROM stock_movements sm
    LEFT JOIN products p ON sm.product_id = p.id
    WHERE sm.movement_type = 'adjust'
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
        <i class="fas fa-sliders-h me-2 text-primary"></i> স্টক সমন্বয়
      </h5>
    </div>
  </div>

  <?php if (isset($_GET['msg'])): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>
    স্টক সফলভাবে সমন্বয় হয়েছে!
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <div class="row g-3">
    <!-- Adjust Form -->
    <div class="col-lg-4 col-md-5">
      <div class="card table-card p-4">
        <h6 class="fw-bold mb-3">
          <i class="fas fa-edit text-primary me-2"></i>স্টক সমন্বয় করুন
        </h6>
        <form method="POST">
          <div class="mb-3">
            <label class="form-label">প্রোডাক্ট নির্বাচন করুন</label>
            <select name="product_id" class="form-select" 
              onchange="showCurrentStock(this)" required>
              <option value="">-- প্রোডাক্ট বেছে নিন --</option>
              <?php foreach ($prod_array as $p): ?>
              <option value="<?= $p['id'] ?>"
                data-stock="<?= $p['stock_qty'] ?>"
                data-unit="<?= $p['unit'] ?>"
                data-category="<?= htmlspecialchars($p['category_name'] ?? 'N/A') ?>">
                <?= htmlspecialchars($p['name']) ?>
                (<?= $p['stock_qty'] ?> <?= $p['unit'] ?>)
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Current Stock Info -->
          <div id="stockInfo" style="display:none;" class="mb-3">
            <div class="card bg-light border-0 p-3">
              <div class="row text-center">
                <div class="col-6">
                  <div class="text-muted small">বর্তমান স্টক</div>
                  <div class="fw-bold fs-4 text-primary" id="currentStockVal">0</div>
                  <div class="text-muted small" id="currentUnit"></div>
                </div>
                <div class="col-6">
                  <div class="text-muted small">ক্যাটাগরি</div>
                  <div class="fw-bold" id="currentCategory">-</div>
                </div>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">নতুন স্টক পরিমাণ</label>
            <input type="number" name="new_qty" class="form-control"
              id="newQtyInput"
              placeholder="সঠিক পরিমাণ লিখুন" min="0" required
              oninput="showDiff()">
          </div>

          <!-- Difference Display -->
          <div id="diffInfo" style="display:none;" class="mb-3">
            <div id="diffBadge" class="text-center p-2 rounded"></div>
          </div>

          <div class="mb-3">
            <label class="form-label">কারণ / নোট</label>
            <textarea name="note" class="form-control" rows="3"
              placeholder="কেন স্টক সমন্বয় করা হচ্ছে?" required></textarea>
          </div>

          <div class="d-grid">
            <button type="submit" name="adjust_stock" class="btn btn-primary py-2">
              <i class="fas fa-check me-2"></i>স্টক সমন্বয় করুন
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Products List -->
    <div class="col-lg-8 col-md-7">
      <!-- Current Stock Status -->
      <div class="card table-card mb-3">
        <div class="card-header bg-white fw-bold py-3">
          <i class="fas fa-list text-primary me-2"></i>
          সব প্রোডাক্টের বর্তমান স্টক
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>প্রোডাক্ট</th>
                  <th>ক্যাটাগরি</th>
                  <th>বর্তমান স্টক</th>
                  <th>Alert স্তর</th>
                  <th>অবস্থা</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                $i=1;
                foreach ($prod_array as $p): ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                  <td><?= htmlspecialchars($p['category_name'] ?? '-') ?></td>
                  <td>
                    <span class="fw-bold">
                      <?= $p['stock_qty'] ?> <?= $p['unit'] ?>
                    </span>
                  </td>
                  <td><?= $p['alert_qty'] ?> <?= $p['unit'] ?></td>
                  <td>
                    <?php if ($p['stock_qty'] <= 0): ?>
                    <span class="badge bg-danger">স্টক নেই</span>
                    <?php elseif ($p['stock_qty'] <= $p['alert_qty']): ?>
                    <span class="badge bg-warning text-dark">কম স্টক</span>
                    <?php else: ?>
                    <span class="badge bg-success">স্বাভাবিক</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>

                <?php if (count($prod_array) == 0): ?>
                <tr>
                  <td colspan="6" class="text-center text-muted py-4">
                    কোনো প্রোডাক্ট নেই
                  </td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Adjustment History -->
      <div class="card table-card">
        <div class="card-header bg-white fw-bold py-3">
          <i class="fas fa-history text-warning me-2"></i>
          সমন্বয়ের ইতিহাস (সর্বশেষ ২০টি)
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
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
                <?php 
                $i=1;
                $num = mysqli_num_rows($history);
                while($row = mysqli_fetch_assoc($history)): ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td>
                    <strong><?= htmlspecialchars($row['product_name']) ?></strong>
                  </td>
                  <td>
                    <span class="badge bg-info text-dark">
                      <?= $row['quantity'] ?> <?= $row['unit'] ?>
                    </span>
                  </td>
                  <td>
                    <small><?= htmlspecialchars($row['note'] ?? '-') ?></small>
                  </td>
                  <td>
                    <small>
                      <?= date('d M Y, h:i A', strtotime($row['created_at'])) ?>
                    </small>
                  </td>
                </tr>
                <?php endwhile; ?>

                <?php if ($num == 0): ?>
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">
                    কোনো সমন্বয়ের ইতিহাস নেই
                  </td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>
</div>

<script>
var currentStock = 0;

function showCurrentStock(select) {
  var option = select.options[select.selectedIndex];
  var stock = option.getAttribute('data-stock');
  var unit = option.getAttribute('data-unit');
  var category = option.getAttribute('data-category');

  if (stock !== null && select.value != '') {
    currentStock = parseInt(stock);
    document.getElementById('currentStockVal').textContent = stock;
    document.getElementById('currentUnit').textContent = unit;
    document.getElementById('currentCategory').textContent = category;
    document.getElementById('stockInfo').style.display = 'block';
    document.getElementById('newQtyInput').value = stock;
    showDiff();
  } else {
    document.getElementById('stockInfo').style.display = 'none';
    document.getElementById('diffInfo').style.display = 'none';
    currentStock = 0;
  }
}

function showDiff() {
  var newQty = parseInt(document.getElementById('newQtyInput').value) || 0;
  var diff = newQty - currentStock;
  var diffDiv = document.getElementById('diffInfo');
  var diffBadge = document.getElementById('diffBadge');

  if (document.getElementById('stockInfo').style.display == 'block') {
    diffDiv.style.display = 'block';
    if (diff > 0) {
      diffBadge.className = 'text-center p-2 rounded bg-success text-white';
      diffBadge.innerHTML = '<i class="fas fa-arrow-up me-1"></i>' + 
        diff + ' বাড়বে (' + currentStock + ' → ' + newQty + ')';
    } else if (diff < 0) {
      diffBadge.className = 'text-center p-2 rounded bg-danger text-white';
      diffBadge.innerHTML = '<i class="fas fa-arrow-down me-1"></i>' + 
        Math.abs(diff) + ' কমবে (' + currentStock + ' → ' + newQty + ')';
    } else {
      diffBadge.className = 'text-center p-2 rounded bg-secondary text-white';
      diffBadge.innerHTML = '<i class="fas fa-equals me-1"></i>কোনো পরিবর্তন নেই';
    }
  }
}
</script>