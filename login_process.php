<?php
session_start();
require_once 'config/database.php';

if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // খালি চেক
    if (empty($username) || empty($password)) {
        header("Location: index.php?error=empty");
        exit();
    }

    // Database থেকে user খোঁজো
    $username = mysqli_real_escape_string($conn, $username);
    $sql = "SELECT * FROM users WHERE username = '$username' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);

        // Password verify করো
        if (password_verify($password, $user['password'])) {
            // Session তৈরি করো
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];

            header("Location: dashboard.php");
            exit();
        } else {
            header("Location: index.php?error=invalid");
            exit();
        }
    } else {
        header("Location: index.php?error=invalid");
        exit();
    }

} else {
    header("Location: index.php");
    exit();
}
?>