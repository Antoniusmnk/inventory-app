<?php
require_once __DIR__ . '/../../helpers/auth.php';
check_admin();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/escape.php';

$success = '';
$error   = '';

// Tangani Form Tambah Kategori via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = 'Nama kategori wajib diisi.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
            $stmt->execute([
                ':name'        => $name,
                ':description' => $description
            ]);
            $success = 'Kategori berhasil ditambahkan!';
        } catch (PDOException $e) {
            $error = 'Gagal menambahkan kategori: ' . $e->getMessage();
        }
    }
}

// Ambil seluruh data kategori dari tabel categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $stmt->fetchAll();

require_once __DIR__ . '/../../views/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h2>Manajemen Kategori</h2>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <?= e($success) ?>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
    <!-- Form Tambah Kategori -->
    <div style="background: var(--surface-color); padding: 1.5rem; border-radius: 0.5rem; border: 1px solid var(--border-color); height: fit-content;">
        <h3 style="margin-bottom: 1rem; color: var(--primary-color);">Tambah Kategori Baru</h3>
        <form action="" method="POST">
            <div class="form-group">
                <label for="name" class="form-label">Nama Kategori <span style="color:red;">*</span></label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Contoh: Elektronik" required>
            </div>

            <div class="form-group">
                <label for="description" class="form-label">Deskripsi</label>
                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Keterangan singkat kategori..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Simpan Kategori</button>
        </form>
    </div>

    <!-- Tabel Daftar Kategori -->
    <div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 60px;">No</th>
                        <th>Nama Kategori</th>
                        <th>Deskripsi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">Belum ada data kategori.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $index => $cat): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><strong><?= e($cat['name']) ?></strong></td>
                                <td><?= e($cat['description'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../views/footer.php';
