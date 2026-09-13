<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: courses/view.php
 * 
 * Course Syllabus, Curriculum Details & Enrollment Action
 * Author: G. Praveen
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';

$courseId = (int)($_GET['id'] ?? 0);

if ($courseId <= 0) {
    set_flash('danger', 'Invalid course selection.');
    redirect('courses/list.php');
}

// Fetch Course Information with Instructor Details
$course = db_fetch_one("
    SELECT c.*, u.name AS instructor_name, u.email AS instructor_email,
           (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) AS lesson_count,
           (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) AS student_count
    FROM courses c
    JOIN users u ON c.instructor_id = u.id
    WHERE c.id = ?
    LIMIT 1
", "i", [$courseId]);

if (!$course) {
    set_flash('danger', 'Course not found.');
    redirect('courses/list.php');
}

// Fetch Structured Lessons
$lessons = db_fetch_all("
    SELECT id, title, video_url, order_index, created_at
    FROM lessons
    WHERE course_id = ?
    ORDER BY order_index ASC, id ASC
", "i", [$courseId]);

// Check Enrollment Status
$enrollment = null;
$completedLessonIds = [];

if (is_logged_in()) {
    $enrollment = db_fetch_one("
        SELECT * FROM enrollments 
        WHERE user_id = ? AND course_id = ? 
        LIMIT 1
    ", "ii", [current_user_id(), $courseId]);

    if ($enrollment) {
        $completions = db_fetch_all("
            SELECT lesson_id FROM lesson_completions lc
            JOIN lessons l ON lc.lesson_id = l.id
            WHERE lc.user_id = ? AND l.course_id = ?
        ", "ii", [current_user_id(), $courseId]);
        $completedLessonIds = array_column($completions, 'lesson_id');
    }
}

$pageTitle = $course['title'] . ' | Course Syllabus & Enrollment';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="bg-light border-bottom py-3 mb-4">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= base_url(); ?>" class="text-decoration-none text-muted">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('courses/list.php'); ?>" class="text-decoration-none text-muted">Courses</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('courses/list.php?category=' . urlencode($course['category'])); ?>" class="text-decoration-none text-muted"><?= e($course['category']); ?></a></li>
                <li class="breadcrumb-item active text-truncate" style="max-width: 300px;" aria-current="page"><?= e($course['title']); ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="container pb-5">
    <?= render_flash_messages(); ?>

    <div class="row g-4">
        <!-- LEFT COLUMN: COURSE DETAILS & SYLLABUS -->
        <div class="col-lg-8">
            <div class="mb-4">
                <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-1.5 mb-2"><?= e($course['category']); ?></span>
                <h1 class="h2 fw-extrabold text-dark mb-3"><?= e($course['title']); ?></h1>
                
                <div class="d-flex flex-wrap align-items-center gap-4 text-secondary small mb-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <div>
                            <span class="text-muted d-block" style="font-size: 0.75rem;">Instructor</span>
                            <span class="fw-semibold text-dark"><?= e($course['instructor_name']); ?></span>
                        </div>
                    </div>
                    <div>
                        <span class="text-muted d-block" style="font-size: 0.75rem;">Curriculum</span>
                        <span class="fw-semibold text-dark"><i class="bi bi-journal-text me-1"></i> <?= count($lessons); ?> Lessons</span>
                    </div>
                    <div>
                        <span class="text-muted d-block" style="font-size: 0.75rem;">Community</span>
                        <span class="fw-semibold text-dark"><i class="bi bi-people me-1"></i> <?= (int)$course['student_count']; ?> Enrolled</span>
                    </div>
                </div>
            </div>

            <!-- COURSE OVERVIEW -->
            <div class="card custom-card border-0 p-4 mb-4">
                <h3 class="h5 fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Course Overview</h3>
                <p class="text-secondary mb-0" style="white-space: pre-line; line-height: 1.7;">
                    <?= e($course['description']); ?>
                </p>
            </div>

            <!-- SYLLABUS / CURRICULUM -->
            <div class="card custom-card border-0 p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h5 fw-bold mb-0"><i class="bi bi-list-check text-primary me-2"></i>Curriculum Syllabus</h3>
                    <span class="badge bg-secondary-subtle text-secondary"><?= count($lessons); ?> Total Lessons</span>
                </div>

                <?php if (empty($lessons)): ?>
                    <div class="text-center py-4 text-muted small">
                        <i class="bi bi-clock-history fs-3 d-block mb-2"></i>
                        Curriculum lessons are currently being uploaded by the instructor. Check back soon!
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush syllabus-list-group rounded-3 border">
                        <?php foreach ($lessons as $idx => $l): 
                            $isDone = in_array((int)$l['id'], $completedLessonIds);
                        ?>
                            <div class="list-group-item d-flex align-items-center justify-content-between py-3 px-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold small <?= $isDone ? 'bg-success text-white' : 'bg-light text-muted border'; ?>" style="width: 34px; height: 34px; flex-shrink: 0;">
                                        <?php if ($isDone): ?>
                                            <i class="bi bi-check-lg"></i>
                                        <?php else: ?>
                                            <?= $idx + 1; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark mb-0"><?= e($l['title']); ?></div>
                                        <small class="text-muted"><i class="bi bi-play-circle text-info me-1"></i> Video & Lecture Material</small>
                                    </div>
                                </div>

                                <div>
                                    <?php if ($enrollment): ?>
                                        <a href="<?= base_url('lessons/view.php?course_id=' . $courseId . '&lesson_id=' . $l['id']); ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                            <?= $isDone ? '<i class="bi bi-arrow-repeat me-1"></i> Review' : '<i class="bi bi-play-fill me-1"></i> Start'; ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border px-2 py-1"><i class="bi bi-lock me-1"></i> Locked</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT COLUMN: ENROLLMENT ACTION CARD -->
        <div class="col-lg-4">
            <div class="card custom-card border-0 shadow-sm p-4 sticky-top" style="top: 90px;">
                <div class="course-thumbnail-wrapper rounded-3 mb-3">
                    <img src="<?= get_course_thumbnail($course['thumbnail'], $course['title'], $course['category']); ?>" alt="<?= e($course['title']); ?>" class="course-thumbnail-img">
                </div>

                <div class="d-flex justify-content-between align-items-baseline mb-3">
                    <span class="text-secondary small fw-bold">Tuition Fee</span>
                    <span class="display-6 fw-bold text-primary"><?= format_currency($course['price']); ?></span>
                </div>

                <!-- DYNAMIC CTA BUTTON -->
                <div class="mb-4">
                    <?php if (!is_logged_in()): ?>
                        <div class="d-grid gap-2">
                            <a href="<?= base_url('auth/login.php'); ?>" class="btn btn-primary btn-lg rounded-3 fw-bold shadow-sm">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Sign In to Enroll
                            </a>
                            <a href="<?= base_url('auth/register.php'); ?>" class="btn btn-outline-secondary btn-sm rounded-3">
                                Don't have an account? Register
                            </a>
                        </div>
                    <?php elseif ($enrollment): ?>
                        <div class="bg-success bg-opacity-10 border border-success border-opacity-25 p-3 rounded-3 mb-3">
                            <div class="d-flex align-items-center gap-2 text-success fw-bold small mb-1">
                                <i class="bi bi-check-circle-fill"></i> You are enrolled in this course
                            </div>
                            <div class="progress my-2" style="height: 8px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?= (int)$enrollment['progress_percent']; ?>%;" aria-valuenow="<?= (int)$enrollment['progress_percent']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="d-flex justify-content-between text-muted small" style="font-size: 0.75rem;">
                                <span>Progress: <?= (int)$enrollment['progress_percent']; ?>%</span>
                                <span><?= count($completedLessonIds); ?> / <?= count($lessons); ?> Lessons</span>
                            </div>
                        </div>
                        <div class="d-grid">
                            <a href="<?= base_url('lessons/view.php?course_id=' . $courseId); ?>" class="btn btn-primary btn-lg rounded-3 fw-bold shadow-sm">
                                <i class="bi bi-play-circle-fill me-2"></i> Continue Learning
                            </a>
                        </div>
                    <?php else: ?>
                        <form action="<?= base_url('courses/enroll.php'); ?>" method="POST">
                            <?= csrf_field(); ?>
                            <input type="hidden" name="course_id" value="<?= $courseId; ?>">
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg rounded-3 fw-bold shadow-sm">
                                    <i class="bi bi-bookmark-plus-fill me-2"></i> Enroll in Course
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- WHAT INCLUDED CHECKLIST -->
                <div class="border-top pt-3">
                    <h6 class="fw-bold small text-dark mb-3">Course Inclusions:</h6>
                    <ul class="list-unstyled small text-secondary d-flex flex-column gap-2 mb-0">
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-camera-video text-primary"></i> <?= count($lessons); ?> HD video lectures & reading modules
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-clock-history text-primary"></i> Self-paced lifetime access
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-award text-success"></i> Verified certificate upon 100% completion
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-phone text-primary"></i> Mobile & tablet accessible LMS player
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
