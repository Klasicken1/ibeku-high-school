<?php
/* ============================================================
   IBEKU HIGH SCHOOL - CORPS MEMBER PORTAL AUTH
   File: src/includes/corps-auth.php
   ============================================================ */

function corpsSessionStart(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('ihs_corps');
        session_start();
    }
}

function currentCorpsMember(): ?array {
    corpsSessionStart();
    return $_SESSION['corps_member'] ?? null;
}

function corpsLoggedIn(): bool {
    return currentCorpsMember() !== null;
}

function requireCorpsLogin(): array {
    corpsSessionStart();
    if (!corpsLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    return currentCorpsMember();
}

function loginCorpsMember(array $member): void {
    corpsSessionStart();
    session_regenerate_id(true);
    $_SESSION['corps_member'] = [
        'id'             => $member['id'],
        'state_code'     => $member['state_code'],
        'full_name'      => $member['full_name'],
        'photo'          => $member['photo']          ?? null,
        'batch'          => $member['batch']          ?? null,
        'section'        => $member['section']        ?? null,
        'class_arms'     => $member['class_arms']     ?? null,
        'subject_taught' => $member['subject_taught'] ?? null,
        'status'         => $member['status']         ?? 'active',
    ];
}

function refreshCorpsSession(PDO $pdo): void {
    $member = currentCorpsMember();
    if (!$member) return;
    $stmt = $pdo->prepare('SELECT * FROM corps_members WHERE id = ? LIMIT 1');
    $stmt->execute([$member['id']]);
    $fresh = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($fresh) loginCorpsMember($fresh);
}

function logoutCorpsMember(): void {
    corpsSessionStart();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function sectionLabel(string $section): string {
    return match(strtolower($section)) {
        'js'   => 'Junior Secondary',
        'ss'   => 'Senior Secondary',
        'both' => 'Junior & Senior Secondary',
        default => ucfirst($section),
    };
}