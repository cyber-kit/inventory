<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'techcamp_inv_user');
define('DB_PASS', 'Inv@12345###');
define('DB_NAME', 'techcamp_inventory');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");
?>