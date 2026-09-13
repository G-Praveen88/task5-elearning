-- =============================================================================
-- CAPSTONE PROJECT: E-LEARNING PORTAL (LMS)
-- Database Schema & Comprehensive Seed Data
-- Track: Full Stack Web Development Internship (PHP/MySQL)
-- Author: G. Praveen
-- Database Name: elearning_db
-- =============================================================================

CREATE DATABASE IF NOT EXISTS elearning_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE elearning_db;

-- -----------------------------------------------------------------------------
-- Disable foreign key checks during schema creation
-- -----------------------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS lesson_completions;
DROP TABLE IF EXISTS enrollments;
DROP TABLE IF EXISTS lessons;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- 1. Table: users
-- -----------------------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'instructor', 'admin') NOT NULL DEFAULT 'student',
    otp_code VARCHAR(6) NULL,
    otp_expires_at DATETIME NULL,
    otp_verified TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_otp_verified (otp_verified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Table: courses
-- -----------------------------------------------------------------------------
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    instructor_id INT NOT NULL,
    thumbnail VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_courses_instructor FOREIGN KEY (instructor_id) 
        REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_category (category),
    INDEX idx_instructor (instructor_id),
    INDEX idx_created_at (created_at),
    INDEX idx_price (price)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Table: lessons
-- -----------------------------------------------------------------------------
CREATE TABLE lessons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NULL,
    video_url VARCHAR(255) NULL,
    order_index INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_lessons_course FOREIGN KEY (course_id) 
        REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_course_id (course_id),
    INDEX idx_order_index (order_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. Table: enrollments
-- -----------------------------------------------------------------------------
CREATE TABLE enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    progress_percent INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_enrollments_user FOREIGN KEY (user_id) 
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_enrollments_course FOREIGN KEY (course_id) 
        REFERENCES courses(id) ON DELETE CASCADE,
    CONSTRAINT uq_user_course UNIQUE (user_id, course_id),
    INDEX idx_user_id (user_id),
    INDEX idx_course_id (course_id),
    INDEX idx_enrolled_at (enrolled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. Table: lesson_completions
-- -----------------------------------------------------------------------------
CREATE TABLE lesson_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    lesson_id INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_completions_user FOREIGN KEY (user_id) 
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_completions_lesson FOREIGN KEY (lesson_id) 
        REFERENCES lessons(id) ON DELETE CASCADE,
    CONSTRAINT uq_user_lesson UNIQUE (user_id, lesson_id),
    INDEX idx_user_lesson (user_id, lesson_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- SEED DATA
-- =============================================================================

-- Seed Users:
-- Admin: admin@elearn.test / Admin@123
-- Instructor: instructor@elearn.test / Admin@123
-- Students: student@elearn.test, jane@elearn.test, alex@elearn.test / Student@123
INSERT INTO users (id, name, email, password_hash, role, otp_code, otp_expires_at, otp_verified, created_at) VALUES
(1, 'Lead Administrator', 'admin@elearn.test', '$2y$10$TwF4UlM6qjvCEDok.jydR.LSp1VVAb1IRGTF0EgTKqgFDwQZzU20K', 'admin', NULL, NULL, 1, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(2, 'Prof. Alan Vance', 'instructor@elearn.test', '$2y$10$TwF4UlM6qjvCEDok.jydR.LSp1VVAb1IRGTF0EgTKqgFDwQZzU20K', 'instructor', NULL, NULL, 1, DATE_SUB(NOW(), INTERVAL 25 DAY)),
(3, 'Praveen Student', 'student@elearn.test', '$2y$10$unW81k4PW4gn62Cv73wNPe5ntWDCEYjtePDk/U6ECiDAt4ePn4HyW', 'student', NULL, NULL, 1, DATE_SUB(NOW(), INTERVAL 14 DAY)),
(4, 'Jane Doe', 'jane@elearn.test', '$2y$10$unW81k4PW4gn62Cv73wNPe5ntWDCEYjtePDk/U6ECiDAt4ePn4HyW', 'student', NULL, NULL, 1, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(5, 'Alex Rivera', 'alex@elearn.test', '$2y$10$unW81k4PW4gn62Cv73wNPe5ntWDCEYjtePDk/U6ECiDAt4ePn4HyW', 'student', NULL, NULL, 1, DATE_SUB(NOW(), INTERVAL 5 DAY));

-- Seed Courses:
INSERT INTO courses (id, title, description, category, price, instructor_id, thumbnail, created_at) VALUES
(1, 'Full Stack Web Development with PHP & MySQL', 
    'Master modern web engineering from relational database design and secure PDO/MySQLi querying to responsive Bootstrap frontends, AJAX architectures, and enterprise capstone deployment.', 
    'Web Development', 49.99, 1, 'course_php_mysql.png', DATE_SUB(NOW(), INTERVAL 20 DAY)),

(2, 'Data Science & Machine Learning with Python', 
    'A comprehensive journey through Python data analysis, NumPy, Pandas, statistical modeling, Scikit-learn predictive algorithms, and interactive visual reporting.', 
    'Data Science', 59.99, 2, 'course_python_data.png', DATE_SUB(NOW(), INTERVAL 18 DAY)),

(3, 'Cyber Security & Ethical Hacking Essentials', 
    'Learn network defensive architectures, vulnerability scanning, OWASP Top 10 mitigation, web penetration testing, and security auditing fundamentals.', 
    'Cyber Security', 64.99, 1, 'course_cyber_security.png', DATE_SUB(NOW(), INTERVAL 15 DAY)),

(4, 'Cloud Computing with Docker, Kubernetes & AWS', 
    'Deploy, orchestrate, and scale enterprise container workloads. Covers AWS EC2, S3, Dockerfile optimization, CI/CD pipelines, and microservices architecture.', 
    'Cloud Computing', 54.99, 2, 'course_cloud_aws.png', DATE_SUB(NOW(), INTERVAL 12 DAY)),

(5, 'Modern UI/UX Design: From Wireframe to Figma', 
    'Master human-centered digital design, interactive prototyping, color theory, responsive grid layouts, and design handoff workflows in Figma.', 
    'UI/UX Design', 39.99, 1, 'course_ui_ux.png', DATE_SUB(NOW(), INTERVAL 8 DAY)),

(6, 'Master Modern JavaScript & Async Architectures', 
    'Deep dive into ES6+, closures, prototype chains, asynchronous event loops, Promises, Fetch API, and modular Single Page Application architectures.', 
    'Web Development', 0.00, 2, 'course_javascript.png', DATE_SUB(NOW(), INTERVAL 4 DAY));

-- Seed Lessons:
-- Course 1: Full Stack PHP/MySQL (3 Lessons)
INSERT INTO lessons (id, course_id, title, content, video_url, order_index, created_at) VALUES
(1, 1, 'Introduction to PHP 8 & Web Architecture', 
    '### Welcome to Full Stack Web Development\n\nIn this foundational lesson, we explore how client-server architectures interact over HTTP/HTTPS.\n\n#### Key Objectives:\n1. Understand PHP request lifecycles.\n2. Configure Apache VirtualHosts and document roots in XAMPP.\n3. Superglobals overview: `$_SERVER`, `$_POST`, `$_GET`, and `$_SESSION`.\n\n```php\n<?php\necho "Hello, World! PHP Version: " . phpversion();\n?>\n```', 
    'https://www.youtube.com/embed/OK_JCtrrv-c', 1, DATE_SUB(NOW(), INTERVAL 20 DAY)),

(2, 1, 'Relational Database Design & MySQLi Prepared Statements', 
    '### Secure Database Access\n\nDirect string interpolation in SQL queries causes devastating SQL injection vulnerabilities. In this lesson, we build parameterized prepared statements.\n\n#### Safe Prepared Query Example:\n```php\n$stmt = $conn->prepare("SELECT id, email FROM users WHERE role = ?");\n$stmt->bind_param("s", $role);\n$stmt->execute();\n$result = $stmt->get_result();\n```\n\nAlways validate input before execution.', 
    'https://www.youtube.com/embed/2HVKizgcfjo', 2, DATE_SUB(NOW(), INTERVAL 19 DAY)),

(3, 1, 'Session Authentication, CSRF & Role Guards', 
    '### Defense-in-Depth Authentication\n\nLearn how to safeguard authentication endpoints with CSRF tokens and role-based redirect middleware.\n\n- Passwords must always be hashed with `password_hash($raw, PASSWORD_BCRYPT)`.\n- Use `session_regenerate_id(true)` upon successful verification.\n- Implement anti-CSRF token verification with timing-attack safe `hash_equals()`.', 
    'https://www.youtube.com/embed/3aK3k5vOQ2E', 3, DATE_SUB(NOW(), INTERVAL 18 DAY));

-- Course 2: Data Science with Python (3 Lessons)
INSERT INTO lessons (id, course_id, title, content, video_url, order_index, created_at) VALUES
(4, 2, 'Data Wrangling with NumPy & Pandas', 
    '### Introduction to Python Data Structures\n\nMaster high-performance vector manipulation using NumPy arrays and structured Pandas DataFrames.\n\n#### Code Example:\n```python\nimport pandas as pd\ndf = pd.read_csv("dataset.csv")\nprint(df.describe())\n```', 
    'https://www.youtube.com/embed/vmEHCJofslg', 1, DATE_SUB(NOW(), INTERVAL 18 DAY)),

(5, 2, 'Exploratory Data Analysis & Matplotlib', 
    '### Visualizing Distributions and Correlative Trends\n\nUncover patterns, detect outliers, and engineer features using visual exploratory methodologies.', 
    'https://www.youtube.com/embed/r-uOLxNrNk8', 2, DATE_SUB(NOW(), INTERVAL 17 DAY)),

(6, 2, 'Supervised Machine Learning with Scikit-Learn', 
    '### Regression and Classification Pipelines\n\nTrain, validate, and evaluate linear regression and random forest classification pipelines.', 
    'https://www.youtube.com/embed/0Lt9w-BxKFQ', 3, DATE_SUB(NOW(), INTERVAL 16 DAY));

-- Course 3: Cyber Security (2 Lessons)
INSERT INTO lessons (id, course_id, title, content, video_url, order_index, created_at) VALUES
(7, 3, 'Network Defense & Port Scanning Fundamentals', 
    '### Network Security Topology\n\nUnderstand TCP/IP three-way handshakes, firewall configurations, and perimeter security auditing using Wireshark and Nmap.', 
    'https://www.youtube.com/embed/inWWhr5tnEA', 1, DATE_SUB(NOW(), INTERVAL 15 DAY)),

(8, 3, 'Mitigating the OWASP Top 10 Web Vulnerabilities', 
    '### Application Layer Hardening\n\nComprehensive analysis of Broken Access Control, Injection flaws, Cryptographic Failures, and Cross-Site Scripting (XSS).', 
    'https://www.youtube.com/embed/sQpG_K7wB_E', 2, DATE_SUB(NOW(), INTERVAL 14 DAY));

-- Course 4: Cloud Computing (2 Lessons)
INSERT INTO lessons (id, course_id, title, content, video_url, order_index, created_at) VALUES
(9, 4, 'Containerization Essentials with Docker', 
    '### Building Portable Containers\n\nLearn Dockerfile multi-stage builds, container networking, and lightweight image optimization techniques.', 
    'https://www.youtube.com/embed/fqMOX6JJhGo', 1, DATE_SUB(NOW(), INTERVAL 12 DAY)),

(10, 4, 'Deploying Cloud Workloads to AWS EC2 & S3', 
    '### Infrastructure Provisioning\n\nConfigure Elastic Compute Cloud instances, secure IAM access roles, and provision high-availability cloud storage buckets.', 
    'https://www.youtube.com/embed/ulprqHHWlng', 2, DATE_SUB(NOW(), INTERVAL 11 DAY));

-- Course 5: UI/UX Design (2 Lessons)
INSERT INTO lessons (id, course_id, title, content, video_url, order_index, created_at) VALUES
(11, 5, 'User Empathy, Wireframing & Information Architecture', 
    '### Structuring User Journeys\n\nConduct user persona mapping, construct low-fidelity wireframes, and design clear information hierarchies.', 
    'https://www.youtube.com/embed/c9Wg6Cb_YlU', 1, DATE_SUB(NOW(), INTERVAL 8 DAY)),

(12, 5, 'High-Fidelity Component Prototyping in Figma', 
    '### Modern UI Systems\n\nDesign design tokens, reusable auto-layout components, variant states, and interactive animated prototypes.', 
    'https://www.youtube.com/embed/FTFaQWZBqQ8', 2, DATE_SUB(NOW(), INTERVAL 7 DAY));

-- Course 6: Modern JavaScript (2 Lessons)
INSERT INTO lessons (id, course_id, title, content, video_url, order_index, created_at) VALUES
(13, 6, 'ES6 Modules, Destructuring & Arrow Functions', 
    '### Modern ECMAScript Syntax\n\nClean coding patterns with spread/rest operators, template literals, and modular imports/exports.', 
    'https://www.youtube.com/embed/W6NZfCO5SIk', 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),

(14, 6, 'Asynchronous Programming: Promises, Async/Await & Fetch', 
    '### Non-Blocking JavaScript\n\nMaster asynchronous communication, handling JSON payloads from RESTful APIs, and robust error management with try/catch.', 
    'https://www.youtube.com/embed/PoRJizFvM7s', 2, DATE_SUB(NOW(), INTERVAL 3 DAY));

-- Seed Enrollments:
-- Praveen Student (id=3):
-- Enrolled in Course 1 (PHP): 2 of 3 lessons completed (67%)
-- Enrolled in Course 2 (Python): 1 of 3 lessons completed (33%)
-- Enrolled in Course 6 (JS): 2 of 2 lessons completed (100%)
INSERT INTO enrollments (id, user_id, course_id, enrolled_at, progress_percent) VALUES
(1, 3, 1, DATE_SUB(NOW(), INTERVAL 13 DAY), 67),
(2, 3, 2, DATE_SUB(NOW(), INTERVAL 11 DAY), 33),
(3, 3, 6, DATE_SUB(NOW(), INTERVAL 4 DAY), 100),

-- Jane Doe (id=4):
-- Enrolled in Course 1 (PHP): 1 of 3 lessons completed (33%)
-- Enrolled in Course 3 (Cyber): 2 of 2 lessons completed (100%)
(4, 4, 1, DATE_SUB(NOW(), INTERVAL 9 DAY), 33),
(5, 4, 3, DATE_SUB(NOW(), INTERVAL 8 DAY), 100),

-- Alex Rivera (id=5):
-- Enrolled in Course 4 (Cloud): 0 of 2 lessons completed (0%)
-- Enrolled in Course 5 (UI/UX): 1 of 2 lessons completed (50%)
(6, 5, 4, DATE_SUB(NOW(), INTERVAL 3 DAY), 0),
(7, 5, 5, DATE_SUB(NOW(), INTERVAL 2 DAY), 50),

-- Seed additional enrollments over the past 14 days for rich analytics:
(8, 4, 6, DATE_SUB(NOW(), INTERVAL 1 DAY), 50),
(9, 5, 1, NOW(), 0);

-- Seed Lesson Completions matching the above enrollment progress:
-- Praveen Student (id=3):
-- Completed Course 1 Lessons: 1, 2
-- Completed Course 2 Lesson: 4
-- Completed Course 6 Lessons: 13, 14
INSERT INTO lesson_completions (user_id, lesson_id, completed_at) VALUES
(3, 1, DATE_SUB(NOW(), INTERVAL 12 DAY)),
(3, 2, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(3, 4, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(3, 13, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(3, 14, DATE_SUB(NOW(), INTERVAL 2 DAY)),

-- Jane Doe (id=4):
-- Completed Course 1 Lesson: 1
-- Completed Course 3 Lessons: 7, 8
-- Completed Course 6 Lesson: 13
(4, 1, DATE_SUB(NOW(), INTERVAL 8 DAY)),
(4, 7, DATE_SUB(NOW(), INTERVAL 7 DAY)),
(4, 8, DATE_SUB(NOW(), INTERVAL 6 DAY)),
(4, 13, DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- Alex Rivera (id=5):
-- Completed Course 5 Lesson: 11
(5, 11, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- =============================================================================
-- End of Schema & Seed Data
-- =============================================================================
