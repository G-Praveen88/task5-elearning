<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: includes/navbar.php
 * 
 * Dynamic Role-Based Top Navigation Bar
 * Author: G. Praveen
 */

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top py-2 py-lg-3">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= base_url(); ?>">
            <div class="bg-primary text-white rounded-3 p-2 d-inline-flex align-items-center justify-content-center me-2" style="width: 38px; height: 38px;">
                <i class="bi bi-mortarboard-fill fs-5"></i>
            </div>
            <span>Edu<strong>Stream</strong></span>
        </a>

        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3 gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link <?= (str_ends_with($currentPath, 'index.php') || str_ends_with($currentPath, 'task5-elearning/')) ? 'active fw-bold' : ''; ?>" href="<?= base_url(); ?>">
                        <i class="bi bi-house-door me-1"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($currentPath, 'courses/list.php') ? 'active fw-bold' : ''; ?>" href="<?= base_url('courses/list.php'); ?>">
                        <i class="bi bi-collection-play me-1"></i> Browse Courses
                    </a>
                </li>

                <?php if (is_logged_in() && is_student()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= str_contains($currentPath, 'dashboard.php') ? 'active fw-bold' : ''; ?>" href="<?= base_url('dashboard.php'); ?>">
                            <i class="bi bi-speedometer2 me-1"></i> My Learning
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (is_admin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-primary fw-semibold" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-shield-check me-1"></i> Instructor / Admin
                        </a>
                        <ul class="dropdown-menu shadow-sm border-0">
                            <li><a class="dropdown-item py-2" href="<?= base_url('admin/index.php'); ?>"><i class="bi bi-grid me-2 text-primary"></i>Admin Overview</a></li>
                            <li><a class="dropdown-item py-2" href="<?= base_url('admin/courses.php'); ?>"><i class="bi bi-journal-album me-2 text-primary"></i>Manage Courses</a></li>
                            <li><a class="dropdown-item py-2" href="<?= base_url('admin/lessons.php'); ?>"><i class="bi bi-play-circle me-2 text-primary"></i>Manage Lessons</a></li>
                            <li><a class="dropdown-item py-2" href="<?= base_url('admin/students.php'); ?>"><i class="bi bi-people me-2 text-primary"></i>Enrolled Students</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2" href="<?= base_url('admin/analytics.php'); ?>"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Analytics Dashboard</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <?php if (!is_logged_in()): ?>
                    <a href="<?= base_url('auth/login.php'); ?>" class="btn btn-outline-secondary btn-sm px-3 rounded-pill fw-semibold">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </a>
                    <a href="<?= base_url('auth/register.php'); ?>" class="btn btn-primary btn-sm px-3 rounded-pill fw-semibold shadow-sm">
                        <i class="bi bi-person-plus-fill me-1"></i> Get Started
                    </a>
                <?php else: ?>
                    <div class="dropdown">
                        <button class="btn btn-light bg-white border dropdown-toggle d-flex align-items-center gap-2 py-1 px-3 rounded-pill shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 0.85rem;">
                                <?= strtoupper(substr(current_user_name(), 0, 1)); ?>
                            </div>
                            <span class="small fw-semibold text-truncate" style="max-width: 140px;"><?= e(current_user_name()); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2 p-2" style="min-width: 220px;">
                            <li class="px-3 py-2">
                                <div class="fw-bold text-dark text-truncate"><?= e(current_user_name()); ?></div>
                                <div class="text-muted small text-truncate"><?= e(current_user_email()); ?></div>
                                <div class="mt-1">
                                    <?php if (is_admin()): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-0.5">Admin / Instructor</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5">Student</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if (is_admin()): ?>
                                <li><a class="dropdown-item py-1.5 rounded-2" href="<?= base_url('admin/index.php'); ?>"><i class="bi bi-speedometer2 me-2 text-muted"></i>Admin Portal</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item py-1.5 rounded-2" href="<?= base_url('dashboard.php'); ?>"><i class="bi bi-book me-2 text-muted"></i>My Courses</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item py-1.5 rounded-2 text-danger" href="<?= base_url('auth/logout.php'); ?>"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
