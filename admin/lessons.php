<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: admin/lessons.php
 * 
 * Curriculum Lesson Management (Add, Edit, Delete, Reorder)
 * Author: G. Praveen
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Access Control: Admin or Instructor Required
require_admin();

// Fetch all available courses for dropdown selection
$allCourses = db_fetch_all("SELECT id, title, category FROM courses ORDER BY title ASC");

if (empty($allCourses)) {
    set_flash('warning', 'Please create a course before managing curriculum lessons.');
    redirect('admin/courses.php');
}

// Active Selected Course
$selectedCourseId = (int)($_GET['course_id'] ?? ($_POST['course_id'] ?? $allCourses[0]['id']));
$selectedCourse = db_fetch_one("SELECT * FROM courses WHERE id = ? LIMIT 1", "i", [$selectedCourseId]);
if (!$selectedCourse) {
    $selectedCourse = $allCourses[0];
    $selectedCourseId = (int)$selectedCourse['id'];
}

// =============================================================================
// HANDLE POST ACTIONS
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Security token invalid or expired. Please try again.');
        redirect('admin/lessons.php?course_id=' . $selectedCourseId);
    }

    $action = clean_string($_POST['action'] ?? '');

    // 1. ADD LESSON
    if ($action === 'add') {
        $title      = clean_string($_POST['title'] ?? '');
        $videoUrl   = clean_string($_POST['video_url'] ?? '');
        $content    = clean_string($_POST['content'] ?? '');
        $orderIndex = max(1, (int)($_POST['order_index'] ?? 1));

        if (empty($title) || mb_strlen($title) < 2) {
            set_flash('danger', 'Lesson title must be at least 2 characters long.');
        } else {
            $insert = db_execute(
                "INSERT INTO lessons (course_id, title, content, video_url, order_index) VALUES (?, ?, ?, ?, ?)",
                "isssi",
                [$selectedCourseId, $title, $content, $videoUrl, $orderIndex]
            );

            if ($insert['affected_rows'] > 0) {
                set_flash('success', 'Lesson "' . e($title) . '" successfully added to curriculum.');
            } else {
                set_flash('danger', 'Failed to save lesson.');
            }
        }
        redirect('admin/lessons.php?course_id=' . $selectedCourseId);
    }

    // 2. EDIT LESSON
    if ($action === 'edit') {
        $lessonId   = (int)($_POST['lesson_id'] ?? 0);
        $title      = clean_string($_POST['title'] ?? '');
        $videoUrl   = clean_string($_POST['video_url'] ?? '');
        $content    = clean_string($_POST['content'] ?? '');
        $orderIndex = max(1, (int)($_POST['order_index'] ?? 1));

        if (empty($title) || mb_strlen($title) < 2) {
            set_flash('danger', 'Lesson title must be at least 2 characters long.');
        } else {
            db_execute(
                "UPDATE lessons SET title = ?, content = ?, video_url = ?, order_index = ? WHERE id = ? AND course_id = ?",
                "sssiii",
                [$title, $content, $videoUrl, $orderIndex, $lessonId, $selectedCourseId]
            );
            set_flash('success', 'Lesson "' . e($title) . '" updated successfully.');
        }
        redirect('admin/lessons.php?course_id=' . $selectedCourseId);
    }

    // 3. DELETE LESSON
    if ($action === 'delete') {
        $lessonId = (int)($_POST['lesson_id'] ?? 0);
        $lesson = db_fetch_one("SELECT title FROM lessons WHERE id = ? AND course_id = ? LIMIT 1", "ii", [$lessonId, $selectedCourseId]);

        if ($lesson) {
            db_execute("DELETE FROM lessons WHERE id = ?", "i", [$lessonId]);
            
            // Recalculate progress for all enrollments in this course
            $enrollments = db_fetch_all("SELECT id, user_id FROM enrollments WHERE course_id = ?", "i", [$selectedCourseId]);
            $totalLessons = (int)(db_fetch_one("SELECT COUNT(*) AS total FROM lessons WHERE course_id = ?", "i", [$selectedCourseId])['total'] ?? 0);

            foreach ($enrollments as $enr) {
                $completed = (int)(db_fetch_one("
                    SELECT COUNT(*) AS total FROM lesson_completions lc 
                    JOIN lessons l ON lc.lesson_id = l.id 
                    WHERE lc.user_id = ? AND l.course_id = ?
                ", "ii", [$enr['user_id'], $selectedCourseId])['total'] ?? 0);
                $newP = calculate_progress($completed, $totalLessons);
                db_execute("UPDATE enrollments SET progress_percent = ? WHERE id = ?", "ii", [$newP, $enr['id']]);
            }

            set_flash('success', 'Lesson "' . e($lesson['title']) . '" was deleted.');
        } else {
            set_flash('danger', 'Lesson not found.');
        }
        redirect('admin/lessons.php?course_id=' . $selectedCourseId);
    }
}

// Fetch Lessons for Selected Course
$lessons = db_fetch_all("
    SELECT * FROM lessons 
    WHERE course_id = ? 
    ORDER BY order_index ASC, id ASC
", "i", [$selectedCourseId]);

// Suggested next order index
$maxOrder = (int)(db_fetch_one("SELECT MAX(order_index) AS max_idx FROM lessons WHERE course_id = ?", "i", [$selectedCourseId])['max_idx'] ?? 0);
$nextOrderIndex = $maxOrder + 1;

$pageTitle = 'Manage Curriculum Lessons | Admin Portal - EduStream LMS';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="bg-dark text-white py-4 mb-4 border-bottom border-secondary border-opacity-25">
    <div class="container-fluid px-lg-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php'); ?>" class="text-secondary text-decoration-none">Admin Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/courses.php'); ?>" class="text-secondary text-decoration-none">Courses</a></li>
                        <li class="breadcrumb-item active text-white" aria-current="page">Curriculum Lessons</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-extrabold text-white mb-0">Manage Course Curriculum</h1>
            </div>
            <div>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#addLessonModal">
                    <i class="bi bi-plus-lg me-1"></i> Add New Lesson
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-lg-5 pb-5">
    <?= render_flash_messages(); ?>

    <!-- COURSE SELECTOR BAR -->
    <div class="card custom-card border-0 shadow-sm p-3 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-3">
                <label for="courseSelect" class="form-label small fw-bold text-secondary mb-0">
                    <i class="bi bi-filter-square me-1 text-primary"></i> Select Course Curriculum:
                </label>
            </div>
            <div class="col-md-9">
                <select id="courseSelect" class="form-select" onchange="location.href='<?= base_url('admin/lessons.php?course_id='); ?>' + this.value;">
                    <?php foreach ($allCourses as $c): ?>
                        <option value="<?= $c['id']; ?>" <?= ($c['id'] == $selectedCourseId) ? 'selected' : ''; ?>>
                            <?= e($c['title']); ?> (<?= e($c['category']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- LESSONS TABLE -->
    <div class="card custom-card border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h6 fw-bold mb-0 text-dark">
                    <i class="bi bi-play-circle text-primary me-2"></i>Lessons for: <strong><?= e($selectedCourse['title']); ?></strong>
                </h2>
            </div>
            <span class="badge bg-secondary-subtle text-secondary small"><?= count($lessons); ?> Total Lessons</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-secondary">
                    <tr>
                        <th style="width: 70px;">Order</th>
                        <th>Lesson Title</th>
                        <th>Video Source</th>
                        <th>Lecture Content</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lessons)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-collection-play fs-1 d-block mb-2"></i>
                                No lessons created for this course yet. Click "Add New Lesson" above to construct the syllabus.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lessons as $l): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-primary rounded-pill px-2.5 py-1 fw-bold">#<?= (int)$l['order_index']; ?></span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark mb-0"><?= e($l['title']); ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($l['video_url'])): ?>
                                        <a href="<?= e($l['video_url']); ?>" target="_blank" class="small text-info text-decoration-none">
                                            <i class="bi bi-play-btn me-1"></i> Preview Link
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">Reading Only</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-secondary text-truncate d-block" style="max-width: 320px;">
                                        <?= e(mb_strimwidth(strip_tags($l['content'] ?? ''), 0, 90, '...')); ?>
                                    </small>
                                </td>
                                <td class="small text-muted">
                                    <?= format_date($l['created_at']); ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('lessons/view.php?course_id=' . $selectedCourseId . '&lesson_id=' . $l['id']); ?>" target="_blank" class="btn btn-light border text-info" title="Preview Student View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-light border text-secondary edit-lesson-btn"
                                            data-id="<?= $l['id']; ?>"
                                            data-title="<?= e($l['title']); ?>"
                                            data-video-url="<?= e($l['video_url'] ?? ''); ?>"
                                            data-order-index="<?= $l['order_index']; ?>"
                                            data-content="<?= e($l['content'] ?? ''); ?>"
                                            title="Edit Lesson">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-light border text-danger delete-lesson-btn"
                                            data-id="<?= $l['id']; ?>"
                                            data-title="<?= e($l['title']); ?>"
                                            title="Delete Lesson">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===========================================================================
     MODAL 1: ADD LESSON
============================================================================ -->
<div class="modal fade" id="addLessonModal" tabindex="-1" aria-labelledby="addLessonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form action="<?= base_url('admin/lessons.php?course_id=' . $selectedCourseId); ?>" method="POST">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="course_id" value="<?= $selectedCourseId; ?>">

                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="addLessonModalLabel"><i class="bi bi-plus-circle me-2 text-primary"></i>Add Lesson to <?= e($selectedCourse['title']); ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-9">
                            <label for="add_lesson_title" class="form-label small fw-bold text-secondary">Lesson Title *</label>
                            <input type="text" name="title" id="add_lesson_title" class="form-control" placeholder="e.g. Setting Up PHP & MariaDB Environment" required>
                        </div>
                        <div class="col-md-3">
                            <label for="add_order_index" class="form-label small fw-bold text-secondary">Order Index *</label>
                            <input type="number" name="order_index" id="add_order_index" class="form-control" value="<?= $nextOrderIndex; ?>" min="1" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="add_video_url" class="form-label small fw-bold text-secondary">Video URL (Embeddable YouTube or Vimeo URL)</label>
                        <input type="url" name="video_url" id="add_video_url" class="form-control" placeholder="https://www.youtube.com/embed/VIDEO_ID">
                        <small class="text-muted">Use standard embed format: <code>https://www.youtube.com/embed/...</code></small>
                    </div>

                    <div class="mb-3">
                        <label for="add_content" class="form-label small fw-bold text-secondary">Lecture Content & Markdown Notes</label>
                        <textarea name="content" id="add_content" class="form-control font-monospace" rows="8" placeholder="### Lesson Introduction&#10;&#10;In this lesson, we will explore...&#10;&#10;```php&#10;echo 'Code snippet';&#10;```"></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <i class="bi bi-check-lg me-1"></i> Save Lesson
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===========================================================================
     MODAL 2: EDIT LESSON
============================================================================ -->
<div class="modal fade" id="editLessonModal" tabindex="-1" aria-labelledby="editLessonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form action="<?= base_url('admin/lessons.php?course_id=' . $selectedCourseId); ?>" method="POST">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="course_id" value="<?= $selectedCourseId; ?>">
                <input type="hidden" name="lesson_id" id="edit_lesson_id" value="">

                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="editLessonModalLabel"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Lesson</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-9">
                            <label for="edit_lesson_title" class="form-label small fw-bold text-secondary">Lesson Title *</label>
                            <input type="text" name="title" id="edit_lesson_title" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label for="edit_order_index" class="form-label small fw-bold text-secondary">Order Index *</label>
                            <input type="number" name="order_index" id="edit_order_index" class="form-control" min="1" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_video_url" class="form-label small fw-bold text-secondary">Video URL</label>
                        <input type="url" name="video_url" id="edit_video_url" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="edit_content" class="form-label small fw-bold text-secondary">Lecture Content & Markdown Notes</label>
                        <textarea name="content" id="edit_content" class="form-control font-monospace" rows="8"></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <i class="bi bi-check-lg me-1"></i> Update Lesson
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===========================================================================
     MODAL 3: DELETE LESSON
============================================================================ -->
<div class="modal fade" id="deleteLessonModal" tabindex="-1" aria-labelledby="deleteLessonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form action="<?= base_url('admin/lessons.php?course_id=' . $selectedCourseId); ?>" method="POST">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="course_id" value="<?= $selectedCourseId; ?>">
                <input type="hidden" name="lesson_id" id="delete_lesson_id" value="">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold" id="deleteLessonModalLabel"><i class="bi bi-trash-fill me-2"></i>Confirm Lesson Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <p class="text-secondary mb-2">Are you sure you want to delete this lesson?</p>
                    <div class="p-3 bg-light rounded-3 border fw-bold text-danger mb-3" id="delete_lesson_title"></div>
                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Student completion progress will automatically be recalculated across all active enrollments.
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">
                        <i class="bi bi-trash me-1"></i> Delete Lesson
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Edit Lesson Modal
    const editModal = new bootstrap.Modal(document.getElementById('editLessonModal'));
    document.querySelectorAll('.edit-lesson-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit_lesson_id').value = btn.getAttribute('data-id');
            document.getElementById('edit_lesson_title').value = btn.getAttribute('data-title');
            document.getElementById('edit_video_url').value = btn.getAttribute('data-video-url');
            document.getElementById('edit_order_index').value = btn.getAttribute('data-order-index');
            document.getElementById('edit_content').value = btn.getAttribute('data-content');
            editModal.show();
        });
    });

    // Delete Lesson Modal
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteLessonModal'));
    document.querySelectorAll('.delete-lesson-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('delete_lesson_id').value = btn.getAttribute('data-id');
            document.getElementById('delete_lesson_title').textContent = btn.getAttribute('data-title');
            deleteModal.show();
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
