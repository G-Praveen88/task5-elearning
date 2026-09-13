<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: admin/students.php
 * 
 * Enrolled Students Roster, Search & Real-Time Progress Monitoring
 * Author: G. Praveen
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Access Control: Admin or Instructor Required
require_admin();

// Filter parameters
$searchQuery = clean_string($_GET['q'] ?? '');
$courseFilter = (int)($_GET['course_id'] ?? 0);

// Build SQL Query
$where = ["1=1"];
$params = [];
$types = "";

if (!empty($searchQuery)) {
    $where[] = "(u.name LIKE ? OR u.email LIKE ?)";
    $like = "%" . $searchQuery . "%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

if ($courseFilter > 0) {
    $where[] = "e.course_id = ?";
    $params[] = $courseFilter;
    $types .= "i";
}

$whereSql = implode(" AND ", $where);

// Fetch Enrolled Students
$sql = "
    SELECT e.id AS enrollment_id, e.enrolled_at, e.progress_percent,
           u.id AS user_id, u.name AS student_name, u.email AS student_email, u.created_at AS user_joined,
           c.id AS course_id, c.title AS course_title, c.category, c.price,
           (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) AS total_lessons,
           (SELECT COUNT(*) FROM lesson_completions lc 
            JOIN lessons l ON lc.lesson_id = l.id 
            WHERE lc.user_id = u.id AND l.course_id = c.id) AS completed_lessons
    FROM enrollments e
    JOIN users u ON e.user_id = u.id
    JOIN courses c ON e.course_id = c.id
    WHERE {$whereSql}
    ORDER BY e.enrolled_at DESC
";

$enrollments = db_fetch_all($sql, $types, $params);

// Fetch distinct courses for filter dropdown
$allCourses = db_fetch_all("SELECT id, title FROM courses ORDER BY title ASC");

// Calculate aggregate statistics
$totalCount = count($enrollments);
$completedCount = 0;
$inProgressCount = 0;
foreach ($enrollments as $enr) {
    if ((int)$enr['progress_percent'] >= 100) {
        $completedCount++;
    } else {
        $inProgressCount++;
    }
}

$pageTitle = 'Enrolled Students & Progress Roster | Admin Portal - EduStream LMS';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="bg-dark text-white py-4 mb-4 border-bottom border-secondary border-opacity-25">
    <div class="container-fluid px-lg-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php'); ?>" class="text-secondary text-decoration-none">Admin Dashboard</a></li>
                        <li class="breadcrumb-item active text-white" aria-current="page">Student Roster</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-extrabold text-white mb-0">Enrolled Students & Progress</h1>
            </div>
            <div class="d-flex gap-2">
                <span class="badge bg-primary text-white p-2">
                    <i class="bi bi-people-fill me-1"></i> <?= $totalCount; ?> Active Enrollments Shown
                </span>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-lg-5 pb-5">
    <?= render_flash_messages(); ?>

    <!-- KPI CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-widget custom-card border-0 shadow-sm d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Total Matching</span>
                    <h3 class="h2 fw-bold text-dark mb-0 mt-1"><?= $totalCount; ?></h3>
                </div>
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-person-lines-fill"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-widget custom-card border-0 shadow-sm d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">In Progress</span>
                    <h3 class="h2 fw-bold text-warning mb-0 mt-1"><?= $inProgressCount; ?></h3>
                </div>
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-widget custom-card border-0 shadow-sm d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-secondary small fw-semibold text-uppercase">Completed (100%)</span>
                    <h3 class="h2 fw-bold text-success mb-0 mt-1"><?= $completedCount; ?></h3>
                </div>
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER & SEARCH FORM -->
    <div class="card custom-card border-0 shadow-sm p-3 mb-4">
        <form action="<?= base_url('admin/students.php'); ?>" method="GET" class="row g-3 align-items-center">
            <div class="col-lg-5 col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control bg-light border-start-0" placeholder="Search by student name or email..." value="<?= e($searchQuery); ?>">
                </div>
            </div>

            <div class="col-lg-5 col-md-4">
                <select name="course_id" class="form-select bg-light">
                    <option value="0">All Courses & Curriculums</option>
                    <?php foreach ($allCourses as $c): ?>
                        <option value="<?= $c['id']; ?>" <?= ($courseFilter === (int)$c['id']) ? 'selected' : ''; ?>>
                            <?= e($c['title']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-2 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm rounded-pill w-100 fw-bold">
                    <i class="bi bi-funnel-fill me-1"></i> Filter
                </button>
                <?php if (!empty($searchQuery) || $courseFilter > 0): ?>
                    <a href="<?= base_url('admin/students.php'); ?>" class="btn btn-outline-secondary btn-sm rounded-pill" title="Reset Filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- STUDENT ROSTER TABLE -->
    <div class="card custom-card border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h2 class="h6 fw-bold mb-0 text-dark">
                <i class="bi bi-mortarboard text-primary me-2"></i>Active Student Enrollments
            </h2>
            <span class="badge bg-secondary-subtle text-secondary small"><?= count($enrollments); ?> Records</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-secondary">
                    <tr>
                        <th>Student</th>
                        <th>Course Curriculum</th>
                        <th>Category</th>
                        <th>Enrolled Date</th>
                        <th style="width: 220px;">Curriculum Progress</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($enrollments)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-person-x fs-1 d-block mb-2"></i>
                                No student enrollments found matching your filter criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($enrollments as $s): 
                            $percent = (int)$s['progress_percent'];
                            $pColor = get_progress_color($percent);
                            $isDone = ($percent >= 100);
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold small" style="width: 34px; height: 34px;">
                                            <?= strtoupper(substr($s['student_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark"><?= e($s['student_name']); ?></div>
                                            <small class="text-muted"><?= e($s['student_email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?= base_url('courses/view.php?id=' . $s['course_id']); ?>" class="fw-semibold text-dark text-decoration-none hover-primary">
                                        <?= e($s['course_title']); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary"><?= e($s['category']); ?></span>
                                </td>
                                <td class="small text-muted">
                                    <?= format_date($s['enrolled_at'], true); ?>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-between align-items-center small text-muted mb-1">
                                        <span class="fw-semibold text-<?= $pColor; ?>"><?= $percent; ?>%</span>
                                        <span><?= (int)$s['completed_lessons']; ?> of <?= (int)$s['total_lessons']; ?> Lessons</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-<?= $pColor; ?>" role="progressbar" style="width: <?= $percent; ?>%;"></div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($isDone): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                            <i class="bi bi-check-circle-fill me-1"></i> Completed
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                            <i class="bi bi-hourglass-split me-1"></i> In Progress
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= base_url('courses/view.php?id=' . $s['course_id']); ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1" style="font-size: 0.8rem;">
                                        <i class="bi bi-arrow-up-right me-1"></i> Course
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
