<?php
require_once __DIR__ . '/../helpers/auth.php';
check_login();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/escape.php';

// Ambil statistik inventaris
$totalCategories = (int) $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$totalProducts   = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalSuppliers  = (int) $pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
$lowStockCount   = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE stock <= minimum_stock")->fetchColumn();

// Ambil 5 produk dengan stok paling sedikit di bawah/sama dengan minimum stock
$stmtLowStock = $pdo->query("SELECT * FROM products WHERE stock <= minimum_stock ORDER BY stock ASC LIMIT 5");
$lowStockProducts = $stmtLowStock->fetchAll();

require_once __DIR__ . '/../views/header.php';
?>

<div style="margin-bottom: 2rem;">
    <h2 style="font-size: 1.75rem; margin-bottom: 0.5rem; color: var(--primary-color);">
        Selamat Datang, <?= e($_SESSION['user']['username'] ?? 'User') ?>!
    </h2>
    <p style="color: var(--text-muted);">
        Anda login sebagai <strong><?= e(strtoupper($_SESSION['user']['role'] ?? 'staff')) ?></strong>. Berikut adalah ringkasan inventaris sistem.
    </p>
</div>

<!-- Kartu Statistik -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
    <div style="background: var(--surface-color); padding: 1.5rem; border-radius: 0.5rem; border: 1px solid var(--border-color); border-left: 4px solid #3b82f6; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="font-size: 0.875rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Kategori</div>
        <div style="font-size: 2rem; font-weight: 700; color: var(--text-color); margin-top: 0.5rem;"><?= $totalCategories ?></div>
    </div>

    <div style="background: var(--surface-color); padding: 1.5rem; border-radius: 0.5rem; border: 1px solid var(--border-color); border-left: 4px solid #10b981; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="font-size: 0.875rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Produk</div>
        <div style="font-size: 2rem; font-weight: 700; color: var(--text-color); margin-top: 0.5rem;"><?= $totalProducts ?></div>
    </div>

    <div style="background: var(--surface-color); padding: 1.5rem; border-radius: 0.5rem; border: 1px solid var(--border-color); border-left: 4px solid #8b5cf6; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="font-size: 0.875rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Supplier</div>
        <div style="font-size: 2rem; font-weight: 700; color: var(--text-color); margin-top: 0.5rem;"><?= $totalSuppliers ?></div>
    </div>

    <div style="background: var(--surface-color); padding: 1.5rem; border-radius: 0.5rem; border: 1px solid var(--border-color); border-left: 4px solid #ef4444; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="font-size: 0.875rem; color: #ef4444; font-weight: 600; text-transform: uppercase;">Stok Menipis</div>
        <div style="font-size: 2rem; font-weight: 700; color: #ef4444; margin-top: 0.5rem;"><?= $lowStockCount ?></div>
    </div>
</div>

<!-- Tabel Peringatan Produk Stok Menipis -->
<div style="background: var(--surface-color); padding: 1.5rem; border-radius: 0.5rem; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 style="color: var(--text-color); font-size: 1.25rem;">⚠️ Perhatian: Produk Stok Menipis</h3>
        <a href="/inventory-app/public/products/index.php" class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">Lihat Semua Produk</a>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>SKU</th>
                    <th>Nama Produk</th>
                    <th>Stok Saat Ini</th>
                    <th>Minimum Stok</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lowStockProducts)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--success-color); background-color: var(--success-bg); padding: 1rem;">
                            ✅ Semua stok produk dalam kondisi aman.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($lowStockProducts as $index => $item): ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><code><?= e($item['sku']) ?></code></td>
                            <td><strong><?= e($item['name']) ?></strong></td>
                            <td style="color: #ef4444; font-weight: 700;"><?= (int)$item['stock'] ?></td>
                            <td><?= (int)$item['minimum_stock'] ?></td>
                            <td>
                                <span style="background-color: var(--danger-bg); color: var(--danger-color); font-size: 0.75rem; font-weight: 700; padding: 0.2rem 0.5rem; border-radius: 0.25rem;">
                                    Perlu Restock
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/../views/footer.php';
