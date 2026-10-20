<?php
session_start();
require_once __DIR__ . '/../config/database.php';

function e(mixed $v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function jattr(array $row): string { return htmlspecialchars(json_encode($row, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): void { header('Location: ' . $url); exit; }

function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function get_flash(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function check_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
        !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403); die('Invalid CSRF token. Go back and reload the page.');
    }
}

function require_login(): void { if (empty($_SESSION['user'])) redirect('login.php'); }
function require_role(string ...$roles): void {
    require_login();
    if (!in_array($_SESSION['user']['role'], $roles, true)) {
        http_response_code(403); die('Access denied.');
    }
}
function nullable(mixed $v): ?string { $v = trim((string)$v); return $v === '' ? null : $v; }
