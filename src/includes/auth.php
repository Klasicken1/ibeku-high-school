<?php
/* ============================================================
   IBEKU HIGH SCHOOL — STUDENT PORTAL AUTH
   File: src/includes/auth.php

   Session helpers for the student portal.
   Completely separate from admin auth (admin-auth.php).
   Students are in the `students` table; admins in `users`.
   ============================================================ */

/**
 * Start session if not already started.
 */
function portalSessionStart(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('ihs_portal');
        session_start();
    }
}

/**
 * Return the currently logged-in student array, or null.
 */
function currentStudent(): ?array {
    portalSessionStart();
    return $_SESSION['portal_student'] ?? null;
}

/**
 * True if a student is logged in.
 */
function studentLoggedIn(): bool {
    return currentStudent() !== null;
}

/**
 * Require student to be logged in.
 * Redirects to login page if not authenticated.
 * Also enforces portal_blocked — sends to blocked.php.
 * Also enforces must_change_password — sends to change-password.php
 * on first login (i.e. the student is still using their admission
 * number as their password, per students.password being NULL in DB).
 */
function requireStudentLogin(): array {
    portalSessionStart();

    if (!studentLoggedIn()) {
        header('Location: login.php');
        exit;
    }

    $student = currentStudent();
    $current = basename($_SERVER['PHP_SELF']);

    /* Re-check blocked status on every request */
    if ($student['portal_blocked']) {
        /* Allow blocked.php itself to load (prevent redirect loop) */
        if ($current !== 'blocked.php' && $current !== 'logout.php') {
            header('Location: blocked.php');
            exit;
        }
    }

    /* Force a password change on first login — allow change-password.php,
       logout.php, and blocked.php through (prevent redirect loop, and
       don't block a student who's already been locked out anyway) */
    if (!empty($student['must_change_password'])) {
        $allowedPages = ['change-password.php', 'logout.php', 'blocked.php'];
        if (!in_array($current, $allowedPages, true)) {
            header('Location: change-password.php');
            exit;
        }
    }

    return $student;
}

/**
 * Log a student in — call after verifying credentials.
 * Stores a minimal safe subset in session (no password hash).
 */
function loginStudent(array $student): void {
    portalSessionStart();
    session_regenerate_id(true);

    $_SESSION['portal_student'] = [
        'id'               => $student['id'],
        'admission_number' => $student['admission_number'],
        'first_name'       => $student['first_name'],
        'last_name'        => $student['last_name'],
        'other_name'       => $student['other_name'] ?? null,
        'gender'           => $student['gender'],
        'grade_level'      => $student['grade_level'],
        'class'            => $student['class'],
        'section'          => $student['section'],
        'department'       => $student['department'],
        'photo'            => $student['photo'] ?? null,
        'portal_blocked'   => (bool) $student['portal_blocked'],
        'results_blocked'  => (bool) $student['results_blocked'],
        'portal_blocked_reason' => $student['portal_blocked_reason'] ?? null,
        'results_blocked_reason' => $student['results_blocked_reason'] ?? null,
        /* True until the student sets their own password (students.password
           starts NULL and is only ever set via change-password.php) */
        'must_change_password' => empty($student['password']),
    ];
}

/**
 * Refresh the session student data from DB.
 * Call this after any admin changes to the student record.
 */
function refreshStudentSession(PDO $pdo): void {
    $student = currentStudent();
    if (!$student) return;

    $stmt = $pdo->prepare(
        'SELECT * FROM students WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$student['id']]);
    $fresh = $stmt->fetch();
    if ($fresh) loginStudent($fresh);
}

/**
 * Log the student out.
 */
function logoutStudent(): void {
    portalSessionStart();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $p['path'], $p['domain'],
            $p['secure'], $p['httponly']
        );
    }
    session_destroy();
}

/**
 * Grade level display label.
 */
function gradeLabel(string $grade): string {
    return match($grade) {
        'JSS1'  => 'JSS 1',
        'JSS2'  => 'JSS 2',
        'JSS3'  => 'JSS 3',
        'SSS1'  => 'SSS 1',
        'SSS2'  => 'SSS 2',
        'SSS3'  => 'SSS 3',
        default => $grade,
    };
}

/**
 * Section display label.
 */
function sectionLabel(string $section): string {
    return match(strtolower($section)) {
        'ss'    => 'Senior Secondary',
        'js'    => 'Junior Secondary',
        default => strtoupper($section),
    };
}