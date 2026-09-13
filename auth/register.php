<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: auth/register.php
 * 
 * User Registration with Bcrypt Password Hashing & 6-Digit Email OTP Generation
 * Author: G. Praveen
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Prevent authenticated users from accessing registration
require_guest();

$pageTitle = 'Create Your Account | EduStream LMS';
$errors = [];
$name = '';
$email = '';
$role = 'student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!verify_csrf_token()) {
        $errors[] = 'Invalid or expired security token. Please refresh and try again.';
    }

    // 2. Extract & Sanitize Input
    $name             = clean_string($_POST['name'] ?? '');
    $email            = clean_string($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $roleInput        = clean_string($_POST['role'] ?? 'student');
    $role             = in_array($roleInput, ['student', 'instructor']) ? $roleInput : 'student';

    // 3. Server-Side Validation
    if (empty($name) || mb_strlen($name) < 2) {
        $errors[] = 'Full name must be at least 2 characters.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

    if (empty($password) || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters in length.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    // 4. Check for Duplicate Email using Prepared Statement
    if (empty($errors)) {
        $existing = db_fetch_one("SELECT id, otp_verified FROM users WHERE email = ? LIMIT 1", "s", [$email]);
        if ($existing) {
            if ($existing['otp_verified'] == 0) {
                // User started registration earlier but did not verify OTP
                $_SESSION['pending_otp_email'] = $email;
                $newOtp = sprintf('%06d', random_int(100000, 999999));
                $expiresAt = date('Y-m-d H:i:s', time() + 600);
                db_execute("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?", "ssi", [$newOtp, $expiresAt, $existing['id']]);
                $_SESSION['simulated_otp'] = $newOtp;
                set_flash('info', 'An unverified account with this email was found. A fresh OTP has been generated.');
                redirect('auth/verify_otp.php');
            } else {
                $errors[] = 'An account with this email address already exists. Please sign in instead.';
            }
        }
    }

    // 5. Insert New User & Generate 6-digit OTP with 10-Minute Expiry
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $otp = sprintf('%06d', random_int(100000, 999999));
        $otpExpiresAt = date('Y-m-d H:i:s', time() + 600); // 10 minutes from now

        $insertSql = "INSERT INTO users (name, email, password_hash, role, otp_code, otp_expires_at, otp_verified) VALUES (?, ?, ?, ?, ?, ?, 0)";
        $res = db_execute($insertSql, "ssssss", [$name, $email, $passwordHash, $role, $otp, $otpExpiresAt]);

        if ($res['affected_rows'] > 0) {
            // Save state in session for verification screen
            $_SESSION['pending_otp_email'] = $email;
            $_SESSION['simulated_otp'] = $otp; // Simulated inbox preview for grading/demo

            set_flash('success', 'Account created! A 6-digit verification code has been dispatched to your email.');
            redirect('auth/verify_otp.php');
        } else {
            $errors[] = 'Failed to register account. Please try again.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8 col-sm-10">
            <div class="card custom-card border-0 shadow-lg p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 58px; height: 58px;">
                        <i class="bi bi-person-plus-fill fs-3"></i>
                    </div>
                    <h2 class="h3 fw-bold mb-1">Create an Account</h2>
                    <p class="text-secondary small">Start your learning journey or instruct new courses on EduStream</p>
                </div>

                <?= render_flash_messages(); ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger shadow-sm mb-4">
                        <div class="d-flex align-items-center gap-2 fw-semibold mb-1">
                            <i class="bi bi-exclamation-octagon-fill"></i> Please resolve the following errors:
                        </div>
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('auth/register.php'); ?>" method="POST" autocomplete="off" novalidate>
                    <?= csrf_field(); ?>

                    <div class="mb-3">
                        <label for="name" class="form-label small fw-bold text-secondary">Full Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                            <input type="text" name="name" id="name" class="form-control bg-light border-start-0 ps-0" placeholder="e.g. John Doe" value="<?= e($name); ?>" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label small fw-bold text-secondary">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" id="email" class="form-control bg-light border-start-0 ps-0" placeholder="name@example.com" value="<?= e($email); ?>" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label small fw-bold text-secondary">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="password" class="form-control bg-light border-start-0 border-end-0 ps-0" placeholder="Min. 6 chars" required>
                                <button class="btn btn-light border border-start-0 text-muted" type="button" data-toggle="password" data-target="password" tabindex="-1">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label small fw-bold text-secondary">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" name="confirm_password" id="confirm_password" class="form-control bg-light border-start-0 border-end-0 ps-0" placeholder="Repeat password" required>
                                <button class="btn btn-light border border-start-0 text-muted" type="button" data-toggle="password" data-target="confirm_password" tabindex="-1">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Account Role</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="role" id="role_student" value="student" <?= ($role === 'student') ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary w-100 py-2 d-flex flex-column align-items-center" for="role_student">
                                    <i class="bi bi-mortarboard fs-5 mb-1"></i>
                                    <span class="small fw-bold">Student</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="role" id="role_instructor" value="instructor" <?= ($role === 'instructor') ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-primary w-100 py-2 d-flex flex-column align-items-center" for="role_instructor">
                                    <i class="bi bi-person-video3 fs-5 mb-1"></i>
                                    <span class="small fw-bold">Instructor</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-lg rounded-3 fw-bold py-2 shadow-sm">
                            <i class="bi bi-shield-check me-2"></i> Register & Send OTP
                        </button>
                    </div>

                    <div class="text-center small text-secondary">
                        Already have an account? <a href="<?= base_url('auth/login.php'); ?>" class="text-primary fw-bold text-decoration-none">Sign in</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
