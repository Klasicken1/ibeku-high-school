<?php
/* ============================================================
   IBEKU HIGH SCHOOL — DATABASE CONNECTION
   File: src/config/database.php
   ============================================================ */

declare(strict_types=1);

function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;
        $key   = trim($parts[0]);
        $value = trim(trim($parts[1]), '"\'');
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

$envPath = dirname(__DIR__, 2) . '/.env';
loadEnv($envPath);

$_pdo_instance = null;

function getDB(): PDO {
    global $_pdo_instance;
    if ($_pdo_instance !== null) return $_pdo_instance;

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
        $isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
        if ($isLocal) {
            die('<pre style="background:#1a0835;color:#ff6b6b;padding:20px;font-size:14px">'
                . 'DATABASE CONNECTION FAILED' . PHP_EOL
                . 'Host: ' . $host . PHP_EOL
                . 'Database: ' . $name . PHP_EOL
                . 'Error: ' . $e->getMessage()
                . '</pre>');
        } else {
            error_log('IHS DB Connection failed: ' . $e->getMessage());
            die('<p style="text-align:center;padding:40px;font-family:sans-serif">'
                . 'The website is temporarily unavailable. Please try again shortly.'
                . '</p>');
        }
    }
}

/* ── Settings loader — cached per request ── */
$_settings_cache = null;

function getSettings(): array {
    global $_settings_cache;
    if ($_settings_cache !== null) return $_settings_cache;

    $defaults = [
        /* School Identity */
        'school_name'            => 'Ibeku High School',
        'school_tagline'         => 'Excellence in Education',
        'school_address'         => 'Umuahia, Abia State, Nigeria',
        'school_phone'           => '+234 000 000 0000',
        'school_email'           => 'info@ibekuhighschool.edu.ng',
        'school_website'         => 'https://ibekuhighschool.edu.ng',
        'school_motto'           => 'Knowledge, Discipline, Excellence',
        'school_hours'           => 'Mon – Fri: 8:00 AM – 3:00 PM',

        /* Principals */
        'principal_ss_name'      => '[SS Principal\'s Full Name]',
        'principal_ss_message'   => 'At Ibeku High School, we do not merely teach subjects — we shape futures. Every student who walks through our gates carries within them the potential to become a leader, a builder, a thinker.',
        'principal_js_name'      => '[JS Principal\'s Full Name]',
        'principal_js_message'   => 'The junior secondary years are the most formative in a child\'s academic journey. At Ibeku High School, we ensure every JSS student builds a solid foundation — not just in Mathematics and English, but in confidence, curiosity, and the love of learning.',

        /* Academic Year */
        'current_session'        => '2025/2026',
        'current_term'           => 'first',
        'next_term_resumption'   => '',

        /* Feature Toggles */
        'result_checker_open'    => '1',
        'admissions_open'        => '1',

        /* Announcement Bar (persistent, below nav) */
        'announcement_show'      => '0',
        'announcement_text'      => '',
        'announcement_link'      => '',
        'announcement_link_text' => 'Read more →',

        /* Popup Notification (intrusive — scroll/time triggered) */
        'popup_show'             => '0',
        'popup_title'            => '',
        'popup_text'             => '',
        'popup_link'             => '',
        'popup_link_text'        => 'Learn more →',
        'popup_trigger_scroll'   => '20',
        'popup_trigger_seconds'  => '5',

        /* Homepage YouTube Embed */
        'youtube_video_id'       => '',
        'youtube_video_title'    => 'A Look Inside Ibeku High School',
    ];

    try {
        $pdo  = getDB();
        $rows = $pdo->query('SELECT `key`, `value` FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
        $_settings_cache = array_merge($defaults, $rows);
    } catch (Throwable $e) {
        $_settings_cache = $defaults;
    }

    return $_settings_cache;
}

/* ── Grade calculator ── */
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

function esc(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}