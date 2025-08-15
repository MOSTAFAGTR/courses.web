<?php
session_start();
require_once 'partials/db.php';

// Security: Only admins can access this page.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// --- HANDLE ALL FORM SUBMISSIONS ON THIS PAGE ---

// Handle adding a new course
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_course'])) {
    $course_name = $_POST['course_name'];
    $instructor_id = $_POST['instructor_id'];
    $description = $_POST['description'];
    
    $stmt = $conn->prepare("INSERT INTO courses (course_name, instructor_id, description) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $course_name, $instructor_id, $description);
    $stmt->execute();
    header("Location: meetings.php");
    exit();
}

// ** THE FIX: Handle enrolling a student into a course **
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enroll_student'])) {
    $course_id = $_POST['course_id'];
    $student_id = $_POST['student_id'];
    
    // Check if student is already enrolled to prevent duplicates
    $check_stmt = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
    $check_stmt->bind_param("ii", $student_id, $course_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $student_id, $course_id);
        $stmt->execute();
    }
    header("Location: meetings.php?view_id=" . $course_id);
    exit();
}

// Handle removing an enrolled student
if (isset($_GET['remove_enrollment_id'])) {
    $enrollment_id = $_GET['remove_enrollment_id'];
    $course_id = $_GET['course_id']; // Needed to redirect back
    $stmt = $conn->prepare("DELETE FROM enrollments WHERE id = ?");
    $stmt->bind_param("i", $enrollment_id);
    $stmt->execute();
    header("Location: meetings.php?view_id=" . $course_id);
    exit();
}

// --- FETCH DATA FOR DISPLAY ---
$courses_list = $conn->query("SELECT c.id, c.course_name, u.firstname, u.lastname FROM courses c JOIN users u ON c.instructor_id = u.id ORDER BY c.id DESC");
$instructors = $conn->query("SELECT u.id, u.firstname, u.lastname FROM users u JOIN instructors i ON u.id = i.user_id ORDER BY u.firstname");
$students = $conn->query("SELECT id, firstname, lastname FROM users WHERE role = 'student' AND id NOT IN (SELECT user_id FROM instructors)");


$page_title = "Manage Courses";
require_once 'partials/header.php';
?>
<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="admin_dashboard.php">Admin Panel</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Users</a></li>
                <li class="nav-item"><a class="nav-link active" href="meetings.php">Courses</a></li>
            </ul>
            <ul class="navbar-nav ms-auto"><li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li></ul>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <h1 class="display-5 fw-bold text-center mb-5">Course Management</h1>

    <?php if(isset($_GET['view_id'])): 
        // --- THIS IS THE DETAILED "MANAGE" VIEW FOR A SINGLE COURSE ---
        $course_id = $_GET['view_id'];
        $course_details_stmt = $conn->prepare("SELECT course_name FROM courses WHERE id = ?"); $course_details_stmt->bind_param("i", $course_id); $course_details_stmt->execute(); $course_details = $course_details_stmt->get_result()->fetch_assoc();
        $enrolled_stmt = $conn->prepare("SELECT e.id, u.firstname, u.lastname FROM enrollments e JOIN users u ON e.user_id = u.id WHERE e.course_id = ?"); $enrolled_stmt->bind_param("i", $course_id); $enrolled_stmt->execute(); $enrolled_students = $enrolled_stmt->get_result();
    ?>
    <div class="card"><div class="card-header"><h4>Manage Enrollments for "<?= htmlspecialchars($course_details['course_name']) ?>"</h4></div><div class="card-body">
        <a href="meetings.php" class="btn btn-secondary mb-4">&larr; Back to All Courses</a>
        <h5>Enroll New Student</h5>
        <form method="POST" action="meetings.php" class="d-flex mb-4">
            <input type="hidden" name="course_id" value="<?= $course_id ?>">
            <select name="student_id" class="form-select me-2" required><option value="" selected disabled>Select a student...</option>
                <?php while($student = $students->fetch_assoc()): ?>
                    <option value="<?= $student['id'] ?>"><?= htmlspecialchars($student['firstname'] . ' ' . $student['lastname']) ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" name="enroll_student" class="btn btn-success">Enroll</button>
        </form>
        <hr>
        <h5>Currently Enrolled Students</h5>
        <table class="table"><tbody>
            <?php if($enrolled_students->num_rows > 0): while($student = $enrolled_students->fetch_assoc()): ?>
                <tr><td><?= htmlspecialchars($student['firstname'] . ' ' . $student['lastname']) ?></td><td class="text-end"><a href="meetings.php?remove_enrollment_id=<?= $student['id'] ?>&course_id=<?= $course_id ?>" class="btn btn-sm btn-warning">Remove</a></td></tr>
            <?php endwhile; else: ?>
                <tr><td class="text-muted">No students are enrolled in this course yet.</td></tr>
            <?php endif; ?>
        </tbody></table>
    </div></div>
    
    <?php else: ?>
    
    <!-- THIS IS THE DEFAULT VIEW SHOWING ALL COURSES AND THE ADD FORM -->
    <div class="row">
        <div class="col-md-4">
            <div class="card"><div class="card-header"><h4>Add New Course</h4></div><div class="card-body">
                <form method="POST" action="meetings.php">
                    <div class="mb-3"><label class="form-label">Course Name</label><input type="text" name="course_name" class="form-control" required></div>
                    <div class="mb-3">
                        <label class="form-label">Assign Instructor</label>
                        <select name="instructor_id" class="form-select" required>
                            <option value="" selected disabled>Select an instructor...</option>
                            <?php while($instructor = $instructors->fetch_assoc()): ?>
                                <option value="<?= $instructor['id'] ?>"><?= htmlspecialchars($instructor['firstname'] . ' ' . $instructor['lastname']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
                    <button type="submit" name="add_course" class="btn btn-primary w-100">Create Course</button>
                </form>
            </div></div>
        </div>
        <div class="col-md-8">
            <div class="card"><div class="card-header"><h4>Existing Courses</h4></div><div class="card-body">
                <table class="table table-striped">
                    <thead><tr><th>Course Name</th><th>Instructor</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        <?php if($courses_list->num_rows > 0): while($course = $courses_list->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($course['course_name']) ?></td>
                                <td><?= htmlspecialchars($course['firstname'] . ' ' . $course['lastname']) ?></td>
                                <td class="text-end">
                                    <a href="meetings.php?view_id=<?= $course['id'] ?>" class="btn btn-primary btn-sm">Manage</a>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="3" class="text-center text-muted">No courses have been created yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div></div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php require_once 'partials/footer.php'; ?>