<?php
session_start();

// Set timezone to Indian Standard Time (Asia/Kolkata)
date_default_timezone_set('Asia/Kolkata');

if (!isset($_SESSION['username']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "weather_database");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
$error = '';

// Initialize variables for add user
$username_val = '';
$email_val = '';
$gender_val = '';
$phone_val = '';

// Variables for edit user
$edit_mode = false;
$edit_user = null;
$edit_user_id = 0;
$edit_username_val = '';
$edit_email_val = '';
$edit_gender_val = '';
$edit_phone_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add User form submission
    if (isset($_POST['add_user'])) {
        $username_val = htmlspecialchars(trim($_POST['username']));
        $email_val = htmlspecialchars(trim($_POST['email']));
        $gender_val = $_POST['gender'] ?? '';
        $phone_val = htmlspecialchars(trim($_POST['phone']));
        $password_raw = $_POST['password'];
        $user_type = "user";

        if (strlen($password_raw) < 6) {
            $error = "⚠ Password must be at least 6 characters.";
        } else {
            $check = $conn->query("SELECT * FROM users WHERE (username='$username_val' OR email='$email_val')");
            if ($check->num_rows > 0) {
                $error = "⚠ Username or Email already exists!";
            } else {
                $password = password_hash($password_raw, PASSWORD_DEFAULT);
                $conn->query("INSERT INTO users (username, email, password, gender, phone, user_type) VALUES ('$username_val', '$email_val', '$password', '$gender_val', '$phone_val', '$user_type')");
                if ($conn->affected_rows > 0) {
                    $message = "✅ User added successfully!";
                    $username_val = $email_val = $gender_val = $phone_val = '';
                } else {
                    $error = "❌ Failed to add user.";
                }
            }
        }
    }

    // Handle user deletion request
    if (isset($_POST['delete_user_id'])) {
        $delete_id = (int)$_POST['delete_user_id'];
        $current_admin_username = $_SESSION['username'];
        $resultCheck = $conn->query("SELECT username, user_type FROM users WHERE id = $delete_id");
        if ($resultCheck && $row = $resultCheck->fetch_assoc()) {
            if ($row['username'] === $current_admin_username) {
                $error = "⛔ You cannot delete your own account.";
            } elseif ($row['user_type'] === 'admin') {
                $error = "⛔ You cannot delete another admin account.";
            } else {
                $conn->query("DELETE FROM users WHERE id = $delete_id AND user_type = 'user'");
                if ($conn->affected_rows > 0) {
                    $message = "User deleted successfully.";
                } else {
                    $error = "Failed to delete user.";
                }
            }
        }
    }

    // Edit user - show edit form
    if (isset($_POST['edit_user_id'])) {
        $edit_user_id = (int)$_POST['edit_user_id'];
        $result = $conn->query("SELECT * FROM users WHERE id = $edit_user_id AND user_type != 'admin' LIMIT 1");
        if ($result && $result->num_rows == 1) {
            $edit_user = $result->fetch_assoc();
            $edit_mode = true;
            $edit_username_val = htmlspecialchars($edit_user['username']);
            $edit_email_val = htmlspecialchars($edit_user['email']);
            $edit_gender_val = $edit_user['gender'];
            $edit_phone_val = htmlspecialchars($edit_user['phone']);
        }
    }

    // Handle update user submission
    if (isset($_POST['update_user'])) {
        $edit_user_id = (int)$_POST['user_id'];
        $edit_username_val = htmlspecialchars(trim($_POST['username']));
        $edit_email_val = htmlspecialchars(trim($_POST['email']));
        $edit_gender_val = $_POST['gender'] ?? '';
        $edit_phone_val = htmlspecialchars(trim($_POST['phone']));
        $password_raw = $_POST['password']; // Optional password change

        // Validate inputs (simple validation)
        if (strlen($edit_username_val) < 3) {
            $error = "⚠ Username must be at least 3 characters.";
            $edit_mode = true;
        } elseif (!filter_var($edit_email_val, FILTER_VALIDATE_EMAIL)) {
            $error = "⚠ Invalid email format.";
            $edit_mode = true;
        } else {
            // Check for duplicate username/email except for current user
            $check = $conn->query("SELECT * FROM users WHERE (username='$edit_username_val' OR email='$edit_email_val') AND id != $edit_user_id");
            if ($check->num_rows > 0) {
                $error = "⚠ Username or Email already exists for another user!";
                $edit_mode = true;
            } else {
                // Build UPDATE query
                $update_sql = "UPDATE users SET username='$edit_username_val', email='$edit_email_val', gender='$edit_gender_val', phone='$edit_phone_val'";

                if ($password_raw !== '') {
                    if (strlen($password_raw) < 6) {
                        $error = "⚠ Password must be at least 6 characters.";
                        $edit_mode = true;
                    } else {
                        $hashed_pass = password_hash($password_raw, PASSWORD_DEFAULT);
                        $update_sql .= ", password='$hashed_pass'";
                    }
                }

                if (!$edit_mode) {
                    $update_sql .= " WHERE id = $edit_user_id AND user_type != 'admin'";
                    $conn->query($update_sql);
                    if ($conn->affected_rows >= 0) {
                        $message = "✅ User updated successfully.";
                        $edit_mode = false;
                        // Clear edit vars
                        $edit_user_id = 0;
                        $edit_username_val = $edit_email_val = $edit_gender_val = $edit_phone_val = '';
                    } else {
                        $error = "❌ Failed to update user.";
                        $edit_mode = true;
                    }
                }
            }
        }
    }
}
// Fetch remaining users excluding admins for listing
$result = $conn->query("SELECT id, username, email, gender, phone, user_type FROM users WHERE user_type != 'admin' ORDER BY username ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-dark text-light p-5">
<div class="container">
    <h1 class="mb-3">⚙️ Admin Panel - <?= htmlspecialchars($_SESSION['username']); ?></h1>
    <p>Manage users / settings here.</p>
    <p>📅 Current Time: <strong><?= date("d M Y, h:i A"); ?></strong></p>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Add User Section -->
    <button id="toggleAddUserBtn" class="btn btn-primary mb-3">Add New User</button>
    <div id="addUserFormContainer" class="card bg-secondary text-dark mb-4 p-4" style="display:none;">
        <h3>Add New User</h3>
        <form method="POST" novalidate onsubmit="return validateAddUserForm()">
            <input type="hidden" name="add_user" value="1" />
            <div class="mb-3">
                <label for="username" class="form-label text-dark">Username *</label>
                <input type="text" id="username" name="username" class="form-control" required minlength="3" 
                    value="<?= isset($username_val) ? $username_val : '' ?>" />
            </div>
            <div class="mb-3">
                <label for="email" class="form-label text-dark">Email *</label>
                <input type="email" id="email" name="email" class="form-control" required 
                    value="<?= isset($email_val) ? $email_val : '' ?>" />
            </div>
            <div class="mb-3">
                <label for="password" class="form-label text-dark">Password *</label>
                <input type="password" id="password" name="password" class="form-control" required minlength="6" />
            </div>
            <div class="mb-3">
                <label for="gender" class="form-label text-dark">Gender *</label>
                <select id="gender" name="gender" class="form-select" required>
                    <option value="">-- Select Gender --</option>
                    <option value="male" <?= (isset($gender_val) && $gender_val === 'male') ? 'selected' : '' ?>>Male 👨</option>
                    <option value="female" <?= (isset($gender_val) && $gender_val === 'female') ? 'selected' : '' ?>>Female 👩</option>
                    <option value="other" <?= (isset($gender_val) && $gender_val === 'other') ? 'selected' : '' ?>>Other ⚧</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label text-dark">Phone (Optional)</label>
                <input type="text" id="phone" name="phone" class="form-control" 
                    value="<?= isset($phone_val) ? $phone_val : '' ?>" />
            </div>
            <button type="submit" class="btn btn-success">Add User</button>
        </form>
    </div>

    <!-- Users Table -->
    <h3>Registered Users</h3>
    <?php if ($result && $result->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-striped table-light align-middle rounded-4 shadow-sm">
            <thead style="background: #6f5b3e; color: #f9f6f0;">
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Gender</th>
                    <th>Phone</th>
                    <th>User Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
             <?php while ($user = $result->fetch_assoc()): ?>
             <tr style="background: #c4ae78; color: #171515;">
                 <td><?= htmlspecialchars($user['username']) ?></td>
                 <td><?= htmlspecialchars($user['email']) ?></td>
                 <td><?= htmlspecialchars(ucfirst($user['gender'])) ?></td>
                 <td><?= htmlspecialchars($user['phone']) ?: 'N/A' ?></td>
                 <td><?= htmlspecialchars(ucfirst($user['user_type'])) ?></td>
                 <td>
                   <form method="POST" style="display:inline;">
                       <input type="hidden" name="edit_user_id" value="<?= (int)$user['id'] ?>" />
                       <button type="submit" class="btn btn-sm btn-info text-light" style="background:#6f5b3e; border:none;">Edit</button>
                   </form>
                   <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                       <input type="hidden" name="delete_user_id" value="<?= (int)$user['id'] ?>" />
                       <button type="submit" class="btn btn-sm btn-danger text-light" style="background:#6f5b3e; border:none;">Delete</button>
                   </form>
                 </td>
             </tr>
             <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <p>No users found.</p>
    <?php endif; ?>

    <!-- Edit User Form -->
    <?php if ($edit_mode && $edit_user): ?>
    <div class="card bg-secondary text-dark mt-4 p-4" style="background: #c4ae78;">
        <h3>Edit User: <?= htmlspecialchars($edit_username_val) ?></h3>
        <form method="POST" novalidate onsubmit="return validateEditUserForm()">
            <input type="hidden" name="update_user" value="1" />
            <input type="hidden" name="user_id" value="<?= $edit_user_id ?>" />
            <div class="mb-3">
                <label for="edit_username" class="form-label text-dark">Username *</label>
                <input type="text" id="edit_username" name="username" class="form-control" required minlength="3" 
                    value="<?= $edit_username_val ?>" />
            </div>
            <div class="mb-3">
                <label for="edit_email" class="form-label text-dark">Email *</label>
                <input type="email" id="edit_email" name="email" class="form-control" required 
                    value="<?= $edit_email_val ?>" />
            </div>
            <div class="mb-3">
                <label for="edit_password" class="form-label text-dark">Password (leave blank to keep current)</label>
                <input type="password" id="edit_password" name="password" class="form-control" minlength="6" />
            </div>
            <div class="mb-3">
                <label for="edit_gender" class="form-label text-dark">Gender *</label>
                <select id="edit_gender" name="gender" class="form-select" required>
                    <option value="">-- Select Gender --</option>
                    <option value="male" <?= $edit_gender_val === 'male' ? 'selected' : '' ?>>Male 👨</option>
                    <option value="female" <?= $edit_gender_val === 'female' ? 'selected' : '' ?>>Female 👩</option>
                    <option value="other" <?= $edit_gender_val === 'other' ? 'selected' : '' ?>>Other ⚧</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="edit_phone" class="form-label text-dark">Phone (Optional)</label>
                <input type="text" id="edit_phone" name="phone" class="form-control" 
                    value="<?= $edit_phone_val ?>" />
            </div>
            <button type="submit" class="btn btn-success" style="background:#6f5b3e; border:none;">Update User</button>
        </form>
    </div>
    <?php endif; ?>

    <a href="logout.php" class="btn btn-warning mt-4" style="background:#6f5b3e; border:none; color:#f9f6f0;">Logout</a>
</div>

<script>
document.getElementById('toggleAddUserBtn').addEventListener('click', function() {
    var formContainer = document.getElementById('addUserFormContainer');
    if (formContainer.style.display === 'none' || formContainer.style.display === '') {
        formContainer.style.display = 'block';
        this.textContent = 'Hide Add User Form';
    } else {
        formContainer.style.display = 'none';
        this.textContent = 'Add New User';
    }
});
function validateAddUserForm() {
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const gender = document.getElementById('gender').value;
    if (username.length < 3) {
        alert('⚠ Username must be at least 3 characters long.');
        return false;
    }
    if (!email.includes('@')) {
        alert('⚠ Please enter a valid email address.');
        return false;
    }
    if (password.length < 6) {
        alert('⚠ Password must be at least 6 characters long.');
        return false;
    }
    if (!gender) {
        alert('⚠ Please select a gender.');
        return false;
    }
    return true;
}
function validateEditUserForm() {
    const username = document.getElementById('edit_username').value.trim();
    const email = document.getElementById('edit_email').value.trim();
    const password = document.getElementById('edit_password').value;
    const gender = document.getElementById('edit_gender').value;
    if (username.length < 3) {
        alert('⚠ Username must be at least 3 characters long.');
        return false;
    }
    if (!email.includes('@')) {
        alert('⚠ Please enter a valid email address.');
        return false;
    }
    if (password !== '' && password.length < 6) {
        alert('⚠ Password must be at least 6 characters long if changing.');
        return false;
    }
    if (!gender) {
        alert('⚠ Please select a gender.');
        return false;
    }
    return true;
}
</script>
<style>
body.bg-dark {
    background-color: #171515;
    color: #f9f6f0;
    font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
    min-height: 100vh;
}

.container {
    background: #f9f6f0;
    border-radius: 24px;
    box-shadow: 0 8px 40px rgba(111, 91, 62, 0.3);
    padding: 2.5rem;
    margin-top: 40px;
    color: #171515;
}

h1, h3 {
    background: linear-gradient(90deg, #c4ae78 40%, #6f5b3e 80%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: bold;
}

.btn-primary, .btn-success {
    background: #6f5b3e;
    border: none;
    font-weight: bold;
    color: #f9f6f0;
    box-shadow: 0 2px 12px rgba(111, 91, 62, 0.5);
    transition: box-shadow 0.2s ease-in-out;
}

.btn-primary:hover, .btn-success:hover {
    box-shadow: 0 4px 20px rgba(111, 91, 62, 0.8);
    background: #c4ae78;
    color: #171515;
}

.btn-warning, .btn-info, .btn-danger {
    font-weight: bold;
    border: none;
    color: #171515;
    background: #c4ae78;
    box-shadow: 0 1px 6px rgba(111, 91, 62, 0.4);
}

.card.bg-secondary {
    background: #c4ae78;
    border-radius: 18px;
    box-shadow: 0 4px 16px rgba(111, 91, 62, 0.5);
    border: none;
    color: #171515;
}

.table-light {
    background: #f9f6f0;
    border-radius: 16px;
    overflow: hidden;
    color: #171515;
}

.table-light th, .table-light td {
    background: #f9f6f0;
    color: #6f5b3e;
}

.table-light thead th {
    background: #6f5b3e;
    color: #f9f6f0;
}

.alert-success {
    background: linear-gradient(90deg, #c4ae78 10%, #6f5b3e 100%);
    color: #171515;
    font-weight: bold;
    border: none;
}

.alert-danger {
    background: #6f5b3e;
    color: #f9f6f0;
    font-weight: bold;
    border: none;
}

a.btn.btn-warning {
    box-shadow: 0 2px 8px rgba(111, 91, 62, 0.6);
    transition: all 0.3s ease;
}

a.btn.btn-warning:hover {
    background: #c4ae78;
    color: #171515;
    box-shadow: 0 4px 14px rgba(111, 91, 62, 0.9);
}

::-webkit-scrollbar {
    width: 8px;
    background: #171515;
}

::-webkit-scrollbar-thumb {
    background: #6f5b3e;
    border-radius: 10px;
}
</style>
</body>
</html>
