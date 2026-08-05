<?php
/**
 * includes/auth.php
 * Session authentication & role helpers — Attendance Management System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require user to be logged in with a specific role.
 *
 * @param string|array|null $role  'admin' | 'head_teacher' | 'student' | array of roles
 * @param string $redirect         URL to redirect to if not authorized
 */
function requireRole($role = null, string $redirect = '../index.php'): void
{
    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        header('Location: ' . $redirect);
        exit;
    }

    if ($role !== null) {
        $allowed = is_array($role) ? $role : [$role];
        if (!in_array($_SESSION['role'], $allowed, true)) {
            header('Location: ' . $redirect);
            exit;
        }
    }
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function currentRole(): ?string
{
    return $_SESSION['role'] ?? null;
}

function currentUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function currentUserName(): string
{
    return $_SESSION['display_name'] ?? $_SESSION['username'] ?? 'User';
}

function currentTeacherId(): ?int
{
    return isset($_SESSION['teacher_id']) ? (int)$_SESSION['teacher_id'] : null;
}

function currentStudentId(): ?int
{
    return isset($_SESSION['student_id']) ? (int)$_SESSION['student_id'] : null;
}

function e(string $val): string
{
    return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}
