<?php
$page_title = 'My Profile';
require_once '../config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Get current admin data
$query = "SELECT * FROM admin_users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['admin_id']);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    header("Location: logout.php");
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);

        // Validation
        if (empty($full_name) || empty($email) || empty($username)) {
            $error_message = "All fields are required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please enter a valid email address.";
        } elseif (strlen($username) < 3) {
            $error_message = "Username must be at least 3 characters long.";
        } else {
            // Check if email or username already exists (excluding current user)
            $check_query = "SELECT COUNT(*) as count FROM admin_users 
                           WHERE (email = :email OR username = :username) AND id != :id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(':email', $email);
            $check_stmt->bindParam(':username', $username);
            $check_stmt->bindParam(':id', $_SESSION['admin_id']);
            $check_stmt->execute();
            $exists = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;

            if ($exists) {
                $error_message = "Email or username already exists.";
            } else {
                $update_query = "UPDATE admin_users SET 
                                full_name = :full_name, 
                                email = :email, 
                                username = :username,
                                updated_at = CURRENT_TIMESTAMP
                                WHERE id = :id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(':full_name', $full_name);
                $update_stmt->bindParam(':email', $email);
                $update_stmt->bindParam(':username', $username);
                $update_stmt->bindParam(':id', $_SESSION['admin_id']);

                if ($update_stmt->execute()) {
                    // Update session variables
                    $_SESSION['admin_name'] = $full_name;
                    $_SESSION['admin_email'] = $email;
                    $_SESSION['admin_username'] = $username;

                    $success_message = "Profile updated successfully!";

                    // Refresh admin data
                    $stmt->execute();
                    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error_message = "Error updating profile.";
                }
            }
        }
    }

    // Handle password change
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = "All password fields are required.";
        } elseif (!password_verify($current_password, $admin['password'])) {
            $error_message = "Current password is incorrect.";
        } elseif (strlen($new_password) < 6) {
            $error_message = "New password must be at least 6 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $password_query = "UPDATE admin_users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $password_stmt = $db->prepare($password_query);
            $password_stmt->bindParam(':password', $hashed_password);
            $password_stmt->bindParam(':id', $_SESSION['admin_id']);

            if ($password_stmt->execute()) {
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "Error changing password.";
            }
        }
    }
}

include 'includes/header.php';
?>

<div id="alerts-container"></div>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">My Profile</h1>
    <div class="text-muted">
        <i class="fas fa-user-circle"></i> <?php echo ucfirst($admin['role']); ?>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <!-- Profile Card -->
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3">
                    <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width: 80px; height: 80px;">
                        <i class="fas fa-user fa-2x text-white"></i>
                    </div>
                </div>
                <h5 class="card-title"><?php echo htmlspecialchars($admin['full_name']); ?></h5>
                <p class="text-muted"><?php echo htmlspecialchars($admin['email']); ?></p>
                <div class="row text-center">
                    <div class="col-6">
                        <div class="border-end">
                            <div class="text-primary">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <small class="text-muted"><?php echo ucfirst($admin['role']); ?></small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <small class="text-muted"><?php echo ucfirst($admin['status']); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Info -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">Account Information</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Member Since</small>
                    <div><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></div>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Last Login</small>
                    <div>
                        <?php if ($admin['last_login']): ?>
                            <?php echo date('M d, Y H:i', strtotime($admin['last_login'])); ?>
                        <?php else: ?>
                            Never
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mb-0">
                    <small class="text-muted">Profile Updated</small>
                    <div><?php echo date('M d, Y H:i', strtotime($admin['updated_at'])); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <!-- Edit Profile Form -->
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-edit"></i> Edit Profile
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                value="<?php echo htmlspecialchars($admin['full_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="username" name="username"
                                value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email"
                            value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>

        <!-- Change Password Form -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-lock"></i> Change Password
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="current_password" name="current_password"
                                required>
                            <button class="btn btn-outline-secondary" type="button"
                                onclick="togglePassword('current_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="new_password" class="form-label">New Password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="new_password" name="new_password"
                                    minlength="6" required>
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-text">Minimum 6 characters</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password"
                                    name="confirm_password" minlength="6" required>
                                <button class="btn btn-outline-secondary" type="button"
                                    onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" name="change_password" class="btn btn-warning">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>
        </div>

        <!-- Activity Log -->
        <div class="card mt-3">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-history"></i> Recent Activity
                </h6>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0">
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 32px; height: 32px;">
                                <i class="fas fa-sign-in-alt text-white small"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Logged in to admin panel</h6>
                            <small class="text-muted">Today at <?php echo date('H:i'); ?></small>
                        </div>
                    </div>

                    <?php if ($admin['last_login']): ?>
                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0">
                                <div class="bg-success rounded-circle d-flex align-items-center justify-content-center"
                                    style="width: 32px; height: 32px;">
                                    <i class="fas fa-clock text-white small"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1">Previous login</h6>
                                <small
                                    class="text-muted"><?php echo date('M d, Y \a\t H:i', strtotime($admin['last_login'])); ?></small>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <div class="bg-info rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 32px; height: 32px;">
                                <i class="fas fa-user-plus text-white small"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Account created</h6>
                            <small
                                class="text-muted"><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const button = field.nextElementSibling;
        const icon = button.querySelector('i');

        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Password confirmation validation
    document.getElementById('confirm_password').addEventListener('input', function () {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = this.value;

        if (newPassword !== confirmPassword) {
            this.setCustomValidity('Passwords do not match');
        } else {
            this.setCustomValidity('');
        }
    });

    document.getElementById('new_password').addEventListener('input', function () {
        const confirmPassword = document.getElementById('confirm_password');
        if (confirmPassword.value) {
            confirmPassword.dispatchEvent(new Event('input'));
        }
    });
</script>

<?php include 'includes/footer.php'; ?>