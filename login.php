<?php
session_start();
require_once 'partials/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, email, role, firstname, avatar_url FROM users WHERE email = ? AND firstname = ?");
    $stmt->bind_param("ss", $email, $password);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $email, $role, $firstname, $avatar_url);
        $stmt->fetch();
        $_SESSION['user_id'] = $id;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;
        $_SESSION['user_firstname'] = $firstname;
        $_SESSION['user_avatar'] = $avatar_url; // Store avatar in session
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid email or password!";
    }
    $stmt->close();
}

$page_title = "Login";
require_once 'partials/header.php';
?>
<div class="d-flex align-items-center justify-content-center min-vh-100">
    <div class="card login-card">
        <div class="card-body">
            <h3 class="card-title text-center mb-4">Login</h3>
            <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" id="email" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password (Use First Name)</label>
                    <input type="password" class="form-control" name="password" id="password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>
            <p class="text-center mt-3">Don't have an account? <a href="register.php">Register</a></p>
        </div>
    </div>
</div>
<?php require_once 'partials/footer.php'; ?>