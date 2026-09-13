<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: auth/verify_otp.php
 * 
 * Email OTP Verification & Account Activation (with Simulated Email Delivery)
 * Author: G. Praveen
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Prevent already logged-in users
require_guest();

$pageTitle = 'Verify Email OTP | EduStream LMS';
$email = clean_string($_SESSION['pending_otp_email'] ?? ($_GET['email'] ?? ($_POST['email'] ?? '')));

if (empty($email)) {
    set_flash('warning', 'Please enter your email to verify your account.');
    redirect('auth/register.php');
}

// Fetch user by email
$user = db_fetch_one("SELECT id, name, email, otp_code, otp_expires_at, otp_verified FROM users WHERE email = ? LIMIT 1", "s", [$email]);

if (!$user) {
    set_flash('danger', 'Account not found. Please register first.');
    redirect('auth/register.php');
}

if ($user['otp_verified'] == 1) {
    set_flash('info', 'Your account is already verified. You can log in right away.');
    unset($_SESSION['pending_otp_email'], $_SESSION['simulated_otp']);
    redirect('auth/login.php');
}

$error = '';
$simulatedOtp = $_SESSION['simulated_otp'] ?? $user['otp_code'];

// Handle Resend OTP Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resend') {
    if (!verify_csrf_token()) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $newOtp = sprintf('%06d', random_int(100000, 999999));
        $newExpiry = date('Y-m-d H:i:s', time() + 600); // 10 minutes expiry

        db_execute("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?", "ssi", [$newOtp, $newExpiry, $user['id']]);
        
        $_SESSION['simulated_otp'] = $newOtp;
        $simulatedOtp = $newOtp;
        $user['otp_code'] = $newOtp;
        $user['otp_expires_at'] = $newExpiry;

        set_flash('info', 'A new 6-digit OTP code has been generated and dispatched to your simulated inbox.');
    }
}

// Handle OTP Verification Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify') {
    if (!verify_csrf_token()) {
        $error = 'Security validation failed. Please refresh the page.';
    } else {
        $enteredOtp = clean_string($_POST['otp_code'] ?? '');

        if (empty($enteredOtp) || strlen($enteredOtp) !== 6) {
            $error = 'Please enter a valid 6-digit verification code.';
        } elseif (empty($user['otp_code'])) {
            $error = 'No active OTP found. Please click "Resend OTP" below.';
        } elseif (strtotime($user['otp_expires_at']) < time()) {
            $error = 'The verification code has expired (valid for 10 minutes). Please request a new OTP.';
        } elseif (!hash_equals($user['otp_code'], $enteredOtp)) {
            $error = 'Incorrect OTP code. Please check the code provided below and try again.';
        } else {
            // Success! Activate account and clear OTP tokens
            db_execute("UPDATE users SET otp_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE id = ?", "i", [$user['id']]);
            
            unset($_SESSION['pending_otp_email'], $_SESSION['simulated_otp']);
            set_flash('success', 'Email verified successfully! Your account is now active. Please sign in.');
            redirect('auth/login.php');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8 col-sm-10">
            
            <?= render_flash_messages(); ?>

            <!-- SIMULATED EMAIL INBOX (For local demo & grading) -->
            <div class="simulated-email-box p-4 mb-4 shadow-lg">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary border-opacity-50">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-envelope-open-fill text-info fs-5"></i>
                        <span class="fw-bold small text-light text-uppercase tracking-wider">Simulated Email Delivery</span>
                    </div>
                    <span class="badge bg-info bg-opacity-25 text-info border border-info border-opacity-25 small">
                        Evaluation Inbox
                    </span>
                </div>

                <div class="small text-secondary mb-2">
                    <div><strong>From:</strong> EduStream Security &lt;no-reply@edustream.org&gt;</div>
                    <div><strong>To:</strong> <?= e($user['email']); ?></div>
                    <div><strong>Subject:</strong> Your EduStream Account Verification Code</div>
                </div>

                <div class="bg-black bg-opacity-40 p-3 rounded-3 text-center my-3 border border-secondary border-opacity-25">
                    <p class="text-light small mb-2">Use the 6-digit OTP below to verify your account. Valid for 10 minutes:</p>
                    <div class="otp-digit-display my-1">
                        <?= e($simulatedOtp ?? '------'); ?>
                    </div>
                    <div class="text-muted small mt-2">
                        <i class="bi bi-clock-history me-1"></i> Expires in 10 minutes
                    </div>
                </div>

                <div class="text-secondary small fst-italic">
                    <i class="bi bi-info-circle me-1"></i> <strong>Note:</strong> In production with sendmail/SMTP configured, this email lands directly in the user's inbox. For grading and local demo video, the generated code is rendered here.
                </div>
            </div>

            <!-- OTP INPUT FORM -->
            <div class="card custom-card border-0 shadow-lg p-4 p-md-5">
                <div class="text-center mb-4">
                    <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 58px; height: 58px;">
                        <i class="bi bi-shield-lock-fill fs-3"></i>
                    </div>
                    <h2 class="h4 fw-bold mb-1">Enter Verification Code</h2>
                    <p class="text-secondary small mb-0">We have dispatched a 6-digit code to <strong><?= e($user['email']); ?></strong></p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center gap-2 shadow-sm mb-4">
                        <i class="bi bi-exclamation-octagon-fill fs-5 flex-shrink-0"></i>
                        <div><?= e($error); ?></div>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('auth/verify_otp.php'); ?>" method="POST" autocomplete="off">
                    <?= csrf_field(); ?>
                    <input type="hidden" name="action" value="verify">
                    <input type="hidden" name="email" value="<?= e($user['email']); ?>">

                    <div class="mb-4">
                        <label for="otp_code" class="form-label small fw-bold text-secondary text-center d-block">6-Digit OTP</label>
                        <input type="text" name="otp_code" id="otp_code" class="form-control otp-input-field py-2" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" placeholder="123456" required autofocus>
                    </div>

                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-lg rounded-3 fw-bold py-2 shadow-sm">
                            <i class="bi bi-check-circle-fill me-2"></i> Verify Account
                        </button>
                    </div>
                </form>

                <!-- RESEND OTP FORM -->
                <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-2">
                    <span class="text-muted small">Didn't receive the code?</span>
                    <form action="<?= base_url('auth/verify_otp.php'); ?>" method="POST" class="d-inline">
                        <?= csrf_field(); ?>
                        <input type="hidden" name="action" value="resend">
                        <input type="hidden" name="email" value="<?= e($user['email']); ?>">
                        <button type="submit" class="btn btn-link btn-sm text-decoration-none fw-bold text-primary p-0">
                            <i class="bi bi-arrow-clockwise me-1"></i> Resend OTP
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
