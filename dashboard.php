<?php
session_start();
require_once 'config/database.php';
require_once 'includes/header.php';

// Dashboard এর সব তথ্য
$total_products  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products"))['total'];
$total_categories = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM categories"))['total'];
$total_stock     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stock_qty) as total FROM products"))['total'];
$low_stock       = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE stock_qty <= alert_qty"))['total'];
$total_sales     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM sales"))['total'];
$total_revenue   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as total FROM sales"))['total'];

// Low Stock Products
$low_stock_products = mysqli_query($conn, "
    SELECT p.name, p.stock_qty, p.alert_qty, c.name as category 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.stock_qty <= p.alert_qty 
    ORDER BY p.stock_qty ASC 
    LIMIT 5
");

// সাম্প্রতিক বিক্রয়
$recent_sales = mysqli_query($conn, "
    SELECT * FROM sales 
    ORDER BY sale_date DESC 
    LIMIT 5
");
?>

<div class="sidebar">
    <?php include 'includes/sidebar.php'; ?>
</div>

<div class="main-content">
    <div class="topbar">
    <div class="d-flex align-items-center gap-2">
      <button class="hamburger" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
      </button>
      <h5 class="mb-0 fw-bold">
        <i class="fas fa-tachometer-alt me-2 text-primary"></i> Dashboard
      </h5>
    </div>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted">
                <i class="fas fa-user-circle me-1"></i>
                <?= $_SESSION['full_name'] ?>
            </span>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">

        <div class="col-md-4 col-sm-6">
            <div class="card stat-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">মোট প্রোডাক্ট</div>
                        <div class="fs-3 fw-bold"><?= $total_products ?></div>
                    </div>
                    <div class="stat-icon" style="background:#3498db">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-6">
            <div class="card stat-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">মোট ক্যাটাগরি</div>
                        <div class="fs-3 fw-bold"><?= $total_categories ?></div>
                    </div>
                    <div class="stat-icon" style="background:#9b59b6">
                        <i class="fas fa-tags"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-6">
            <div class="card stat-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">মোট স্টক</div>
                        <div class="fs-3 fw-bold"><?= $total_stock ?? 0 ?></div>
                    </div>
                    <div class="stat-icon" style="background:#27ae60">
                        <i class="fas fa-cubes"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-6">
            <div class="card stat-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">কম স্টক Alert</div>
                        <div class="fs-3 fw-bold text-danger"><?= $low_stock ?></div>
                    </div>
                    <div class="stat-icon" style="background:#e74c3c">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-6">
            <div class="card stat-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">মোট বিক্রয়</div>
                        <div class="fs-3 fw-bold"><?= $total_sales ?></div>
                    </div>
                    <div class="stat-icon" style="background:#e67e22">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-6">
            <div class="card stat-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">মোট আয়</div>
                        <div class="fs-3 fw-bold">৳<?= number_format($total_revenue ?? 0, 2) ?></div>
                    </div>
                    <div class="stat-icon" style="background:#16a085">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Low Stock & Recent Sales -->
    <div class="row g-3">

        <div class="col-md-6">
            <div class="card table-card">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="fas fa-exclamation-triangle text-danger me-2"></i> কম স্টক প্রোডাক্ট
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>প্রোডাক্ট</th>
                                <th>ক্যাটাগরি</th>
                                <th>স্টক</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (mysqli_num_rows($low_stock_products) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($low_stock_products)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['category'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge badge-low px-2 py-1">
                                        <?= $row['stock_qty'] ?> টি
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">
                                    কম স্টক নেই ✅
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card table-card">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="fas fa-shopping-cart text-primary me-2"></i> সাম্প্রতিক বিক্রয়
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice</th>
                                <th>কাস্টমার</th>
                                <th>মোট</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (mysqli_num_rows($recent_sales) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($recent_sales)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['invoice_no']) ?></td>
                                <td><?= htmlspecialchars($row['customer_name'] ?? 'N/A') ?></td>
                                <td>৳<?= number_format($row['total_amount'], 2) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">
                                    কোনো বিক্রয় নেই এখনো
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