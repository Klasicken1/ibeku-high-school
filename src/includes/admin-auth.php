<?php
/* ============================================================
   IBEKU HIGH SCHOOL — ADMIN AUTHENTICATION HELPER
   File: src/includes/admin-auth.php

   Include at the top of EVERY admin page.
   Never build an admin page without calling requireRole().

   USAGE:
   ──────────────────────────────────────────────────────
   // Allow any logged-in admin:
   requireRole(['superadmin','principal','dean']);

   // Allow only SS section users:
   requireRole(['dean','vp_academics'], 'ss');

   // Allow superadmin only:
   requireRole(['superadmin']);
   ──────────────────────────────────────────────────────
   ============================================================ */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ── Role hierarchy — higher index = more permissions ──
   section_admin sits between principal and superadmin: full
   control within their own section, but never cross-section and
   never superadmin's site-wide reach. ── */
define('ROLE_HIERARCHY', [
    'subject_teacher' => 1,
    'form_teacher'    => 2,
    'hod'             => 3,
    'counselor'       => 3,
    'dean'            => 4,
    'vp_general'      => 5,
    'vp_student_affairs' => 5,
    'vp_admin'        => 5,
    'vp_academics'    => 5,
    'principal'       => 6,
    'section_admin'   => 7,
    'superadmin'      => 8,
]);

/* ── Check if user is logged in ── */
function isLoggedIn(): bool {
    return isset($_SESSION['admin_id'])
        && isset($_SESSION['admin_role'])
        && isset($_SESSION['admin_section']);
}

/* ── Redirect to login if not authenticated ── */
function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . (defined('BASE_PATH') ? BASE_PATH . 'admin/login.php' : 'login.php'));
        exit;
    }
}

/* ── Require specific roles — optionally enforce section ──
   $roles   : array of allowed role codes
   $section : 'ss', 'js', or null (null = any section allowed)
*/
function requireRole(array $roles, ?string $section = null): void {
    requireLogin();

    $userRole    = $_SESSION['admin_role'];
    $userSection = $_SESSION['admin_section'];

    /* Superadmin bypasses all role and section checks */
    if ($userRole === 'superadmin') return;

    /* Check role */
    if (!in_array($userRole, $roles, true)) {
        $_SESSION['admin_error'] = 'You do not have permission to access that page.';
        header('Location: ' . (defined('BASE_PATH') ? BASE_PATH . 'admin/index.php' : 'index.php'));
        exit;
    }

    /* Check section if specified */
    if ($section !== null) {
        if ($userSection !== $section && $userSection !== 'both') {
            $_SESSION['admin_error'] = 'You can only access your own section.';
            header('Location: ' . (defined('BASE_PATH') ? BASE_PATH . 'admin/index.php' : 'index.php'));
            exit;
        }
    }
}

/* ── Get current user info ── */
function currentAdmin(): array {
    return [
        'id'             => $_SESSION['admin_id']      ?? null,
        'name'           => $_SESSION['admin_name']    ?? 'Unknown',
        'role'           => $_SESSION['admin_role']    ?? null,
        'section'        => $_SESSION['admin_section'] ?? null,
        'dept'           => $_SESSION['admin_dept']    ?? null,
        'class_assigned' => $_SESSION['admin_class']   ?? null,
    ];
}

/* ── Convenience check — true if the logged-in admin is a
   section_admin (i.e. full control within one section only,
   never site-wide). Used throughout the admin panel to decide
   whether to scope queries/UI to the admin's own section. ── */
function isSectionAdmin(): bool {
    return ($_SESSION['admin_role'] ?? null) === 'section_admin';
}

/* ── Human-readable role label ── */
function roleLabel(string $role, string $section): string {
    $labels = [
        'superadmin'      => 'System Administrator',
        'section_admin'   => 'Section Admin',
        'principal'       => 'Principal',
        'vp_admin'        => 'Vice Principal (Administration)',
        'vp_academics'    => 'Vice Principal (Academics)',
        'vp_general'      => 'Vice Principal (General Duties)',
        'vp_student_affairs' => 'Vice Principal (Student Affairs)',
        'dean'            => 'Dean of Studies',
        'counselor'       => 'Guidance Counsellor',
        'hod'             => 'Head of Department',
        'form_teacher'    => 'Form Teacher',
        'subject_teacher' => 'Subject Teacher',
    ];

    $sectionLabel = match($section) {
        'ss'   => ' — Senior Secondary',
        'js'   => ' — Junior Secondary',
        'both' => '',
        default => '',
    };

    return ($labels[$role] ?? $role) . $sectionLabel;
}