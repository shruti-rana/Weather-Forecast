<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['user_type'] != 'user') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5 bg-light">
    <div class="container text-center">
        <h1>👋 Welcome, <?= htmlspecialchars($_SESSION['username']); ?></h1>
        <p class="text-muted">This is your user dashboard.</p>
        <p>📅 Today’s Date: <strong><?= date("d M Y, h:i A"); ?></strong></p>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
</body>
</html>
