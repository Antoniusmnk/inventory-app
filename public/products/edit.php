<?php
require_once __DIR__ . '/../../helpers/auth.php';
check_admin();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/validation.php';

$productId = $_GET['id'] ?? '';

if (empty($productId) || !validate_int($productId)) {
    header('Location: /inventory-app/public/products/index.php');
    exit;
}

$productId = (int)$productId;

// Ambil data produk berdasarkan ID
$stmtProduct = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$stmtProduct->execute([':id' => $productId]);
$product = $stmtProduct->fetch();

if (!$product) {
    $_SESSION['flash_error'] = 'Produk tidak ditemukan.';
    header('Location: /inventory-app/public/products/index.php');
    exit;
}

// Ambil seluruh data kategori
$stmtCat = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmtCat->fetchAll();

$errors = [];

// Pre-fill form values
$sku           = $_POST['sku'] ?? $product['sku'];
$name          = $_POST['name'] ?? $product['name'];
$categoryId   = $_POST['category_id'] ?? $product['category_id'];
$description   = $_POST['description'] ?? ($product['description'] ?? '');
$price         = $_POST['price'] ?? $product['price'];
$stock         = $_POST['stock'] ?? $product['stock'];
$minimumStock = $_POST['minimum_stock'] ?? $product['minimum_stock'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku           = trim($_POST['sku'] ?? '');
    $name          = trim($_POST['name'] ?? '');
    $categoryId   = trim($_POST['category_id'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $price         = trim($_POST['price'] ?? '');
    $stock         = trim($_POST['stock'] ?? '0');
    $minimumStock = trim($_POST['minimum_stock'] ?? '5');

    // Validasi Field Wajib & Tipe Data
    if (empty($sku)) {
        $errors[] = 'SKU wajib diisi.';
    }
    if (empty($name)) {
        $errors[] = 'Nama produk wajib diisi.';
    }
    if (empty($categoryId) || !validate_int($categoryId)) {
        $errors[] = 'Kategori produk wajib dipilih.';
    }
    if ($price === '' || !is_numeric($price) || (float)$price < 0) {
        $errors[] = 'Harga harus berupa angka dan tidak boleh negatif.';
    }
    if ($stock === '' || !is_numeric($stock) || (int)$stock < 0) {
        $errors[] = 'Stok harus berupa angka dan tidak boleh negatif.';
    }
    if ($minimumStock !== '' && (!is_numeric($minimumStock) || (int)$minimumStock < 0)) {
        $errors[] = 'Stok minimum harus berupa angka dan tidak boleh negatif.';
    }

    // Cek Keunikan SKU untuk Produk Lain
    if (empty($errors)) {
        $stmtCheckSku = $pdo->prepare("SELECT id FROM products WHERE sku = :sku AND id != :current_id");
        $stmtCheckSku->execute([
            ':sku'        => $sku,
            ':current_id' => $productId
        ]);
        if ($stmtCheckSku->fetch()) {
            $errors[] = 'SKU "' . $sku . '" sudah digunakan oleh produk lain.';
        }
    }

    // Update jika validasi lolos
    if (empty($errors)) {
        try {
            $stmtUpdate = $pdo->prepare("
                UPDATE products 
                SET sku = :sku, 
                    name = :name, 
                    category_id = :category_id, 
                    description = :description, 
                    price = :price, 
                    stock = :stock, 
                    minimum_stock = :minimum_stock 
                WHERE id = :id
            ");

            $stmtUpdate->execute([
                ':sku'           => $sku,
                ':name'          => $name,
                ':category_id'   => (int)$categoryId,
                ':description'   => $description,
                ':price'         => (float)$price,
                ':stock'         => (int)$stock,
                ':minimum_stock' => (int)($minimumStock ?: 5),
                ':id'            => $productId
            ]);

            $_SESSION['flash_success'] = 'Produk "' . $name . '" berhasil diperbarui!';
            header('Location: /inventory-app/public/products/index.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Gagal memperbarui produk: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../views/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <h2>Edit Produk</h2>
    <a href="/inventory-app/public/products/index.php" class="btn btn-secondary">&laquo; Kembali ke Daftar Produk</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul style="margin-left: 1.25rem;">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div style="background: var(--surface-color); padding: 2rem; border-radius: 0.5rem; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.05); max-width: 800px; margin: 0 auto;">
    <form action="" method="POST">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
            <div class="form-group">
                <label for="sku" class="form-label">SKU Produk <span style="color:red;">*</span></label>
                <input type="text" id="sku" name="sku" class="form-control" value="<?= e($sku) ?>" required>
            </div>

            <div class="form-group">
                <label for="name" class="form-label">Nama Produk <span style="color:red;">*</span></label>
                <input type="text" id="name" name="name" class="form-control" value="<?= e($name) ?>" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
            <div class="form-group">
                <label for="category_id" class="form-label">Kategori <span style="color:red;">*</span></label>
                <select id="category_id" name="category_id" class="form-control" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="price" class="form-label">Harga (Rp) <span style="color:red;">*</span></label>
                <input type="number" step="0.01" min="0" id="price" name="price" class="form-control" value="<?= e($price) ?>" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
            <div class="form-group">
                <label for="stock" class="form-label">Stok <span style="color:red;">*</span></label>
                <input type="number" min="0" id="stock" name="stock" class="form-control" value="<?= e($stock) ?>" required>
            </div>

            <div class="form-group">
                <label for="minimum_stock" class="form-label">Stok Minimum</label>
                <input type="number" min="0" id="minimum_stock" name="minimum_stock" class="form-control" value="<?= e($minimumStock) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="description" class="form-label">Deskripsi Produk</label>
            <textarea id="description" name="description" class="form-control" rows="4"><?= e($description) ?></textarea>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1rem;">
            <a href="/inventory-app/public/products/index.php" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</div>

<?php
require_once __DIR__ . '/../../views/footer.php';
