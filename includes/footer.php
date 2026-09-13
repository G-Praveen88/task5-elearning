<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: includes/footer.php
 * 
 * Global Layout Footer & JavaScript Inclusions
 * Author: G. Praveen
 */
?>
</main>

<footer class="py-5 mt-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5 col-md-6">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary text-white rounded-3 p-2 d-inline-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px;">
                        <i class="bi bi-mortarboard-fill fs-5"></i>
                    </div>
                    <span class="fs-4 fw-bold text-white">Edu<strong class="text-primary">Stream</strong></span>
                </div>
                <p class="small text-secondary mb-3 pe-lg-4">
                    A next-generation full-stack Learning Management System designed for scalable online education, interactive multimedia lesson delivery, real-time student progress tracking, and administrative analytics.
                </p>
                <div class="d-flex gap-3 text-secondary">
                    <a href="https://github.com" target="_blank" class="text-secondary fs-5"><i class="bi bi-github"></i></a>
                    <a href="https://linkedin.com" target="_blank" class="text-secondary fs-5"><i class="bi bi-linkedin"></i></a>
                    <a href="mailto:support@elearn.test" class="text-secondary fs-5"><i class="bi bi-envelope"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-3 col-6">
                <h6 class="text-white fw-bold mb-3">Curriculum</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                    <li><a href="<?= base_url('courses/list.php?category=Web+Development'); ?>">Web Development</a></li>
                    <li><a href="<?= base_url('courses/list.php?category=Data+Science'); ?>">Data Science</a></li>
                    <li><a href="<?= base_url('courses/list.php?category=Cyber+Security'); ?>">Cyber Security</a></li>
                    <li><a href="<?= base_url('courses/list.php?category=Cloud+Computing'); ?>">Cloud & DevOps</a></li>
                    <li><a href="<?= base_url('courses/list.php?category=UI/UX+Design'); ?>">UI/UX Design</a></li>
                </ul>
            </div>

            <div class="col-lg-2 col-md-3 col-6">
                <h6 class="text-white fw-bold mb-3">Quick Links</h6>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
                    <li><a href="<?= base_url(); ?>">Home</a></li>
                    <li><a href="<?= base_url('courses/list.php'); ?>">Browse All Courses</a></li>
                    <?php if (is_logged_in()): ?>
                        <li><a href="<?= base_url('dashboard.php'); ?>">My Dashboard</a></li>
                        <li><a href="<?= base_url('auth/logout.php'); ?>">Sign Out</a></li>
                    <?php else: ?>
                        <li><a href="<?= base_url('auth/login.php'); ?>">Sign In</a></li>
                        <li><a href="<?= base_url('auth/register.php'); ?>">Create Account</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6 class="text-white fw-bold mb-3">Capstone Internship Project</h6>
                <div class="p-3 rounded-3 bg-dark border border-secondary border-opacity-25 small text-secondary">
                    <div class="text-white fw-semibold mb-1">ApexPlanet Software Pvt. Ltd.</div>
                    <div class="mb-2">Full Stack Web Development Internship &bull; PHP / MySQL Track</div>
                    <div class="badge bg-primary bg-opacity-25 text-info border border-info border-opacity-25">Task 5 &bull; Capstone Project</div>
                </div>
            </div>
        </div>

        <hr class="border-secondary border-opacity-25 my-4">

        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center small text-secondary gap-2">
            <div>
                &copy; <?= date('Y'); ?> <strong>EduStream LMS</strong>. All rights reserved. Developed by <strong>G. Praveen</strong>.
            </div>
            <div class="d-flex gap-3">
                <a href="<?= base_url('REQUIREMENTS.md'); ?>" class="text-secondary">Requirements</a>
                <a href="<?= base_url('ER_DIAGRAM.md'); ?>" class="text-secondary">ER Diagram</a>
                <a href="<?= base_url('schema.sql'); ?>" class="text-secondary">Schema</a>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3.3 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Global LMS Client-Side Script -->
<script src="<?= base_url('js/main.js'); ?>"></script>
</body>
</html>
