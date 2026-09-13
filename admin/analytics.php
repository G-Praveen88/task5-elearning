<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: admin/analytics.php
 * 
 * Chart.js Visual Analytics Dashboard (Enrollment Trajectory, Popularity & Revenue)
 * Author: G. Praveen
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';

// Access Control: Admin or Instructor Required
require_admin();

// =============================================================================
// DATA QUERY 1: DAILY ENROLLMENTS (PAST 14 DAYS)
// =============================================================================
$rawDaily = db_fetch_all("
    SELECT DATE(enrolled_at) AS date_str, COUNT(*) AS total
    FROM enrollments
    WHERE enrolled_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
    GROUP BY DATE(enrolled_at)
    ORDER BY date_str ASC
");
$dailyMap = array_column($rawDaily, 'total', 'date_str');

// Fill all 14 days continuously for smooth line charting
$dates = [];
$dailyCounts = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $displayLabel = date('M j', strtotime($d));
    $dates[] = $displayLabel;
    $dailyCounts[] = (int)($dailyMap[$d] ?? 0);
}

// =============================================================================
// DATA QUERY 2: MOST POPULAR COURSES BY ENROLLMENT
// =============================================================================
$popularCourses = db_fetch_all("
    SELECT c.id, c.title, c.category, c.price,
           COUNT(e.id) AS enrollment_count,
           AVG(e.progress_percent) AS avg_progress,
           SUM(CASE WHEN e.progress_percent >= 100 THEN 1 ELSE 0 END) AS completed_students
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id
    GROUP BY c.id
    ORDER BY enrollment_count DESC
    LIMIT 6
");

$courseTitles = [];
$courseEnrollments = [];
foreach ($popularCourses as $pc) {
    $courseTitles[] = mb_strimwidth($pc['title'], 0, 24, '...');
    $courseEnrollments[] = (int)$pc['enrollment_count'];
}

// =============================================================================
// DATA QUERY 3: CATEGORY DISTRIBUTION
// =============================================================================
$categoryData = db_fetch_all("
    SELECT category, COUNT(*) AS count
    FROM courses
    GROUP BY category
    ORDER BY count DESC
");
$catLabels = array_column($categoryData, 'category');
$catCounts = array_map('intval', array_column($categoryData, 'count'));

// Summary Aggregates
$totalEnrollmentsAll = array_sum($courseEnrollments);
$totalRevenueAll = (float)(db_fetch_one("
    SELECT SUM(c.price) AS total 
    FROM enrollments e 
    JOIN courses c ON e.course_id = c.id
")['total'] ?? 0.00);

$pageTitle = 'Visual Analytics & Reports | Admin Portal - EduStream LMS';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Chart.js 4.4 CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>

<div class="bg-dark text-white py-4 mb-4 border-bottom border-secondary border-opacity-25">
    <div class="container-fluid px-lg-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/index.php'); ?>" class="text-secondary text-decoration-none">Admin Dashboard</a></li>
                        <li class="breadcrumb-item active text-white" aria-current="page">Visual Analytics</li>
                    </ol>
                </nav>
                <h1 class="h3 fw-extrabold text-white mb-0">Platform Analytics & Intelligence</h1>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= base_url('admin/index.php'); ?>" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard Overview
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-lg-5 pb-5">
    <?= render_flash_messages(); ?>

    <!-- TOP KPI STATS -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="stat-widget custom-card border-0 shadow-sm">
                <span class="text-secondary small fw-semibold text-uppercase">14-Day Velocity</span>
                <h3 class="display-6 fw-bold text-primary mb-0 mt-1"><?= array_sum($dailyCounts); ?></h3>
                <div class="small text-muted mt-1"><i class="bi bi-graph-up-arrow text-success me-1"></i> New student signups</div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="stat-widget custom-card border-0 shadow-sm">
                <span class="text-secondary small fw-semibold text-uppercase">Total Enrollments</span>
                <h3 class="display-6 fw-bold text-success mb-0 mt-1"><?= $totalEnrollmentsAll; ?></h3>
                <div class="small text-muted mt-1"><i class="bi bi-mortarboard-fill text-success me-1"></i> All published courses</div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="stat-widget custom-card border-0 shadow-sm">
                <span class="text-secondary small fw-semibold text-uppercase">Platform Revenue</span>
                <h3 class="display-6 fw-bold text-primary mb-0 mt-1">$<?= number_format($totalRevenueAll, 2); ?></h3>
                <div class="small text-muted mt-1"><i class="bi bi-cash text-primary me-1"></i> Aggregate tuition</div>
            </div>
        </div>

        <div class="col-md-3 col-6">
            <div class="stat-widget custom-card border-0 shadow-sm">
                <span class="text-secondary small fw-semibold text-uppercase">Catalog Categories</span>
                <h3 class="display-6 fw-bold text-info mb-0 mt-1"><?= count($categoryData); ?></h3>
                <div class="small text-muted mt-1"><i class="bi bi-pie-chart text-info me-1"></i> Domain specializations</div>
            </div>
        </div>
    </div>

    <!-- CHARTS ROW -->
    <div class="row g-4 mb-4">
        <!-- CHART 1: DAILY ENROLLMENTS LINE CHART -->
        <div class="col-lg-8">
            <div class="card custom-card border-0 shadow-sm p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-1 text-dark">
                            <i class="bi bi-activity text-primary me-2"></i>Daily Course Enrollments Trend
                        </h2>
                        <p class="text-secondary small mb-0">Volume of student signups per day over the past 14 days</p>
                    </div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1">Last 14 Days</span>
                </div>
                <div style="position: relative; height: 290px;">
                    <canvas id="dailyEnrollmentsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- CHART 3: CATEGORY SHARE DOUGHNUT CHART -->
        <div class="col-lg-4">
            <div class="card custom-card border-0 shadow-sm p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-1 text-dark">
                            <i class="bi bi-pie-chart-fill text-info me-2"></i>Curriculum by Category
                        </h2>
                        <p class="text-secondary small mb-0">Course distribution share</p>
                    </div>
                </div>
                <div style="position: relative; height: 290px;">
                    <canvas id="categoryShareChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- CHART 2: POPULAR COURSES BAR CHART -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card custom-card border-0 shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="h6 fw-bold mb-1 text-dark">
                            <i class="bi bi-bar-chart-fill text-success me-2"></i>Most Popular Curriculums
                        </h2>
                        <p class="text-secondary small mb-0">Top courses ranked by active student enrollments</p>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">Top Curriculums</span>
                </div>
                <div style="position: relative; height: 260px;">
                    <canvas id="popularCoursesChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- PERFORMANCE BREAKDOWN TABLE -->
    <div class="card custom-card border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom">
            <h2 class="h6 fw-bold mb-0 text-dark">
                <i class="bi bi-table text-primary me-2"></i>Course Performance & Completion Matrix
            </h2>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-secondary">
                    <tr>
                        <th>Course Title</th>
                        <th>Category</th>
                        <th>Tuition</th>
                        <th>Enrollments</th>
                        <th>Completed Students</th>
                        <th>Avg. Progress</th>
                        <th class="text-end">Revenue Generated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($popularCourses as $c): 
                        $enrCount = (int)$c['enrollment_count'];
                        $price = (float)$c['price'];
                        $courseRev = $enrCount * $price;
                        $avgP = round((float)$c['avg_progress']);
                    ?>
                        <tr>
                            <td class="fw-bold text-dark">
                                <a href="<?= base_url('courses/view.php?id=' . $c['id']); ?>" class="text-dark text-decoration-none hover-primary">
                                    <?= e($c['title']); ?>
                                </a>
                            </td>
                            <td><span class="badge bg-secondary-subtle text-secondary"><?= e($c['category']); ?></span></td>
                            <td><?= format_currency($price); ?></td>
                            <td><strong><?= $enrCount; ?></strong> students</td>
                            <td>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="bi bi-check-circle-fill me-1"></i> <?= (int)$c['completed_students']; ?> Graduated
                                </span>
                            </td>
                            <td style="min-width: 130px;">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span><?= $avgP; ?>%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" style="width: <?= $avgP; ?>%;"></div>
                                </div>
                            </td>
                            <td class="text-end fw-bold text-primary">
                                $<?= number_format($courseRev, 2); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // -------------------------------------------------------------------------
    // 1. DAILY ENROLLMENTS LINE CHART
    // -------------------------------------------------------------------------
    const dailyCtx = document.getElementById('dailyEnrollmentsChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($dates); ?>,
            datasets: [{
                label: 'Enrollments',
                data: <?= json_encode($dailyCounts); ?>,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointBackgroundColor: '#4f46e5',
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    padding: 10,
                    backgroundColor: '#0f172a'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // -------------------------------------------------------------------------
    // 2. CATEGORY DISTRIBUTION DOUGHNUT CHART
    // -------------------------------------------------------------------------
    const catCtx = document.getElementById('categoryShareChart').getContext('2d');
    new Chart(catCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($catLabels); ?>,
            datasets: [{
                data: <?= json_encode($catCounts); ?>,
                backgroundColor: [
                    '#4f46e5',
                    '#0d9488',
                    '#dc2626',
                    '#0284c7',
                    '#9333ea',
                    '#f59e0b'
                ],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 11 } }
                }
            },
            cutout: '65%'
        }
    });

    // -------------------------------------------------------------------------
    // 3. MOST POPULAR COURSES BAR CHART
    // -------------------------------------------------------------------------
    const popCtx = document.getElementById('popularCoursesChart').getContext('2d');
    new Chart(popCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($courseTitles); ?>,
            datasets: [{
                label: 'Enrolled Students',
                data: <?= json_encode($courseEnrollments); ?>,
                backgroundColor: 'rgba(16, 185, 129, 0.85)',
                borderColor: '#10b981',
                borderWidth: 1,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { backgroundColor: '#0f172a' }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
