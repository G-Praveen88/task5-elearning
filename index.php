<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: index.php
 * 
 * Public Homepage & LMS Course Showcase
 * Author: G. Praveen
 */

$pageTitle = 'EduStream LMS | Next-Generation Online Learning Portal';
$pageDescription = 'Advance your tech career with project-driven courses, hands-on curriculum, interactive video lessons, and verified certifications.';

require_once __DIR__ . '/includes/header.php';

// Fetch Live Platform Statistics
$totalStudents = (int)(db_fetch_one("SELECT COUNT(*) AS total FROM users WHERE role = 'student'")['total'] ?? 0);
$totalCourses = (int)(db_fetch_one("SELECT COUNT(*) AS total FROM courses")['total'] ?? 0);
$totalLessons = (int)(db_fetch_one("SELECT COUNT(*) AS total FROM lessons")['total'] ?? 0);
$totalEnrollments = (int)(db_fetch_one("SELECT COUNT(*) AS total FROM enrollments")['total'] ?? 0);

// Fetch Featured Courses (Top 3 with highest enrollments or newest)
$featuredCourses = db_fetch_all("
    SELECT c.*, u.name AS instructor_name,
           (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) AS lesson_count,
           (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) AS student_count
    FROM courses c
    JOIN users u ON c.instructor_id = u.id
    ORDER BY student_count DESC, c.created_at DESC
    LIMIT 3
");

// Fetch Popular Categories with course count
$categories = db_fetch_all("
    SELECT category, COUNT(*) AS count
    FROM courses
    GROUP BY category
    ORDER BY count DESC
    LIMIT 5
");
?>

<!-- HERO SECTION -->
<section class="hero-section">
    <div class="hero-glow"></div>
    <div class="container position-relative py-4 py-lg-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill bg-white bg-opacity-10 border border-white border-opacity-20 text-white small fw-bold mb-3 shadow-sm">
                    <span class="badge bg-primary rounded-pill px-2 py-1">NEW</span>
                    <span>Industry-Aligned Tech Capstone Curriculums</span>
                </div>
                <h1 class="display-4 fw-extrabold text-white mb-3" style="line-height: 1.15;">
                    Master Modern Skills With <span class="text-info">Hands-On</span> Learning
                </h1>
                <p class="lead text-light opacity-85 mb-4 pe-lg-4">
                    Explore curated courses in Web Engineering, Data Science, Cyber Security, Cloud Computing, and UI/UX. Track your progress lesson-by-lesson with interactive video lectures and real-time assessments.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= base_url('courses/list.php'); ?>" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold shadow">
                        <i class="bi bi-collection-play me-2"></i> Browse All Courses
                    </a>
                    <?php if (!is_logged_in()): ?>
                        <a href="<?= base_url('auth/register.php'); ?>" class="btn btn-outline-light btn-lg rounded-pill px-4 fw-bold">
                            <i class="bi bi-person-plus me-2"></i> Join for Free
                        </a>
                    <?php else: ?>
                        <a href="<?= base_url('dashboard.php'); ?>" class="btn btn-outline-light btn-lg rounded-pill px-4 fw-bold">
                            <i class="bi bi-speedometer2 me-2"></i> Go to My Dashboard
                        </a>
                    <?php endif; ?>
                </div>

                <div class="d-flex align-items-center gap-4 mt-5 pt-3 border-top border-white border-opacity-15 text-light small">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-shield-check text-success fs-5"></i>
                        <span>Email OTP Verified</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-play-circle text-info fs-5"></i>
                        <span>HD Video Lessons</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-trophy text-warning fs-5"></i>
                        <span>Progress Tracking</span>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 d-none d-lg-block">
                <div class="p-4 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-20 shadow-lg backdrop-blur">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 fw-bold">Live Student Roster</span>
                        <span class="text-white-50 small"><i class="bi bi-circle-fill text-success me-1 small"></i> Active Now</span>
                    </div>
                    <div class="stat-widget bg-dark text-white border-secondary border-opacity-50 mb-3 shadow">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-secondary small">Total Platform Students</div>
                                <div class="display-6 fw-bold text-white"><?= number_format($totalStudents); ?></div>
                            </div>
                            <div class="stat-icon bg-primary bg-opacity-25 text-primary">
                                <i class="bi bi-people-fill"></i>
                            </div>
                        </div>
                    </div>
                    <div class="stat-widget bg-dark text-white border-secondary border-opacity-50 shadow">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="text-secondary small">Published Curriculum Lessons</div>
                                <div class="display-6 fw-bold text-info"><?= number_format($totalLessons); ?></div>
                            </div>
                            <div class="stat-icon bg-info bg-opacity-25 text-info">
                                <i class="bi bi-camera-video-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- PLATFORM KEY METRICS -->
<section class="py-5 bg-white border-bottom">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-6 col-md-3">
                <div class="p-3">
                    <div class="h2 fw-extrabold text-primary mb-1"><?= number_format($totalStudents); ?>+</div>
                    <div class="text-secondary small fw-semibold text-uppercase tracking-wider">Active Students</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3">
                    <div class="h2 fw-extrabold text-primary mb-1"><?= number_format($totalCourses); ?></div>
                    <div class="text-secondary small fw-semibold text-uppercase tracking-wider">Expert Courses</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3">
                    <div class="h2 fw-extrabold text-primary mb-1"><?= number_format($totalLessons); ?>+</div>
                    <div class="text-secondary small fw-semibold text-uppercase tracking-wider">Video Lessons</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3">
                    <div class="h2 fw-extrabold text-primary mb-1"><?= number_format($totalEnrollments); ?>+</div>
                    <div class="text-secondary small fw-semibold text-uppercase tracking-wider">Course Enrollments</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURED COURSES SECTION -->
<section class="py-5">
    <div class="container py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary fw-bold mb-2">CURATED PICKS</span>
                <h2 class="h3 fw-bold mb-1">Featured Top-Rated Courses</h2>
                <p class="text-secondary small mb-0">Learn from certified instructors with structured roadmaps and real code exercises</p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="<?= base_url('courses/list.php'); ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold">
                    View All <?= $totalCourses; ?> Courses <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredCourses as $c): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card custom-card course-card border-0">
                        <div class="course-thumbnail-wrapper">
                            <img src="<?= get_course_thumbnail($c['thumbnail'], $c['title'], $c['category']); ?>" alt="<?= e($c['title']); ?>" class="course-thumbnail-img" loading="lazy">
                            <span class="badge-category"><?= e($c['category']); ?></span>
                        </div>
                        <div class="card-body p-4 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small text-muted"><i class="bi bi-journal-text me-1"></i> <?= (int)$c['lesson_count']; ?> Lessons</span>
                                <span class="small text-muted"><i class="bi bi-person-check me-1"></i> <?= (int)$c['student_count']; ?> Enrolled</span>
                            </div>
                            <h3 class="h5 fw-bold mb-2 flex-grow-1">
                                <a href="<?= base_url('courses/view.php?id=' . $c['id']); ?>" class="text-dark text-decoration-none hover-primary">
                                    <?= e($c['title']); ?>
                                </a>
                            </h3>
                            <p class="text-secondary small line-clamp-2 mb-3">
                                <?= e(mb_strimwidth($c['description'], 0, 115, '...')); ?>
                            </p>
                            <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-auto">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                        <i class="bi bi-person-circle fs-5"></i>
                                    </div>
                                    <span class="small fw-semibold text-secondary text-truncate" style="max-width: 120px;"><?= e($c['instructor_name']); ?></span>
                                </div>
                                <div class="fw-bold fs-5 text-primary">
                                    <?= format_currency($c['price']); ?>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="<?= base_url('courses/view.php?id=' . $c['id']); ?>" class="btn btn-outline-primary btn-sm w-100 rounded-pill fw-semibold">
                                    View Syllabus & Enroll
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CATEGORY BROWSE SECTION -->
<section class="py-5 bg-light border-top border-bottom">
    <div class="container py-3">
        <div class="text-center mb-5">
            <span class="badge bg-secondary-subtle text-secondary fw-bold mb-2">DISCOVERY</span>
            <h2 class="h3 fw-bold mb-1">Explore High-Demand Categories</h2>
            <p class="text-secondary small">Filter specialized courses aligned with modern engineering roles</p>
        </div>

        <div class="row g-3 justify-content-center">
            <?php
            $catIcons = [
                'Web Development' => 'bi-code-slash text-primary',
                'Data Science'    => 'bi-graph-up text-success',
                'Cyber Security'  => 'bi-shield-shaded text-danger',
                'Cloud Computing' => 'bi-cloud-arrow-up text-info',
                'UI/UX Design'    => 'bi-palette text-warning',
            ];
            foreach ($categories as $cat):
                $iconClass = $catIcons[$cat['category']] ?? 'bi-bookmark text-primary';
            ?>
                <div class="col-md-4 col-sm-6">
                    <a href="<?= base_url('courses/list.php?category=' . urlencode($cat['category'])); ?>" class="card custom-card border-0 text-decoration-none p-3 h-100 d-flex flex-row align-items-center gap-3">
                        <div class="bg-white rounded-3 p-3 shadow-sm border d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                            <i class="bi <?= $iconClass; ?> fs-4"></i>
                        </div>
                        <div>
                            <h4 class="h6 fw-bold text-dark mb-0"><?= e($cat['category']); ?></h4>
                            <span class="text-secondary small"><?= (int)$cat['count']; ?> Available Courses</span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CALL TO ACTION BANNER -->
<section class="py-5">
    <div class="container py-4">
        <div class="p-4 p-md-5 rounded-4 bg-primary text-white position-relative overflow-hidden shadow-lg text-center text-md-start">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="display-6 fw-bold mb-2 text-white">Ready to Elevate Your Tech Career?</h2>
                    <p class="lead mb-0 text-white-50">
                        Create your free account today, verify your email with OTP, and start learning immediately.
                    </p>
                </div>
                <div class="col-md-4 text-md-end mt-4 mt-md-0">
                    <a href="<?= base_url('auth/register.php'); ?>" class="btn btn-light btn-lg rounded-pill px-4 fw-bold text-primary shadow">
                        Get Started Free <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
