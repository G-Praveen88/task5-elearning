# EduStream LMS — Full-Stack E-Learning Portal
### Capstone Project (Task 5) — Full Stack Web Development Internship (PHP / MySQL Track)
**Organization:** ApexPlanet Software Pvt. Ltd.  
**Author / Developer:** G. Praveen  
**Database:** MySQL / MariaDB (`elearning_db`)  
**Backend:** PHP 8.x (Native MySQLi with 100% Prepared Statements)  
**Frontend:** Bootstrap 5.3, Bootstrap Icons, Custom CSS, Vanilla JavaScript (Fetch API / AJAX), Chart.js  
**Hosting Compatibility:** Local (XAMPP) & Live Cloud / Shared Hosting (InfinityFree, 000webhost)

---

## 1. Project Overview
**EduStream LMS** is a modern, responsive, and secure Learning Management System designed for scalable online education. It delivers a complete end-to-end learning lifecycle:
- **Prospective Students** explore interactive course curriculums with real-time AJAX keyword search and category filters.
- **Learners** create accounts protected by cryptographically secure 6-digit Email OTP verification, enroll in courses, study video lectures and markdown notes, and track progress lesson-by-lesson in real time.
- **Instructors & Administrators** manage course catalogs, upload validated thumbnails, sequence lesson modules, monitor student enrollment rosters, and analyze engagement trends via interactive Chart.js dashboards.

---

## 2. Key Architecture & Features

### 2.1 Security & Code Hardening (100% Compliant)
- **100% Parameterized Prepared Statements:** All database queries utilize `mysqli_prepare()` and `bind_param()` via centralized helpers (`db_fetch_all`, `db_fetch_one`, `db_execute`). Raw SQL string interpolation is eliminated.
- **Anti-CSRF Protection:** Cryptographically secure CSRF tokens (`random_bytes(32)`) embedded in all POST forms and validated using timing-attack safe `hash_equals()`.
- **Bcrypt Password Encryption:** Passwords hashed with `PASSWORD_BCRYPT` (cost factor 12).
- **Email OTP Verification Lifecycle:**
  - 6-digit numeric OTP generated on registration (`random_int(100000, 999999)`).
  - Strictly expires after 10 minutes (`otp_expires_at`).
  - Single-use validation; cleared upon verification.
  - Features an on-screen **Simulated Email Inbox** preview banner for environments without an active SMTP server (local XAMPP and free hosting).
- **Session Hijacking & Fixation Defense:** `session_regenerate_id(true)` executed upon successful authentication.
- **XSS Sanitization:** Global escaping helper `e()` wraps all dynamic outputs with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- **Hardened File Uploads:** Validates file size (max 2MB), extension whitelist (`.jpg`, `.jpeg`, `.png`, `.webp`), and inspects binary MIME types via `finfo`.

### 2.2 Student & Learning Experience
- **Public Showcase (`index.php`):** Hero section, platform statistics counters, featured courses, and category explore cards.
- **Course Catalog (`courses/list.php`):**
  - Instant, debounced AJAX keyword search across course titles, descriptions, and instructors (no full page reload).
  - Interactive category filter tabs (*Web Development*, *Data Science*, *Cyber Security*, *Cloud Computing*, *UI/UX Design*).
  - Responsive pagination controls.
  - High-resolution dynamic category SVG covers for courses without custom images.
- **Course Syllabus & Details (`courses/view.php`):**
  - Course overview, instructor profile, and complete curriculum syllabus.
  - Dynamic enrollment CTA (adapts for guests, enrolled students, and non-enrolled students).
- **Enrollment Processor (`courses/enroll.php`):**
  - CSRF-protected POST enrollment handler.
  - Initializes student progress at 0% and automatically routes learner to Lesson 1.
- **Learning Interface (`lessons/view.php`):**
  - Split-screen distraction-free LMS learning view.
  - Responsive 16:9 video player supporting YouTube embeds and HTML5 video.
  - Markdown lesson notes parser (code blocks, inline code, headers, lists).
  - Previous / Next lesson sequential navigation.
  - Curriculum syllabus sidebar with active lesson highlight and completion checkmarks.
  - **Real-Time AJAX Completion Toggle:** "Mark as Complete" button triggers asynchronous state update in `lesson_completions`, recalculates `progress_percent`, and updates UI meters in real time without refreshing the page.
- **Student Dashboard (`dashboard.php`):**
  - Learning KPI summary tiles (*Enrolled Courses*, *In Progress*, *Completed Certifications*, *Lessons Finished*).
  - Enrolled course cards with dynamic Bootstrap progress bars and "Continue Learning" links.

### 2.3 Admin & Instructor Management Suite (`admin/`)
- **Executive Overview (`admin/index.php`):**
  - Real-time KPI summary widgets (*Total Students*, *Total Courses*, *Lifetime Enrollments*, *7-Day Growth*, *Platform Tuition Revenue*).
  - Live student enrollment stream feed.
  - Popular courses ranking with average progress metrics.
- **Course Management CRUD (`admin/courses.php`):**
  - Full CRUD operations with Bootstrap modals.
  - Thumbnail image upload handler with file inspection and automatic cleanup of replaced files.
- **Curriculum Lesson Sequence Manager (`admin/lessons.php`):**
  - Course curriculum switcher dropdown.
  - Manage lesson titles, markdown lecture notes, video URLs, and order indices.
  - Automated progress recalculation across all active enrollments when lessons are pruned.
- **Student Progress Roster (`admin/students.php`):**
  - Filterable by course curriculum and searchable by student name/email.
  - Shows completion percentage, completed lesson counts, and status badges.
- **Visual Intelligence & Analytics (`admin/analytics.php`):**
  - **Daily Enrollments Trendline (Line Chart)**: Smooth 14-day trajectory using Chart.js.
  - **Popularity Ranking (Bar Chart)**: Most enrolled curriculums.
  - **Domain Category Distribution (Doughnut Chart)**: Course share across domains.
  - Detailed Course Performance & Revenue Matrix table.

---

## 3. Directory Structure
```
task5-elearning/
├── REQUIREMENTS.md                     # Complete SRS documentation & use-cases
├── ER_DIAGRAM.md                       # Mermaid ER diagram & data dictionary
├── schema.sql                          # MySQL schema + foreign keys + seed records
├── README.md                           # Setup, deployment & live hosting guide
├── PROJECT_REPORT_GUIDE.md             # Project report outline & 12-min video script
├── index.php                           # Homepage & curriculum showcase
├── dashboard.php                       # Student learning dashboard & progress meters
├── config/
│   └── db_connect.php                  # Centralized MySQLi connection & prepared query helpers
├── includes/
│   ├── helpers.php                     # Global security, CSRF, flash messaging, SVG generator
│   ├── auth_check.php                  # Session guards (require_login, require_admin, require_guest)
│   ├── header.php                      # Bootstrap 5 head & global fonts
│   ├── navbar.php                      # Dynamic role-based navigation
│   └── footer.php                      # Layout footer & script bundles
├── auth/
│   ├── register.php                    # Registration with bcrypt & 6-digit OTP generation
│   ├── verify_otp.php                  # OTP verification with simulated inbox preview
│   ├── login.php                       # Prepared statement login & role-based routing
│   └── logout.php                      # Session destruction & cookie cleanup
├── courses/
│   ├── list.php                        # Real-time AJAX search, category filters, pagination
│   ├── view.php                        # Course syllabus & enrollment action
│   └── enroll.php                      # CSRF-protected enrollment processor
├── lessons/
│   └── view.php                        # LMS learning player, video embed & AJAX progress toggle
├── admin/
│   ├── index.php                       # Executive KPI dashboard & activity stream
│   ├── courses.php                     # Course CRUD & thumbnail upload validation
│   ├── lessons.php                     # Lesson sequence manager & progress sync
│   ├── students.php                    # Student roster & progress tracking
│   └── analytics.php                   # Chart.js analytics (line, bar, doughnut)
├── css/
│   └── style.css                       # Modern UI theme, badges & responsive tokens
├── js/
│   └── main.js                         # Alert dismissers, password toggles
├── uploads/
│   └── .gitkeep                        # Course thumbnails storage
└── wireframes/
    └── README.md                       # Figma/Canva wireframe design blueprints
```

---

## 4. Local Setup Instructions (XAMPP)

### Step 1: Clone / Place the Project in XAMPP `htdocs`
Ensure the project folder is located at:
`C:\xampp\htdocs\task5-elearning`

### Step 2: Start Apache & MySQL Services
1. Open the **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.

### Step 3: Import Database Schema
1. Open phpMyAdmin in your browser: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/).
2. Click on the **Import** tab.
3. Choose the `schema.sql` file located in `C:\xampp\htdocs\task5-elearning\schema.sql`.
4. Click **Go** to execute the script.
   *(Alternatively, run via command line: `mysql -u root < schema.sql`)*.
5. This creates the database `elearning_db` with all 5 tables and rich seed data.

### Step 4: Open in Web Browser
Navigate to:
[http://localhost/task5-elearning/](http://localhost/task5-elearning/)

---

## 5. Demo Credentials

For quick evaluation and demonstration, the login page includes **1-Click Quick Demo Login** buttons:

| Role | Email | Password | Pre-Verified? | Access Scope |
| :--- | :--- | :--- | :--- | :--- |
| **Lead Administrator** | `admin@elearn.test` | `Admin@123` | Yes | Full Admin & Instructor Console (`admin/`) |
| **Course Instructor** | `instructor@elearn.test` | `Admin@123` | Yes | Full Admin & Instructor Console (`admin/`) |
| **Student** | `student@elearn.test` | `Student@123` | Yes | Student Dashboard (`dashboard.php`) |
| **Student (Jane)** | `jane@elearn.test` | `Student@123` | Yes | Student Dashboard (`dashboard.php`) |

---

## 6. Live Deployment Guide (InfinityFree / 000webhost)

### Step 1: Create a Free Hosting Account
1. Sign up for a free web hosting account on **InfinityFree** ([infinityfree.com](https://www.infinityfree.com/)) or **000webhost** ([000webhost.com](https://www.000webhost.com/)).
2. Create a new website/account and note your:
   - Website URL (e.g., `https://edustream-lms.epizy.com` or `https://edustream-lms.000webhostapp.com`)
   - FTP Hostname, Username, and Password
   - MySQL Hostname (e.g., `sqlXXX.infinityfree.com`), Database Name, Username, and Password.

### Step 2: Create and Import the MySQL Database
1. Open the **phpMyAdmin** link from your hosting control panel (cPanel).
2. Click on the database assigned to your account.
3. Import the `schema.sql` file.

### Step 3: Configure Database Credentials
In `config/db_connect.php`, update the credentials or set environment variables in your hosting panel:
```php
if (!defined('DB_HOST')) define('DB_HOST', 'sqlXXX.infinityfree.com'); // Your live host
if (!defined('DB_PORT')) define('DB_PORT', 3306);
if (!defined('DB_NAME')) define('DB_NAME', 'epiz_xxxx_elearning_db'); // Your live DB name
if (!defined('DB_USER')) define('DB_USER', 'epiz_xxxx');              // Your live DB user
if (!defined('DB_PASS')) define('DB_PASS', 'YourLivePasswordHere');   // Your live DB password
```

### Step 4: Upload Project Files via FTP (FileZilla)
1. Download and launch **FileZilla Client**.
2. Connect using your FTP credentials.
3. Navigate to the remote document root:
   - For InfinityFree: `htdocs/`
   - For 000webhost: `public_html/`
4. Upload all files and folders from `task5-elearning/` into this remote directory.
5. Ensure the `uploads/` directory has write permissions (`chmod 755` or `777`).

### Step 5: Test Live Deployment
1. Visit your live domain in your browser.
2. Test user registration with OTP, browse course catalogs, test course enrollment, and verify admin access.
