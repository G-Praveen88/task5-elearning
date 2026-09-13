<?php
/**
 * APEXPLANET SOFTWARE PVT. LTD. — FULL STACK WEB DEVELOPMENT INTERNSHIP
 * Task 5: Capstone Project — E-Learning Portal (LMS)
 * File: config/db_connect.php
 * 
 * Centralized MySQLi Database Connection & Prepared Statement Helpers
 * Configured for XAMPP (Local) and Cloud / Shared Hosting (InfinityFree / 000webhost)
 * Author: G. Praveen
 */

// Define database credentials with fallback defaults for local XAMPP
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_PORT')) define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'elearning_db');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

/**
 * Establish and return a singleton MySQLi connection.
 *
 * @return mysqli
 */
function get_db_connection(): mysqli {
    static $conn = null;

    if ($conn === null) {
        // Report all MySQLi errors as exceptions for robust error trapping
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            $conn->set_charset(DB_CHARSET);
        } catch (mysqli_sql_exception $e) {
            http_response_code(500);
            ?>
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Database Connection Error | E-Learning Portal</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
                <style>
                    body { background-color: #0f172a; color: #f8fafc; font-family: system-ui, sans-serif; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; }
                    .card { background: #1e293b; border: 1px solid #334155; border-radius: 1rem; box-shadow: 0 20px 40px rgba(0,0,0,0.3); max-width: 650px; width: 100%; }
                    .code-box { background: #090d16; border-radius: 0.5rem; padding: 1rem; font-family: monospace; font-size: 0.875rem; color: #38bdf8; overflow-x: auto; }
                </style>
            </head>
            <body>
                <div class="card p-4 p-md-5">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="bg-danger bg-opacity-25 text-danger rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                            <i class="bi bi-database-exclamation fs-3"></i>
                        </div>
                        <div>
                            <h2 class="h4 mb-0 text-white">Database Connection Failed</h2>
                            <small class="text-secondary">E-Learning Portal &bull; Capstone Project (PHP/MySQL)</small>
                        </div>
                    </div>

                    <p class="text-light mb-3">
                        MySQLi could not establish a connection to the database. Please verify that your XAMPP MySQL service is active and the database <code><?= htmlspecialchars(DB_NAME); ?></code> has been created.
                    </p>

                    <div class="code-box mb-4">
                        <strong>Error:</strong> <?= htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'); ?><br>
                        <strong>Host:</strong> <?= htmlspecialchars(DB_HOST); ?>:<?= DB_PORT; ?><br>
                        <strong>Database:</strong> <?= htmlspecialchars(DB_NAME); ?><br>
                        <strong>User:</strong> <?= htmlspecialchars(DB_USER); ?>
                    </div>

                    <h5 class="h6 text-white mb-2"><i class="bi bi-tools me-2 text-warning"></i>Quick Troubleshooting:</h5>
                    <ol class="text-secondary small mb-4 ps-3">
                        <li class="mb-1">Open <strong>XAMPP Control Panel</strong> and confirm <strong>MySQL</strong> is running.</li>
                        <li class="mb-1">Import <code>schema.sql</code> via phpMyAdmin (<a href="http://localhost/phpmyadmin/" target="_blank" class="text-info text-decoration-none">http://localhost/phpmyadmin/</a>) or command prompt.</li>
                        <li>Verify credentials in <code>config/db_connect.php</code>.</li>
                    </ol>

                    <div class="d-flex gap-2">
                        <a href="javascript:location.reload()" class="btn btn-primary btn-sm px-3">
                            <i class="bi bi-arrow-clockwise me-1"></i> Retry Connection
                        </a>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }

    return $conn;
}

/**
 * Execute a parameterized SELECT query using prepared statements.
 * Returns an array of associative rows.
 *
 * @param string $sql SQL statement with ? placeholders
 * @param string $types Type definition string (e.g., 'isi' for int, string, int)
 * @param array $params Parameters to bind
 * @return array
 */
function db_fetch_all(string $sql, string $types = '', array $params = []): array {
    $conn = get_db_connection();
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException("Prepare failed: " . $conn->error);
    }

    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

/**
 * Execute a parameterized query and fetch a single associative row.
 *
 * @param string $sql SQL statement with ? placeholders
 * @param string $types Type definition string
 * @param array $params Parameters to bind
 * @return array|null
 */
function db_fetch_one(string $sql, string $types = '', array $params = []): ?array {
    $rows = db_fetch_all($sql, $types, $params);
    return !empty($rows) ? $rows[0] : null;
}

/**
 * Execute an INSERT, UPDATE, or DELETE query using prepared statements.
 *
 * @param string $sql SQL statement with ? placeholders
 * @param string $types Type definition string
 * @param array $params Parameters to bind
 * @return array ['affected_rows' => int, 'insert_id' => int]
 */
function db_execute(string $sql, string $types = '', array $params = []): array {
    $conn = get_db_connection();
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException("Prepare failed: " . $conn->error);
    }

    if (!empty($types) && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $affected = $stmt->affected_rows;
    $insertId = $stmt->insert_id;
    $stmt->close();

    return [
        'affected_rows' => $affected,
        'insert_id'     => $insertId
    ];
}

// Global connection instance for direct procedural/transactional access when required
$conn = get_db_connection();
