<?php
session_start();
require_once 'partials/db.php';

// Define the secret code required to register as an admin.
define('ADMIN_SECRET_CODE', 'SUPERADMIN123');

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $email = $_POST['email'];
    $admin_code = $_POST['admin_code'] ?? ''; // Get the admin code if it was submitted

    // Determine the role based on the admin code
    if (!empty($admin_code) && $admin_code === ADMIN_SECRET_CODE) {
        $role = 'admin';
    } else {
        $role = 'user';
    }
    
    // Check if user already exists
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $error = "A user with this email already exists!";
    } else {
        // Updated INSERT statement to include the 'role'
        $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, role) VALUES (?, ?, ?, ?)");
        // Updated bind_param to include the role (s = string)
        $stmt->bind_param("ssss", $firstname, $lastname, $email, $role);
        
        if ($stmt->execute()) {
            if ($role === 'admin') {
                $message = "Admin registration successful! You can now <a href='login.php'>login</a>.";
            } else {
                $message = "Registration successful! You can now <a href='login.php'>login</a>.";
            }
        } else {
            $error = "Error: Could not register user.";
        }
        $stmt->close();
    }
    $stmt_check->close();
}

$page_title = "Register";
require_once 'partials/header.php';
?>
<div class="d-flex align-items-center justify-content-center min-vh-100">
    <div class="card register-card">
        <div class="card-body">
            <h3 class="card-title text-center mb-4">Create an Account</h3>
            
            <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

            <form method="POST" action="register.php">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="firstname" class="form-label">First Name</label>
                        <input type="text" class="form-control" name="firstname" id="firstname" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="lastname" class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="lastname" id="lastname" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" id="email" required>
                </div>
                
                <!-- THE FIX: This field separates admins from users -->
                <div class="mb-3">
                    <label for="admin_code" class="form-label">Admin Code (Optional)</label>
                    <input type="text" class="form-control" name="admin_code" id="admin_code" placeholder="Enter code to register as admin">
                </div>

                <p class="text-muted small">For this demo, your password will be your First Name.</p>
                <button type="submit" class="btn btn-success w-100">Register</button>
            </form>
            <p class="text-center mt-3">Already have an account? <a href="login.php">Login</a></p>
        </div>
    </div>
</div>
<?php require_once 'partials/footer.php'; ?>