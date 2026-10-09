<?php
require_once __DIR__ . '/../../helpers/auth.php';
check_login();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/validation.php';

// Ambil parameter GET untuk pencarian, filter tipe, dan pagination
$search = trim($_GET['search'] ?? '');
$type   = strtoupper(trim($_GET['type'] ?? ''));
$page   = isset($_GET['page']) && validate_int($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;

$limit  = 10;
$offset = ($page - 1) * $limit;

// Menyusun Klausa WHERE & parameter filter
$whereClauses = [];
$params       = [];

if (!empty($search)) {
    $whereClauses[] = "(p.name LIKE :search OR p.sku LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($type) && in_array($type, ['IN', 'OUT'], true)) {
    $whereClauses[] = "sm.type = :type";
    $params[':type'] = $type;
}

$whereSql = '';
if (count($whereClauses) > 0) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Hitung total data terfilter untuk pagination
$countSql = "SELECT COUNT(*) 
             FROM stock_movements sm 
             LEFT JOIN products p ON sm.product_id = p.id 
             $whereSql";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalRecords = (int)$stmtCount->fetchColumn();
$totalPages   = ceil($totalRecords / $limit);
if ($totalPages < 1) {
    $totalPages = 1;
}

// Ambil data riwayat transaksi terfilter & paginated
$sql = "SELECT sm.*, p.name AS product_name, p.sku, u.username 
        FROM stock_movements sm 
        LEFT JOIN products p ON sm.product_id = p.id 
        LEFT JOIN users u ON sm.user_id = u.id 
        $whereSql 
        ORDER BY sm.id DESC 
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$movements = $stmt->fetchAll();

require_once __DIR__ . '/../../views/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <h2>Riwayat Transaksi Stok</h2>
    <a href="/inventory-app/public/stock/movement.php" class="btn btn-primary">+ Catat Transaksi Stok</a>
</div>

<?php if (isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success">
        <?= e($_SESSION['flash_success']) ?>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger">
        <?= e($_SESSION['flash_error']) ?>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- Form Filter & Search -->
<div style="background: var(--surface-color); padding: 1.25rem; border-radius: 0.5rem; border: 1px solid var(--border-color); margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
    <form action="" method="GET" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
        <div style="flex: 2; min-width: 200px;">
            <label for="search" class="form-label" style="font-size: 0.875rem;">Cari Produk / SKU</label>
            <input type="text" id="search" name="search" class="form-control" placeholder="Ketik SKU atau Nama Produk..." value="<?= e($search) ?>">
        </div>

        <div style="flex: 1; min-width: 160px;">
            <label for="type" class="form-label" style="font-size: 0.875rem;">Filter Tipe Transaksi</label>
            <select id="type" name="type" class="form-control">
                <option value="">-- Semua Tipe --</option>
                <option value="IN" <?= $type === 'IN' ? 'selected' : '' ?>>📥 Stok Masuk (IN)</option>
                <option value="OUT" <?= $type === 'OUT' ? 'selected' : '' ?>>📤 Stok Keluar (OUT)</option>
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="/inventory-app/public/stock/history.php" class="btn btn-secondary">Reset</a>
        </div>
    </form>
</div>

<!-- Tabel Riwayat Stok -->
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th style="width: 50px;">No</th>
                <th>Tanggal & Waktu</th>
                <th>SKU</th>
                <th>Nama Produk</th>
                <th>Tipe Transaksi</th>
                <th>Jumlah (Qty)</th>
                <th>Catatan / Keterangan</th>
                <th>Dicatat Oleh</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($movements)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                        Tidak ada riwayat transaksi stok yang ditemukan.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($movements as $index => $item): ?>
                    <tr>
                        <td><?= $offset + $index + 1 ?></td>
                        <td style="font-size: 0.875rem; color: var(--text-muted);"><?= e($item['created_at']) ?></td>
                        <td><code><?= e($item['sku'] ?: '-') ?></code></td>
                        <td><strong><?= e($item['product_name'] ?: 'Produk Dihapus') ?></strong></td>
                        <td>
                            <?php if ($item['type'] === 'IN'): ?>
                                <span style="background-color: var(--success-bg); color: var(--success-color); font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.5rem; border-radius: 0.25rem;">
                                    📥 Masuk (IN)
                                </span>
                            <?php else: ?>
                                <span style="background-color: var(--danger-bg); color: var(--danger-color); font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.5rem; border-radius: 0.25rem;">
                                    📤 Keluar (OUT)
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 700; color: <?= $item['type'] === 'IN' ? 'var(--success-color)' : 'var(--danger-color)' ?>;">
                            <?= $item['type'] === 'IN' ? '+' : '-' ?><?= (int)$item['quantity'] ?>
                        </td>
                        <td><?= e($item['notes'] ?: '-') ?></td>
                        <td>👤 <?= e($item['username'] ?: 'System') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Navigasi Pagination -->
<?php if ($totalPages > 1): ?>
    <div style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; margin-top: 1.5rem;">
        <?php $queryParams = $_GET; ?>

        <?php if ($page > 1): ?>
            <?php $queryParams['page'] = $page - 1; ?>
            <a href="?<?= http_build_query($queryParams) ?>" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.875rem;">&laquo; Previous</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php $queryParams['page'] = $i; ?>
            <a href="?<?= http_build_query($queryParams) ?>" class="btn <?= $i === $page ? 'btn-primary' : 'btn-secondary' ?>" style="padding: 0.35rem 0.75rem; font-size: 0.875rem;">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <?php $queryParams['page'] = $page + 1; ?>
            <a href="?<?= http_build_query($queryParams) ?>" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.875rem;">Next &raquo;</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/../../views/footer.php';
