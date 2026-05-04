<?php

require_once BASE_PATH . '/app/helpers/functions.php';

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return !empty($_SESSION['user']);
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        $user = current_user();
        return $user && (($user['role_name'] ?? '') === 'admin');
    }
}

if (!function_exists('auth_only')) {
    function auth_only(): void
    {
        require_login();
    }
}

if (!function_exists('admin_only')) {
    function admin_only(): void
    {
        require_admin();
    }
}



/**
 * Auth middleware compatibility layer for LUMINA.
 *
 * Project auth chính đang nằm trong app/helpers/functions.php:
 * - auth_user()
 * - login_user()
 * - logout_user()
 * - require_login()
 * - require_admin()
 *
 * File này chỉ bổ sung alias để các file cũ/mới gọi thống nhất.
 */

if (!function_exists('auth_user') && defined('BASE_PATH')) {
    require_once BASE_PATH . '/app/helpers/functions.php';
}

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        return auth_user();
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return is_admin_user();
    }
}

if (!function_exists('auth_only')) {
    function auth_only(): void
    {
        require_login();
    }
}

if (!function_exists('admin_only')) {
    function admin_only(): void
    {
        require_admin();
    }
}

if (!function_exists('auth_user') && defined('BASE_PATH')) {
    require_once BASE_PATH . '/app/helpers/functions.php';
}

if (!function_exists('add_flash')) {
    function add_flash(string $type, string $message): void
    {
        if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
            $_SESSION['flash'] = [];
        }

        if (!isset($_SESSION['flash'][$type]) || !is_array($_SESSION['flash'][$type])) {
            $_SESSION['flash'][$type] = [];
        }

        $_SESSION['flash'][$type][] = $message;
    }
}

if (!function_exists('get_flash')) {
    function get_flash(?string $type = null): array
    {
        if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
            return [];
        }

        if ($type === null) {
            $messages = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $messages;
        }

        $messages = $_SESSION['flash'][$type] ?? [];
        unset($_SESSION['flash'][$type]);

        if (empty($_SESSION['flash'])) {
            unset($_SESSION['flash']);
        }

        return is_array($messages) ? $messages : [];
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        return function_exists('auth_user') ? auth_user() : ($_SESSION['auth_user'] ?? null);
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return function_exists('is_admin_user')
            ? is_admin_user()
            : in_array(current_user()['role_name'] ?? null, ['admin', 'manager', 'sales', 'operations'], true);
    }
}

if (!function_exists('auth_only')) {
    function auth_only(): void
    {
        if (function_exists('require_login')) {
            require_login();
            return;
        }

        if (empty($_SESSION['auth_user'])) {
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
    }
}

if (!function_exists('admin_only')) {
    function admin_only(): void
    {
        if (function_exists('require_admin')) {
            require_admin();
            return;
        }

        if (!is_admin()) {
            header('Location: ' . APP_URL . '/login.php');
            exit;
        }
    }
}
