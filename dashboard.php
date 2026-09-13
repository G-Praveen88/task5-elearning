<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: dashboard.php
 * 
 * Student Learning Dashboard with Enrolled Courses, Real-Time Progress Bars & KPIs
 * Author: G. Praveen
 */

require_once __DIR__ . '/config/db_connect.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth_check.php';

// Ensure user is signed in
require_login('auth/login.php');

$userId = current_user_id();

// Fetch Enrolled Courses with Detailed Progress
$enrolledCourses = db_fetch_all("
    SELECT e.id AS enrollment_id, e.enrolled_at, e.progress_percent,
           c.id AS course_id, c.title, c.description, c.category, c.thumbnail,
           u.name AS instructor_name,
           (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) AS total_lessons,
           (SELECT COUNT(*) FROM lesson_completions lc 
            JOIN lessons l ON lc.lesson_id = l.id 
            WHERE lc.user_id = e.user_id AND l.course_id = c.id) AS completed_lessons,
           (SELECT id FROM lessons WHERE course_id = c.id ORDER BY order_index ASC LIMIT 1) AS first_lesson_id
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON c.instructor_id = u.id
    WHERE e.user_id = ?
    ORDER BY e.enrolled_at DESC
", "i", [$userId]);

// Calculate Student Learning Statistics
$totalEnrolled = count($enrolledCourses);
$totalCompleted = 0;
$totalInProgress = 0;
$totalLessonsCompleted = (int)(db_fetch_one("SELECT COUNT(*) AS total FROM lesson_completions WHERE user_id = ?", "i", [$userId])['total'] ?? 0);

foreach ($enrolledCourses as $ec) {
    if ((int)$ec['progress_percent'] >= 100) {
        $totalCompleted++;
    } else {
        $totalInProgress++;
    }
}

$pageTitle = 'Student Learning Dashboard | EduStream LMS';
require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-primary text-white py-4 mb-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold fs-3 shadow" style="width: 56px; height: 56px;">
                        <?= strtoupper(substr(current_user_name(), 0, 1)); ?>
                    </div>
                    <div>
                        <h1 class="h3 fw-extrabold mb-1">Welcome back, <?= e(current_user_name()); ?>!</h1>
                        <p class="text-light opacity-75 small mb-0">Track your courses, resume lessons, and achieve your learning targets.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mt-3 mt-md-0 text-md-end">
                <a href="<?= base_url('courses/list.php'); ?>" class="btn btn-light btn-sm rounded-pill px-3 fw-bold text-primary shadow-sm">
                    <i class="bi bi-search me-1"></i> Browse More Courses
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <?= render_flash_messages(); ?>

    <?php if (is_admin()): ?>
        <div class="alert alert-info border-info border-opacity-25 d-flex align-items-center justify-content-between mb-4 shadow-sm">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-shield-check fs-4 text-primary"></i>
                <div>
                    <strong>Instructor / Admin Mode Available:</strong> You have administrative privileges. You can author courses and monitor student enrollments in the Admin Portal.
                </div>
            </div>
            <a href="<?= base_url('admin/index.php'); ?>" class="btn btn-primary btn-sm px-3 rounded-pill fw-semibold text-nowrap">
                Open Admin Panel <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    <?php endif; ?>

    <!-- KPI STAT CARDS -->
    <div class="row g-3 mb-5">
        <div class="col-md-3 col-6">
            <div class="stat-widget border-0 shadow-sm d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Enrolled Courses</span>
                    <h3 class="display-6 fw-bold text-primary mb-0 mt-1"><?= $totalEnrolled; ?></h3>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="stat-widget border-0 shadow-sm d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">In Progress</span>
                    <h3 class="display-6 fw-bold text-warning mb-0 mt-1"><?= $totalInProgress; ?></h3>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="stat-widget border-0 shadow-sm d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Completed</span>
                    <h3 class="display-6 fw-bold text-success mb-0 mt-1"><?= $totalCompleted; ?></h3>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="stat-widget border-0 shadow-sm d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Completed Lessons</span>
                    <h3 class="display-6 fw-bold text-info mb-0 mt-1"><?= $totalLessonsCompleted; ?></h3>
                </div>
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-play-circle-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- ENROLLED COURSES SECTION -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h4 fw-bold mb-0">My Enrolled Courses</h2>
            <p class="text-secondary small mb-0">Continue your curriculum right where you left off</p>
        </div>
        <span class="badge bg-secondary-subtle text-secondary px-3 py-1.5"><?= $totalEnrolled; ?> Active Enrolled</span>
    </div>

    <?php if (empty($enrolledCourses)): ?>
        <div class="card custom-card border-0 p-5 text-center shadow-sm">
            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 72px; height: 72px;">
                <i class="bi bi-mortarboard fs-2"></i>
            </div>
            <h3 class="h4 fw-bold text-dark mb-2">You have not enrolled in any courses yet</h3>
            <p class="text-secondary small mb-4 mx-auto" style="max-width: 480px;">
                Explore our catalog of web engineering, data science, cyber security, and design curriculums to kick off your hands-on learning.
            </p>
            <div>
                <a href="<?= base_url('courses/list.php'); ?>" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold shadow-sm">
                    <i class="bi bi-search me-2"></i> Browse Course Catalog
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($enrolledCourses as $course): 
                $percent = (int)$course['progress_percent'];
                $progressColor = get_progress_color($percent);
                $isFinished = ($percent >= 100);
            ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card custom-card course-card border-0 shadow-sm h-100">
                        <div class="course-thumbnail-wrapper">
                            <img src="<?= get_course_thumbnail($course['thumbnail'], $course['title'], $course['category']); ?>" alt="<?= e($course['title']); ?>" class="course-thumbnail-img" loading="lazy">
                            <span class="badge-category"><?= e($course['category']); ?></span>
                        </div>
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small text-muted">
                                    <i class="bi bi-calendar-check me-1"></i> Enrolled <?= format_date($course['enrolled_at']); ?>
                                </span>
                                <?php if ($isFinished): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0.5">
                                        <i class="bi bi-check-circle-fill me-1"></i> Completed
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-0.5">
                                        <i class="bi bi-play-fill me-1"></i> In Progress
                                    </span>
                                <?php endif; ?>
                            </div>

                            <h3 class="h5 fw-bold mb-2">
                                <a href="<?= base_url('lessons/view.php?course_id=' . $course['course_id']); ?>" class="text-dark text-decoration-none hover-primary">
                                    <?= e($course['title']); ?>
                                </a>
                            </h3>
                            <div class="small text-secondary mb-3">
                                <i class="bi bi-person me-1"></i> Instructor: <?= e($course['instructor_name']); ?>
                            </div>

                            <!-- REAL-TIME PROGRESS BAR -->
                            <div class="mt-auto pt-3 border-top">
                                <div class="d-flex justify-content-between align-items-center small mb-1">
                                    <span class="text-secondary fw-semibold">Curriculum Progress</span>
                                    <span class="fw-bold text-<?= $progressColor; ?>"><?= $percent; ?>%</span>
                                </div>
                                <div class="progress mb-2" style="height: 8px;">
                                    <div class="progress-bar bg-<?= $progressColor; ?>" role="progressbar" style="width: <?= $percent; ?>%;" aria-valuenow="<?= $percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="d-flex justify-content-between text-muted small" style="font-size: 0.75rem;">
                                    <span><?= (int)$course['completed_lessons']; ?> of <?= (int)$course['total_lessons']; ?> Lessons Done</span>
                                </div>
                            </div>

                            <div class="mt-3">
                                <a href="<?= base_url('lessons/view.php?course_id=' . $course['course_id']); ?>" class="btn btn-primary btn-sm w-100 rounded-pill fw-bold shadow-sm">
                                    <i class="bi bi-play-circle-fill me-1"></i> <?= $isFinished ? 'Review Curriculum' : 'Continue Learning'; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
