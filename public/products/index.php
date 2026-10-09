<?php
require_once __DIR__ . '/../../helpers/auth.php';
check_login();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/validation.php';

// Ambil parameter GET untuk pencarian, filter, dan pagination
$search     = trim($_GET['search'] ?? '');
$categoryId = trim($_GET['category_id'] ?? '');
$page       = isset($_GET['page']) && validate_int($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;

$limit  = 10;
$offset = ($page - 1) * $limit;

// Ambil daftar kategori untuk dropdown filter
$stmtCategories = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories     = $stmtCategories->fetchAll();

// Menyusun SQL klausa WHERE & parameter filter
$whereClauses = [];
$params       = [];

if (!empty($search)) {
    $whereClauses[] = "(p.name LIKE :search OR p.sku LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if (!empty($categoryId) && validate_int($categoryId)) {
    $whereClauses[] = "p.category_id = :category_id";
    $params[':category_id'] = (int)$categoryId;
}

$whereSql = '';
if (count($whereClauses) > 0) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Hitung total record terfilter untuk pagination
$countSql = "SELECT COUNT(*) FROM products p $whereSql";
$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalRecords = (int)$stmtCount->fetchColumn();
$totalPages   = ceil($totalRecords / $limit);
if ($totalPages < 1) {
    $totalPages = 1;
}

// Ambil data produk terfilter & paginated
$sql = "SELECT p.*, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        $whereSql 
        ORDER BY p.id DESC 
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$products = $stmt->fetchAll();

$isAdmin = ($_SESSION['user']['role'] ?? '') === 'admin';

require_once __DIR__ . '/../../views/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <h2>Daftar Produk</h2>
    <?php if ($isAdmin): ?>
        <a href="/inventory-app/public/products/create.php" class="btn btn-primary">+ Tambah Produk Baru</a>
    <?php endif; ?>
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
            <label for="search" class="form-label" style="font-size: 0.875rem;">Cari Nama / SKU</label>
            <input type="text" id="search" name="search" class="form-control" placeholder="Ketik SKU atau Nama..." value="<?= e($search) ?>">
        </div>

        <div style="flex: 1; min-width: 180px;">
            <label for="category_id" class="form-label" style="font-size: 0.875rem;">Filter Kategori</label>
            <select id="category_id" name="category_id" class="form-control">
                <option value="">-- Semua Kategori --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem;">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="/inventory-app/public/products/index.php" class="btn btn-secondary">Reset</a>
        </div>
    </form>
</div>

<!-- Tabel Daftar Produk -->
<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th style="width: 50px;">No</th>
                <th>SKU</th>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Harga</th>
                <th>Stok</th>
                <th>Min. Stok</th>
                <th>Status</th>
                <?php if ($isAdmin): ?>
                    <th style="text-align: center; width: 140px;">Aksi</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="<?= $isAdmin ? 9 : 8 ?>" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                        Tidak ada data produk yang ditemukan.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $index => $item): ?>
                    <?php $isLowStock = (int)$item['stock'] <= (int)$item['minimum_stock']; ?>
                    <tr>
                        <td><?= $offset + $index + 1 ?></td>
                        <td><code><?= e($item['sku']) ?></code></td>
                        <td><strong><?= e($item['name']) ?></strong></td>
                        <td><?= e($item['category_name'] ?: 'Tidak ada') ?></td>
                        <td>Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                        <td style="<?= $isLowStock ? 'color: #ef4444; font-weight: 700;' : '' ?>">
                            <?= (int)$item['stock'] ?>
                        </td>
                        <td><?= (int)$item['minimum_stock'] ?></td>
                        <td>
                            <?php if ($isLowStock): ?>
                                <span style="background-color: var(--danger-bg); color: var(--danger-color); font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.5rem; border-radius: 0.25rem;">
                                    Stok Menipis
                                </span>
                            <?php else: ?>
                                <span style="background-color: var(--success-bg); color: var(--success-color); font-size: 0.75rem; font-weight: 600; padding: 0.25rem 0.5rem; border-radius: 0.25rem;">
                                    Aman
                                </span>
                            <?php endif; ?>
                        </td>
                        <?php if ($isAdmin): ?>
                            <td style="text-align: center;">
                                <a href="/inventory-app/public/products/edit.php?id=<?= $item['id'] ?>" class="btn btn-secondary" style="font-size: 0.75rem; padding: 0.25rem 0.5rem; margin-right: 0.25rem;">Edit</a>
                                <a href="/inventory-app/public/products/delete.php?id=<?= $item['id'] ?>" class="btn btn-danger" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">Hapus</a>
                            </td>
                        <?php endif; ?>
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
