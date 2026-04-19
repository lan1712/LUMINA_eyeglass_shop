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

if (!function_exists('cart_count')) {
    function cart_count(): int
    {
        if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            return 0;
        }

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
        if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            return 0;
        }

        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total += ((float) ($item['price'] ?? 0)) * ((int) ($item['quantity'] ?? 0));
        }

        return $total;
    }
}
