# Capstone Project Report & 12-Minute Presentation Video Guide
## E-Learning Portal (Course Enrollment & Learning Management System)
**Internship Program:** Full Stack Web Development Internship (PHP / MySQL Track) — Task 5 (Capstone Project)  
**Author / Presenter:** G. Praveen  
**Company:** ApexPlanet Software Pvt. Ltd.  

---

# PART 1: PROJECT REPORT PDF STRUCTURE & CONTENT GUIDE

Use this outline to assemble your formal Capstone Project Report PDF. Insert actual screenshots of the application at the indicated places.

---

### 1. Title Page & Certificate
- **Project Title:** EduStream LMS — Full-Stack E-Learning Portal
- **Subtitle:** Capstone Project Report Submitted in Partial Fulfillment of the Full Stack Web Development Internship
- **Student Name:** G. Praveen
- **Organization:** ApexPlanet Software Pvt. Ltd.
- **Track:** PHP & MySQL Backend Engineering

---

### 2. Executive Summary & Problem Statement
- **Executive Summary:** Summarizes the design, implementation, and deployment of a modern Learning Management System (LMS) with email OTP verification, real-time AJAX search, interactive video lesson delivery, student progress tracking, course CRUD, and visual Chart.js analytics.
- **Problem Statement:** Traditional educational portals are often clunky, monolithic, and lack real-time feedback. EduStream LMS solves this by providing:
  - Frictionless student onboarding via secure OTP verification.
  - Zero-reload course discovery with debounced AJAX searching and category filters.
  - Granular lesson completion tracking with real-time progress recalculation.
  - Executive administrative analytics for curriculum management and student retention.

---

### 3. System Architecture & Relational Database Design
- **Architecture Diagram:** Client-Server architecture interacting over HTTP/HTTPS with Apache, PHP 8 backend, and MySQL 8 relational database.
- **ER Diagram:** Include the visual Mermaid diagram from [`ER_DIAGRAM.md`](file:///c:/Users/prave/OneDrive/Desktop/hackwave/task5-elearning/ER_DIAGRAM.md).
- **Data Dictionary:**
  - `users`: User identity, Bcrypt hash, roles (`student`, `instructor`, `admin`), OTP code, 10-minute expiry timestamp, and verification flag.
  - `courses`: Instructional courses, categories, tuition pricing, foreign key to instructor, and thumbnails.
  - `lessons`: Sequential learning modules with markdown content, video URLs, and order indices.
  - `enrollments`: Course signups with unique composite constraint `(user_id, course_id)` and progress percentage (0–100%).
  - `lesson_completions`: Granular per-lesson completion records with composite constraint `(user_id, lesson_id)`.

---

### 4. Key Functional Modules & Implementation Details
*(Include application screenshots in each section)*

#### 4.1 Authentication with Email OTP Verification
- **Screenshot 1:** User Registration Form (`auth/register.php`).
- **Screenshot 2:** Simulated Email Delivery Preview & 6-Digit OTP Verification Form (`auth/verify_otp.php`).
- **Screenshot 3:** Sign-In Page with 1-Click Demo Logins (`auth/login.php`).
- **Technical Explanation:** Password hashing using `PASSWORD_BCRYPT` (cost 12), random 6-digit OTP generation (`random_int(100000, 999999)`), 10-minute expiry validation, and `session_regenerate_id(true)` to defeat session fixation.

#### 4.2 Student Course Catalog & Real-Time AJAX Search
- **Screenshot 4:** Public Homepage Hero & Live Platform Metrics (`index.php`).
- **Screenshot 5:** Course Catalog with Filter Pills & Debounced Search (`courses/list.php`).
- **Screenshot 6:** Detailed Course Overview & Curriculum Syllabus (`courses/view.php`).
- **Technical Explanation:** Client-side JavaScript sends asynchronous `fetch()` requests to `courses/list.php?ajax=1&q=...&category=...` without reloading the browser. Backend returns JSON payload containing rendered card HTML and pagination controls.

#### 4.3 Interactive LMS Learning Player & Real-Time Completion
- **Screenshot 7:** Student Learning Dashboard with Progress Bars (`dashboard.php`).
- **Screenshot 8:** Learning Player with 16:9 Video Embed & Syllabus Sidebar (`lessons/view.php`).
- **Technical Explanation:** Two-column responsive layout. Asynchronous completion toggle button sends POST with CSRF token to `lessons/view.php`. The backend recalculates `progress_percent = (completed / total) * 100` and updates `enrollments` table. The DOM updates checkmarks and progress meters in real time.

#### 4.4 Admin & Instructor Management Console
- **Screenshot 9:** Executive Admin Dashboard with 5 KPI Cards & Activity Feed (`admin/index.php`).
- **Screenshot 10:** Course CRUD Management & Thumbnail Upload Modal (`admin/courses.php`).
- **Screenshot 11:** Course Lesson Sequence Manager (`admin/lessons.php`).
- **Screenshot 12:** Enrolled Students Progress Roster (`admin/students.php`).
- **Screenshot 13:** Visual Analytics Dashboard with Chart.js Charts (`admin/analytics.php`).

---

### 5. Security & Code Hardening Standards
- **100% Prepared Statements:** Eliminates SQL Injection.
- **CSRF Token Verification:** Validated on all state-changing POST requests using `hash_equals()`.
- **XSS Prevention:** Output escaping using `htmlspecialchars()` via helper `e()`.
- **Role Guards:** `require_admin()` and `require_login()` middleware protecting sensitive routes.
- **Hardened Uploads:** Extension whitelist, MIME type inspection with `finfo`, and file size capping.

---

### 6. Live Deployment & Testing
- **Hosting Platform:** InfinityFree / 000webhost.
- **Live URL:** Provide your live website link.
- **GitHub Repository:** Provide your repository URL.
- **Testing Matrix:** Table displaying all test scenarios (Registration, OTP expiration, Login, AJAX search, Enrollment, Lesson toggle, Course CRUD, Analytics rendering) and their PASS results.

---

# PART 2: 12-MINUTE FINAL PRESENTATION VIDEO SCRIPT

This step-by-step presentation script is timed to exactly **12 minutes** for your video recording. Follow this script while sharing your screen.

---

### ⏱️ [0:00 - 1:30] — Introduction & Project Vision (1.5 Minutes)
- **On Screen:** Title slide showing project name, your name, and ApexPlanet internship capstone banner. Then switch to the live homepage [http://localhost/task5-elearning/](http://localhost/task5-elearning/).
- **Spoken Script:**
  > "Hello everyone, my name is G. Praveen. Today, I am excited to present my Capstone Project for the Full Stack Web Development Internship at ApexPlanet Software Pvt. Ltd.
  > 
  > The project is **EduStream LMS** — a full-stack, enterprise-grade E-Learning Portal and Learning Management System engineered using PHP 8 and MySQL.
  > 
  > In today's digital learning landscape, online portals often struggle with clunky interfaces, full-page reloads, and lack of real-time feedback. In this capstone project, I set out to build a modern, high-performance platform that provides a frictionless experience for students and powerful curriculum tools for instructors and administrators.
  > 
  > Over the next 10 minutes, I will walk you through the relational database architecture, our secure email OTP authentication flow, the student learning experience with real-time AJAX progress tracking, and the administrative suite powered by Chart.js analytics."

---

### ⏱️ [1:30 - 3:00] — Database Schema & ER Diagram (1.5 Minutes)
- **On Screen:** Open `ER_DIAGRAM.md` or display the Mermaid diagram on screen. Then briefly show phpMyAdmin showing `elearning_db` with all 5 tables.
- **Spoken Script:**
  > "Let's examine the relational database design underpinning EduStream. The database is called `elearning_db` and is built on the InnoDB engine with `utf8mb4` character encoding.
  > 
  > We have 5 core relational tables:
  > 1. `users`: Stores user credentials, roles (`student`, `instructor`, `admin`), password hashes, and our 6-digit OTP code with a 10-minute expiry timestamp.
  > 2. `courses`: Holds course metadata, tuition pricing, category indexes, and foreign keys referencing the instructor.
  > 3. `lessons`: Stores curriculum modules with markdown lecture notes, video embed URLs, and sequential order indices.
  > 4. `enrollments`: Tracks student signups with a composite unique constraint on `user_id` and `course_id` to prevent duplicate enrollments, along with a computed `progress_percent` column.
  > 5. `lesson_completions`: Provides granular, idempotent tracking of which individual lessons each student has completed.
  > 
  > Foreign key constraints with `ON DELETE CASCADE` ensure referential integrity, so if a course or instructor is removed, child records are automatically cleaned up without leaving orphaned data."

---

### ⏱️ [3:00 - 5:00] — Authentication & Email OTP Verification Flow (2.0 Minutes)
- **On Screen:** Navigate to `auth/register.php`. Fill in a new student name and email (e.g. `alex.demo@elearn.test`), enter password, and click **Register & Send OTP**.
- **Spoken Script:**
  > "Now, let's look at our authentication system. Security is built in at every layer:
  > - Passwords are encrypted using PHP's industry-standard `password_hash()` with the `PASSWORD_BCRYPT` algorithm and cost factor 12.
  > - All forms are protected by anti-CSRF tokens generated via `random_bytes(32)`.
  > 
  > When a new user registers, the platform generates a cryptographically secure 6-digit numeric OTP using `random_int(100000, 999999)` and stores an expiry timestamp set strictly 10 minutes in the future.
  > 
  > *(Point to the on-screen simulated inbox banner on `auth/verify_otp.php`)*
  > In a production environment with an active SMTP gateway, this code is dispatched to the user's inbox. For local evaluation and our grading demonstration, the system features a simulated email delivery preview directly on the screen.
  > 
  > Notice that our account status remains unverified (`otp_verified = 0`) until the correct code is supplied. Let's enter the 6-digit code and click **Verify Account**.
  > 
  > As you can see, our account is activated, the OTP token is destroyed to enforce single-use, and we are redirected to sign in. On login, the system executes `session_regenerate_id(true)` to prevent session fixation attacks, and routes users based on their role."

---

### ⏱️ [5:00 - 7:30] — Course Discovery, AJAX Search & Real-Time Learning Engine (2.5 Minutes)
- **On Screen:** Open `courses/list.php`. Type in the search box (e.g., "PHP", "Security") and watch cards filter instantly. Click category pills (*Data Science*, *Cloud Computing*). Then click on a course, show `courses/view.php`, click **Enroll**, and show `lessons/view.php`.
- **Spoken Script:**
  > "Now let's explore the student learning experience.
  > 
  > On the course catalog page (`courses/list.php`), we have implemented **real-time AJAX search and category filtering**. Notice that as I type keywords like 'Python' or 'Security', the course cards update instantly with zero page reload. When I toggle category pills, a debounced asynchronous `fetch` request fetches matching curriculums and updates both the card grid and pagination controls seamlessly.
  > 
  > For courses without custom uploaded covers, our custom SVG engine generates dynamic high-resolution gradient banners based on the category.
  > 
  > Now, let's open **Full Stack Web Development with PHP & MySQL**. The course view displays the instructor profile, full syllabus, and an enrollment action card. Let's click **Enroll in Course**.
  > 
  > The system validates our CSRF token, creates an enrollment record at 0% progress, and immediately routes us into the interactive learning interface (`lessons/view.php`).
  > 
  > Here we have a distraction-free two-column layout:
  > - On the left: A responsive 16:9 video player with embedded HD video, previous/next navigation buttons, and rich markdown lecture notes.
  > - On the right: A collapsible curriculum syllabus with order numbers, active lesson indicators, and completion checkmarks.
  > 
  > *(Click the 'Mark as Complete' button)*
  > Watch closely: When I click **Mark as Complete**, an asynchronous AJAX POST request sends the lesson ID and CSRF token to the server. The backend records completion in `lesson_completions`, recalculates our overall progress percentage, and updates the database.
  > 
  > In real time, without refreshing the page:
  > - The button transitions to green with a checkmark.
  > - The syllabus icon shows a green checkmark.
  > - Both the topbar and sidebar progress meters update dynamically!"

---

### ⏱️ [7:30 - 8:30] — Student Dashboard (1.0 Minute)
- **On Screen:** Click on **My Learning** / `dashboard.php`.
- **Spoken Script:**
  > "Next, let's look at the **Student Dashboard** (`dashboard.php`).
  > 
  > This provides learners with a centralized hub:
  > - At the top, we see four summary KPIs: Enrolled Courses, Courses In Progress, Completed Certifications, and Total Lessons Finished.
  > - Below is the grid of all active enrollments showing the course thumbnail, enrollment timestamp, instructor name, and dynamic Bootstrap progress meters.
  > - The **Continue Learning** button instantly navigates to the student's next unfinished lesson."

---

### ⏱️ [8:30 - 10:30] — Admin & Instructor Management Console (2.0 Minutes)
- **On Screen:** Sign out, log in using the 1-click **Admin Login** button (`admin@elearn.test`), and open `admin/index.php`. Navigate to `admin/courses.php`, `admin/lessons.php`, `admin/students.php`, and `admin/analytics.php`.
- **Spoken Script:**
  > "Now, let's switch to the **Admin and Instructor Panel**.
  > 
  > The admin section is protected by our `require_admin()` middleware. If an unauthenticated user or student attempts to access any `/admin/` route, they are securely redirected.
  > 
  > On the **Executive Overview** (`admin/index.php`), we see real-time platform metrics:
  > - Total Students, Published Courses, Lifetime Enrollments, 7-Day Velocity, and Platform Revenue.
  > - Below is a live stream of recent student enrollment activity with progress indicators.
  > 
  > Let's visit **Course Management** (`admin/courses.php`). Here, administrators have full CRUD capabilities. When creating or updating a course:
  > - The form validates title, category, pricing, and instructor attribution.
  > - The thumbnail upload handler enforces a strict extension whitelist (`.jpg`, `.jpeg`, `.png`, `.webp`), checks binary MIME types using PHP's `finfo` extension, restricts file sizes to 2MB, and generates cryptographically randomized filenames.
  > 
  > On **Lesson Management** (`admin/lessons.php`), instructors can pick any course from the dropdown to sequence, add, edit, or delete lessons. Notice that if a lesson is deleted, the platform automatically recalculates completion percentages across all enrolled students of that course.
  > 
  > On **Student Roster** (`admin/students.php`), instructors can filter enrollments by course or search by student name/email to monitor who is falling behind and who has completed the curriculum.
  > 
  > Finally, let's look at **Visual Analytics** (`admin/analytics.php`).
  > Using **Chart.js**, we render three live analytical visualizations:
  > 1. A smooth line chart displaying the daily enrollment trajectory over the past 14 days with zero-filled continuous date series.
  > 2. A bar chart ranking our top courses by enrollment volume.
  > 3. A doughnut chart displaying course distribution across specialized domains."

---

### ⏱️ [10:30 - 12:00] — Security Summary, Live Deployment & Conclusion (1.5 Minutes)
- **On Screen:** Show `README.md` and live deployment URL (or InfinityFree/000webhost cPanel/FTP). Show GitHub repository with commit history.
- **Spoken Script:**
  > "To summarize our technical implementation:
  > - **Security:** 100% prepared SQL statements, anti-CSRF protection on all forms, XSS sanitization via `e()`, Bcrypt password hashing, session fixation prevention, and 10-minute expiring single-use OTP codes.
  > - **Performance:** Real-time AJAX search and asynchronous lesson progress tracking eliminating jarring full-page refreshes.
  > - **Deployment:** The project is configured with dynamic base URL resolution and environment-variable database overrides, making it 100% portable for free hosting platforms like InfinityFree and 000webhost.
  > - **Version Control:** All code is version-controlled in our GitHub repository with clear commit histories for continuous deployment.
  > 
  > Developing EduStream LMS has given me deep hands-on experience in architecting relational database schemas, implementing defense-in-depth web security, building interactive AJAX frontends, and deploying production PHP applications.
  > 
  > Thank you to the ApexPlanet Software team for this incredible internship experience. I am now open to any questions!"

---
*End of Presentation Script*
