<?php
session_start();
require_once 'config/database.php';

// Category Add
if (isset($_POST['add_category'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $desc = mysqli_real_escape_string($conn, trim($_POST['description']));
    if (!empty($name)) {
        mysqli_query($conn, "INSERT INTO categories (name, description) VALUES ('$name', '$desc')");
    }
    header("Location: categories.php?msg=added");
    exit();
}

// Category Edit
if (isset($_POST['edit_category'])) {
    $id = (int)$_POST['id'];
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $desc = mysqli_real_escape_string($conn, trim($_POST['description']));
    if (!empty($name)) {
        mysqli_query($conn, "UPDATE categories SET name='$name', description='$desc' WHERE id=$id");
    }
    header("Location: categories.php?msg=updated");
    exit();
}

// Category Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM categories WHERE id=$id");
    header("Location: categories.php?msg=deleted");
    exit();
}

require_once 'includes/header.php';

// Get all categories
$categories = mysqli_query($conn, "SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY c.name ASC");
?>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
  <div class="topbar">
    <div class="d-flex align-items-center gap-2">
      <button class="hamburger" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
      </button>
      <h5 class="mb-0 fw-bold">
        <i class="fas fa-tags me-2 text-primary"></i> ক্যাটাগরি ম্যানেজমেন্ট
      </h5>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
      <i class="fas fa-plus me-1"></i> নতুন ক্যাটাগরি
    </button>
  </div>

  <?php if (isset($_GET['msg'])): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php
    if ($_GET['msg'] == 'added') echo "✅ ক্যাটাগরি সফলভাবে যোগ হয়েছে!";
    if ($_GET['msg'] == 'updated') echo "✅ ক্যাটাগরি আপডেট হয়েছে!";
    if ($_GET['msg'] == 'deleted') echo "✅ ক্যাটাগরি ডিলিট হয়েছে!";
    ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <div class="card table-card">
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>ক্যাটাগরি নাম</th>
            <th>বিবরণ</th>
            <th>প্রোডাক্ট সংখ্যা</th>
            <th>তারিখ</th>
            <th>অ্যাকশন</th>
          </tr>
        </thead>
        <tbody>
          <?php $i=1; while($row = mysqli_fetch_assoc($categories)): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
            <td><?= htmlspecialchars($row['description'] ?? '-') ?></td>
            <td><span class="badge bg-primary"><?= $row['product_count'] ?></span></td>
            <td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
            <td>
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                data-bs-target="#editModal<?= $row['id'] ?>">
                <i class="fas fa-edit"></i>
              </button>
              <a href="categories.php?delete=<?= $row['id'] ?>" 
                class="btn btn-sm btn-outline-danger"
                onclick="return confirm('আপনি কি নিশ্চিত?')">
                <i class="fas fa-trash"></i>
              </a>
            </td>
          </tr>

          <!-- Edit Modal -->
          <div class="modal fade" id="editModal<?= $row['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content">
                <form method="POST">
                  <div class="modal-header">
                    <h5 class="modal-title">ক্যাটাগরি এডিট</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <div class="mb-3">
                      <label class="form-label">ক্যাটাগরি নাম</label>
                      <input type="text" name="name" class="form-control" 
                        value="<?= htmlspecialchars($row['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">বিবরণ</label>
                      <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($row['description'] ?? '') ?></textarea>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
                    <button type="submit" name="edit_category" class="btn btn-primary">আপডেট</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          <?php endwhile; ?>

          <?php if (mysqli_num_rows($categories) == 0): ?>
          <tr>
            <td colspan="6" class="text-center text-muted py-4">
              কোনো ক্যাটাগরি নেই। নতুন যোগ করুন!
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
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fas fa-plus me-2"></i>নতুন ক্যাটাগরি</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">ক্যাটাগরি নাম</label>
            <input type="text" name="name" class="form-control" placeholder="যেমন: ইলেকট্রনিক্স" required>
          </div>
          <div class="mb-3">
            <label class="form-label">বিবরণ (ঐচ্ছিক)</label>
            <textarea name="description" class="form-control" rows="3" placeholder="ক্যাটাগরি সম্পর্কে বিবরণ..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">বাতিল</button>
          <button type="submit" name="add_category" class="btn btn-primary">
            <i class="fas fa-save me-1"></i> সেভ করুন
          </button>
        </div>
      </form>
    </div>
  </div>
</div>