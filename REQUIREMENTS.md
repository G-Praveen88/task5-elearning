# Software Requirements Specification (SRS)
## Capstone Project: E-Learning Portal (LMS)
**Program:** Full Stack Web Development Internship (PHP / MySQL Track) — Task 5 (Capstone Project)  
**Author:** G. Praveen  
**Technology Stack:** PHP 8.x, MySQL 8.x / MariaDB, Bootstrap 5.3, JavaScript (Fetch / AJAX), Chart.js, Apache / XAMPP  
**Deployment Target:** Live Web Hosting (InfinityFree / 000webhost) & Local Development (XAMPP)  

---

## 1. Project Overview & Vision
The **E-Learning Portal** is a modern, responsive, and secure Learning Management System (LMS) designed to facilitate seamless online course discovery, enrollment, student progress tracking, instructor course delivery, and administrative analytics.

The portal provides an end-to-end learning lifecycle:
1. **Prospective Students** explore interactive course catalogs with instant AJAX search and category filtering.
2. **Students** register with secure email OTP verification, enroll in courses, access high-definition video lessons and markdown tutorials, track their progress in real-time, and view their certificates/learning dashboard.
3. **Instructors / Administrators** manage courses, structure lessons with multimedia attachments, monitor student engagement and completion rates, and analyze enrollment trends through visual Chart.js dashboards.

---

## 2. User Roles & Personas

### 2.1 Student (`role = 'student'`)
- **Primary Goal:** Discover courses, enroll, consume educational lessons, and track personal learning milestones.
- **Characteristics:** Requires a distraction-free interface, mobile accessibility, progress visibility, and clear lesson navigation.

### 2.2 Instructor / Administrator (`role = 'admin'` or `'instructor'`)
- **Primary Goal:** Author and curate course curricula, manage multimedia lessons, monitor student engagement and completion rates, and review platform performance analytics.
- **Characteristics:** Requires administrative access controls, tabular student records, file upload validators, and actionable statistical charts.

---

## 3. Core Functional Requirements

### 3.1 Authentication & Authorization Module
- **User Registration:** Students sign up with Full Name, unique Email address, and secure Password.
- **Email OTP Verification:**
  - On registration, the platform generates a cryptographically secure 6-digit numeric OTP.
  - OTP is stored in the database alongside a 10-minute expiry timestamp (`otp_expires_at`).
  - To accommodate environments without dedicated SMTP gateways (local XAMPP and free hosting), the system features a simulated email delivery modal/alert directly on the verification view, with explicit developer annotations.
  - Account status remains unverified (`otp_verified = 0`) until the correct unexpired OTP is submitted.
  - Resend OTP mechanism with rate-limiting and timestamp renewal.
- **Secure Authentication & Session Management:**
  - Login authentication using prepared SQL queries and PHP `password_verify()`.
  - Session fixation protection using `session_regenerate_id(true)` upon successful authentication.
  - Role-based routing: Admins/Instructors are routed to the Admin Dashboard (`admin/index.php`), while Students are routed to their personal Dashboard (`dashboard.php`).
  - Secure Logout with complete session destruction, cookie cleanup, and redirection.

### 3.2 Course Catalog & Discovery Module
- **Course Catalog View (`courses/list.php`):**
  - Grid display of all published courses featuring dynamic cover art, instructor badge, category tag, lesson count, and pricing.
  - **Real-Time AJAX Search:** Keyword search across course titles, descriptions, and instructors without full page reload.
  - **Category Filtering:** Interactive filter tabs (Web Development, Data Science, Cyber Security, Cloud Computing, UI/UX Design, etc.).
  - **Pagination:** Clean, responsive pagination for scalable course catalogs.
- **Course Detail & Syllabus (`courses/view.php`):**
  - Course overview, instructor details, learning objectives, and structured syllabus breakdown.
  - Dynamic Call-to-Action:
    - Guest: "Log in to Enroll".
    - Logged-in Non-Enrolled: "Enroll in Course" (triggers enrollment).
    - Enrolled Student: "Continue Learning" (navigates directly to active lesson).

### 3.3 Enrollment & Learning Engine
- **Course Enrollment (`courses/enroll.php`):**
  - CSRF-protected POST enrollment handler.
  - Duplicate enrollment prevention (unique constraint on `user_id` + `course_id`).
  - Automatically initializes student progress at 0% and redirects to the introductory lesson.
- **Learning Interface (`lessons/view.php`):**
  - Split-screen, distraction-free LMS layout:
    - **Main Area:** Lesson title, responsive video player (YouTube/Vimeo embed or HTML5 player with fallback), comprehensive markdown/HTML text notes, and Next/Previous navigation buttons.
    - **Curriculum Sidebar:** List of all course lessons, order sequence, active lesson indicator, and completion status checkmarks.
  - **Real-time Lesson Completion:**
    - "Mark as Complete" / "Completed" toggle button powered by asynchronous AJAX.
    - Dynamically updates database records in `lesson_completions` and recalculates `progress_percent` in `enrollments`.
    - Updates sidebar checkmarks and progress bar without refreshing the page.

### 3.4 Student Dashboard (`dashboard.php`)
- Overview of all enrolled courses with dynamic Bootstrap progress meters.
- Quick statistical summary: Total Enrolled, Courses in Progress, and Completed Courses (100% progress).
- Quick links to jump directly into the active lesson of any enrolled course.

### 3.5 Instructor & Administrative Panel (`admin/`)
- **Executive Dashboard (`admin/index.php`):**
  - Real-time statistics: Total Registered Students, Total Active Courses, Total Enrollments, Weekly Enrollment Growth, Platform Revenue.
  - Recent student enrollment activity stream.
- **Course Management CRUD (`admin/courses.php`):**
  - Complete Create, Read, Update, and Delete capabilities for courses.
  - Course thumbnail upload handler with MIME validation, file size restrictions, and automatic fallback SVG generation.
  - Instructor attribution, category assignment, and pricing control.
- **Lesson Curriculum Management (`admin/lessons.php`):**
  - Create, edit, delete, and reorder lessons for any designated course.
  - Support for rich lecture notes and external video URLs.
- **Student Progress Monitoring (`admin/students.php`):**
  - Tabular roster of enrolled students with real-time completion percentages and enrollment dates.
  - Filterable by course and searchable by student name/email.
- **Analytics & Reporting (`admin/analytics.php`):**
  - Visual charts rendered via Chart.js:
    - Daily enrollment trajectory over the last 14–30 days (Line Chart).
    - Course popularity ranking by enrollment volume (Bar Chart).
    - Category distribution breakdown (Doughnut Chart).

---

## 4. Use Case Specifications

### Use Case 1: Student Course Enrollment & Progress Tracking
- **Actor:** Student
- **Preconditions:** Registered and OTP-verified account; logged in.
- **Trigger:** Student clicks "Enroll Now" on a course details page.
- **Main Flow:**
  1. System checks CSRF token and validates student session.
  2. System creates record in `enrollments` table with `progress_percent = 0`.
  3. System queries the first lesson of the course by `order_index`.
  4. System redirects student to `lessons/view.php?course_id={id}&lesson_id={id}`.
  5. Student watches video, reads lecture notes, and clicks "Mark as Complete".
  6. Asynchronous AJAX request records completion in `lesson_completions`.
  7. System recalculates `(completed_lessons / total_lessons) * 100` and updates `enrollments.progress_percent`.
  8. System returns JSON response; UI progress bar and syllabus checkmark update immediately.

### Use Case 2: Instructor Course & Curriculum Management
- **Actor:** Admin / Instructor
- **Preconditions:** Logged in with `role IN ('admin', 'instructor')`.
- **Trigger:** Instructor accesses `admin/courses.php` or `admin/lessons.php`.
- **Main Flow:**
  1. Instructor creates a new course with title, category, price, and thumbnail.
  2. System validates input, sanitizes data, saves thumbnail to `uploads/`, and inserts course.
  3. Instructor clicks "Manage Lessons" to add lessons with titles, markdown content, video links, and sequence indices.
  4. System updates syllabus order and publishes changes immediately to the course catalog.

---

## 5. Security & Non-Functional Requirements

| Category | Implementation Standard |
| :--- | :--- |
| **SQL Injection Defense** | 100% Prepared Statements using `mysqli_prepare` / parameter binding (`bind_param`). No raw SQL string concatenation. |
| **Cross-Site Request Forgery (CSRF)** | Cryptographically random tokens (`random_bytes(32)`) embedded in all POST forms and validated via `hash_equals()`. |
| **Cross-Site Scripting (XSS)** | Global escaping helper `e()` utilizing `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` for all dynamic outputs. |
| **Password Security** | Passwords hashed using industry-standard `PASSWORD_BCRYPT` with cost factor 12. |
| **OTP Expiry & Single-Use** | OTPs are randomly generated 6-digit integers, expire strictly after 10 minutes, and are erased upon successful verification. |
| **Session Protection** | Sessions regenerate IDs on privilege transition (`session_regenerate_id(true)`) to prevent fixation. |
| **File Upload Hardening** | File extension whitelist (`.jpg`, `.jpeg`, `.png`, `.webp`), MIME type verification via `finfo`, file size cap (2MB), and randomized file naming. |
| **Responsive UI** | Mobile-first architecture built on Bootstrap 5.3, optimized for smartphones, tablets, and desktop displays. |
