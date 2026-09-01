<?php
session_start();

// Redirect logged-in users (prevent duplicate registration)
if (isset($_SESSION['username'])) {
    header("Location: index.html"); // Or dashboard.php
    exit();
}

$conn = new mysqli("localhost", "root", "", "weather_database");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $gender = $_POST['gender'];
    $phone = $conn->real_escape_string($_POST['phone']);
    $user_type = "user";

    // Check for duplicate username or email
    $check = $conn->query("SELECT * FROM users WHERE username='$username' OR email='$email'");
    if ($check->num_rows > 0) {
        $message = "⚠️ Username or Email already exists!";
    } else {
        $conn->query("INSERT INTO users (username, email, password, gender, phone, user_type)
            VALUES ('$username', '$email', '$password', '$gender', '$phone', '$user_type')");
        $message = "✅ Registration successful!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - User Only</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Your CSS styles here */
        body {background: linear-gradient(135deg, #8fd3f4 0%, #a6c1ee 50%, #fbc2eb 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;}
        .card {width: 450px; border-radius: 24px; background: rgba(255,255,255,0.98); box-shadow: 0 10px 40px rgba(170,120,220,0.13), 0 2px 16px rgba(68,138,255,0.11); border: none;}
        h3.text-center {background: linear-gradient(90deg, #8fd3f4, #fbc2eb 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: bold; margin-bottom: 20px;}
        .btn-success {background: linear-gradient(90deg, #8fd3f4, #91eac9 100%); border: none; font-weight: bold; box-shadow: 0 2px 8px rgba(170,120,220,0.10);}
        .btn-outline-secondary {border-color: #8fd3f4; color: #8fd3f4; background: #fff;}
        .form-control:focus {border-color: #8fd3f4; box-shadow: 0 0 0 0.12rem #8fd3f465;}
        .form-select:focus {border-color: #fbc2eb; box-shadow: 0 0 0 0.12rem #fbc2eb65;}
        .alert-info {background: linear-gradient(90deg, #a6c1ee 0%, #fbc2eb 100%); color: #495d7f; border: none; font-weight: bold;}
    </style>
</head>
<body>
<div class="card shadow p-4">
    <h3 class="text-center">📝 User Registration</h3>
    <?php if ($message): ?>
        <div class="alert alert-info"><?= $message ?></div>
    <?php endif; ?>
    <form method="POST" onsubmit="return validateRegister()">
        <div class="mb-3">
            <label>Username</label>
            <input type="text" class="form-control" name="username" required>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" class="form-control" name="email" required>
        </div>
        <div class="mb-3">
            <label>Password</label>
            <div class="input-group">
                <input type="password" class="form-control" name="password" id="regPass" required>
                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('regPass')">👁</button>
            </div>
        </div>
        <div class="mb-3">
            <label>Gender</label><br>
            <select class="form-select" name="gender" required>
                <option value="">-- Select Gender --</option>
                <option value="male">👨 Male</option>
                <option value="female">👩 Female</option>
                <option value="other">⚧ Other</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Phone</label>
            <input type="text" class="form-control" name="phone" placeholder="Optional">
        </div>
        <button class="btn btn-success w-100">Register</button>
    </form>
    <p class="text-center mt-3">Already have an account? <a href="login.php">Login</a></p>
</div>
<script>
function togglePassword(id) {
    let field = document.getElementById(id);
    field.type = field.type === "password" ? "text" : "password";
}
function validateRegister() {
    let pass = document.getElementById("regPass").value;
    if(pass.length < 6) {
        alert("⚠ Password must be at least 6 characters.");
        return false;
    }
    return true;
}
</script>
</body>
</html>
