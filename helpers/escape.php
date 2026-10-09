<?php

if (!function_exists('e')) {
    /**
     * Escape data dari XSS (Cross-Site Scripting).
     *
     * @param mixed $value
     * @return string
     */
    function e($value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}
