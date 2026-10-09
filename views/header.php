<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../helpers/escape.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System</title>
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --bg-color: #f8fafc;
            --surface-color: #ffffff;
            --text-color: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --success-bg: #dcfce7;
            --success-color: #166534;
            --danger-bg: #fee2e2;
            --danger-color: #991b1b;
            --warning-bg: #fef3c7;
            --warning-color: #92400e;
            --admin-badge-bg: #dbeafe;
            --admin-badge-color: #1e40af;
            --staff-badge-bg: #f3e8ff;
            --staff-badge-color: #6b21a8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            line-height: 1.5;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Navbar Styling */
        .navbar {
            background-color: var(--surface-color);
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0.75rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .brand {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            list-style: none;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text-muted);
            font-weight: 500;
            transition: color 0.2s;
            padding: 0.5rem 0;
        }

        .nav-link:hover {
            color: var(--primary-color);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
        }

        .role-admin {
            background-color: var(--admin-badge-bg);
            color: var(--admin-badge-color);
        }

        .role-staff {
            background-color: var(--staff-badge-bg);
            color: var(--staff-badge-color);
        }

        .btn-logout {
            text-decoration: none;
            color: #ef4444;
            font-weight: 600;
            font-size: 0.875rem;
            padding: 0.4rem 0.8rem;
            border: 1px solid #fca5a5;
            border-radius: 0.375rem;
            transition: all 0.2s;
        }

        .btn-logout:hover {
            background-color: #fee2e2;
        }

        /* Container Layout */
        .main-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            flex: 1;
            width: 100%;
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background-color: var(--success-bg);
            color: var(--success-color);
            border: 1px solid #bbf7d0;
        }

        .alert-danger {
            background-color: var(--danger-bg);
            color: var(--danger-color);
            border: 1px solid #fecaca;
        }

        .alert-warning {
            background-color: var(--warning-bg);
            color: var(--warning-color);
            border: 1px solid #fef08a;
        }

        /* Tables */
        .table-responsive {
            overflow-x: auto;
            background: var(--surface-color);
            border-radius: 0.5rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .table th, .table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .table th {
            background-color: #f1f5f9;
            font-weight: 600;
            color: var(--text-color);
        }

        .table tr:hover {
            background-color: #f8fafc;
        }

        /* Forms & Buttons */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.375rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-control {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .btn {
            display: inline-block;
            padding: 0.625rem 1.25rem;
            font-weight: 600;
            font-size: 0.875rem;
            border-radius: 0.375rem;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: background-color 0.2s, transform 0.1s;
        }

        .btn:active {
            transform: scale(0.98);
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
        }

        .btn-secondary {
            background-color: #64748b;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #475569;
        }

        .btn-danger {
            background-color: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background-color: #dc2626;
        }

        /* Footer Styling */
        footer {
            margin-top: auto;
            background-color: var(--surface-color);
            border-top: 1px solid var(--border-color);
            padding: 1.5rem 0;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.875rem;
        }
    </style>
</head>
<body>

<?php if (isset($_SESSION['user'])): ?>
<nav class="navbar">
    <div class="nav-container">
        <a href="/inventory-app/public/dashboard.php" class="brand">
            📦 Inventory System
        </a>

        <ul class="nav-menu">
            <li><a href="/inventory-app/public/dashboard.php" class="nav-link">Dashboard</a></li>
            <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
                <li><a href="/inventory-app/public/categories/index.php" class="nav-link">Kategori</a></li>
            <?php endif; ?>
            <li><a href="/inventory-app/public/products/index.php" class="nav-link">Produk</a></li>
            <li><a href="/inventory-app/public/stock/movement.php" class="nav-link">Transaksi Stok</a></li>
        </ul>

        <div class="user-info">
            <span class="user-badge <?= ($_SESSION['user']['role'] ?? '') === 'admin' ? 'role-admin' : 'role-staff' ?>">
                👤 <?= e($_SESSION['user']['username'] ?? 'User') ?> (<?= e(strtoupper($_SESSION['user']['role'] ?? 'staff')) ?>)
            </span>
            <a href="/inventory-app/public/logout.php" class="btn-logout">Logout</a>
        </div>
    </div>
</nav>
<?php endif; ?>

<main class="main-container">
