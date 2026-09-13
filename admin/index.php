<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: admin/index.php
 * 
 * Administrative Executive Dashboard with Platform KPIs & Activity Feeds
 * Author: G. Praveen
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Access Control: Administrator or Instructor Privileges Required
require_admin();

// Platform Key Performance Indicators (KPIs)
$totalStudents = (int)(db_fetch_one("SELECT COUNT(*) AS total FROM users WHERE role = 'student'")['total'] ?? 0);
$totalCourses = (int)(db_fetch_one("SELECT COUNT(*) AS total FROM courses")['total'] ?? 0);
$totalLessons = (int)(db_fetch_one("SELECT COUNT(*) AS total FROM lessons")['total'] ?? 0);
$totalEnrollments = (int)(db_fetch_one("SELECT COUNT(*) AS total FROM enrollments")['total'] ?? 0);

// Enrollments this week (past 7 days)
$weeklyEnrollments = (int)(db_fetch_one("
    SELECT COUNT(*) AS total 
    FROM enrollments 
    WHERE enrolled_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")['total'] ?? 0);

// Total Platform Revenue
$totalRevenue = (float)(db_fetch_one("
    SELECT SUM(c.price) AS total 
    FROM enrollments e 
    JOIN courses c ON e.course_id = c.id
")['total'] ?? 0.00);

// Recent Enrollment Activity Feed (Last 6 Enrollments)
$recentEnrollments = db_fetch_all("
    SELECT e.id, e.enrolled_at, e.progress_percent,
           u.name AS student_name, u.email AS student_email,
           c.id AS course_id, c.title AS course_title, c.category, c.price
    FROM enrollments e
    JOIN users u ON e.user_id = u.id
    JOIN courses c ON e.course_id = c.id
    ORDER BY e.enrolled_at DESC
    LIMIT 6
");

// Top 4 Most Active Courses
$popularCourses = db_fetch_all("
    SELECT c.id, c.title, c.category, c.price,
           u.name AS instructor_name,
           COUNT(e.id) AS enrollment_count,
           AVG(e.progress_percent) AS avg_progress
    FROM courses c
    JOIN users u ON c.instructor_id = u.id
    LEFT JOIN enrollments e ON c.id = e.course_id
    GROUP BY c.id
    ORDER BY enrollment_count DESC
    LIMIT 4
");

$pageTitle = 'Admin Portal Dashboard | EduStream LMS';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="bg-dark text-white py-4 mb-4 border-bottom border-secondary border-opacity-25">
    <div class="container-fluid px-lg-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="badge bg-danger text-white fw-bold">ADMIN / INSTRUCTOR CONSOLE</span>
                    <span class="text-secondary small">&bull; Management & Intelligence</span>
                </div>
                <h1 class="h3 fw-extrabold text-white mb-0">Platform Executive Overview</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('admin/courses.php'); ?>" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm fw-semibold">
                    <i class="bi bi-plus-circle me-1"></i> Add Course
                </a>
                <a href="<?= base_url('admin/analytics.php'); ?>" class="btn btn-outline-light btn-sm rounded-pill px-3 fw-semibold">
                    <i class="bi bi-graph-up-arrow me-1"></i> Analytics
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-lg-5 pb-5">
    <?= render_flash_messages(); ?>

    <!-- KPI STATS CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-widget custom-card border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-secondary small fw-semibold text-uppercase">Students</span>
                        <h2 class="h3 fw-bold text-dark mb-0 mt-1"><?= number_format($totalStudents); ?></h2>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted small"><i class="bi bi-check-circle text-success me-1"></i> Registered learners</div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-widget custom-card border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-secondary small fw-semibold text-uppercase">Courses</span>
                        <h2 class="h3 fw-bold text-info mb-0 mt-1"><?= number_format($totalCourses); ?></h2>
                    </div>
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-journal-album"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted small"><i class="bi bi-play-circle text-info me-1"></i> <?= $totalLessons; ?> Total Lessons</div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 col-sm-6">
            <div class="stat-widget custom-card border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-secondary small fw-semibold text-uppercase">Enrollments</span>
                        <h2 class="h3 fw-bold text-success mb-0 mt-1"><?= number_format($totalEnrollments); ?></h2>
                    </div>
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted small"><i class="bi bi-award text-success me-1"></i> Lifetime signups</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-sm-6">
            <div class="stat-widget custom-card border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-secondary small fw-semibold text-uppercase">This Week</span>
                        <h2 class="h3 fw-bold text-warning mb-0 mt-1">+<?= number_format($weeklyEnrollments); ?></h2>
                    </div>
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-lightning-charge-fill"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted small"><i class="bi bi-arrow-up-right text-warning me-1"></i> Past 7 days growth</div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 col-sm-12">
            <div class="stat-widget custom-card border-0 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-secondary small fw-semibold text-uppercase">Platform Revenue</span>
                        <h2 class="h3 fw-bold text-primary mb-0 mt-1">$<?= number_format($totalRevenue, 2); ?></h2>
                    </div>
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                </div>
                <div class="mt-2 text-muted small"><i class="bi bi-graph-up text-primary me-1"></i> Course tuition aggregate</div>
            </div>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="card custom-card border-0 shadow-sm p-3 mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <span class="fw-bold text-dark small"><i class="bi bi-gear-wide-connected me-1 text-primary"></i> Administrative Navigation:</span>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?= base_url('admin/courses.php'); ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                    <i class="bi bi-journal-album me-1"></i> Manage Courses
                </a>
                <a href="<?= base_url('admin/lessons.php'); ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                    <i class="bi bi-play-circle me-1"></i> Manage Lessons
                </a>
                <a href="<?= base_url('admin/students.php'); ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                    <i class="bi bi-people me-1"></i> Student Progress Roster
                </a>
                <a href="<?= base_url('admin/analytics.php'); ?>" class="btn btn-outline-success btn-sm rounded-pill px-3">
                    <i class="bi bi-bar-chart-line me-1"></i> Visual Analytics
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- RECENT ENROLLMENTS FEED -->
        <div class="col-lg-8">
            <div class="card custom-card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h3 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Student Enrollments</h3>
                    <a href="<?= base_url('admin/students.php'); ?>" class="small text-decoration-none fw-semibold">View All Students &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-secondary">
                            <tr>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Enrolled Date</th>
                                <th>Progress</th>
                                <th class="text-end">Tuition</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentEnrollments)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted small">No enrollments recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentEnrollments as $r): 
                                    $percent = (int)$r['progress_percent'];
                                    $pColor = get_progress_color($percent);
                                ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($r['student_name']); ?></div>
                                            <small class="text-muted"><?= e($r['student_email']); ?></small>
                                        </td>
                                        <td>
                                            <a href="<?= base_url('courses/view.php?id=' . $r['course_id']); ?>" class="fw-semibold text-dark text-decoration-none hover-primary">
                                                <?= e($r['course_title']); ?>
                                            </a>
                                            <div><span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;"><?= e($r['category']); ?></span></div>
                                        </td>
                                        <td class="small text-muted">
                                            <?= format_date($r['enrolled_at']); ?>
                                        </td>
                                        <td style="min-width: 140px;">
                                            <div class="d-flex justify-content-between small text-muted mb-1">
                                                <span><?= $percent; ?>%</span>
                                                <span><?= $percent >= 100 ? 'Done' : 'Active'; ?></span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-<?= $pColor; ?>" role="progressbar" style="width: <?= $percent; ?>%;"></div>
                                            </div>
                                        </td>
                                        <td class="text-end fw-bold text-dark">
                                            <?= format_currency($r['price']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TOP PERFORMING COURSES -->
        <div class="col-lg-4">
            <div class="card custom-card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h3 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-trophy me-2 text-warning"></i>Popular Curriculums</h3>
                    <a href="<?= base_url('admin/courses.php'); ?>" class="small text-decoration-none fw-semibold">Manage &rarr;</a>
                </div>
                <div class="p-3">
                    <?php foreach ($popularCourses as $pc): ?>
                        <div class="p-3 mb-2 rounded-3 bg-light border d-flex flex-column gap-2">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h4 class="h6 fw-bold text-dark mb-1">
                                        <a href="<?= base_url('admin/lessons.php?course_id=' . $pc['id']); ?>" class="text-dark text-decoration-none hover-primary">
                                            <?= e($pc['title']); ?>
                                        </a>
                                    </h4>
                                    <div class="small text-muted"><i class="bi bi-person me-1"></i> <?= e($pc['instructor_name']); ?></div>
                                </div>
                                <span class="badge bg-primary text-white"><?= (int)$pc['enrollment_count']; ?> Students</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center small text-secondary pt-1 border-top">
                                <span>Avg. Progress: <strong><?= round((float)$pc['avg_progress']); ?>%</strong></span>
                                <a href="<?= base_url('admin/lessons.php?course_id=' . $pc['id']); ?>" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.75rem;">
                                    Lessons &rarr;
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
