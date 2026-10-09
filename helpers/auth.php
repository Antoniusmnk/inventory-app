<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('check_login')) {
    /**
     * Memeriksa apakah $_SESSION['user'] sudah ada.
     * Jika belum, redirect pengguna ke halaman login.
     *
     * @return void
     */
    function check_login(): void
    {
        if (!isset($_SESSION['user'])) {
            header('Location: /inventory-app/public/login.php');
            exit;
        }
    }
}

if (!function_exists('check_admin')) {
    /**
     * Memeriksa apakah pengguna sudah login dan memiliki role 'admin'.
     * Jika bukan admin, kirim status code 403 (Forbidden) dan hentikan skrip.
     *
     * @return void
     */
    function check_admin(): void
    {
        check_login();

        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo "Akses ditolak. Halaman khusus Admin.";
            exit;
        }
    }
}
