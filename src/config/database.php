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
        'abia_state_emblem'      => '',
        'school_logo'            => '',

        /* Principals */
        'principal_ss_name'      => '[SS Principal\'s Full Name]',
        'principal_ss_message'   => 'At Ibeku High School, we do not merely teach subjects — we shape futures. Every student who walks through our gates carries within them the potential to become a leader, a builder, a thinker.',
        'principal_ss_signature' => '',
        'principal_ss_stamp'     => '',
        'principal_js_name'      => '[JS Principal\'s Full Name]',
        'principal_js_message'   => 'The junior secondary years are the most formative in a child\'s academic journey. At Ibeku High School, we ensure every JSS student builds a solid foundation — not just in Mathematics and English, but in confidence, curiosity, and the love of learning.',
        'principal_js_signature' => '',
        'principal_js_stamp'     => '',

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
/* ── Inner-page hero background images ──
   Stored as one JSON-encoded value under the settings key
   'hero_images_inner', keyed by page: about, academics, students,
   admissions, contact, hall_of_fame, news, events, gallery,
   results, corps. Each entry is either:
     - a plain string filename (legacy format, defaults to center focal point)
     - an object {"image": "...", "position": "top center"} (current format,
       lets an admin choose which part of the photo survives the crop)
   Managed via admin/hero-images.php.
   ============================================================ */
function getInnerHeroImages(): array {
    $settings = getSettings();
    $raw = $settings['hero_images_inner'] ?? '';
    if ($raw === '') return [];
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

/* Normalises either storage format into ['image' => ..., 'position' => ...] */
function getInnerHeroEntry(string $pageKey): ?array {
    $images = getInnerHeroImages();
    $entry  = $images[$pageKey] ?? null;
    if (!$entry) return null;

    if (is_string($entry)) {
        return ['image' => $entry, 'position' => 'center center'];
    }
    if (is_array($entry) && !empty($entry['image'])) {
        return [
            'image'    => $entry['image'],
            'position' => $entry['position'] ?? 'center center',
        ];
    }
    return null;
}

function getInnerHeroImage(string $pageKey): ?string {
    $entry = getInnerHeroEntry($pageKey);
    return $entry['image'] ?? null;
}

function getInnerHeroPosition(string $pageKey): string {
    $entry = getInnerHeroEntry($pageKey);
    return $entry['position'] ?? 'center center';
}

/**
 * Returns a ready-to-echo inline style attribute setting the
 * background-image AND background-position for a page-hero, or an
 * empty string if no image has been uploaded for that page. Pair
 * with the page-hero--photo modifier class (see style.css) which
 * adds the dark overlay needed for text readability over a photo:
 *
 *   <div class="page-hero page-hero--about
 *               <?php echo getInnerHeroImage('about') ? 'page-hero--photo' : ''; ?>"
 *        <?php echo renderInnerHeroStyle('about'); ?>>
 */
function renderInnerHeroStyle(string $pageKey): string {
    $entry = getInnerHeroEntry($pageKey);
    if (!$entry) return '';
    $url = BASE_PATH . 'assets/images/hero/' . rawurlencode($entry['image']);
    $pos = htmlspecialchars($entry['position'], ENT_QUOTES);
    return ' style="background-image:url(\'' . htmlspecialchars($url, ENT_QUOTES) . '\');background-position:' . $pos . '"';
}
/* ── Staff Directory sync ──
   Keeps the public Staff Directory (the `staff` table, shown on
   Academics) automatically populated from login accounts (`users`
   table), so admins enter a staff member's info once — on the
   Create/Edit User form — instead of twice.

   `staff.user_id` links a directory row back to its source user.
   Rows created directly on admin/staff.php (for staff who don't
   have a login account — e.g. support staff) have a NULL user_id
   and are completely untouched by this sync; both systems coexist.

   Category is auto-derived rather than asked for again: admin-tier
   roles map to 'administration'; teaching roles look up their
   assigned subject's own department in the subjects table; anyone
   else falls back to 'support'.
   ============================================================ */
function deriveStaffCategory(PDO $pdo, string $role, ?string $department): string {
    $adminRoles = [
        'principal', 'vp_admin', 'vp_academics', 'vp_general', 'vp_student_affairs',
        'dean', 'section_admin', 'counselor', 'superadmin',
    ];
    if (in_array($role, $adminRoles, true)) return 'administration';

    if ($department) {
        $stmt = $pdo->prepare('SELECT department FROM subjects WHERE name = ? LIMIT 1');
        $stmt->execute([$department]);
        $subjectDept = $stmt->fetchColumn();
        if (in_array($subjectDept, ['sciences', 'arts', 'commercial'], true)) {
            return $subjectDept;
        }
    }
    return 'support';
}

function syncStaffDirectoryFromUser(PDO $pdo, int $userId): void {
    /* Self-healing — the link column may not exist yet on a page
       that hasn't run this before */
    try {
        $pdo->exec("ALTER TABLE staff ADD COLUMN user_id INT UNSIGNED NULL AFTER id");
    } catch (PDOException $e) { /* already exists */ }

    $uStmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $uStmt->execute([$userId]);
    $user = $uStmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) return;

    /* Not opted into the public directory — hide (not delete) any
       existing linked entry, so re-enabling later just restores it */
    if (empty($user['show_on_directory'])) {
        $pdo->prepare('UPDATE staff SET is_published = 0 WHERE user_id = ?')->execute([$userId]);
        return;
    }

    $roleDisplay = function_exists('roleLabel')
        ? preg_replace('/\s*—.*/', '', roleLabel($user['role'], $user['section'] ?? 'both')) /* strip the " — Senior Secondary" suffix for the directory */
        : ucwords(str_replace('_', ' ', $user['role']));

    $category = deriveStaffCategory($pdo, $user['role'], $user['department'] ?? null);

    $existing = $pdo->prepare('SELECT id FROM staff WHERE user_id = ? LIMIT 1');
    $existing->execute([$userId]);
    $existingId = $existing->fetchColumn();

    if ($existingId) {
        $pdo->prepare(
            'UPDATE staff SET full_name=?, role=?, department=?, section=?, category=?,
                bio=?, photo=?, is_published=1
             WHERE user_id=?'
        )->execute([
            $user['full_name'], $roleDisplay, $user['department'] ?? null, $user['section'] ?? 'both',
            $category, $user['bio'] ?? null, $user['photo'] ?? null, $userId,
        ]);
    } else {
        $pdo->prepare(
            'INSERT INTO staff (user_id, full_name, role, department, section, category, bio, photo, is_published, sort_order)
             VALUES (?,?,?,?,?,?,?,?,1,0)'
        )->execute([
            $userId, $user['full_name'], $roleDisplay, $user['department'] ?? null, $user['section'] ?? 'both',
            $category, $user['bio'] ?? null, $user['photo'] ?? null,
        ]);
    }
}

/* ── Principal signature/stamp assets ──
   Returns the correct principal's name, signature filename, and
   stamp filename for a given section ('ss' or 'js'). 'both' or
   any unrecognised value defaults to 'ss'. Filenames are stored
   in public/assets/images/signatures/ — managed via
   admin/settings.php's Principals panel.

   Callers construct the actual URL themselves rather than this
   helper returning one, since BASE_PATH isn't defined in every
   context that needs these assets (e.g. corps-letter.php doesn't
   load header.php, and check_result.php is a JSON API that lets
   the frontend prefix filenames with window.IHS_BASE instead).
   ============================================================ */
function getPrincipalAssets(string $section): array {
    $s = strtolower($section);
    if ($s !== 'ss' && $s !== 'js') $s = 'ss';

    $settings = getSettings();
    return [
        'section'   => $s,
        'name'      => $settings["principal_{$s}_name"]      ?? '',
        'signature' => $settings["principal_{$s}_signature"] ?? '',
        'stamp'     => $settings["principal_{$s}_stamp"]     ?? '',
    ];
}