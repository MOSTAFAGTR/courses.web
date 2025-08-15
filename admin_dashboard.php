<?php
session_start();
require_once 'partials/db.php';

// Security: Only admins can access this page.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- NEW ACTION: Promote a user to an instructor ---
if (isset($_GET['promote_id'])) {
    $user_to_promote_id = $_GET['promote_id'];
    $check_stmt = $conn->prepare("SELECT user_id FROM instructors WHERE user_id = ?");
    $check_stmt->bind_param("i", $user_to_promote_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO instructors (user_id) VALUES (?)");
        $stmt->bind_param("i", $user_to_promote_id);
        $stmt->execute();
        $stmt->close();
    }
    $check_stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// --- NEW ACTION: Demote an instructor back to a student ---
if (isset($_GET['demote_id'])) {
    $user_to_demote_id = $_GET['demote_id'];
    $stmt = $conn->prepare("DELETE FROM instructors WHERE user_id = ?");
    $stmt->bind_param("i", $user_to_demote_id);
    $stmt->execute();
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}

// (The existing Add/Update/Delete user logic remains the same)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_user'])) {
    $firstname = $_POST['firstname']; $lastname = $_POST['lastname']; $email = $_POST['email'];
    if (isset($_POST['update_id']) && !empty($_POST['update_id'])) {
        $id_to_update = $_POST['update_id'];
        $stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ? WHERE id = ?");
        $stmt->bind_param("sssi", $firstname, $lastname, $email, $id_to_update);
        if ($stmt->execute()) $_SESSION['update_message'] = "User updated successfully!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $firstname, $lastname, $email);
        if ($stmt->execute()) $_SESSION['message'] = "New user added successfully!";
    }
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) $_SESSION['delete_message'] = "User deleted successfully!";
    $stmt->close();
    header("Location: admin_dashboard.php");
    exit();
}
$edit_user = null;
if (isset($_GET['edit_id'])) {
    $id = $_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// --- DATA FETCHING (Now includes instructor status) ---
$instructor_ids = [];
$instructor_result = $conn->query("SELECT user_id FROM instructors");
while ($row = $instructor_result->fetch_assoc()) {
    $instructor_ids[] = $row['user_id'];
}

$search_query = $_GET['search'] ?? '';
$sql = "SELECT id, firstname, lastname, email, role, reg_date FROM users";
if (!empty($search_query)) {
    $search_term = "%" . $search_query . "%";
    $sql .= " WHERE firstname LIKE ? OR lastname LIKE ? OR email LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $search_term, $search_term, $search_term);
} else {
    $sql .= " ORDER BY id DESC";
    $stmt = $conn->prepare($sql);
}
$stmt->execute();
$users_result = $stmt->get_result();

$page_title = "Admin Dashboard";
require_once 'partials/header.php';
?>
<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin_dashboard.php">Admin Panel</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link active" href="admin_dashboard.php">Users</a></li>
                <li class="nav-item"><a class="nav-link" href="meetings.php">Courses</a></li>
            </ul>
            <ul class="navbar-nav ms-auto"><li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li></ul>
        </div>
    </div>
</nav>

<div class="container">
    <div class="text-center my-5"><h1 class="display-5 fw-bold">User Management Dashboard</h1></div>
    <?php if (isset($_SESSION['message'])): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= $_SESSION['message']; unset($_SESSION['message']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if (isset($_SESSION['update_message'])): ?><div class="alert alert-primary alert-dismissible fade show" role="alert"><?= $_SESSION['update_message']; unset($_SESSION['update_message']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <?php if (isset($_SESSION['delete_message'])): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= $_SESSION['delete_message']; unset($_SESSION['delete_message']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
    <div class="mb-3"><button type="button" class="btn btn-success w-100" data-bs-toggle="modal" data-bs-target="#userModal"><i class="bi bi-plus-circle-fill"></i> Add New User</button></div>
    <div class="card">
        <div class="card-header"><h5 class="mb-0"><i class="bi bi-people-fill"></i> Current Users</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead><tr><th class="py-3 px-4">Name</th><th class="py-3 px-4">Email</th><th class="py-3 px-4">Role</th><th class="py-3 px-4">Status</th><th class="py-3 px-4 text-end">Actions</th></tr></thead>
                    <tbody>
                        <?php if ($users_result->num_rows > 0): while($row = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td class="py-3 px-4"><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></td>
                            <td class="py-3 px-4"><?= htmlspecialchars($row['email']) ?></td>
                            <td class="py-3 px-4"><?= htmlspecialchars(ucfirst($row['role'])) ?></td>
                            <td class="py-3 px-4">
                                <?php if($row['role'] === 'admin'): ?>
                                    <span class="badge bg-danger">Site Admin</span>
                                <?php elseif(in_array($row['id'], $instructor_ids)): ?>
                                    <span class="badge bg-info">Instructor</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Student</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-end">
                                <?php if($row['role'] !== 'admin'): ?>
                                    <?php if(in_array($row['id'], $instructor_ids)): ?>
                                        <a href="admin_dashboard.php?demote_id=<?= $row['id'] ?>" class="btn btn-secondary btn-sm" title="Remove Instructor Role">Demote</a>
                                    <?php else: ?>
                                        <a href="admin_dashboard.php?promote_id=<?= $row['id'] ?>" class="btn btn-info btn-sm" title="Make this user an Instructor">Promote</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <a href="admin_dashboard.php?edit_id=<?= $row['id'] ?>" class="btn btn-outline-warning btn-sm border-0"><i class="bi bi-pencil-square"></i></a>
                                <a href="admin_dashboard.php?delete_id=<?= $row['id'] ?>" class="btn btn-outline-danger btn-sm border-0" onclick="return confirm('Are you sure?')"><i class="bi bi-trash3-fill"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="5" class="text-center p-4">No users found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- =================================================================== -->
<!-- == THE FIX: The missing Add/Edit User Modal HTML is now included == -->
<!-- =================================================================== -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="admin_dashboard.php">
                <input type="hidden" name="save_user" value="1">
                <input type="hidden" id="update_id" name="update_id" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalLabel">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" id="firstname" name="firstname" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="lastname" name="lastname" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="submit-button" class="btn btn-primary">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<section class="name-section">
    <h1 class="animated-name">Mostafa Mohamed Goda</h1>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const userModal = new bootstrap.Modal(document.getElementById('userModal'));
    
    <?php if ($edit_user): ?>
        // This part pre-fills the modal for editing a user
        document.getElementById('userModalLabel').textContent = 'Edit User Details';
        let btn = document.getElementById('submit-button');
        btn.textContent = 'Update User';
        btn.classList.replace('btn-primary', 'btn-warning');
        
        document.getElementById('update_id').value = '<?= $edit_user['id'] ?>';
        document.getElementById('firstname').value = '<?= addslashes(htmlspecialchars($edit_user['firstname'])) ?>';
        document.getElementById('lastname').value = '<?= addslashes(htmlspecialchars($edit_user['lastname'])) ?>';
        document.getElementById('email').value = '<?= addslashes(htmlspecialchars($edit_user['email'])) ?>';
        
        userModal.show();
    <?php endif; ?>

    // This part resets the modal to "Add" mode after it's closed
    document.getElementById('userModal').addEventListener('hidden.bs.modal', () => {
        document.getElementById('userModalLabel').textContent = 'Add New User';
        let btn = document.getElementById('submit-button');
        btn.textContent = 'Save User';
        btn.classList.replace('btn-warning', 'btn-primary');
        
        document.querySelector('#userModal form').reset();
        document.getElementById('update_id').value = '';
    });
});
</script>

<?php require_once 'partials/footer.php'; ?>