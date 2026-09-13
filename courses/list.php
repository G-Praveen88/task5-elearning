<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: courses/list.php
 * 
 * Course Catalog with Real-Time AJAX Keyword Search, Category Filtering & Pagination
 * Author: G. Praveen
 */

require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth_check.php';

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

// Input Filters
$searchQuery = clean_string($_GET['q'] ?? '');
$categoryFilter = clean_string($_GET['category'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 6;
$offset = ($page - 1) * $limit;

// Build Dynamic SQL with Prepared Statements
$whereClauses = ["1=1"];
$params = [];
$types = "";

if (!empty($searchQuery)) {
    $whereClauses[] = "(c.title LIKE ? OR c.description LIKE ? OR u.name LIKE ?)";
    $likeSearch = "%" . $searchQuery . "%";
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $types .= "sss";
}

if (!empty($categoryFilter) && $categoryFilter !== 'All') {
    $whereClauses[] = "c.category = ?";
    $params[] = $categoryFilter;
    $types .= "s";
}

$whereSql = implode(" AND ", $whereClauses);

// Count Total Matching Courses
$countSql = "SELECT COUNT(*) AS total FROM courses c JOIN users u ON c.instructor_id = u.id WHERE {$whereSql}";
$totalRows = (int)(db_fetch_one($countSql, $types, $params)['total'] ?? 0);
$totalPages = ceil($totalRows / $limit);
if ($page > $totalPages && $totalPages > 0) {
    $page = $totalPages;
    $offset = ($page - 1) * $limit;
}

// Fetch Paginated Courses
$dataSql = "
    SELECT c.*, u.name AS instructor_name,
           (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) AS lesson_count,
           (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) AS student_count
    FROM courses c
    JOIN users u ON c.instructor_id = u.id
    WHERE {$whereSql}
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
";
$fetchParams = array_merge($params, [$limit, $offset]);
$fetchTypes = $types . "ii";

$courses = db_fetch_all($dataSql, $fetchTypes, $fetchParams);

// Helper function to render course card HTML chunk
function render_course_cards(array $courses): string {
    if (empty($courses)) {
        return '
        <div class="col-12 text-center py-5">
            <div class="p-5 bg-white rounded-4 border">
                <i class="bi bi-search fs-1 text-muted mb-3 d-block"></i>
                <h4 class="h5 fw-bold text-dark">No courses match your criteria</h4>
                <p class="text-secondary small mb-3">Try adjusting your keywords or clearing the category filter.</p>
                <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" onclick="resetFilters()">
                    Reset Search & Filters
                </button>
            </div>
        </div>';
    }

    $html = '';
    foreach ($courses as $c) {
        $img = get_course_thumbnail($c['thumbnail'], $c['title'], $c['category']);
        $title = e($c['title']);
        $cat = e($c['category']);
        $desc = e(mb_strimwidth($c['description'], 0, 110, '...'));
        $instructor = e($c['instructor_name']);
        $lessons = (int)$c['lesson_count'];
        $students = (int)$c['student_count'];
        $price = format_currency($c['price']);
        $link = base_url('courses/view.php?id=' . $c['id']);

        $html .= <<<HTML
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card custom-card course-card border-0">
                <div class="course-thumbnail-wrapper">
                    <img src="{$img}" alt="{$title}" class="course-thumbnail-img" loading="lazy">
                    <span class="badge-category">{$cat}</span>
                </div>
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small text-muted"><i class="bi bi-journal-text me-1"></i> {$lessons} Lessons</span>
                        <span class="small text-muted"><i class="bi bi-people me-1"></i> {$students} Students</span>
                    </div>
                    <h3 class="h5 fw-bold mb-2 flex-grow-1">
                        <a href="{$link}" class="text-dark text-decoration-none hover-primary">
                            {$title}
                        </a>
                    </h3>
                    <p class="text-secondary small mb-3">
                        {$desc}
                    </p>
                    <div class="d-flex align-items-center justify-content-between pt-3 border-top mt-auto">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                <i class="bi bi-person-circle fs-5"></i>
                            </div>
                            <span class="small fw-semibold text-secondary text-truncate" style="max-width: 110px;">{$instructor}</span>
                        </div>
                        <div class="fw-bold fs-5 text-primary">
                            {$price}
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="{$link}" class="btn btn-outline-primary btn-sm w-100 rounded-pill fw-semibold">
                            View Syllabus & Enroll
                        </a>
                    </div>
                </div>
            </div>
        </div>
HTML;
    }
    return $html;
}

// Helper function to render pagination controls
function render_pagination(int $currentPage, int $totalPages, string $q, string $cat): string {
    if ($totalPages <= 1) return '';

    $html = '<nav aria-label="Course navigation"><ul class="pagination pagination-sm justify-content-center gap-1 mb-0">';

    // Previous Button
    $prevDisabled = ($currentPage <= 1) ? 'disabled' : '';
    $prevPage = max(1, $currentPage - 1);
    $html .= '<li class="page-item ' . $prevDisabled . '"><a class="page-link rounded-pill px-3" href="javascript:void(0)" onclick="fetchPage(' . $prevPage . ')"><i class="bi bi-chevron-left me-1"></i> Prev</a></li>';

    for ($i = 1; $i <= $totalPages; $i++) {
        $activeClass = ($i === $currentPage) ? 'active fw-bold' : '';
        $html .= '<li class="page-item ' . $activeClass . '"><a class="page-link rounded-circle px-3" href="javascript:void(0)" onclick="fetchPage(' . $i . ')">' . $i . '</a></li>';
    }

    // Next Button
    $nextDisabled = ($currentPage >= $totalPages) ? 'disabled' : '';
    $nextPage = min($totalPages, $currentPage + 1);
    $html .= '<li class="page-item ' . $nextDisabled . '"><a class="page-link rounded-pill px-3" href="javascript:void(0)" onclick="fetchPage(' . $nextPage . ')">Next <i class="bi bi-chevron-right ms-1"></i></a></li>';

    $html .= '</ul></nav>';
    return $html;
}

// If AJAX Request: Return JSON Payload for Seamless Client-Side DOM Replacement
if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success'      => true,
        'html'         => render_course_cards($courses),
        'pagination'   => render_pagination($page, $totalPages, $searchQuery, $categoryFilter),
        'total'        => $totalRows,
        'current_page' => $page,
        'total_pages'  => $totalPages
    ]);
    exit;
}

// Fetch all distinct categories for filter buttons
$allCategories = db_fetch_all("SELECT DISTINCT category FROM courses ORDER BY category ASC");

$pageTitle = 'Explore Courses | EduStream LMS';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="bg-primary text-white py-4 mb-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-7">
                <h1 class="h2 fw-extrabold mb-1">Course Catalog</h1>
                <p class="text-light opacity-75 small mb-0">Browse interactive courses, comprehensive syllabuses, and video curriculums</p>
            </div>
            <div class="col-md-5 mt-3 mt-md-0 text-md-end">
                <span class="badge bg-white bg-opacity-20 border border-white border-opacity-25 px-3 py-2 text-white">
                    <i class="bi bi-collection-play me-1"></i> <span id="totalCoursesCount"><?= $totalRows; ?></span> Courses Available
                </span>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <?= render_flash_messages(); ?>

    <!-- REAL-TIME SEARCH & FILTER BAR -->
    <div class="card custom-card border-0 shadow-sm p-3 mb-4">
        <div class="row g-3 align-items-center">
            <div class="col-lg-5 col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" id="courseSearchInput" class="form-control bg-light border-start-0" placeholder="Search by title, description, or instructor..." value="<?= e($searchQuery); ?>" autocomplete="off">
                    <button class="btn btn-outline-secondary border-start-0" type="button" id="clearSearchBtn" title="Clear Search" style="display: <?= !empty($searchQuery) ? 'block' : 'none'; ?>;">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            <div class="col-lg-7 col-md-6">
                <div class="d-flex flex-wrap gap-1" id="categoryPillContainer">
                    <button type="button" class="btn btn-sm rounded-pill category-filter-btn <?= (empty($categoryFilter) || $categoryFilter === 'All') ? 'btn-primary' : 'btn-outline-secondary'; ?>" data-category="All">
                        All Categories
                    </button>
                    <?php foreach ($allCategories as $c): ?>
                        <button type="button" class="btn btn-sm rounded-pill category-filter-btn <?= ($categoryFilter === $c['category']) ? 'btn-primary' : 'btn-outline-secondary'; ?>" data-category="<?= e($c['category']); ?>">
                            <?= e($c['category']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- COURSE CARDS GRID -->
    <div class="row g-4" id="coursesContainer">
        <?= render_course_cards($courses); ?>
    </div>

    <!-- PAGINATION CONTAINER -->
    <div class="mt-4" id="paginationContainer">
        <?= render_pagination($page, $totalPages, $searchQuery, $categoryFilter); ?>
    </div>
</div>

<script>
let activeCategory = '<?= e($categoryFilter ?: "All"); ?>';
let activePage = <?= $page; ?>;
let searchTimer = null;

function fetchPage(pageNumber) {
    activePage = pageNumber;
    triggerAjaxFilter();
}

function resetFilters() {
    document.getElementById('courseSearchInput').value = '';
    document.getElementById('clearSearchBtn').style.display = 'none';
    activeCategory = 'All';
    activePage = 1;
    document.querySelectorAll('.category-filter-btn').forEach(b => {
        b.classList.toggle('btn-primary', b.getAttribute('data-category') === 'All');
        b.classList.toggle('btn-outline-secondary', b.getAttribute('data-category') !== 'All');
    });
    triggerAjaxFilter();
}

function triggerAjaxFilter() {
    const q = document.getElementById('courseSearchInput').value.trim();
    const container = document.getElementById('coursesContainer');
    
    // Smooth loading state
    container.style.opacity = '0.5';

    const url = new URL(window.location.href);
    url.searchParams.set('ajax', '1');
    url.searchParams.set('q', q);
    url.searchParams.set('category', activeCategory);
    url.searchParams.set('page', activePage);

    // Update browser URL without reload for clean shareability
    const displayUrl = new URL(window.location.href);
    displayUrl.searchParams.delete('ajax');
    if (q) displayUrl.searchParams.set('q', q); else displayUrl.searchParams.delete('q');
    if (activeCategory !== 'All') displayUrl.searchParams.set('category', activeCategory); else displayUrl.searchParams.delete('category');
    if (activePage > 1) displayUrl.searchParams.set('page', activePage); else displayUrl.searchParams.delete('page');
    window.history.replaceState({}, '', displayUrl.toString());

    fetch(url.toString(), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        container.style.opacity = '1';
        if (data.success) {
            container.innerHTML = data.html;
            document.getElementById('paginationContainer').innerHTML = data.pagination;
            document.getElementById('totalCoursesCount').textContent = data.total;
        }
    })
    .catch(err => {
        container.style.opacity = '1';
        console.error('Search error:', err);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('courseSearchInput');
    const clearBtn = document.getElementById('clearSearchBtn');

    // Debounced Search Input
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        clearBtn.style.display = searchInput.value ? 'block' : 'none';
        searchTimer = setTimeout(() => {
            activePage = 1;
            triggerAjaxFilter();
        }, 300);
    });

    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        clearBtn.style.display = 'none';
        activePage = 1;
        triggerAjaxFilter();
    });

    // Category Filter Buttons
    document.querySelectorAll('.category-filter-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            activeCategory = btn.getAttribute('data-category');
            activePage = 1;

            document.querySelectorAll('.category-filter-btn').forEach(b => {
                b.classList.toggle('btn-primary', b === btn);
                b.classList.toggle('btn-outline-secondary', b !== btn);
            });

            triggerAjaxFilter();
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
