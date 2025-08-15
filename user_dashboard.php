<?php
session_start();
require_once 'partials/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] === 'admin') {
    header("Location: login.php");
    exit();
}

// THE FIX: Check if the logged-in user is an instructor
$is_instructor = false;
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT user_id FROM instructors WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $is_instructor = true;
}
$stmt->close();

$page_title = "User Dashboard";
require_once 'partials/header.php';
?>

<!-- This is the navbar for all non-admin users -->
<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="user_dashboard.php">Dashboard</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link active" href="user_dashboard.php">Home</a></li>
                
                <!-- THE FIX: The navbar now shows the correct link -->
                <?php if ($is_instructor): ?>
                    <li class="nav-item"><a class="nav-link" href="instructor_dashboard.php">Instructor Dashboard</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="my_courses.php">My Courses</a></li>
                <?php endif; ?>

            </ul>
            <ul class="navbar-nav ms-auto"><li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li></ul>
        </div>
    </div>
</nav>

<!-- This is the restored, simple home page content -->
<div class="container mt-4">
    <h1 class="display-5 text-center mb-4">Welcome, <?= htmlspecialchars($_SESSION['user_firstname']) ?>!</h1>
    
    <!-- Carousel Slider -->
    <div id="carouselExampleIndicators" class="carousel slide shadow-sm rounded overflow-hidden mb-5">
      <div class="carousel-inner">
        <div class="carousel-item active"><img src="https://placehold.co/1200x400/007bff/white?text=Welcome+to+Your+Dashboard" class="d-block w-100"></div>
        <div class="carousel-item"><img src="https://placehold.co/1200x400/17a2b8/white?text=Explore+Your+Courses" class="d-block w-100"></div>
      </div>
      <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
      <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
    </div>

    <!-- Cards Section -->
    <div class="row justify-content-center">
        <div class="col-md-4 mb-4"><div class="card" style="width: 18rem;"><img src="https://placehold.co/300x200/ffc107/black?text=Featured" class="card-img-top"><div class="card-body"><h5 class="card-title">Featured Course</h5><p class="card-text">Check out our featured course of the month, designed to boost your skills.</p><a href="#" class="btn btn-primary">Learn More</a></div></div></div>
        <div class="col-md-4 mb-4"><div class="card" style="width: 18rem;"><img src="https://placehold.co/300x200/dc3545/white?text=Announcements" class="card-img-top"><div class="card-body"><h5 class="card-title">Announcements</h5><p class="card-text">Stay up to date with the latest news, events, and important announcements.</p><a href="#" class="btn btn-primary">Read More</a></div></div></div>
        <div class="col-md-4 mb-4"><div class="card" style="width: 18rem;"><img src="https://placehold.co/300x200/6c757d/white?text=Your+Profile" class="card-img-top"><div class="card-body"><h5 class="card-title">Your Profile</h5><p class="card-text">Manage your account settings and view your progress on your learning journey.</p><a href="#" class="btn btn-primary">Go to Profile</a></div></div></div>
    </div>
</div>

<?php require_once 'partials/footer.php'; ?>