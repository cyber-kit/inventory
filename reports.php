<?php
session_start();
require_once 'config/database.php';
require_once 'includes/header.php';

// Date range থাকলে filter করো
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Sales Report
$sales_query = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_sales,
        SUM(total_amount) as total_revenue,
        SUM(paid_amount) as total_paid,
        SUM(due_amount) as total_due
    FROM sales 
    WHERE DATE(sale_date) BETWEEN '$date_from' AND '$date_to'
");
$sales_data = mysqli_fetch_assoc($sales_query);

// Product wise sales
$product_sales = mysqli_query($conn, "
    SELECT 
        p.name as product_name,
        SUM(si.quantity) as total_sold,
        p.unit,
        SUM(si.subtotal) as revenue
    FROM sale_items si
    LEFT JOIN products p ON si.product_id = p.id
    LEFT JOIN sales s ON si.sale_id = s.id
    WHERE DATE(s.sale_date) BETWEEN '$date_from' AND '$date_to'
    GROUP BY si.product_id
    ORDER BY total_sold DESC
    LIMIT 10
");

// Low stock products
$low_stock = mysqli_query($conn, "
    SELECT * FROM products 
    WHERE stock_qty <= alert_qty 
    ORDER BY stock_qty ASC
");

// Recent sales
$recent_sales = mysqli_query($conn, "
    SELECT * FROM sales 
    WHERE DATE(sale_date) BETWEEN '$date_from' AND '$date_to'
    ORDER BY sale_date DESC 
    LIMIT 10
");

// Stock movements
$stock_in_total = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT SUM(quantity) as total 
    FROM stock_movements 
    WHERE movement_type='in' 
    AND DATE(created_at) BETWEEN '$date_from' AND '$date_to'
"))['total'] ?? 0;

$stock_out_total = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT SUM(quantity) as total 
    FROM stock_movements 
    WHERE movement_type='out' 
    AND DATE(created_at) BETWEEN '$date_from' AND '$date_to'
"))['total'] ?? 0;

// Total products & categories
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products"))['total'];
$total_categories = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM categories"))['total'];
?>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-2">
      <button class="hamburger" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
      </button>
      <h5 class="mb-0 fw-bold">
        <i class="fas fa-chart-bar me-2 text-primary"></i> রিপোর্ট ও এনালিটিক্স
      </h5>
    </div>
  </div>

  <!-- Date Filter -->
  <div class="card table-card mb-3 p-3">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small">শুরুর তারিখ</label>
        <input type="date" name="date_from" class="form-control" 
          value="<?= $date_from ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label small">শেষ তারিখ</label>
        <input type="date" name="date_to" class="form-control" 
          value="<?= $date_to ?>" required>
      </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">
          <i class="fas fa-filter me-1"></i> ফিল্টার
        </button>
      </div>
      <div class="col-md-2">
        <a href="reports.php" class="btn btn-secondary w-100">
          <i class="fas fa-refresh"></i> রিসেট
        </a>
      </div>
      <div class="col-md-2">
        <button type="button" onclick="window.print()" class="btn btn-success w-100">
          <i class="fas fa-print me-1"></i> প্রিন্ট
        </button>
      </div>
    </form>
  </div>

  <div class="alert alert-info">
    <i class="fas fa-calendar me-2"></i>
    <strong>রিপোর্ট সময়কাল:</strong> 
    <?= date('d M Y', strtotime($date_from)) ?> থেকে <?= date('d M Y', strtotime($date_to)) ?>
  </div>

  <!-- Summary Cards -->
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card table-card">
        <div class="card-body text-center">
          <i class="fas fa-box fa-2x text-primary mb-2"></i>
          <h3 class="mb-0"><?= $total_products ?></h3>
          <small class="text-muted">মোট প্রোডাক্ট</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card table-card">
        <div class="card-body text-center">
          <i class="fas fa-tags fa-2x text-info mb-2"></i>
          <h3 class="mb-0"><?= $total_categories ?></h3>
          <small class="text-muted">মোট ক্যাটাগরি</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card table-card">
        <div class="card-body text-center">
          <i class="fas fa-arrow-down fa-2x text-success mb-2"></i>
          <h3 class="mb-0"><?= $stock_in_total ?></h3>
          <small class="text-muted">স্টক ইন (সময়কাল)</small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card table-card">
        <div class="card-body text-center">
          <i class="fas fa-arrow-up fa-2x text-warning mb-2"></i>
          <h3 class="mb-0"><?= $stock_out_total ?></h3>
          <small class="text-muted">স্টক আউট (সময়কাল)</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Sales Summary -->
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="card table-card">
        <div class="card-body">
          <h6 class="text-muted mb-2">মোট বিক্রয় সংখ্যা</h6>
          <h3 class="mb-0 text-primary"><?= $sales_data['total_sales'] ?? 0 ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card table-card">
        <div class="card-body">
          <h6 class="text-muted mb-2">মোট বিক্রয় আয়</h6>
          <h3 class="mb-0 text-success">৳<?= number_format($sales_data['total_revenue'] ?? 0, 2) ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card table-card">
        <div class="card-body">
          <h6 class="text-muted mb-2">পরিশোধিত</h6>
          <h3 class="mb-0 text-info">৳<?= number_format($sales_data['total_paid'] ?? 0, 2) ?></h3>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card table-card">
        <div class="card-body">
          <h6 class="text-muted mb-2">মোট বাকি</h6>
          <h3 class="mb-0 text-danger">৳<?= number_format($sales_data['total_due'] ?? 0, 2) ?></h3>
        </div>
      </div>
    </div>
  </div>

  <!-- Top Selling Products -->
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <div class="card table-card">
        <div class="card-header bg-white fw-bold">
          <i class="fas fa-trophy text-warning me-2"></i>সর্বাধিক বিক্রিত প্রোডাক্ট (Top 10)
        </div>
        <div class="card-body p-0">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>প্রোডাক্ট</th>
                <th>বিক্রিত পরিমাণ</th>
                <th>আয়</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $i=1;
              $num_ps = mysqli_num_rows($product_sales);
              while($ps = mysqli_fetch_assoc($product_sales)): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><strong><?= htmlspecialchars($ps['product_name']) ?></strong></td>
                <td><span class="badge bg-primary"><?= $ps['total_sold'] ?> <?= $ps['unit'] ?></span></td>
                <td>৳<?= number_format($ps['revenue'], 2) ?></td>
              </tr>
              <?php endwhile; ?>
              <?php if ($num_ps == 0): ?>
              <tr>
                <td colspan="4" class="text-center text-muted py-3">কোনো বিক্রয় নেই</td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Low Stock Alert -->
    <div class="col-md-6">
      <div class="card table-card">
        <div class="card-header bg-white fw-bold">
          <i class="fas fa-exclamation-triangle text-danger me-2"></i>লো স্টক সতর্কতা
        </div>
        <div class="card-body p-0">
          <table class="table table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>প্রোডাক্ট</th>
                <th>বর্তমান স্টক</th>
                <th>Alert লেভেল</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              $i=1;
              $num_ls = mysqli_num_rows($low_stock);
              while($ls = mysqli_fetch_assoc($low_stock)): ?>
              <tr>
                <td><?= $i++ ?></td>
                <td><strong><?= htmlspecialchars($ls['name']) ?></strong></td>
                <td>
                  <span class="badge bg-danger"><?= $ls['stock_qty'] ?> <?= $ls['unit'] ?></span>
                </td>
                <td><?= $ls['alert_qty'] ?> <?= $ls['unit'] ?></td>
              </tr>
              <?php endwhile; ?>
              <?php if ($num_ls == 0): ?>
              <tr>
                <td colspan="4" class="text-center text-success py-3">
                  <i class="fas fa-check-circle me-2"></i>সব প্রোডাক্টের স্টক পর্যাপ্ত আছে!
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Sales -->
  <div class="card table-card">
    <div class="card-header bg-white fw-bold">
      <i class="fas fa-clock text-primary me-2"></i>সাম্প্রতিক বিক্রয় (সর্বশেষ 10টি)
    </div>
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
          </tr>
        </thead>
        <tbody>
          <?php 
          $i=1;
          $num_rs = mysqli_num_rows($recent_sales);
          while($rs = mysqli_fetch_assoc($recent_sales)): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><code><?= htmlspecialchars($rs['invoice_no']) ?></code></td>
            <td><?= htmlspecialchars($rs['customer_name'] ?? 'N/A') ?></td>
            <td>৳<?= number_format($rs['total_amount'], 2) ?></td>
            <td><span class="badge bg-success">৳<?= number_format($rs['paid_amount'], 2) ?></span></td>
            <td>
              <?php if ($rs['due_amount'] > 0): ?>
              <span class="badge bg-danger">৳<?= number_format($rs['due_amount'], 2) ?></span>
              <?php else: ?>
              <span class="badge bg-success">পরিশোধিত</span>
              <?php endif; ?>
            </td>
            <td><?= date('d M Y, h:i A', strtotime($rs['sale_date'])) ?></td>
          </tr>
          <?php endwhile; ?>
          <?php if ($num_rs == 0): ?>
          <tr>
            <td colspan="7" class="text-center text-muted py-3">কোনো বিক্রয় রেকর্ড নেই</td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php include 'includes/footer.php'; ?>
</div>

<style>
@media print {
  .sidebar, .topbar button, .btn, .no-print { display: none !important; }
  .main-content { margin-left: 0 !important; }
  .card { border: 1px solid #ddd !important; box-shadow: none !important; }
}
</style>