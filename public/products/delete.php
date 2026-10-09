<?php
require_once __DIR__ . '/../../helpers/auth.php';
check_admin();

require_once __DIR__ . '/../../config/database.php';

$productId = $_REQUEST['id'] ?? '';

if (empty($productId) || !filter_var($productId, FILTER_VALIDATE_INT) || (int)$productId <= 0) {
    $_SESSION['flash_error'] = 'ID produk tidak valid.';
    header('Location: /inventory-app/public/products/index.php');
    exit;
}

$productId = (int)$productId;

try {
    // Cek apakah produk memiliki riwayat transaksi di tabel stock_movements
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM stock_movements WHERE product_id = :product_id");
    $stmtCheck->execute([':product_id' => $productId]);
    $movementCount = (int)$stmtCheck->fetchColumn();

    if ($movementCount > 0) {
        $_SESSION['flash_error'] = 'Produk tidak dapat dihapus karena memiliki riwayat transaksi stok!';
    } else {
        // Hapus produk dari tabel products jika tidak ada riwayat transaksi
        $stmtDelete = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $stmtDelete->execute([':id' => $productId]);

        if ($stmtDelete->rowCount() > 0) {
            $_SESSION['flash_success'] = 'Produk berhasil dihapus!';
        } else {
            $_SESSION['flash_error'] = 'Produk gagal dihapus atau tidak ditemukan.';
        }
    }
} catch (PDOException $e) {
    $_SESSION['flash_error'] = 'Terjadi kesalahan sistem: ' . $e->getMessage();
}

header('Location: /inventory-app/public/products/index.php');
exit;
