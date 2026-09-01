<?php
session_start();
$conn = new mysqli("localhost", "root", "", "weather_database");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    $login_as = $_POST['login_as']; // user or admin

    $result = $conn->query("SELECT * FROM users WHERE email='$email' LIMIT 1");
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            if ($login_as == "admin" && $user['user_type'] != "admin") {
                $message = "⛔ You are not authorized as an Admin!";
            } elseif ($login_as == "user" && $user['user_type'] != "user") {
                $message = "⛔ This account is not a User account!";
            } else {
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'];

                header("Location: " . ($user['user_type'] == 'admin' ? "admin_dashboard.php" : "7day.html"));
                exit;
            }
        } else {
            $message = "❌ Invalid password!";
        }
    } else {
        $message = "⚠️ Email not found!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login with Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
body {
    background: linear-gradient(135deg, #7f7fd5 0%, #86a8e7 50%, #91eac9 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
}

.card {
    width: 400px;
    border-radius: 25px;
    background: #fff;
    box-shadow: 0 10px 40px rgba(70, 72, 232, 0.16);
    border: none;
}

h3.text-center {
    background: linear-gradient(90deg, #7f7fd5 0%, #91eac9 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: bold;
    margin-bottom: 20px;
}

.btn-primary {
    background: linear-gradient(90deg, #7f7fd5 0%, #86a8e7 100%);
    border: none;
    box-shadow: 0 2px 8px rgba(127, 127, 213, 0.12);
    font-weight: bold;
}

.btn-outline-secondary {
    border-color: #86a8e7;
    color: #86a8e7;
    background: #fff;
}

.form-control:focus {
    border-color: #7f7fd5;
    box-shadow: 0 0 0 0.12rem #7f7fd565;
}

.form-select:focus {
    border-color: #91eac9;
    box-shadow: 0 0 0 0.12rem #91eac965;
}

.alert-danger {
    background: linear-gradient(90deg, #ff677d 0%, #ffc2c2 100%);
    color: #7f2533;
    border: none;
    font-weight: bold;
}
</style>

</head>
<body>
<div class="card shadow p-4">
    <h3 class="text-center">🔐 Login</h3>
    <?php if ($message): ?>
        <div class="alert alert-danger"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST" onsubmit="return validateLogin()">
        <div class="mb-3">
            <label>Email Address</label>
            <input type="email" class="form-control" name="email" required autofocus>
        </div>
        <div class="mb-3">
            <label>Password</label>
            <div class="input-group">
                <input type="password" class="form-control" name="password" id="loginPass" required>
                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('loginPass')">👁</button>
            </div>
        </div>
        <div class="mb-3">
            <label>Login As</label>
            <select class="form-select" name="login_as" required>
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <button class="btn btn-primary w-100">Login</button>
    </form>

    <p class="text-center mt-3">Don’t have an account? <a href="http://localhost/weather/register.php">Register</a></p>
</div>

<script>
function togglePassword(id) {
    let field = document.getElementById(id);
    field.type = field.type === "password" ? "text" : "password";
}

function validateLogin() {
    let email = document.querySelector('[name="email"]').value;
    let password = document.querySelector('[name="password"]').value;
    if (!email || !password) {
        alert("⚠ Please fill in all fields.");
        return false;
    }
    return true;
}
</script>
</body>
</html>
