<?php
require_once __DIR__ . '/../../helpers/auth.php';
check_login();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';
require_once __DIR__ . '/../../helpers/validation.php';

// Ambil daftar produk untuk dropdown pilihan produk
$stmtProducts = $pdo->query("SELECT id, sku, name, stock FROM products ORDER BY name ASC");
$products = $stmtProducts->fetchAll();

$errors = [];

// Inisialisasi variabel form untuk re-population
$productId = $_POST['product_id'] ?? '';
$type      = strtoupper($_POST['type'] ?? 'IN');
$quantity  = $_POST['quantity'] ?? '';
$notes     = trim($_POST['notes'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = trim($_POST['product_id'] ?? '');
    $type      = strtoupper(trim($_POST['type'] ?? ''));
    $quantity  = trim($_POST['quantity'] ?? '');
    $notes     = trim($_POST['notes'] ?? '');

    // Validasi Input
    if (empty($productId) || !validate_int($productId)) {
        $errors[] = 'Produk wajib dipilih.';
    }
    if (!in_array($type, ['IN', 'OUT'], true)) {
        $errors[] = 'Tipe transaksi stok tidak valid (harus Stok Masuk atau Stok Keluar).';
    }
    if ($quantity === '' || !validate_int($quantity) || !is_positive($quantity)) {
        $errors[] = 'Jumlah (quantity) harus berupa angka bulat positif lebih besar dari 0.';
    }

    if (empty($errors)) {
        // Ambil stok produk saat ini dari database
        $stmtCheck = $pdo->prepare("SELECT id, name, stock FROM products WHERE id = :id");
        $stmtCheck->execute([':id' => (int)$productId]);
        $targetProduct = $stmtCheck->fetch();

        if (!$targetProduct) {
            $errors[] = 'Produk yang dipilih tidak ditemukan.';
        } else {
            $currentStock = (int)$targetProduct['stock'];
            $qty = (int)$quantity;

            // Cek Aturan Bisnis Stok Keluar
            if ($type === 'OUT' && $qty > $currentStock) {
                $errors[] = "Jumlah barang keluar melebihi stok yang tersedia saat ini! (Stok tersedia: {$currentStock})";
            } else {
                // Eksekusi PDO Transaction
                try {
                    $pdo->beginTransaction();

                    // Hitung stok baru
                    $newStock = ($type === 'IN') ? ($currentStock + $qty) : ($currentStock - $qty);

                    // 1. Update stok di tabel products
                    $stmtUpdateStock = $pdo->prepare("UPDATE products SET stock = :new_stock WHERE id = :id");
                    $stmtUpdateStock->execute([
                        ':new_stock' => $newStock,
                        ':id'        => (int)$productId
                    ]);

                    // 2. Insert riwayat ke tabel stock_movements
                    $stmtInsertMovement = $pdo->prepare("
                        INSERT INTO stock_movements (product_id, user_id, type, quantity, notes)
                        VALUES (:product_id, :user_id, :type, :quantity, :notes)
                    ");
                    $stmtInsertMovement->execute([
                        ':product_id' => (int)$productId,
                        ':user_id'    => (int)$_SESSION['user']['id'],
                        ':type'       => $type,
                        ':quantity'   => $qty,
                        ':notes'      => $notes
                    ]);

                    // Commit transaksi PDO
                    $pdo->commit();

                    $typeText = ($type === 'IN') ? 'Masuk' : 'Keluar';
                    $_SESSION['flash_success'] = "Transaksi stok {$typeText} berhasil disimpan! Stok baru: {$newStock}";
                    header('Location: /inventory-app/public/stock/history.php');
                    exit;
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $errors[] = 'Terjadi kesalahan sistem saat memproses transaksi: ' . $e->getMessage();
                }
            }
        }
    }
}

require_once __DIR__ . '/../../views/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <h2>Input Transaksi Stok</h2>
    <a href="/inventory-app/public/stock/history.php" class="btn btn-secondary">📋 Lihat Riwayat Stok</a>
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

<div style="background: var(--surface-color); padding: 2rem; border-radius: 0.5rem; border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.05); max-width: 700px; margin: 0 auto;">
    <form action="" method="POST">
        <div class="form-group">
            <label for="product_id" class="form-label">Pilih Produk <span style="color:red;">*</span></label>
            <select id="product_id" name="product_id" class="form-control" required autofocus>
                <option value="">-- Pilih Produk --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $productId == $p['id'] ? 'selected' : '' ?>>
                        <?= e($p['name']) ?> (SKU: <?= e($p['sku']) ?> | Stok Saat Ini: <?= (int)$p['stock'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Tipe Transaksi <span style="color:red;">*</span></label>
            <div style="display: flex; gap: 1.5rem; margin-top: 0.5rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="radio" name="type" value="IN" <?= $type === 'IN' ? 'checked' : '' ?> required>
                    <span style="font-weight: 600; color: var(--success-color); background-color: var(--success-bg); padding: 0.25rem 0.75rem; border-radius: 0.25rem;">
                        📥 Stok Masuk (IN)
                    </span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="radio" name="type" value="OUT" <?= $type === 'OUT' ? 'checked' : '' ?>>
                    <span style="font-weight: 600; color: var(--danger-color); background-color: var(--danger-bg); padding: 0.25rem 0.75rem; border-radius: 0.25rem;">
                        📤 Stok Keluar (OUT)
                    </span>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label for="quantity" class="form-label">Jumlah / Quantity <span style="color:red;">*</span></label>
            <input type="number" min="1" id="quantity" name="quantity" class="form-control" placeholder="Masukkan jumlah barang" value="<?= e($quantity) ?>" required>
        </div>

        <div class="form-group">
            <label for="notes" class="form-label">Catatan / Keterangan</label>
            <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Contoh: Pembelian dari supplier / Penjualan toko / Restock barang"><?= e($notes) ?></textarea>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">Simpan Transaksi Stok</button>
        </div>
    </form>
</div>

<?php
require_once __DIR__ . '/../../views/footer.php';
