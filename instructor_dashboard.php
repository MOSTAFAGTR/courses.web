<?php
session_start();
require_once 'partials/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$instructor_user_id = $_SESSION['user_id'];
$stmt_courses = $conn->prepare("SELECT id, course_name, description, icon_class FROM courses WHERE instructor_id = ?");
$stmt_courses->bind_param("i", $instructor_user_id);
$stmt_courses->execute();
$courses = $stmt_courses->get_result();

$selected_course_id = $_GET['course_id'] ?? null;
$students = null;

if (!$selected_course_id && $courses->num_rows > 0) {
    // If no course is selected, default to the first one in the list
    $first_course = $courses->fetch_assoc();
    $selected_course_id = $first_course['id'];
    mysqli_data_seek($courses, 0); // Reset pointer
}

if ($selected_course_id) {
    $stmt_students = $conn->prepare("SELECT u.firstname, u.lastname, u.avatar_url FROM enrollments e JOIN users u ON e.user_id = u.id WHERE e.course_id = ?");
    $stmt_students->bind_param("i", $selected_course_id);
    $stmt_students->execute();
    $students = $stmt_students->get_result();
}

$page_title = "Instructor Dashboard";
require_once 'partials/header.php';
?>
<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="user_dashboard.php">Dashboard</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="user_dashboard.php">Home</a></li>
                <li class="nav-item"><a class="nav-link active" href="instructor_dashboard.php">Instructor Dashboard</a></li>
            </ul>
            <ul class="navbar-nav ms-auto"><li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li></ul>
        </div>
    </div>
</nav>

<div class="container my-4">
    <h1 class="dashboard-title">INSTRUCTOR DASHBOARD</h1>
    <!-- THE FIX: The extra <nav class="dash-nav"> has been removed. -->
    
    <div class="row">
        <div class="col-lg-5">
            <?php if($courses->num_rows > 0): mysqli_data_seek($courses, 0); while($course = $courses->fetch_assoc()): ?>
            <div class="mb-4"><a href="?course_id=<?= $course['id'] ?>" class="text-decoration-none">
                <div class="course-card">
                    <div class="icon"><i class="bi <?= htmlspecialchars($course['icon_class']) ?>"></i></div>
                    <h3 class="card-title"><?= htmlspecialchars($course['course_name']) ?></h3>
                    <p class="card-text"><?= htmlspecialchars($course['description']) ?></p>
                    <div class="instructor-info">
                        <img src="<?= htmlspecialchars($_SESSION['user_avatar']) ?>" alt="Instructor">
                        <span><?= htmlspecialchars($_SESSION['user_firstname']) ?></span>
                    </div>
                </div>
            </a></div>
            <?php endwhile; else: ?>
            <p class="text-muted text-center">You are not assigned to any courses.</p>
            <?php endif; ?>
        </div>
        <div class="col-lg-7">
            <?php if($students): ?>
            <div class="student-list-card">
                <h3 class="list-title">Students Enrolled</h3>
                <?php if($students->num_rows > 0): while($student = $students->fetch_assoc()): ?>
                <div class="student-item">
                    <img src="<?= htmlspecialchars($student['avatar_url']) ?>" alt="Student">
                    <span><?= htmlspecialchars($student['firstname'] . ' ' . $student['lastname']) ?></span>
                </div>
                <?php endwhile; else: ?>
                <p class="text-muted">No students are enrolled in this course yet.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once 'partials/footer.php'; ?>