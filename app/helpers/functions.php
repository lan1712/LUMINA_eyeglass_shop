<?php

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('format_price')) {
    function format_price(float|int|string|null $amount): string
    {
        return number_format((float) $amount, 0, ',', '.') . '₫';
    }
}

if (!function_exists('ensure_cart_session')) {
    function ensure_cart_session(): void
    {
        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }
}

if (!function_exists('cart_count')) {
    function cart_count(): int
    {
        ensure_cart_session();

        $count = 0;
        foreach ($_SESSION['cart'] as $item) {
            $count += (int) ($item['quantity'] ?? 0);
        }

        return $count;
    }
}

if (!function_exists('cart_total')) {
    function cart_total(): float
    {
        ensure_cart_session();

        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total += ((float) ($item['price'] ?? 0)) * ((int) ($item['quantity'] ?? 0));
        }

        return $total;
    }
}

if (!function_exists('cart_items')) {
    function cart_items(): array
    {
        ensure_cart_session();
        return array_values($_SESSION['cart']);
    }
}

if (!function_exists('flash_set')) {
    function flash_set(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }
}

if (!function_exists('flash_get')) {
    function flash_get(string $key, mixed $default = null): mixed
    {
        if (!isset($_SESSION['_flash'][$key])) {
            return $default;
        }

        $value = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['_old'][$key] ?? $default;
    }
}

if (!function_exists('set_old_input')) {
    function set_old_input(array $data): void
    {
        $_SESSION['_old'] = $data;
    }
}

if (!function_exists('clear_old_input')) {
    function clear_old_input(): void
    {
        unset($_SESSION['_old']);
    }
}

if (!function_exists('generate_order_code')) {
    function generate_order_code(): string
    {
        return 'LM' . date('ymdHis') . random_int(100, 999);
    }
}
