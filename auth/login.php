<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: auth/login.php
 * 
 * User Sign-In with Prepared Statements, OTP Verification Check & Role-Based Routing
 * Author: G. Praveen
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Prevent already logged-in users
require_guest();

$pageTitle = 'Sign In | EduStream LMS';
$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!verify_csrf_token()) {
        $errors[] = 'Security token invalid or expired. Please refresh and try again.';
    }

    $email    = clean_string($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($password)) {
        $errors[] = 'Please enter your password.';
    }

    // 2. Query User using Prepared Statement
    if (empty($errors)) {
        $sql = "SELECT id, name, email, password_hash, role, otp_verified FROM users WHERE email = ? LIMIT 1";
        $user = db_fetch_one($sql, "s", [$email]);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Invalid email address or password. Please check your credentials.';
        } else {
            // 3. Check Account Verification Status
            if ((int)$user['otp_verified'] !== 1) {
                // Generate a fresh OTP and prompt user to complete verification
                $newOtp = sprintf('%06d', random_int(100000, 999999));
                $newExpiry = date('Y-m-d H:i:s', time() + 600);
                db_execute("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?", "ssi", [$newOtp, $newExpiry, $user['id']]);

                $_SESSION['pending_otp_email'] = $user['email'];
                $_SESSION['simulated_otp'] = $newOtp;

                set_flash('warning', 'Your account has not yet been verified. Please enter the OTP below to activate your account.');
                redirect('auth/verify_otp.php');
            }

            // 4. Session Regeneration to Prevent Session Fixation
            session_regenerate_id(true);

            // 5. Populate Authenticated Session
            $_SESSION['user_id']    = (int)$user['id'];
            $_SESSION['user_name']  = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role']  = $user['role'];

            set_flash('success', 'Welcome back, ' . e($user['name']) . '!');

            // 6. Role-Based Redirection
            if (!empty($_SESSION['intended_url'])) {
                $target = $_SESSION['intended_url'];
                unset($_SESSION['intended_url']);
                redirect($target);
            }

            if ($user['role'] === 'admin' || $user['role'] === 'instructor') {
                redirect('admin/index.php');
            } else {
                redirect('dashboard.php');
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-8 col-sm-10">
            <div class="card custom-card border-0 shadow-lg p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 58px; height: 58px;">
                        <i class="bi bi-box-arrow-in-right fs-3"></i>
                    </div>
                    <h2 class="h3 fw-bold mb-1">Welcome Back</h2>
                    <p class="text-secondary small">Sign in to your EduStream LMS account</p>
                </div>

                <?= render_flash_messages(); ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger shadow-sm mb-4">
                        <div class="d-flex align-items-center gap-2 fw-semibold mb-1">
                            <i class="bi bi-exclamation-octagon-fill"></i> Authentication Error:
                        </div>
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('auth/login.php'); ?>" method="POST" autocomplete="off" novalidate>
                    <?= csrf_field(); ?>

                    <div class="mb-3">
                        <label for="email" class="form-label small fw-bold text-secondary">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" id="email" class="form-control bg-light border-start-0 ps-0" placeholder="name@example.com" value="<?= e($email); ?>" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label small fw-bold text-secondary mb-0">Password</label>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" id="password" class="form-control bg-light border-start-0 border-end-0 ps-0" placeholder="••••••••" required>
                            <button class="btn btn-light border border-start-0 text-muted" type="button" data-toggle="password" data-target="password" tabindex="-1">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-lg rounded-3 fw-bold py-2 shadow-sm">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                        </button>
                    </div>

                    <div class="text-center small text-secondary mb-4">
                        Don't have an account? <a href="<?= base_url('auth/register.php'); ?>" class="text-primary fw-bold text-decoration-none">Create one</a>
                    </div>
                </form>

                <!-- DEMO QUICK LOGIN PRESETS (Evaluator & Demo Convenience) -->
                <div class="p-3 bg-light rounded-3 border">
                    <div class="small fw-bold text-secondary mb-2 d-flex align-items-center justify-content-between">
                        <span><i class="bi bi-lightning-charge-fill text-warning me-1"></i> Quick Demo Logins:</span>
                        <span class="badge bg-secondary-subtle text-secondary small">Evaluator Tool</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-danger btn-sm w-50 py-1.5" onclick="fillCredentials('admin@elearn.test', 'Admin@123')">
                            <i class="bi bi-shield-lock me-1"></i> Admin Login
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm w-50 py-1.5" onclick="fillCredentials('student@elearn.test', 'Student@123')">
                            <i class="bi bi-mortarboard me-1"></i> Student Login
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillCredentials(email, password) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = password;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
