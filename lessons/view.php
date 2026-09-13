<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: lessons/view.php
 * 
 * Interactive Learning Interface, Responsive Video Player & Real-Time AJAX Lesson Completion
 * Author: G. Praveen
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Authentication Guard
require_login('auth/login.php');

$userId = current_user_id();

// =============================================================================
// HANDLE AJAX TOGGLE COMPLETION
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_complete') {
    header('Content-Type: application/json; charset=utf-8');

    if (!verify_csrf_token()) {
        echo json_encode(['success' => false, 'message' => 'CSRF verification failed.']);
        exit;
    }

    $targetLessonId = (int)($_POST['lesson_id'] ?? 0);
    $targetCourseId = (int)($_POST['course_id'] ?? 0);

    if ($targetLessonId <= 0 || $targetCourseId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
        exit;
    }

    // Ensure user is enrolled (or is admin/instructor)
    $enrollment = db_fetch_one("SELECT id, progress_percent FROM enrollments WHERE user_id = ? AND course_id = ? LIMIT 1", "ii", [$userId, $targetCourseId]);
    if (!$enrollment && !is_admin()) {
        echo json_encode(['success' => false, 'message' => 'Enrollment required to mark progress.']);
        exit;
    }

    // Check existing completion record
    $existing = db_fetch_one("SELECT id FROM lesson_completions WHERE user_id = ? AND lesson_id = ? LIMIT 1", "ii", [$userId, $targetLessonId]);
    $isCompleted = false;

    if ($existing) {
        // Toggle OFF (Unmark)
        db_execute("DELETE FROM lesson_completions WHERE id = ?", "i", [$existing['id']]);
        $isCompleted = false;
    } else {
        // Toggle ON (Mark complete)
        db_execute("INSERT INTO lesson_completions (user_id, lesson_id, completed_at) VALUES (?, ?, NOW())", "ii", [$userId, $targetLessonId]);
        $isCompleted = true;
    }

    // Recalculate Overall Progress Percentage
    $totalLessons = (int)(db_fetch_one("SELECT COUNT(*) AS total FROM lessons WHERE course_id = ?", "i", [$targetCourseId])['total'] ?? 1);
    $completedCount = (int)(db_fetch_one("
        SELECT COUNT(*) AS total FROM lesson_completions lc 
        JOIN lessons l ON lc.lesson_id = l.id 
        WHERE lc.user_id = ? AND l.course_id = ?
    ", "ii", [$userId, $targetCourseId])['total'] ?? 0);

    $newPercent = calculate_progress($completedCount, $totalLessons);

    if ($enrollment) {
        db_execute("UPDATE enrollments SET progress_percent = ? WHERE id = ?", "ii", [$newPercent, $enrollment['id']]);
    }

    echo json_encode([
        'success'          => true,
        'is_completed'     => $isCompleted,
        'progress_percent' => $newPercent,
        'completed_count'  => $completedCount,
        'total_lessons'    => $totalLessons
    ]);
    exit;
}

// =============================================================================
// NORMAL PAGE VIEW LOAD
// =============================================================================
$courseId = (int)($_GET['course_id'] ?? 0);
$lessonId = (int)($_GET['lesson_id'] ?? 0);

if ($courseId <= 0) {
    set_flash('danger', 'Invalid course request.');
    redirect('dashboard.php');
}

// Fetch Course
$course = db_fetch_one("
    SELECT c.*, u.name AS instructor_name
    FROM courses c
    JOIN users u ON c.instructor_id = u.id
    WHERE c.id = ?
    LIMIT 1
", "i", [$courseId]);

if (!$course) {
    set_flash('danger', 'Course not found.');
    redirect('dashboard.php');
}

// Check Enrollment (Admins can preview without enrolling)
$enrollment = db_fetch_one("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ? LIMIT 1", "ii", [$userId, $courseId]);
if (!$enrollment && !is_admin()) {
    set_flash('warning', 'Please enroll in this course to access its learning curriculum.');
    redirect('courses/view.php?id=' . $courseId);
}

// Fetch All Lessons for this Course
$lessons = db_fetch_all("
    SELECT id, course_id, title, content, video_url, order_index
    FROM lessons
    WHERE course_id = ?
    ORDER BY order_index ASC, id ASC
", "i", [$courseId]);

if (empty($lessons)) {
    set_flash('info', 'No lessons have been published for this course yet.');
    redirect('courses/view.php?id=' . $courseId);
}

// Fetch Completed Lessons for Current User
$completions = db_fetch_all("
    SELECT lesson_id FROM lesson_completions lc
    JOIN lessons l ON lc.lesson_id = l.id
    WHERE lc.user_id = ? AND l.course_id = ?
", "ii", [$userId, $courseId]);
$completedLessonIds = array_column($completions, 'lesson_id');

// Determine Active Lesson
$currentLesson = null;
if ($lessonId > 0) {
    foreach ($lessons as $l) {
        if ((int)$l['id'] === $lessonId) {
            $currentLesson = $l;
            break;
        }
    }
}

if (!$currentLesson) {
    // Pick the first uncompleted lesson, or the first lesson
    foreach ($lessons as $l) {
        if (!in_array((int)$l['id'], $completedLessonIds)) {
            $currentLesson = $l;
            break;
        }
    }
    if (!$currentLesson) {
        $currentLesson = $lessons[0];
    }
}

$currentLessonId = (int)$currentLesson['id'];
$isCurrentCompleted = in_array($currentLessonId, $completedLessonIds);

// Determine Previous and Next Lesson Navigation
$currentIndex = 0;
foreach ($lessons as $idx => $l) {
    if ((int)$l['id'] === $currentLessonId) {
        $currentIndex = $idx;
        break;
    }
}
$prevLesson = ($currentIndex > 0) ? $lessons[$currentIndex - 1] : null;
$nextLesson = ($currentIndex < count($lessons) - 1) ? $lessons[$currentIndex + 1] : null;

// Calculate current progress
$totalLessonsCount = count($lessons);
$completedCount = count($completedLessonIds);
$progressPercent = calculate_progress($completedCount, $totalLessonsCount);

// Simple Markdown Formatter Helper
function format_lesson_markdown(string $raw): string {
    $text = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
    
    // Code blocks ``` ... ```
    $text = preg_replace('/```([a-zA-Z0-9_\-\+]*)\n(.*?)```/s', '<pre class="bg-dark text-info p-3 rounded-3 my-3"><code>$2</code></pre>', $text);
    // Inline code `...`
    $text = preg_replace('/`([^`]+)`/', '<code class="bg-light text-primary px-1 py-0.5 rounded">$1</code>', $text);
    // Headings
    $text = preg_replace('/^### (.*$)/m', '<h4 class="fw-bold mt-4 mb-2 text-dark">$1</h4>', $text);
    $text = preg_replace('/^#### (.*$)/m', '<h5 class="fw-bold mt-3 mb-2 text-dark">$1</h5>', $text);
    // Bullet lists
    $text = preg_replace('/^\- (.*$)/m', '<li class="mb-1 text-secondary">$1</li>', $text);
    // Paragraphs
    $text = nl2br($text);
    return $text;
}

$pageTitle = $currentLesson['title'] . ' | ' . $course['title'] . ' - Learning Portal';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- TOPBAR HEADER -->
<div class="bg-dark text-white py-3 border-bottom border-secondary border-opacity-25">
    <div class="container-fluid px-lg-5">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <a href="<?= base_url('dashboard.php'); ?>" class="btn btn-outline-light btn-sm rounded-pill px-3" title="Back to Dashboard">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
                <div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle small me-2"><?= e($course['category']); ?></span>
                    <strong class="text-white"><?= e($course['title']); ?></strong>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="text-end d-none d-sm-block">
                    <span class="small text-secondary">Curriculum Progress:</span>
                    <strong class="text-info ms-1" id="topProgressText"><?= $progressPercent; ?>%</strong>
                </div>
                <div class="progress" style="width: 120px; height: 8px;">
                    <div class="progress-bar bg-info" id="topProgressBar" role="progressbar" style="width: <?= $progressPercent; ?>%;" aria-valuenow="<?= $progressPercent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-lg-5 py-4">
    <?= render_flash_messages(); ?>

    <div class="row g-4">
        <!-- MAIN AREA: VIDEO & CONTENT -->
        <div class="col-lg-8">
            <div class="card custom-card border-0 shadow-sm overflow-hidden mb-4">
                <!-- VIDEO PLAYER -->
                <?php if (!empty($currentLesson['video_url'])): ?>
                    <div class="video-container">
                        <iframe src="<?= e($currentLesson['video_url']); ?>" title="<?= e($currentLesson['title']); ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                <?php else: ?>
                    <div class="video-container d-flex align-items-center justify-content-center bg-dark text-white p-5 text-center">
                        <div>
                            <i class="bi bi-file-earmark-text fs-1 text-primary mb-2 d-block"></i>
                            <h5 class="fw-bold">Reading & Interactive Module</h5>
                            <p class="text-secondary small mb-0">Follow the lecture notes and practical code snippets below.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- LESSON CONTROL BAR -->
                <div class="card-body p-4 border-bottom bg-white">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div>
                            <div class="d-flex align-items-center gap-2 small text-muted mb-1">
                                <span class="badge bg-secondary-subtle text-secondary">Lesson <?= $currentIndex + 1; ?> of <?= $totalLessonsCount; ?></span>
                                <span>&bull;</span>
                                <span><i class="bi bi-person text-muted me-1"></i> <?= e($course['instructor_name']); ?></span>
                            </div>
                            <h2 class="h4 fw-bold mb-0 text-dark"><?= e($currentLesson['title']); ?></h2>
                        </div>

                        <!-- AJAX MARK AS COMPLETE BUTTON -->
                        <div>
                            <button type="button" id="markCompleteBtn" class="btn <?= $isCurrentCompleted ? 'btn-success' : 'btn-outline-primary'; ?> rounded-pill px-4 fw-bold shadow-sm" data-lesson-id="<?= $currentLessonId; ?>" data-course-id="<?= $courseId; ?>">
                                <i class="bi <?= $isCurrentCompleted ? 'bi-check-circle-fill' : 'bi-check-circle'; ?> me-2"></i>
                                <span id="markCompleteBtnText"><?= $isCurrentCompleted ? 'Completed' : 'Mark as Complete'; ?></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- NAVIGATION BUTTONS -->
                <div class="p-3 bg-light d-flex justify-content-between align-items-center border-bottom">
                    <div>
                        <?php if ($prevLesson): ?>
                            <a href="<?= base_url('lessons/view.php?course_id=' . $courseId . '&lesson_id=' . $prevLesson['id']); ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                <i class="bi bi-chevron-left me-1"></i> Previous Lesson
                            </a>
                        <?php else: ?>
                            <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" disabled>
                                <i class="bi bi-chevron-left me-1"></i> First Lesson
                            </button>
                        <?php endif; ?>
                    </div>

                    <div>
                        <?php if ($nextLesson): ?>
                            <a href="<?= base_url('lessons/view.php?course_id=' . $courseId . '&lesson_id=' . $nextLesson['id']); ?>" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold">
                                Next Lesson <i class="bi bi-chevron-right ms-1"></i>
                            </a>
                        <?php else: ?>
                            <a href="<?= base_url('dashboard.php'); ?>" class="btn btn-success btn-sm rounded-pill px-3 fw-semibold">
                                <i class="bi bi-award-fill me-1"></i> Complete Course
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- LECTURE NOTES / CONTENT -->
                <div class="card-body p-4 p-md-5">
                    <h3 class="h5 fw-bold mb-3"><i class="bi bi-book-half text-primary me-2"></i>Lecture Notes & Reference Material</h3>
                    <div class="lesson-content text-secondary" style="line-height: 1.8;">
                        <?= format_lesson_markdown($currentLesson['content'] ?? 'No detailed notes published for this lesson yet.'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- SIDEBAR: CURRICULUM SYLLABUS -->
        <div class="col-lg-4">
            <div class="card custom-card border-0 shadow-sm p-4 sticky-top" style="top: 85px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="h6 fw-bold mb-0 text-dark"><i class="bi bi-list-nested text-primary me-2"></i>Course Syllabus</h3>
                    <span class="badge bg-secondary-subtle text-secondary small" id="sidebarProgressCount">
                        <?= $completedCount; ?> / <?= $totalLessonsCount; ?> Complete
                    </span>
                </div>

                <div class="progress mb-3" style="height: 6px;">
                    <div class="progress-bar bg-primary" id="sidebarProgressBar" role="progressbar" style="width: <?= $progressPercent; ?>%;" aria-valuenow="<?= $progressPercent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>

                <div class="list-group list-group-flush syllabus-list-group rounded-3 border overflow-auto" style="max-height: 520px;">
                    <?php foreach ($lessons as $idx => $l): 
                        $isItemActive = ((int)$l['id'] === $currentLessonId);
                        $isItemCompleted = in_array((int)$l['id'], $completedLessonIds);
                    ?>
                        <a href="<?= base_url('lessons/view.php?course_id=' . $courseId . '&lesson_id=' . $l['id']); ?>" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-3 px-3 <?= $isItemActive ? 'active-lesson' : ''; ?>" id="lesson-item-<?= $l['id']; ?>">
                            <div class="d-flex align-items-center gap-2 me-2">
                                <span class="badge <?= $isItemActive ? 'bg-primary text-white' : 'bg-light text-muted border'; ?> rounded-pill" style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem;">
                                    <?= $idx + 1; ?>
                                </span>
                                <span class="small fw-semibold text-truncate" style="max-width: 190px;">
                                    <?= e($l['title']); ?>
                                </span>
                            </div>

                            <span class="lesson-check-icon" id="check-icon-<?= $l['id']; ?>">
                                <?php if ($isItemCompleted): ?>
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                <?php else: ?>
                                    <i class="bi bi-circle text-muted fs-5 opacity-50"></i>
                                <?php endif; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- AJAX CSRF TOKEN HIDDEN FORM -->
<form id="csrfHelperForm" style="display: none;">
    <?= csrf_field(); ?>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('markCompleteBtn');
    if (!btn) return;

    btn.addEventListener('click', () => {
        const lessonId = btn.getAttribute('data-lesson-id');
        const courseId = btn.getAttribute('data-course-id');
        const csrfToken = document.querySelector('#csrfHelperForm input[name="csrf_token"]').value;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Updating...';

        const formData = new FormData();
        formData.append('action', 'toggle_complete');
        formData.append('lesson_id', lessonId);
        formData.append('course_id', courseId);
        formData.append('csrf_token', csrfToken);

        fetch('<?= base_url("lessons/view.php"); ?>', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.success) {
                // Update Button State
                if (data.is_completed) {
                    btn.className = 'btn btn-success rounded-pill px-4 fw-bold shadow-sm';
                    btn.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i><span>Completed</span>';
                } else {
                    btn.className = 'btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm';
                    btn.innerHTML = '<i class="bi bi-check-circle me-2"></i><span>Mark as Complete</span>';
                }

                // Update Sidebar Checkmark Icon
                const checkIcon = document.getElementById('check-icon-' + lessonId);
                if (checkIcon) {
                    checkIcon.innerHTML = data.is_completed
                        ? '<i class="bi bi-check-circle-fill text-success fs-5"></i>'
                        : '<i class="bi bi-circle text-muted fs-5 opacity-50"></i>';
                }

                // Update Progress Meters
                const percent = data.progress_percent;
                document.getElementById('topProgressText').textContent = percent + '%';
                document.getElementById('topProgressBar').style.width = percent + '%';
                document.getElementById('sidebarProgressBar').style.width = percent + '%';
                document.getElementById('sidebarProgressCount').textContent = data.completed_count + ' / ' + data.total_lessons + ' Complete';
            } else {
                alert(data.message || 'Error updating lesson completion.');
            }
        })
        .catch(err => {
            btn.disabled = false;
            console.error('Completion toggle error:', err);
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
