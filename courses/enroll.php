<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: courses/enroll.php
 * 
 * Course Enrollment Processor with CSRF Verification & Auto-Lesson Redirection
 * Author: G. Praveen
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Authentication Guard
require_login('auth/login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('courses/list.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token()) {
    set_flash('danger', 'Security token invalid or expired. Please try enrolling again.');
    redirect('courses/list.php');
}

$courseId = (int)($_POST['course_id'] ?? 0);
$userId = current_user_id();

if ($courseId <= 0) {
    set_flash('danger', 'Invalid course request.');
    redirect('courses/list.php');
}

// 2. Verify Course Exists
$course = db_fetch_one("SELECT id, title FROM courses WHERE id = ? LIMIT 1", "i", [$courseId]);
if (!$course) {
    set_flash('danger', 'The requested course does not exist.');
    redirect('courses/list.php');
}

// 3. Check for Existing Enrollment
$existing = db_fetch_one("SELECT id, progress_percent FROM enrollments WHERE user_id = ? AND course_id = ? LIMIT 1", "ii", [$userId, $courseId]);

if ($existing) {
    set_flash('info', 'You are already enrolled in ' . e($course['title']) . '. Continuing your curriculum.');
    redirect('lessons/view.php?course_id=' . $courseId);
}

// 4. Create New Enrollment Record
$insert = db_execute(
    "INSERT INTO enrollments (user_id, course_id, enrolled_at, progress_percent) VALUES (?, ?, NOW(), 0)",
    "ii",
    [$userId, $courseId]
);

if ($insert['affected_rows'] > 0) {
    set_flash('success', 'Enrollment successful! Welcome to ' . e($course['title']) . '.');

    // Find the first lesson
    $firstLesson = db_fetch_one(
        "SELECT id FROM lessons WHERE course_id = ? ORDER BY order_index ASC, id ASC LIMIT 1",
        "i",
        [$courseId]
    );

    if ($firstLesson) {
        redirect('lessons/view.php?course_id=' . $courseId . '&lesson_id=' . $firstLesson['id']);
    } else {
        redirect('dashboard.php');
    }
} else {
    set_flash('danger', 'Failed to process enrollment. Please try again.');
    redirect('courses/view.php?id=' . $courseId);
}
