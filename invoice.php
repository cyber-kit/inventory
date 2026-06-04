<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$id = (int)($_GET['id'] ?? 0);

if ($id == 0) {
    header("Location: sales.php");
    exit();
}

// Sale data
$sale = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM sales WHERE id=$id"));

if (!$sale) {
    header("Location: sales.php");
    exit();
}

// Sale items
$items = mysqli_query($conn, "
    SELECT si.*, p.name as product_name, p.unit
    FROM sale_items si
    LEFT JOIN products p ON si.product_id = p.id
    WHERE si.sale_id = $id
");
?>
<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice - <?= htmlspecialchars($sale['invoice_no']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background: #f0f2f5;
      font-family: 'Segoe UI', sans-serif;
    }

    .invoice-wrapper {
      max-width: 800px;
      margin: 30px auto;
      padding: 15px;
    }

    .invoice-box {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      padding: 40px;
    }

    .invoice-header {
      border-bottom: 3px solid #3498db;
      padding-bottom: 20px;
      margin-bottom: 25px;
    }

    .brand-name {
      font-size: 1.8rem;
      font-weight: bold;
      color: #2c3e50;
    }

    .brand-sub {
      color: #7f8c8d;
      font-size: 0.9rem;
    }

    .invoice-title {
      font-size: 2rem;
      font-weight: bold;
      color: #3498db;
      text-align: right;
    }

    .invoice-no {
      color: #7f8c8d;
      text-align: right;
      font-size: 0.9rem;
    }

    .info-section {
      background: #f8f9fa;
      border-radius: 8px;
      padding: 15px 20px;
      margin-bottom: 25px;
    }

    .info-label {
      color: #7f8c8d;
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .info-value {
      font-weight: 600;
      color: #2c3e50;
      font-size: 0.95rem;
    }

    .table thead th {
      background: #2c3e50;
      color: white;
      border: none;
      padding: 12px 15px;
      font-weight: 500;
    }

    .table tbody td {
      padding: 12px 15px;
      border-color: #f0f2f5;
      vertical-align: middle;
    }

    .table tbody tr:nth-child(even) {
      background: #f8f9fa;
    }

    .summary-section {
      background: #f8f9fa;
      border-radius: 8px;
      padding: 20px;
      margin-top: 20px;
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      padding: 6px 0;
      border-bottom: 1px solid #e9ecef;
    }

    .summary-row:last-child {
      border-bottom: none;
    }

    .summary-total {
      font-size: 1.2rem;
      font-weight: bold;
      color: #2c3e50;
      padding-top: 10px;
    }

    .paid-badge {
      background: #d4edda;
      color: #155724;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 0.85rem;
    }

    .due-badge {
      background: #f8d7da;
      color: #721c24;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 0.85rem;
    }

    .invoice-footer {
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid #e9ecef;
      text-align: center;
      color: #7f8c8d;
      font-size: 0.85rem;
    }

    .status-paid {
      background: #d4edda;
      color: #155724;
      padding: 5px 15px;
      border-radius: 20px;
      font-weight: bold;
    }

    .status-due {
      background: #f8d7da;
      color: #721c24;
      padding: 5px 15px;
      border-radius: 20px;
      font-weight: bold;
    }

    /* Action Buttons */
    .action-bar {
      max-width: 800px;
      margin: 0 auto 15px auto;
      padding: 0 15px;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    /* Print Styles */
    @media print {
      .action-bar { display: none !important; }
      body { background: white; }
      .invoice-wrapper { margin: 0; padding: 0; }
      .invoice-box {
        box-shadow: none;
        border-radius: 0;
        padding: 20px;
      }
    }

    /* Mobile Styles */
    @media (max-width: 576px) {
      .invoice-box {
        padding: 20px;
      }

      .invoice-title {
        font-size: 1.5rem;
      }

      .brand-name {
        font-size: 1.3rem;
      }

      .action-bar .btn {
        flex: 1;
        text-align: center;
      }
    }
  </style>
</head>
<body>

  <!-- Action Buttons -->
  <div class="action-bar mt-3">
    <a href="sales.php" class="btn btn-secondary">
      <i class="fas fa-arrow-left me-1"></i> ফিরে যান
    </a>
    <button onclick="window.print()" class="btn btn-primary">
      <i class="fas fa-print me-1"></i> প্রিন্ট করুন
    </button>
    <button onclick="downloadPDF()" class="btn btn-success">
      <i class="fas fa-download me-1"></i> PDF ডাউনলোড
    </button>
  </div>

  <div class="invoice-wrapper">
    <div class="invoice-box">

      <!-- Header -->
      <div class="invoice-header">
        <div class="row align-items-center">
          <div class="col-7">
            <div class="brand-name">
              <i class="fas fa-boxes me-2" style="color:#3498db"></i>
              Inventory System
            </div>
            <div class="brand-sub mt-1">
              <i class="fas fa-map-marker-alt me-1"></i> আপনার ঠিকানা এখানে<br>
              <i class="fas fa-phone me-1"></i> ০১৭XX-XXXXXX
            </div>
          </div>
          <div class="col-5">
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-no">
              #<?= htmlspecialchars($sale['invoice_no']) ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Info Section -->
      <div class="info-section">
        <div class="row g-3">
          <div class="col-sm-4 col-6">
            <div class="info-label">কাস্টমার</div>
            <div class="info-value">
              <?= htmlspecialchars($sale['customer_name'] ?? 'N/A') ?>
            </div>
          </div>
          <div class="col-sm-4 col-6">
            <div class="info-label">তারিখ</div>
            <div class="info-value">
              <?= date('d M Y', strtotime($sale['sale_date'])) ?>
            </div>
          </div>
          <div class="col-sm-4 col-6">
            <div class="info-label">সময়</div>
            <div class="info-value">
              <?= date('h:i A', strtotime($sale['sale_date'])) ?>
            </div>
          </div>
          <div class="col-sm-4 col-6">
            <div class="info-label">অবস্থা</div>
            <div class="info-value">
              <?php if ($sale['due_amount'] <= 0): ?>
              <span class="status-paid">✅ পরিশোধিত</span>
              <?php else: ?>
              <span class="status-due">⚠️ বাকি আছে</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Items Table -->
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>প্রোডাক্ট</th>
              <th class="text-center">পরিমাণ</th>
              <th class="text-end">একক মূল্য</th>
              <th class="text-end">সাবটোটাল</th>
            </tr>
          </thead>
          <tbody>
            <?php $i=1; while($item = mysqli_fetch_assoc($items)): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td><strong><?= htmlspecialchars($item['product_name']) ?></strong></td>
              <td class="text-center">
                <?= $item['quantity'] ?> <?= $item['unit'] ?>
              </td>
              <td class="text-end">৳<?= number_format($item['unit_price'], 2) ?></td>
              <td class="text-end">
                <strong>৳<?= number_format($item['subtotal'], 2) ?></strong>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <!-- Summary -->
      <div class="row justify-content-end">
        <div class="col-md-5 col-sm-7">
          <div class="summary-section">
            <div class="summary-row">
              <span class="text-muted">সর্বমোট:</span>
              <strong>৳<?= number_format($sale['total_amount'], 2) ?></strong>
            </div>
            <div class="summary-row">
              <span class="text-muted">পরিশোধ:</span>
              <span class="paid-badge">
                ৳<?= number_format($sale['paid_amount'], 2) ?>
              </span>
            </div>
            <div class="summary-row summary-total">
              <span>বাকি:</span>
              <?php if ($sale['due_amount'] > 0): ?>
              <span class="due-badge">
                ৳<?= number_format($sale['due_amount'], 2) ?>
              </span>
              <?php else: ?>
              <span class="paid-badge">পরিশোধিত</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="invoice-footer">
        <p class="mb-1">
          <strong>ধন্যবাদ আপনার ক্রয়ের জন্য!</strong>
        </p>
        <p class="mb-0">
          এই invoice টি কম্পিউটার দ্বারা তৈরি করা হয়েছে।
          কোনো প্রশ্নের জন্য আমাদের সাথে যোগাযোগ করুন।
        </p>
      </div>

    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function downloadPDF() {
  window.print();
}
</script>
</body>
</html>