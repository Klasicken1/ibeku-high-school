<?php
/* ============================================================
   IBEKU HIGH SCHOOL — DATABASE CONNECTION
   File: src/config/database.php

   Loads credentials from .env and returns a PDO connection.
   Uses PDO with prepared statements throughout — never raw SQL
   with user input.

   USAGE in any PHP file:
   ────────────────────────────────────────────────────────────
   require_once '../src/config/database.php';
   $pdo = getDB();
   $stmt = $pdo->prepare('SELECT * FROM students WHERE admission_number = ?');
   $stmt->execute([$admissionNumber]);
   $student = $stmt->fetch();
   ────────────────────────────────────────────────────────────
   ============================================================ */

declare(strict_types=1);

/* ── Load .env file ── */
function loadEnv(string $path): void {
    if (!file_exists($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        /* Skip comments */
        if (str_starts_with(trim($line), '#')) continue;

        /* Split on first = only */
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;

        $key   = trim($parts[0]);
        $value = trim($parts[1]);

        /* Strip surrounding quotes */
        $value = trim($value, '"\'');

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

/* Load .env from project root — works on both XAMPP and cPanel */
$envPath = dirname(__DIR__, 2) . '/.env';
loadEnv($envPath);

/* ── Database connection singleton ── */
$_pdo_instance = null;

function getDB(): PDO {
    global $_pdo_instance;

    if ($_pdo_instance !== null) {
        return $_pdo_instance;
    }

    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $name = $_ENV['DB_NAME'] ?? 'ibeku_school';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? '';

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    try {
        $_pdo_instance = new PDO($dsn, $user, $pass, $options);
        return $_pdo_instance;
    } catch (PDOException $e) {
        /* Never expose connection details publicly */
        $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);

        if ($isLocal) {
            /* Show full error only on localhost */
            die('<pre style="background:#1a0835;color:#ff6b6b;padding:20px;font-size:14px">'
                . 'DATABASE CONNECTION FAILED' . PHP_EOL
                . 'Host: ' . $host . PHP_EOL
                . 'Database: ' . $name . PHP_EOL
                . 'Error: ' . $e->getMessage()
                . '</pre>');
        } else {
            /* On production — log quietly and show generic message */
            error_log('IHS DB Connection failed: ' . $e->getMessage());
            die('<p style="text-align:center;padding:40px;font-family:sans-serif">'
                . 'The website is temporarily unavailable. Please try again shortly.'
                . '</p>');
        }
    }
}

/* ── Grade calculator — used by result APIs ── */
function calculateGrade(float $total): array {
    if      ($total >= 75) return ['grade' => 'A1', 'remark' => 'Excellent'];
    elseif  ($total >= 70) return ['grade' => 'B2', 'remark' => 'Very Good'];
    elseif  ($total >= 65) return ['grade' => 'B3', 'remark' => 'Good'];
    elseif  ($total >= 60) return ['grade' => 'C4', 'remark' => 'Credit'];
    elseif  ($total >= 55) return ['grade' => 'C5', 'remark' => 'Credit'];
    elseif  ($total >= 50) return ['grade' => 'C6', 'remark' => 'Credit'];
    elseif  ($total >= 45) return ['grade' => 'D7', 'remark' => 'Pass'];
    elseif  ($total >= 40) return ['grade' => 'E8', 'remark' => 'Pass'];
    else                   return ['grade' => 'F9', 'remark' => 'Fail'];
}

/* ── Sanitise output — use on all data going to HTML ── */
function esc(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}