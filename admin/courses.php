<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: admin/courses.php
 * 
 * Full Course CRUD Management with Secure Thumbnail Upload Validation
 * Author: G. Praveen
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Access Control: Admin or Instructor Required
require_admin();

$uploadDir = __DIR__ . '/../uploads/';
$errors = [];

// =============================================================================
// HELPER: SECURE FILE UPLOAD HANDLER
// =============================================================================
function handle_thumbnail_upload(?array $file, ?string $oldThumbnail = null): ?string {
    global $uploadDir;

    if (!$file || empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        return $oldThumbnail; // Keep old thumbnail if no new file uploaded
    }

    // 1. Validate File Size (Max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new RuntimeException('Thumbnail image must not exceed 2MB in size.');
    }

    // 2. Validate File Extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowedExts, true)) {
        throw new RuntimeException('Invalid image format. Allowed formats: JPG, JPEG, PNG, WEBP.');
    }

    // 3. Inspect Real MIME Type using finfo
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowedMimes, true)) {
        throw new RuntimeException('Suspicious file upload rejected. MIME type mismatch.');
    }

    // 4. Generate Cryptographically Randomized Filename
    $newFilename = 'course_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $targetPath = $uploadDir . $newFilename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Failed to store uploaded thumbnail on server.');
    }

    // 5. Clean up old custom thumbnail if replaced
    if ($oldThumbnail && file_exists($uploadDir . $oldThumbnail) && is_file($uploadDir . $oldThumbnail)) {
        @unlink($uploadDir . $oldThumbnail);
    }

    return $newFilename;
}

// =============================================================================
// HANDLE POST ACTIONS (CREATE, UPDATE, DELETE)
// =============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('danger', 'Security token invalid or expired. Please try again.');
        redirect('admin/courses.php');
    }

    $action = clean_string($_POST['action'] ?? '');

    // -------------------------------------------------------------------------
    // 1. ADD COURSE
    // -------------------------------------------------------------------------
    if ($action === 'add') {
        $title        = clean_string($_POST['title'] ?? '');
        $description  = clean_string($_POST['description'] ?? '');
        $category     = clean_string($_POST['category'] ?? '');
        $price        = max(0.0, (float)($_POST['price'] ?? 0.00));
        $instructorId = (int)($_POST['instructor_id'] ?? current_user_id());

        if (empty($title) || mb_strlen($title) < 3) {
            $errors[] = 'Course title must be at least 3 characters long.';
        }
        if (empty($description)) {
            $errors[] = 'Course description cannot be blank.';
        }
        if (empty($category)) {
            $errors[] = 'Please select or provide a valid category.';
        }

        $thumbnail = null;
        if (empty($errors)) {
            try {
                if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                    $thumbnail = handle_thumbnail_upload($_FILES['thumbnail']);
                }
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (empty($errors)) {
            $sql = "INSERT INTO courses (title, description, category, price, instructor_id, thumbnail) VALUES (?, ?, ?, ?, ?, ?)";
            $res = db_execute($sql, "sssdis", [$title, $description, $category, $price, $instructorId, $thumbnail]);

            if ($res['affected_rows'] > 0) {
                set_flash('success', 'Course "' . e($title) . '" was successfully created!');
                redirect('admin/courses.php');
            } else {
                $errors[] = 'Database failed to save the course.';
            }
        }
    }

    // -------------------------------------------------------------------------
    // 2. EDIT COURSE
    // -------------------------------------------------------------------------
    if ($action === 'edit') {
        $courseId     = (int)($_POST['course_id'] ?? 0);
        $title        = clean_string($_POST['title'] ?? '');
        $description  = clean_string($_POST['description'] ?? '');
        $category     = clean_string($_POST['category'] ?? '');
        $price        = max(0.0, (float)($_POST['price'] ?? 0.00));
        $instructorId = (int)($_POST['instructor_id'] ?? current_user_id());

        $existing = db_fetch_one("SELECT * FROM courses WHERE id = ? LIMIT 1", "i", [$courseId]);
        if (!$existing) {
            set_flash('danger', 'The selected course does not exist.');
            redirect('admin/courses.php');
        }

        if (empty($title) || mb_strlen($title) < 3) {
            $errors[] = 'Course title must be at least 3 characters long.';
        }
        if (empty($description)) {
            $errors[] = 'Course description cannot be blank.';
        }
        if (empty($category)) {
            $errors[] = 'Category cannot be blank.';
        }

        $thumbnail = $existing['thumbnail'];
        if (empty($errors)) {
            try {
                if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                    $thumbnail = handle_thumbnail_upload($_FILES['thumbnail'], $existing['thumbnail']);
                }
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (empty($errors)) {
            $sql = "UPDATE courses SET title = ?, description = ?, category = ?, price = ?, instructor_id = ?, thumbnail = ? WHERE id = ?";
            db_execute($sql, "sssdisi", [$title, $description, $category, $price, $instructorId, $thumbnail, $courseId]);

            set_flash('success', 'Course "' . e($title) . '" updated successfully!');
            redirect('admin/courses.php');
        }
    }

    // -------------------------------------------------------------------------
    // 3. DELETE COURSE
    // -------------------------------------------------------------------------
    if ($action === 'delete') {
        $courseId = (int)($_POST['course_id'] ?? 0);
        $existing = db_fetch_one("SELECT * FROM courses WHERE id = ? LIMIT 1", "i", [$courseId]);

        if ($existing) {
            // Remove thumbnail if custom uploaded
            if (!empty($existing['thumbnail']) && file_exists($uploadDir . $existing['thumbnail'])) {
                @unlink($uploadDir . $existing['thumbnail']);
            }

            db_execute("DELETE FROM courses WHERE id = ?", "i", [$courseId]);
            set_flash('success', 'Course "' . e($existing['title']) . '" and all associated curriculums were permanently deleted.');
        } else {
            set_flash('danger', 'Course not found.');
        }
        redirect('admin/courses.php');
    }
}

// Fetch All Courses with Stats
$courses = db_fetch_all("
    SELECT c.*, u.name AS instructor_name,
           (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) AS lesson_count,
           (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) AS student_count
    FROM courses c
    JOIN users u ON c.instructor_id = u.id
    ORDER BY c.created_at DESC
");

// Fetch Instructors for Dropdown
$instructors = db_fetch_all("SELECT id, name, email, role FROM users WHERE role IN ('admin', 'instructor') ORDER BY name ASC");

$pageTitle = 'Manage Courses | Admin Portal - EduStream LMS';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="bg-dark text-white py-4 mb-4 border-bottom border-secondary border-opacity-25">
    <div class="container-fluid px-lg-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php'); ?>" class="text-secondary text-decoration-none">Admin Dashboard</a></li>
                        <li class="breadcrumb-item active text-white" aria-current="page">Course Management</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-extrabold text-white mb-0">Courses Catalog CRUD</h1>
            </div>
            <div>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                    <i class="bi bi-plus-lg me-1"></i> Add New Course
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-lg-5 pb-5">
    <?= render_flash_messages(); ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger shadow-sm mb-4">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon me-1"></i> Validation Errors:</div>
            <ul class="mb-0 ps-3 small">
                <?php foreach ($errors as $e): ?>
                    <li><?= e($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card custom-card border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h2 class="h6 fw-bold mb-0 text-dark">
                <i class="bi bi-journal-album text-primary me-2"></i>Published Courses (<?= count($courses); ?>)
            </h2>
            <span class="badge bg-secondary-subtle text-secondary small">Live Database Table</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-secondary">
                    <tr>
                        <th style="width: 70px;">Cover</th>
                        <th>Course Details</th>
                        <th>Category</th>
                        <th>Instructor</th>
                        <th>Price</th>
                        <th>Lessons</th>
                        <th>Enrolled</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($courses)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No courses created yet. Click "Add New Course" above to publish your first curriculum.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($courses as $c): ?>
                            <tr>
                                <td>
                                    <img src="<?= get_course_thumbnail($c['thumbnail'], $c['title'], $c['category']); ?>" alt="Cover" class="rounded shadow-sm" style="width: 58px; height: 36px; object-fit: cover;">
                                </td>
                                <td>
                                    <div class="fw-bold text-dark mb-0"><?= e($c['title']); ?></div>
                                    <small class="text-secondary text-truncate d-block" style="max-width: 280px;"><?= e($c['description']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= e($c['category']); ?></span>
                                </td>
                                <td>
                                    <span class="small fw-semibold text-secondary"><?= e($c['instructor_name']); ?></span>
                                </td>
                                <td>
                                    <span class="fw-bold text-dark"><?= format_currency($c['price']); ?></span>
                                </td>
                                <td>
                                    <a href="<?= base_url('admin/lessons.php?course_id=' . $c['id']); ?>" class="btn btn-outline-info btn-sm rounded-pill px-2 py-0 text-decoration-none" style="font-size: 0.78rem;">
                                        <i class="bi bi-play-circle me-1"></i> <?= (int)$c['lesson_count']; ?> Lessons
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary"><?= (int)$c['student_count']; ?> Students</span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= base_url('admin/lessons.php?course_id=' . $c['id']); ?>" class="btn btn-light border text-primary" title="Manage Lessons">
                                            <i class="bi bi-list-nested"></i>
                                        </a>
                                        <button type="button" class="btn btn-light border text-secondary edit-course-btn" 
                                            data-id="<?= $c['id']; ?>"
                                            data-title="<?= e($c['title']); ?>"
                                            data-description="<?= e($c['description']); ?>"
                                            data-category="<?= e($c['category']); ?>"
                                            data-price="<?= $c['price']; ?>"
                                            data-instructor-id="<?= $c['instructor_id']; ?>"
                                            title="Edit Course">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <button type="button" class="btn btn-light border text-danger delete-course-btn" 
                                            data-id="<?= $c['id']; ?>" 
                                            data-title="<?= e($c['title']); ?>"
                                            title="Delete Course">
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
     MODAL 1: ADD COURSE
============================================================================ -->
<div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form action="<?= base_url('admin/courses.php'); ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="add">

                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="addCourseModalLabel"><i class="bi bi-plus-circle me-2 text-primary"></i>Create New Course</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="add_title" class="form-label small fw-bold text-secondary">Course Title *</label>
                        <input type="text" name="title" id="add_title" class="form-control" placeholder="e.g. Modern Web Development with PHP & MySQL" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="add_category" class="form-label small fw-bold text-secondary">Category *</label>
                            <select name="category" id="add_category" class="form-select" required>
                                <option value="" disabled selected>Select a category...</option>
                                <option value="Web Development">Web Development</option>
                                <option value="Data Science">Data Science</option>
                                <option value="Cyber Security">Cyber Security</option>
                                <option value="Cloud Computing">Cloud Computing</option>
                                <option value="UI/UX Design">UI/UX Design</option>
                                <option value="Mobile Development">Mobile Development</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="add_price" class="form-label small fw-bold text-secondary">Tuition Price ($) *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0" name="price" id="add_price" class="form-control" value="0.00" required>
                            </div>
                            <small class="text-muted">Enter 0.00 to offer as a free course.</small>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="add_instructor" class="form-label small fw-bold text-secondary">Assigned Instructor *</label>
                            <select name="instructor_id" id="add_instructor" class="form-select" required>
                                <?php foreach ($instructors as $inst): ?>
                                    <option value="<?= $inst['id']; ?>" <?= ($inst['id'] === current_user_id()) ? 'selected' : ''; ?>>
                                        <?= e($inst['name']); ?> (<?= e($inst['role']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="add_thumbnail" class="form-label small fw-bold text-secondary">Course Thumbnail Image</label>
                            <input type="file" name="thumbnail" id="add_thumbnail" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            <small class="text-muted">JPG, PNG, WEBP max 2MB. (Auto SVG cover if empty)</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="add_description" class="form-label small fw-bold text-secondary">Comprehensive Syllabus & Description *</label>
                        <textarea name="description" id="add_description" class="form-control" rows="4" placeholder="Detail course objectives, topics covered, and target audience..." required></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <i class="bi bi-check-lg me-1"></i> Save & Publish Course
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===========================================================================
     MODAL 2: EDIT COURSE
============================================================================ -->
<div class="modal fade" id="editCourseModal" tabindex="-1" aria-labelledby="editCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form action="<?= base_url('admin/courses.php'); ?>" method="POST" enctype="multipart/form-data">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="course_id" id="edit_course_id" value="">

                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="editCourseModalLabel"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Course Information</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_title" class="form-label small fw-bold text-secondary">Course Title *</label>
                        <input type="text" name="title" id="edit_title" class="form-control" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="edit_category" class="form-label small fw-bold text-secondary">Category *</label>
                            <select name="category" id="edit_category" class="form-select" required>
                                <option value="Web Development">Web Development</option>
                                <option value="Data Science">Data Science</option>
                                <option value="Cyber Security">Cyber Security</option>
                                <option value="Cloud Computing">Cloud Computing</option>
                                <option value="UI/UX Design">UI/UX Design</option>
                                <option value="Mobile Development">Mobile Development</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_price" class="form-label small fw-bold text-secondary">Tuition Price ($) *</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" min="0" name="price" id="edit_price" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="edit_instructor" class="form-label small fw-bold text-secondary">Assigned Instructor *</label>
                            <select name="instructor_id" id="edit_instructor" class="form-select" required>
                                <?php foreach ($instructors as $inst): ?>
                                    <option value="<?= $inst['id']; ?>">
                                        <?= e($inst['name']); ?> (<?= e($inst['role']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="edit_thumbnail" class="form-label small fw-bold text-secondary">Update Thumbnail (Optional)</label>
                            <input type="file" name="thumbnail" id="edit_thumbnail" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                            <small class="text-muted">Leave empty to keep existing thumbnail.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_description" class="form-label small fw-bold text-secondary">Description *</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="4" required></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <i class="bi bi-check-lg me-1"></i> Update Course
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===========================================================================
     MODAL 3: DELETE CONFIRMATION
============================================================================ -->
<div class="modal fade" id="deleteCourseModal" tabindex="-1" aria-labelledby="deleteCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form action="<?= base_url('admin/courses.php'); ?>" method="POST">
                <?= csrf_field(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="course_id" id="delete_course_id" value="">

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold" id="deleteCourseModalLabel"><i class="bi bi-trash-fill me-2"></i>Confirm Course Deletion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <p class="text-secondary mb-2">Are you sure you want to permanently delete this course?</p>
                    <div class="p-3 bg-light rounded-3 border fw-bold text-danger mb-3" id="delete_course_title"></div>
                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <strong>Warning:</strong> Deleting this course will automatically cascade and delete all child lessons, student enrollments, and completion records!
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">
                        <i class="bi bi-trash me-1"></i> Permanently Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Edit Modal Hookup
    const editModal = new bootstrap.Modal(document.getElementById('editCourseModal'));
    document.querySelectorAll('.edit-course-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit_course_id').value = btn.getAttribute('data-id');
            document.getElementById('edit_title').value = btn.getAttribute('data-title');
            document.getElementById('edit_description').value = btn.getAttribute('data-description');
            document.getElementById('edit_category').value = btn.getAttribute('data-category');
            document.getElementById('edit_price').value = btn.getAttribute('data-price');
            document.getElementById('edit_instructor').value = btn.getAttribute('data-instructor-id');
            editModal.show();
        });
    });

    // Delete Modal Hookup
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteCourseModal'));
    document.querySelectorAll('.delete-course-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('delete_course_id').value = btn.getAttribute('data-id');
            document.getElementById('delete_course_title').textContent = btn.getAttribute('data-title');
            deleteModal.show();
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
