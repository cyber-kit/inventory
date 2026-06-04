<?php
session_start();
require_once 'config/database.php';

// Sale Add
if (isset($_POST['add_sale'])) {
    $customer_name = mysqli_real_escape_string($conn, trim($_POST['customer_name']));
    $product_ids = $_POST['product_id'];
    $quantities = $_POST['quantity'];
    $unit_prices = $_POST['unit_price'];

    $total_amount = 0;
    $paid_amount = (float)$_POST['paid_amount'];

    // Total calculate করো
    foreach ($quantities as $i => $qty) {
        if ($qty > 0 && $product_ids[$i] > 0) {
            $total_amount += $qty * $unit_prices[$i];
        }
    }

    $due_amount = $total_amount - $paid_amount;

    // Invoice number তৈরি করো
    $invoice_no = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);

    // Sales table এ insert করো
    mysqli_query($conn, "INSERT INTO sales 
        (invoice_no, customer_name, total_amount, paid_amount, due_amount) 
        VALUES ('$invoice_no', '$customer_name', $total_amount, $paid_amount, $due_amount)");

    $sale_id = mysqli_insert_id($conn);

    // Sale items insert করো
    foreach ($product_ids as $i => $product_id) {
        $product_id = (int)$product_id;
        $qty = (int)$quantities[$i];
        $unit_price = (float)$unit_prices[$i];
        $subtotal = $qty * $unit_price;

        if ($product_id > 0 && $qty > 0) {
            // sale_items এ insert
            mysqli_query($conn, "INSERT INTO sale_items 
                (sale_id, product_id, quantity, unit_price, subtotal) 
                VALUES ($sale_id, $product_id, $qty, $unit_price, $subtotal)");

            // stock কমাও
            mysqli_query($conn, "UPDATE products 
                SET stock_qty = stock_qty - $qty 
                WHERE id = $product_id");

            // stock_movements এ record
            mysqli_query($conn, "INSERT INTO stock_movements 
                (product_id, movement_type, quantity, note) 
                VALUES ($product_id, 'out', $qty, 'Sale: $invoice_no')");
        }
    }

    header("Location: sales.php?msg=added&invoice=$invoice_no");
    exit();
}

// Sale Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM sales WHERE id=$id");
    header("Location: sales.php?msg=deleted");
    exit();
}

require_once 'includes/header.php';

// Get all products for dropdown
$products = mysqli_query($conn, "SELECT * FROM products WHERE stock_qty > 0 ORDER BY name ASC");
$prod_array = [];
while ($p = mysqli_fetch_assoc($products)) {
    $prod_array[] = $p;
}

// Get all sales
$sales = mysqli_query($conn, "SELECT * FROM sales ORDER BY sale_date DESC");
?>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-2">
      <button class="hamburger" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
      </button>
      <h5 class="mb-0 fw-bold">
        <i class="fas fa-shopping-cart me-2 text-primary"></i> বিক্রয় ম্যানেজমেন্ট
      </h5>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSaleModal">
      <i class="fas fa-plus me-1"></i> নতুন বিক্রয়
    </button>
  </div>

  <?php if (isset($_GET['msg'])): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <?php
    if ($_GET['msg'] == 'added') echo "✅ বিক্রয় সফলভাবে রেকর্ড হয়েছে! Invoice: " . htmlspecialchars($_GET['invoice'] ?? '');
    if ($_GET['msg'] == 'deleted') echo "✅ বিক্রয় রেকর্ড ডিলিট হয়েছে!";
    ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <!-- Sales Table -->
  <div class="card table-card">
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Invoice</th>
            <th>কাস্টমার</th>
            <th>মোট টাকা</th>
            <th>পরিশোধ</th>
            <th>বাকি</th>
            <th>তারিখ</th>
            <th>অ্যাকশন</th>
          </tr>
        </thead>
        <tbody>
          <?php $i=1;
          $num = mysqli_num_rows($sales);
          while($row = mysqli_fetch_assoc($sales)): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><strong><?= htmlspecialchars($row['invoice_no']) ?></strong></td>
            <td><?= htmlspecialchars($row['customer_name'] ?? 'N/A') ?></td>
            <td>৳<?= number_format($row['total_amount'], 2) ?></td>
            <td>
              <span class="badge bg-success">
                ৳<?= number_format($row['paid_amount'], 2) ?>
              </span>
            </td>
            <td>
              <?php if ($row['due_amount'] > 0): ?>
              <span class="badge bg-danger">
                ৳<?= number_format($row['due_amount'], 2) ?>
              </span>
              <?php else: ?>
              <span class="badge bg-success">পরিশোধিত</span>
              <?php endif; ?>
            </td>
            <td><?= date('d M Y', strtotime($row['sale_date'])) ?></td>
            <td>
                <a href="invoice.php?id=<?= $row['id'] ?>"
                class="btn btn-sm btn-outline-success" target="_blank">
                <i class="fas fa-print"></i>
                </a>
            <button class="btn btn-sm btn-outline-info" data-bs-toggle="modal"
                 data-bs-target="#detailModal<?= $row['id'] ?>">
                  <i class="fas fa-eye"></i>
             </button>
             <a href="sales.php?delete=<?= $row['id'] ?>"
                 class="btn btn-sm btn-outline-danger"
              onclick="return confirm('এই বিক্রয় রেকর্ড ডিলিট করবেন?')">
                 <i class="fas fa-trash"></i>
            </a>
            </td>
          </tr>

          <!-- Detail Modal -->
          <?php
          $items = mysqli_query($conn, "
            SELECT si.*, p.name as product_name, p.unit
            FROM sale_items si
            LEFT JOIN products p ON si.product_id = p.id
            WHERE si.sale_id = {$row['id']}
          ");
          ?>
          <div class="modal fade" id="detailModal<?= $row['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">
                    <i class="fas fa-receipt me-2"></i>
                    Invoice: <?= htmlspecialchars($row['invoice_no']) ?>
                  </h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <div class="row mb-3">
                    <div class="col-md-6">
                      <p><strong>কাস্টমার:</strong> <?= htmlspecialchars($row['customer_name'] ?? 'N/A') ?></p>
                      <p><strong>তারিখ:</strong> <?= date('d M Y, h:i A', strtotime($row['sale_date'])) ?></p>
                    </div>
                    <div class="col-md-6 text-end">
                      <p><strong>মোট:</strong> ৳<?= number_format($row['total_amount'], 2) ?></p>
                      <p><strong>পরিশোধ:</strong> ৳<?= number_format($row['paid_amount'], 2) ?></p>
                      <p><strong>বাকি:</strong> ৳<?= number_format($row['due_amount'], 2) ?></p>
                    </div>
                  </div>
                  <table class="table table-bordered">
                    <thead class="table-light">
                      <tr>
                        <th>প্রোডাক্ট</th>
                        <th>পরিমাণ</th>
                        <th>একক মূল্য</th>
                        <th>সাবটোটাল</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php while($item = mysqli_fetch_assoc($items)): ?>
                      <tr>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td><?= $item['quantity'] ?> <?= $item['unit'] ?></td>
                        <td>৳<?= number_format($item['unit_price'], 2) ?></td>
                        <td>৳<?= number_format($item['subtotal'], 2) ?></td>
                      </tr>
                      <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                      <tr>
                        <td colspan="3" class="text-end fw-bold">মোট:</td>
                        <td class="fw-bold">৳<?= number_format($row['total_amount'], 2) ?></td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <?php endwhile; ?>

          <?php if ($num == 0): ?>
          <tr>
            <td colspan="8" class="text-center text-muted py-4">
              কোনো বিক্রয় রেকর্ড নেই
            </td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>
</div>

<!-- Add Sale Modal -->
<div class="modal fade" id="addSaleModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <form method="POST" id="saleForm">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-shopping-cart me-2"></i>নতুন বিক্রয়</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">কাস্টমারের নাম (ঐচ্ছিক)</label>
              <input type="text" name="customer_name" class="form-control" 
                placeholder="কাস্টমারের নাম লিখুন...">
            </div>
            <div class="col-md-6">
              <label class="form-label">পরিশোধিত টাকা (৳)</label>
              <input type="number" name="paid_amount" class="form-control" 
                placeholder="0.00" step="0.01" value="0" id="paidAmount"
                oninput="calculateDue()">
            </div>
          </div>

          <hr>
          <h6 class="fw-bold mb-3">প্রোডাক্ট যোগ করুন</h6>

          <div id="productRows">
            <!-- Product Row 1 -->
            <div class="row g-2 mb-2 product-row">
              <div class="col-md-5">
                <select name="product_id[]" class="form-select product-select" 
                  onchange="setPrice(this)" required>
                  <option value="">-- প্রোডাক্ট বেছে নিন --</option>
                  <?php foreach ($prod_array as $p): ?>
                  <option value="<?= $p['id'] ?>" 
                    data-price="<?= $p['sale_price'] ?>"
                    data-stock="<?= $p['stock_qty'] ?>">
                    <?= htmlspecialchars($p['name']) ?> (স্টক: <?= $p['stock_qty'] ?>)
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-2">
                <input type="number" name="quantity[]" class="form-control qty-input" 
                  placeholder="পরিমাণ" min="1" value="1" oninput="calculateTotal()" required>
              </div>
              <div class="col-md-3">
                <input type="number" name="unit_price[]" class="form-control price-input" 
                  placeholder="মূল্য" step="0.01" value="0" oninput="calculateTotal()" required>
              </div>
              <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger w-100" 
                  onclick="removeRow(this)">
                  <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
          </div>

          <button type="button" class="btn btn-outline-success btn-sm mb-3" 
            onclick="addRow()">
            <i class="fas fa-plus me-1"></i> আরো প্রোডাক্ট যোগ করুন
          </button>

          <hr>
          <div class="row">
            <div class="col-md-8"></div>
            <div class="col-md-4">
              <table class="table table-sm">
                <tr>
                  <td>মোট:</td>
                  <td class="fw-bold text-end" id="totalAmount">৳0.00</td>
                </tr>
                <tr>
                  <td>পরিশোধ:</td>
                  <td class="fw-bold text-end text-success" id="displayPaid">৳0.00</td>
                </tr>
                <tr class="table-warning">
                  <td>বাকি:</td>
                  <td class="fw-bold text-end text-danger" id="displayDue">৳0.00</td>
                </tr>
              </table>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
          <button type="submit" name="add_sale" class="btn btn-primary">
            <i class="fas fa-save me-1"></i> বিক্রয় সেভ করুন
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// প্রোডাক্ট সিলেক্ট করলে দাম সেট হবে
function setPrice(select) {
    var option = select.options[select.selectedIndex];
    var price = option.getAttribute('data-price');
    var row = select.closest('.product-row');
    row.querySelector('.price-input').value = price || 0;
    calculateTotal();
}

// নতুন row যোগ করো
function addRow() {
    var container = document.getElementById('productRows');
    var firstRow = container.querySelector('.product-row');
    var newRow = firstRow.cloneNode(true);
    
    // Reset values
    newRow.querySelector('.product-select').selectedIndex = 0;
    newRow.querySelector('.qty-input').value = 1;
    newRow.querySelector('.price-input').value = 0;
    
    // Event listener
    newRow.querySelector('.product-select').setAttribute('onchange', 'setPrice(this)');
    newRow.querySelector('.qty-input').setAttribute('oninput', 'calculateTotal()');
    newRow.querySelector('.price-input').setAttribute('oninput', 'calculateTotal()');
    
    container.appendChild(newRow);
}

// Row সরাও
function removeRow(btn) {
    var rows = document.querySelectorAll('.product-row');
    if (rows.length > 1) {
        btn.closest('.product-row').remove();
        calculateTotal();
    }
}

// Total calculate করো
function calculateTotal() {
    var total = 0;
    var rows = document.querySelectorAll('.product-row');
    
    rows.forEach(function(row) {
        var qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        var price = parseFloat(row.querySelector('.price-input').value) || 0;
        total += qty * price;
    });
    
    var paid = parseFloat(document.getElementById('paidAmount').value) || 0;
    var due = total - paid;
    
    document.getElementById('totalAmount').textContent = '৳' + total.toFixed(2);
    document.getElementById('displayPaid').textContent = '৳' + paid.toFixed(2);
    document.getElementById('displayDue').textContent = '৳' + (due < 0 ? 0 : due).toFixed(2);
}

function calculateDue() {
    calculateTotal();
}
</script>