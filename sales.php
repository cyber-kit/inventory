<?php
session_start();
require_once 'config/database.php';

// Sale Add
if (isset($_POST['add_sale'])) {
    $customer_name = mysqli_real_escape_string($conn, trim($_POST['customer_name']));
    $product_ids = $_POST['product_id'];
    $quantities = $_POST['quantity'];
    $unit_prices = $_POST['unit_price'];
    $paid_amount = (float)$_POST['paid_amount'];
    $discount_type = $_POST['discount_type'];
    $discount_value = (float)$_POST['discount_value'];

    $subtotal = 0;
    foreach ($quantities as $i => $qty) {
        if ($qty > 0 && $product_ids[$i] > 0) {
            $subtotal += $qty * $unit_prices[$i];
        }
    }

    if ($discount_type == 'percent') {
        $discount_amount = ($subtotal * $discount_value) / 100;
    } else {
        $discount_amount = $discount_value;
    }

    $total_amount = $subtotal - $discount_amount;
    if ($total_amount < 0) $total_amount = 0;
    $due_amount = $total_amount - $paid_amount;
    if ($due_amount < 0) $due_amount = 0;

    $invoice_no = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);
    $discount_type_safe = mysqli_real_escape_string($conn, $discount_type);

    mysqli_query($conn, "INSERT INTO sales 
        (invoice_no, customer_name, total_amount, paid_amount, due_amount,
         discount_type, discount_value, discount_amount) 
        VALUES ('$invoice_no', '$customer_name', $total_amount, $paid_amount, 
                $due_amount, '$discount_type_safe', $discount_value, $discount_amount)");

    $sale_id = mysqli_insert_id($conn);

    // প্রথম payment record করো
    if ($paid_amount > 0) {
        $note = mysqli_real_escape_string($conn, "প্রাথমিক পরিশোধ");
        mysqli_query($conn, "INSERT INTO due_payments (sale_id, amount, note) 
            VALUES ($sale_id, $paid_amount, '$note')");
    }

    foreach ($product_ids as $i => $product_id) {
        $product_id = (int)$product_id;
        $qty = (int)$quantities[$i];
        $unit_price = (float)$unit_prices[$i];
        $item_subtotal = $qty * $unit_price;

        if ($product_id > 0 && $qty > 0) {
            mysqli_query($conn, "INSERT INTO sale_items 
                (sale_id, product_id, quantity, unit_price, subtotal) 
                VALUES ($sale_id, $product_id, $qty, $unit_price, $item_subtotal)");

            mysqli_query($conn, "UPDATE products 
                SET stock_qty = stock_qty - $qty WHERE id = $product_id");

            mysqli_query($conn, "INSERT INTO stock_movements 
                (product_id, movement_type, quantity, note) 
                VALUES ($product_id, 'out', $qty, 'Sale: $invoice_no')");
        }
    }

    header("Location: sales.php?msg=added&invoice=$invoice_no");
    exit();
}

// Due Payment
if (isset($_POST['pay_due'])) {
    $sale_id = (int)$_POST['sale_id'];
    $pay_amount = (float)$_POST['pay_amount'];
    $pay_note = mysqli_real_escape_string($conn, trim($_POST['pay_note'] ?? ''));

    $sale = mysqli_fetch_assoc(mysqli_query($conn, 
        "SELECT * FROM sales WHERE id=$sale_id"));

    if ($sale && $pay_amount > 0) {
        $new_paid = $sale['paid_amount'] + $pay_amount;
        $new_due = $sale['due_amount'] - $pay_amount;
        if ($new_due < 0) $new_due = 0;

        mysqli_query($conn, "UPDATE sales SET 
            paid_amount=$new_paid, due_amount=$new_due 
            WHERE id=$sale_id");

        // Payment record করো
        mysqli_query($conn, "INSERT INTO due_payments (sale_id, amount, note) 
            VALUES ($sale_id, $pay_amount, '$pay_note')");

        header("Location: sales.php?msg=paid");
        exit();
    }
}

// Sale Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM sales WHERE id=$id");
    header("Location: sales.php?msg=deleted");
    exit();
}

require_once 'includes/header.php';

// Get products
$products = mysqli_query($conn, 
    "SELECT * FROM products WHERE stock_qty > 0 ORDER BY name ASC");
$prod_array = [];
while ($p = mysqli_fetch_assoc($products)) {
    $prod_array[] = $p;
}

// Filter
$filter = $_GET['filter'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

$conditions = [];
if ($filter == 'due') $conditions[] = "due_amount > 0";
if ($filter == 'paid') $conditions[] = "due_amount = 0";
if (!empty($date_from)) $conditions[] = "DATE(sale_date) >= '$date_from'";
if (!empty($date_to)) $conditions[] = "DATE(sale_date) <= '$date_to'";
if (!empty($search)) {
    $search_safe = mysqli_real_escape_string($conn, $search);
    $conditions[] = "(invoice_no LIKE '%$search_safe%' OR customer_name LIKE '%$search_safe%')";
}

$where = count($conditions) > 0 ? "WHERE " . implode(" AND ", $conditions) : "";

// Totals
$total_due = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT SUM(due_amount) as total FROM sales WHERE due_amount > 0"))['total'] ?? 0;
$due_count = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as total FROM sales WHERE due_amount > 0"))['total'] ?? 0;

$sales = mysqli_query($conn, "SELECT * FROM sales $where ORDER BY sale_date DESC");
$total_rows = mysqli_num_rows($sales);

// Summary for filtered results
$summary = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT SUM(total_amount) as revenue, SUM(paid_amount) as paid, 
     SUM(due_amount) as due, SUM(discount_amount) as discount,
     COUNT(*) as count FROM sales $where"));
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
    <button class="btn btn-primary btn-sm" 
      data-bs-toggle="modal" data-bs-target="#addSaleModal">
      <i class="fas fa-plus me-1"></i> নতুন বিক্রয়
    </button>
  </div>

  <?php if (isset($_GET['msg'])): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>
    <?php
    if ($_GET['msg'] == 'added') echo "বিক্রয় সফলভাবে রেকর্ড হয়েছে! Invoice: " . htmlspecialchars($_GET['invoice'] ?? '');
    if ($_GET['msg'] == 'deleted') echo "বিক্রয় রেকর্ড ডিলিট হয়েছে!";
    if ($_GET['msg'] == 'paid') echo "বাকি পরিশোধ সফলভাবে রেকর্ড হয়েছে!";
    ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <!-- Due Summary -->
  <?php if ($due_count > 0): ?>
  <div class="card border-0 mb-3" 
    style="background:linear-gradient(135deg,#e74c3c,#c0392b);border-radius:12px;">
    <div class="card-body text-white p-3">
      <div class="row align-items-center">
        <div class="col-8">
          <div class="small opacity-75">মোট বাকি</div>
          <h3 class="mb-0 fw-bold">৳<?= number_format($total_due, 2) ?></h3>
          <small class="opacity-75"><?= $due_count ?>টি বিক্রয়ে বাকি আছে</small>
        </div>
        <div class="col-4 text-end">
          <i class="fas fa-exclamation-circle fa-3x opacity-50"></i>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Filter Section -->
  <div class="card table-card mb-3 p-3">
    <form method="GET" id="filterForm">
      <div class="row g-2 align-items-end">
        <div class="col-md-3 col-6">
          <label class="form-label small">শুরুর তারিখ</label>
          <input type="date" name="date_from" class="form-control form-control-sm"
            value="<?= htmlspecialchars($date_from) ?>">
        </div>
        <div class="col-md-3 col-6">
          <label class="form-label small">শেষ তারিখ</label>
          <input type="date" name="date_to" class="form-control form-control-sm"
            value="<?= htmlspecialchars($date_to) ?>">
        </div>
        <div class="col-md-3 col-8">
          <label class="form-label small">খুঁজুন</label>
          <input type="text" name="search" class="form-control form-control-sm"
            placeholder="Invoice বা কাস্টমার নাম..."
            value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-1 col-4">
          <button type="submit" class="btn btn-primary btn-sm w-100">
            <i class="fas fa-search"></i>
          </button>
        </div>
        <div class="col-md-2 col-12">
          <a href="sales.php" class="btn btn-secondary btn-sm w-100">
            <i class="fas fa-times me-1"></i>রিসেট
          </a>
        </div>
      </div>
    </form>
  </div>

  <!-- Filter Tabs -->
  <div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="sales.php?filter=all&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&search=<?= $search ?>" 
      class="btn btn-sm <?= $filter=='all'?'btn-primary':'btn-outline-primary' ?>">
      সব বিক্রয়
    </a>
    <a href="sales.php?filter=due&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&search=<?= $search ?>" 
      class="btn btn-sm <?= $filter=='due'?'btn-danger':'btn-outline-danger' ?>">
      <i class="fas fa-exclamation-circle me-1"></i>বাকি আছে
      <?php if ($due_count > 0): ?>
      <span class="badge bg-white text-danger ms-1"><?= $due_count ?></span>
      <?php endif; ?>
    </a>
    <a href="sales.php?filter=paid&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&search=<?= $search ?>" 
      class="btn btn-sm <?= $filter=='paid'?'btn-success':'btn-outline-success' ?>">
      <i class="fas fa-check-circle me-1"></i>পরিশোধিত
    </a>
    <button onclick="printTable()" class="btn btn-sm btn-outline-dark ms-auto">
      <i class="fas fa-print me-1"></i>প্রিন্ট
    </button>
    <button onclick="downloadCSV()" class="btn btn-sm btn-outline-success">
      <i class="fas fa-download me-1"></i>CSV ডাউনলোড
    </button>
  </div>

  <!-- Summary Cards -->
  <?php if ($total_rows > 0): ?>
  <div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
      <div class="card border-0 p-2 text-center" style="background:#e8f4fd;border-radius:10px;">
        <div class="small text-muted">বিক্রয় সংখ্যা</div>
        <div class="fw-bold fs-5 text-primary"><?= $summary['count'] ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 p-2 text-center" style="background:#e8f8f5;border-radius:10px;">
        <div class="small text-muted">মোট আয়</div>
        <div class="fw-bold fs-5 text-success">৳<?= number_format($summary['revenue'] ?? 0, 2) ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 p-2 text-center" style="background:#fef9e7;border-radius:10px;">
        <div class="small text-muted">পরিশোধিত</div>
        <div class="fw-bold fs-5 text-warning">৳<?= number_format($summary['paid'] ?? 0, 2) ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 p-2 text-center" style="background:#fdedec;border-radius:10px;">
        <div class="small text-muted">বাকি</div>
        <div class="fw-bold fs-5 text-danger">৳<?= number_format($summary['due'] ?? 0, 2) ?></div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Sales Table -->
  <div class="card table-card" id="printArea">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0" id="salesTable">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Invoice</th>
              <th>কাস্টমার</th>
              <th>মোট</th>
              <th>ডিসকাউন্ট</th>
              <th>পরিশোধ</th>
              <th>বাকি</th>
              <th>তারিখ</th>
              <th class="no-print">অ্যাকশন</th>
            </tr>
          </thead>
          <tbody>
            <?php $i=1;
            while($row = mysqli_fetch_assoc($sales)): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><strong><?= htmlspecialchars($row['invoice_no']) ?></strong></td>
              <td><?= htmlspecialchars($row['customer_name'] ?? 'N/A') ?></td>
              <td>৳<?= number_format($row['total_amount'], 2) ?></td>
              <td>
                <?php if ($row['discount_amount'] > 0): ?>
                <span class="badge bg-info text-dark">
                  -৳<?= number_format($row['discount_amount'], 2) ?>
                  <?php if ($row['discount_type'] == 'percent'): ?>
                  (<?= $row['discount_value'] ?>%)
                  <?php endif; ?>
                </span>
                <?php else: ?>
                <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
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
              <td class="no-print">
                <div class="d-flex gap-1 flex-wrap">
                  <a href="invoice.php?id=<?= $row['id'] ?>"
                    class="btn btn-sm btn-outline-success" target="_blank"
                    title="প্রিন্ট Invoice">
                    <i class="fas fa-print"></i>
                  </a>
                  <button class="btn btn-sm btn-outline-info"
                    data-bs-toggle="modal"
                    data-bs-target="#detailModal<?= $row['id'] ?>"
                    title="বিস্তারিত">
                    <i class="fas fa-eye"></i>
                  </button>
                  <?php if ($row['due_amount'] > 0): ?>
                  <button class="btn btn-sm btn-outline-warning"
                    data-bs-toggle="modal"
                    data-bs-target="#payModal<?= $row['id'] ?>"
                    title="বাকি পরিশোধ">
                    <i class="fas fa-money-bill-wave"></i>
                  </button>
                  <?php endif; ?>
                  <a href="sales.php?delete=<?= $row['id'] ?>"
                    class="btn btn-sm btn-outline-danger"
                    onclick="return confirm('ডিলিট করবেন?')"
                    title="ডিলিট">
                    <i class="fas fa-trash"></i>
                  </a>
                </div>
              </td>
            </tr>

            <!-- Detail Modal with Payment History -->
            <?php
            $items = mysqli_query($conn, "
                SELECT si.*, p.name as product_name, p.unit
                FROM sale_items si
                LEFT JOIN products p ON si.product_id = p.id
                WHERE si.sale_id = {$row['id']}
            ");
            $payments = mysqli_query($conn, "
                SELECT * FROM due_payments 
                WHERE sale_id = {$row['id']}
                ORDER BY payment_date ASC
            ");
            ?>
            <div class="modal fade" id="detailModal<?= $row['id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">
                      <i class="fas fa-receipt me-2"></i>
                      <?= htmlspecialchars($row['invoice_no']) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <!-- Sale Info -->
                    <div class="row mb-3">
                      <div class="col-6">
                        <p class="mb-1">
                          <strong>কাস্টমার:</strong> 
                          <?= htmlspecialchars($row['customer_name'] ?? 'N/A') ?>
                        </p>
                        <p class="mb-1">
                          <strong>তারিখ:</strong> 
                          <?= date('d M Y, h:i A', strtotime($row['sale_date'])) ?>
                        </p>
                      </div>
                      <div class="col-6 text-end">
                        <?php 
                        $sub = $row['total_amount'] + $row['discount_amount'];
                        ?>
                        <p class="mb-1">
                          <strong>সাবটোটাল:</strong> ৳<?= number_format($sub, 2) ?>
                        </p>
                        <?php if ($row['discount_amount'] > 0): ?>
                        <p class="mb-1 text-success">
                          <strong>ডিসকাউন্ট:</strong> 
                          -৳<?= number_format($row['discount_amount'], 2) ?>
                        </p>
                        <?php endif; ?>
                        <p class="mb-1">
                          <strong>মোট:</strong> ৳<?= number_format($row['total_amount'], 2) ?>
                        </p>
                        <p class="mb-1 text-success">
                          <strong>পরিশোধ:</strong> ৳<?= number_format($row['paid_amount'], 2) ?>
                        </p>
                        <p class="mb-1 text-danger">
                          <strong>বাকি:</strong> ৳<?= number_format($row['due_amount'], 2) ?>
                        </p>
                      </div>
                    </div>

                    <!-- Items -->
                    <h6 class="fw-bold">পণ্যের তালিকা</h6>
                    <div class="table-responsive mb-3">
                      <table class="table table-bordered table-sm">
                        <thead class="table-light">
                          <tr>
                            <th>প্রোডাক্ট</th>
                            <th class="text-center">পরিমাণ</th>
                            <th class="text-end">একক মূল্য</th>
                            <th class="text-end">সাবটোটাল</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php while($item = mysqli_fetch_assoc($items)): ?>
                          <tr>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td class="text-center">
                              <?= $item['quantity'] ?> <?= $item['unit'] ?>
                            </td>
                            <td class="text-end">৳<?= number_format($item['unit_price'], 2) ?></td>
                            <td class="text-end">৳<?= number_format($item['subtotal'], 2) ?></td>
                          </tr>
                          <?php endwhile; ?>
                        </tbody>
                      </table>
                    </div>

                    <!-- Payment History -->
                    <h6 class="fw-bold">
                      <i class="fas fa-history text-success me-2"></i>
                      পরিশোধের ইতিহাস
                    </h6>
                    <div class="table-responsive">
                      <table class="table table-bordered table-sm">
                        <thead class="table-light">
                          <tr>
                            <th>#</th>
                            <th>পরিমাণ</th>
                            <th>নোট</th>
                            <th>তারিখ ও সময়</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php 
                          $p_num = mysqli_num_rows($payments);
                          $p_i = 1;
                          while($pay = mysqli_fetch_assoc($payments)): ?>
                          <tr>
                            <td><?= $p_i++ ?></td>
                            <td>
                              <span class="badge bg-success">
                                ৳<?= number_format($pay['amount'], 2) ?>
                              </span>
                            </td>
                            <td><?= htmlspecialchars($pay['note'] ?? '-') ?></td>
                            <td>
                              <?= date('d M Y, h:i A', strtotime($pay['payment_date'])) ?>
                            </td>
                          </tr>
                          <?php endwhile; ?>
                          <?php if ($p_num == 0): ?>
                          <tr>
                            <td colspan="4" class="text-center text-muted">
                              কোনো পরিশোধের ইতিহাস নেই
                            </td>
                          </tr>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <a href="invoice.php?id=<?= $row['id'] ?>" 
                      class="btn btn-success" target="_blank">
                      <i class="fas fa-print me-1"></i>Invoice প্রিন্ট
                    </a>
                    <button type="button" class="btn btn-secondary" 
                      data-bs-dismiss="modal">বন্ধ করুন</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pay Due Modal -->
            <?php if ($row['due_amount'] > 0): ?>
            <div class="modal fade" id="payModal<?= $row['id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <form method="POST">
                    <div class="modal-header bg-warning">
                      <h5 class="modal-title fw-bold">
                        <i class="fas fa-money-bill-wave me-2"></i>বাকি পরিশোধ
                      </h5>
                      <button type="button" class="btn-close" 
                        data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <input type="hidden" name="sale_id" value="<?= $row['id'] ?>">

                      <div class="card bg-light border-0 p-3 mb-3">
                        <div class="row text-center">
                          <div class="col-4">
                            <div class="text-muted small">Invoice</div>
                            <div class="fw-bold small">
                              <?= htmlspecialchars($row['invoice_no']) ?>
                            </div>
                          </div>
                          <div class="col-4">
                            <div class="text-muted small">মোট বিক্রয়</div>
                            <div class="fw-bold">
                              ৳<?= number_format($row['total_amount'], 2) ?>
                            </div>
                          </div>
                          <div class="col-4">
                            <div class="text-muted small">বাকি</div>
                            <div class="fw-bold text-danger">
                              ৳<?= number_format($row['due_amount'], 2) ?>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div class="mb-3">
                        <label class="form-label fw-bold">পরিশোধের পরিমাণ (৳)</label>
                        <input type="number" name="pay_amount" 
                          class="form-control form-control-lg"
                          placeholder="০.০০" step="0.01" min="0.01"
                          max="<?= $row['due_amount'] ?>"
                          value="<?= $row['due_amount'] ?>" required>
                        <small class="text-muted">
                          সর্বোচ্চ: ৳<?= number_format($row['due_amount'], 2) ?>
                        </small>
                      </div>

                      <div class="mb-3">
                        <label class="form-label">নোট (ঐচ্ছিক)</label>
                        <input type="text" name="pay_note" class="form-control"
                          placeholder="যেমন: নগদ পরিশোধ, bKash ইত্যাদি...">
                      </div>

                      <button type="button" 
                        class="btn btn-outline-primary w-100 mb-2"
                        onclick="this.closest('form').querySelector('[name=pay_amount]').value='<?= $row['due_amount'] ?>'">
                        <i class="fas fa-check me-1"></i>সম্পূর্ণ বাকি পরিশোধ 
                        (৳<?= number_format($row['due_amount'], 2) ?>)
                      </button>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">বাতিল</button>
                      <button type="submit" name="pay_due" class="btn btn-warning fw-bold">
                        <i class="fas fa-check me-1"></i>পরিশোধ করুন
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <?php endif; ?>

            <?php endwhile; ?>

            <?php if ($total_rows == 0): ?>
            <tr>
              <td colspan="9" class="text-center text-muted py-4">
                <?php if ($filter == 'due'): ?>
                <i class="fas fa-check-circle text-success fa-2x mb-2 d-block"></i>
                কোনো বাকি নেই! সব পরিশোধিত।
                <?php else: ?>
                কোনো বিক্রয় রেকর্ড নেই
                <?php endif; ?>
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>
</div>

<!-- Add Sale Modal -->
<div class="modal fade" id="addSaleModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" id="saleForm">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fas fa-shopping-cart me-2"></i>নতুন বিক্রয়
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">কাস্টমারের নাম (ঐচ্ছিক)</label>
              <input type="text" name="customer_name" class="form-control"
                placeholder="কাস্টমারের নাম লিখুন...">
            </div>
            <div class="col-md-3">
              <label class="form-label">ডিসকাউন্ট ধরন</label>
              <select name="discount_type" class="form-select" id="discountType"
                onchange="updateDiscountSymbol(); calculateTotal();">
                <option value="amount">সরাসরি পরিমাণ (৳)</option>
                <option value="percent">শতকরা (%)</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">ডিসকাউন্ট</label>
              <div class="input-group">
                <input type="number" name="discount_value" class="form-control"
                  id="discountValue" placeholder="0" step="0.01" value="0"
                  oninput="calculateTotal()">
                <span class="input-group-text" id="discountSymbol">৳</span>
              </div>
            </div>
          </div>

          <hr>
          <h6 class="fw-bold mb-3">প্রোডাক্ট যোগ করুন</h6>

          <div id="productRows">
            <div class="row g-2 mb-2 product-row align-items-center">
              <div class="col-md-5 col-12">
                <select name="product_id[]" class="form-select product-select"
                  onchange="setPrice(this)" required>
                  <option value="">-- প্রোডাক্ট বেছে নিন --</option>
                  <?php foreach ($prod_array as $p): ?>
                  <option value="<?= $p['id'] ?>"
                    data-price="<?= $p['sale_price'] ?>"
                    data-stock="<?= $p['stock_qty'] ?>">
                    <?= htmlspecialchars($p['name']) ?> 
                    (স্টক: <?= $p['stock_qty'] ?>)
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-2 col-5">
                <input type="number" name="quantity[]" class="form-control qty-input"
                  placeholder="পরিমাণ" min="1" value="1"
                  oninput="calculateTotal()" required>
              </div>
              <div class="col-md-3 col-5">
                <div class="input-group">
                  <span class="input-group-text">৳</span>
                  <input type="number" name="unit_price[]" 
                    class="form-control price-input"
                    placeholder="মূল্য" step="0.01" value="0"
                    oninput="calculateTotal()" required>
                </div>
              </div>
              <div class="col-md-2 col-2">
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
          <div class="row align-items-end">
            <div class="col-md-6">
              <label class="form-label fw-bold">পরিশোধিত টাকা (৳)</label>
              <input type="number" name="paid_amount" 
                class="form-control form-control-lg"
                placeholder="0.00" step="0.01" value="0" id="paidAmount"
                oninput="calculateTotal()">
            </div>
            <div class="col-md-6">
              <table class="table table-sm mb-0">
                <tr>
                  <td class="text-muted">সাবটোটাল:</td>
                  <td class="text-end fw-bold" id="subtotalAmount">৳0.00</td>
                </tr>
                <tr class="text-success">
                  <td>ডিসকাউন্ট:</td>
                  <td class="text-end fw-bold" id="discountAmount">-৳0.00</td>
                </tr>
                <tr class="table-light">
                  <td class="fw-bold">মোট:</td>
                  <td class="text-end fw-bold fs-5" id="totalAmount">৳0.00</td>
                </tr>
                <tr>
                  <td class="text-muted">পরিশোধ:</td>
                  <td class="text-end text-success fw-bold" id="displayPaid">৳0.00</td>
                </tr>
                <tr class="table-warning">
                  <td class="fw-bold">বাকি:</td>
                  <td class="text-end text-danger fw-bold" id="displayDue">৳0.00</td>
                </tr>
              </table>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary"
            data-bs-dismiss="modal">বাতিল</button>
          <button type="submit" name="add_sale" class="btn btn-primary">
            <i class="fas fa-save me-1"></i> বিক্রয় সেভ করুন
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function setPrice(select) {
  var option = select.options[select.selectedIndex];
  var price = option.getAttribute('data-price');
  var row = select.closest('.product-row');
  row.querySelector('.price-input').value = price || 0;
  calculateTotal();
}

function updateDiscountSymbol() {
  var type = document.getElementById('discountType').value;
  document.getElementById('discountSymbol').textContent = 
    type == 'percent' ? '%' : '৳';
}

function addRow() {
  var container = document.getElementById('productRows');
  var firstRow = container.querySelector('.product-row');
  var newRow = firstRow.cloneNode(true);
  newRow.querySelector('.product-select').selectedIndex = 0;
  newRow.querySelector('.qty-input').value = 1;
  newRow.querySelector('.price-input').value = 0;
  container.appendChild(newRow);
}

function removeRow(btn) {
  var rows = document.querySelectorAll('.product-row');
  if (rows.length > 1) {
    btn.closest('.product-row').remove();
    calculateTotal();
  }
}

function calculateTotal() {
  var subtotal = 0;
  document.querySelectorAll('.product-row').forEach(function(row) {
    var qty = parseFloat(row.querySelector('.qty-input').value) || 0;
    var price = parseFloat(row.querySelector('.price-input').value) || 0;
    subtotal += qty * price;
  });

  var discountType = document.getElementById('discountType').value;
  var discountValue = parseFloat(document.getElementById('discountValue').value) || 0;
  var discountAmount = discountType == 'percent' ? 
    (subtotal * discountValue / 100) : discountValue;

  var total = Math.max(0, subtotal - discountAmount);
  var paid = parseFloat(document.getElementById('paidAmount').value) || 0;
  var due = Math.max(0, total - paid);

  document.getElementById('subtotalAmount').textContent = '৳' + subtotal.toFixed(2);
  document.getElementById('discountAmount').textContent = '-৳' + discountAmount.toFixed(2);
  document.getElementById('totalAmount').textContent = '৳' + total.toFixed(2);
  document.getElementById('displayPaid').textContent = '৳' + paid.toFixed(2);
  document.getElementById('displayDue').textContent = '৳' + due.toFixed(2);
}

// Print
function printTable() {
  window.print();
}

// CSV Download
function downloadCSV() {
  var table = document.getElementById('salesTable');
  var rows = table.querySelectorAll('tr');
  var csv = [];

  rows.forEach(function(row) {
    var cols = row.querySelectorAll('th, td');
    var rowData = [];
    cols.forEach(function(col, index) {
      // Last column (action) skip
      if (index < cols.length - 1) {
        rowData.push('"' + col.innerText.replace(/"/g, '""') + '"');
      }
    });
    csv.push(rowData.join(','));
  });

  var csvContent = '\uFEFF' + csv.join('\n');
  var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  var link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = 'sales_report_<?= date('Y-m-d') ?>.csv';
  link.click();
}
</script>

<style>
@media print {
  .no-print, .topbar button, .hamburger,
  .sidebar, .sidebar-overlay,
  .btn-group, .card.table-card.mb-3.p-3,
  .modal { display: none !important; }
  .main-content { margin-left: 0 !important; }
  .card { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>