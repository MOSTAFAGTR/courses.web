<?php
session_start();
require_once 'partials/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT c.course_name, c.description, c.icon_class, i.firstname AS instructor_firstname, i.lastname AS instructor_lastname, i.avatar_url AS instructor_avatar FROM enrollments e JOIN courses c ON e.course_id = c.id JOIN users i ON c.instructor_id = i.id WHERE e.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$enrolled_courses = $stmt->get_result();

$page_title = "My Courses";
require_once 'partials/header.php';
?>
<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="user_dashboard.php">Dashboard</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="user_dashboard.php">Home</a></li>
                <li class="nav-item"><a class="nav-link active" href="my_courses.php">My Courses</a></li>
            </ul>
            <ul class="navbar-nav ms-auto"><li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li></ul>
        </div>
    </div>
</nav>

<div class="container my-4">
    <h1 class="dashboard-title">My Courses</h1>
    <!-- THE FIX: The extra <nav class="dash-nav"> has been removed. -->

    <div class="row">
        <?php if($enrolled_courses->num_rows > 0): while($course = $enrolled_courses->fetch_assoc()): ?>
        <div class="col-lg-4 col-md-6 mb-4 d-flex">
            <div class="course-card d-flex flex-column">
                <div class="icon"><i class="bi <?= htmlspecialchars($course['icon_class']) ?>"></i></div>
                <h3 class="card-title"><?= htmlspecialchars($course['course_name']) ?></h3>
                <p class="card-text"><?= htmlspecialchars($course['description']) ?></p>
                <div class="instructor-info mt-auto">
                    <img src="<?= htmlspecialchars($course['instructor_avatar']) ?>" alt="Instructor">
                    <span><?= htmlspecialchars($course['instructor_firstname'] . ' ' . $course['instructor_lastname']) ?></span>
                </div>
            </div>
        </div>
        <?php endwhile; else: ?>
        <div class="col-12"><p class="text-center text-muted" style="height: 50vh; display: flex; align-items: center; justify-content: center;">You are not currently enrolled in any courses.</p></div>
        <?php endif; ?>
    </div>
</div>
<?php require_once 'partials/footer.php'; ?>