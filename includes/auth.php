<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Detect base path dynamically so folder name does not matter
function basePath(): string {
    // Works regardless of folder name: hostel_project, Hostel_Management_System, etc.
    $script = $_SERVER['SCRIPT_NAME'];          // e.g. /Hostel_Management_System/admin/login.php
    $parts  = explode('/', trim($script, '/'));  // ['Hostel_Management_System', 'admin', 'login.php']
    return '/' . $parts[0];                     // /Hostel_Management_System
}

function requireAdmin(): void {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: ' . basePath() . '/admin/login.php');
        exit;
    }
}
function requireStudent(): void {
    if (!isset($_SESSION['student_id'])) {
        header('Location: ' . basePath() . '/student/login.php');
        exit;
    }
}
function isAdmin(): bool   { return isset($_SESSION['admin_id']); }
function isStudent(): bool { return isset($_SESSION['student_id']); }

function flash(string $key, string $msg = ''): string {
    if ($msg) { $_SESSION['flash'][$key] = $msg; return ''; }
    $v = $_SESSION['flash'][$key] ?? '';
    unset($_SESSION['flash'][$key]);
    return $v;
}
function flashHtml(): string {
    $out   = '';
    $icons = ['success' => '✓', 'error' => '✕', 'info' => 'ℹ'];
    foreach (['success', 'error', 'info'] as $t) {
        $m = flash($t);
        if ($m) {
            $out .= "<div class=\"alert alert-{$t}\"><span class=\"alert-icon\">{$icons[$t]}</span>"
                  . htmlspecialchars($m) . "</div>";
        }
    }
    return $out;
}
