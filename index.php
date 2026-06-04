<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-card {
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .brand-icon {
            font-size: 3rem;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card login-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-boxes brand-icon"></i>
                            <h3 class="mt-3 fw-bold">Inventory System</h3>
                            <p class="text-muted">শপের স্টক ও বিক্রয় ব্যবস্থাপনা</p>
                        </div>

                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger">
                                <?php 
                                    if($_GET['error'] == 'invalid') echo "ভুল ইউজারনেম অথবা পাসওয়ার্ড!";
                                    if($_GET['error'] == 'empty') echo "সব তথ্য পূরণ করুন!";
                                ?>
                            </div>
                        <?php endif; ?>

                        <form action="login_process.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label">ইউজারনেম</label>
                                <input type="text" name="username" class="form-control" value="admin" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">পাসওয়ার্ড</label>
                                <input type="password" name="password" class="form-control" value="password" required>
                            </div>
                            <button type="submit" name="login" class="btn btn-primary w-100 py-2 fw-bold">
                                <i class="fas fa-sign-in-alt"></i> লগইন করুন
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <small class="text-muted">
                                ডিফল্ট ইউজার:<br>
                                <strong>Username:</strong> admin<br>
                                <strong>Password:</strong> password
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>