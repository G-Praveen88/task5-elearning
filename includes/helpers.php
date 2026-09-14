<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: includes/helpers.php
 * 
 * Global Utility, Security, CSRF & View Helpers
 * Author: G. Praveen
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

/**
 * Escape HTML output to prevent Cross-Site Scripting (XSS).
 *
 * @param mixed $value
 * @return string
 */
function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Determine dynamic base URL for task5-elearning.
 *
 * @param string $path Optional relative path to append
 * @return string
 */
function base_url(string $path = ''): string {
    static $detectedBase = null;

    if ($detectedBase === null) {
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if (preg_match('#^(.*?/task5-elearning)#i', $scriptDir, $matches)) {
            $detectedBase = $matches[1];
        } else {
    $detectedBase = '';   // site is hosted at the domain root
}
        $detectedBase = rtrim($detectedBase, '/');
    }

    $cleanPath = ltrim($path, '/');
        return ($cleanPath === '') ? ($detectedBase === '' ? '/' : $detectedBase) : ($detectedBase . '/' . $cleanPath);
}

/**
 * Safe HTTP Redirection.
 *
 * @param string $path
 */
function redirect(string $path): void {
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        header("Location: " . $path);
    } else {
        header("Location: " . base_url($path));
    }
    exit;
}

/**
 * Generate or retrieve current CSRF token for the active session.
 *
 * @return string
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden CSRF token input tag.
 *
 * @return string
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verify submitted CSRF token using timing-attack safe comparison.
 *
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token(?string $token = null): bool {
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (empty($token) || empty($sessionToken)) {
        return false;
    }
    return hash_equals($sessionToken, $token);
}

/**
 * Set a session flash message.
 *
 * @param string $type 'success' | 'danger' | 'warning' | 'info'
 * @param string $message
 */
function set_flash(string $type, string $message): void {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Retrieve and clear all session flash messages.
 *
 * @return array
 */
function get_flashes(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Render HTML dismissible Bootstrap alerts for pending flash messages.
 */
function render_flash_messages(): void {
    $flashes = get_flashes();
    if (empty($flashes)) {
        return;
    }

    $iconMap = [
        'success' => 'bi-check-circle-fill',
        'danger'  => 'bi-exclamation-octagon-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        'info'    => 'bi-info-circle-fill'
    ];

    echo '<div class="flash-messages-container mb-4">';
    foreach ($flashes as $flash) {
        $type = e($flash['type']);
        $msg  = e($flash['message']);
        $icon = $iconMap[$type] ?? 'bi-info-circle-fill';
        echo <<<HTML
        <div class="alert alert-{$type} alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
            <i class="bi {$icon} me-2 fs-5 flex-shrink-0" aria-hidden="true"></i>
            <div class="flex-grow-1">{$msg}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
HTML;
    }
    echo '</div>';
}

/**
 * Format course tuition or free label.
 *
 * @param float|int|string $amount
 * @return string
 */
function format_currency($amount): string {
    $num = (float)$amount;
    if ($num <= 0.00) {
        return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Free</span>';
    }
    return '$' . number_format($num, 2);
}

/**
 * Format timestamp into readable human date.
 *
 * @param string $date
 * @param bool $includeTime
 * @return string
 */
function format_date(string $date, bool $includeTime = false): string {
    $timestamp = strtotime($date);
    if (!$timestamp) return $date;
    return $includeTime ? date('M j, Y g:i A', $timestamp) : date('M j, Y', $timestamp);
}

/**
 * Clean string input.
 *
 * @param string|null $str
 * @return string
 */
function clean_string(?string $str): string {
    return trim((string)$str);
}

/**
 * Compute progress percentage clamped between 0 and 100.
 *
 * @param int $completed
 * @param int $total
 * @return int
 */
function calculate_progress(int $completed, int $total): int {
    if ($total <= 0) {
        return 0;
    }
    $percent = (int)round(($completed / $total) * 100);
    return max(0, min(100, $percent));
}

/**
 * Color badge class based on progress percentage.
 *
 * @param int $percent
 * @return string
 */
function get_progress_color(int $percent): string {
    if ($percent >= 100) return 'success';
    if ($percent >= 60)  return 'primary';
    if ($percent >= 25)  return 'warning';
    return 'info';
}

/**
 * Resolve course thumbnail image URL or generate dynamic high-res SVG banner with category gradients.
 *
 * @param string|null $image
 * @param string $title
 * @param string $category
 * @return string
 */
function get_course_thumbnail(?string $image, string $title = 'Course', string $category = 'General'): string {
    if (!empty($image)) {
        $localPath = dirname(__DIR__) . '/uploads/' . $image;
        if (file_exists($localPath) && is_file($localPath) && filesize($localPath) > 0) {
            return base_url('uploads/' . $image);
        }
    }

    // Dynamic high-res SVG cover with category-based styling
    $colorThemes = [
        'Web Development' => ['#1e1b4b', '#4338ca', 'WEB DEV', 'bi-code-slash'],
        'Data Science'    => ['#042f2e', '#0d9488', 'DATA SCIENCE', 'bi-graph-up'],
        'Cyber Security'  => ['#450a0a', '#dc2626', 'SECURITY', 'bi-shield-lock'],
        'Cloud Computing' => ['#082f49', '#0284c7', 'CLOUD & DEVOPS', 'bi-cloud-check'],
        'UI/UX Design'    => ['#3b0764', '#9333ea', 'UI / UX DESIGN', 'bi-palette'],
    ];

    $theme = $colorThemes[$category] ?? ['#0f172a', '#334155', strtoupper($category), 'bi-book'];
    $bg1 = $theme[0];
    $bg2 = $theme[1];
    $tag = $theme[2];

    $displayTitle = mb_strimwidth($title, 0, 42, '...');
    $cleanTitleXml = htmlspecialchars($displayTitle, ENT_XML1, 'UTF-8');
    $cleanTagXml   = htmlspecialchars($tag, ENT_XML1, 'UTF-8');

    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 340" width="600" height="340">
  <defs>
    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$bg1}" />
      <stop offset="100%" stop-color="{$bg2}" />
    </linearGradient>
    <radialGradient id="glow" cx="80%" cy="20%" r="60%">
      <stop offset="0%" stop-color="#ffffff" stop-opacity="0.15" />
      <stop offset="100%" stop-color="#ffffff" stop-opacity="0" />
    </radialGradient>
  </defs>
  <rect width="600" height="340" fill="url(#grad)" />
  <rect width="600" height="340" fill="url(#glow)" />
  
  <!-- Grid overlay -->
  <g opacity="0.08" stroke="#ffffff" stroke-width="1">
    <line x1="0" y1="68" x2="600" y2="68" />
    <line x1="0" y1="136" x2="600" y2="136" />
    <line x1="0" y1="204" x2="600" y2="204" />
    <line x1="0" y1="272" x2="600" y2="272" />
    <line x1="120" y1="0" x2="120" y2="340" />
    <line x1="240" y1="0" x2="240" y2="340" />
    <line x1="360" y1="0" x2="360" y2="340" />
    <line x1="480" y1="0" x2="480" y2="340" />
  </g>

  <!-- Category Badge -->
  <rect x="40" y="40" width="160" height="30" rx="15" fill="rgba(255,255,255,0.18)" />
  <text x="120" y="60" fill="#ffffff" font-family="system-ui, sans-serif" font-size="12" font-weight="700" letter-spacing="1.5" text-anchor="middle">{$cleanTagXml}</text>

  <!-- Central Emblem -->
  <circle cx="500" cy="90" r="45" fill="rgba(255,255,255,0.08)" />
  <polygon points="492,75 515,90 492,105" fill="#ffffff" opacity="0.85" />

  <!-- Course Title -->
  <text x="40" y="165" fill="#ffffff" font-family="'Plus Jakarta Sans', system-ui, sans-serif" font-size="24" font-weight="800">
    {$cleanTitleXml}
  </text>

  <line x1="40" y1="205" x2="260" y2="205" stroke="#38bdf8" stroke-width="3" stroke-linecap="round" />

  <text x="40" y="275" fill="rgba(255,255,255,0.75)" font-family="system-ui, sans-serif" font-size="13" font-weight="600" letter-spacing="0.5">
    E-LEARNING PORTAL &bull; CERTIFIED CURRICULUM
  </text>
</svg>
SVG;

    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}
