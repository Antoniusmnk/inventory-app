<?php

if (!function_exists('validate_int')) {
    /**
     * Memvalidasi angka bulat (integer).
     *
     * @param mixed $value
     * @return bool
     */
    function validate_int($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
}

if (!function_exists('validate_float')) {
    /**
     * Memvalidasi angka desimal (float).
     *
     * @param mixed $value
     * @return bool
     */
    function validate_float($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
    }
}

if (!function_exists('is_positive')) {
    /**
     * Memastikan nilai bernilai lebih besar dari 0 (tidak boleh negatif atau nol).
     *
     * @param mixed $value
     * @return bool
     */
    function is_positive($value): bool
    {
        return is_numeric($value) && $value > 0;
    }
}
