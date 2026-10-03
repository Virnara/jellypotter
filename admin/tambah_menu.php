<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

session_start();
include '../koneksi.php';

/*
|--------------------------------------------------------------------------
| AUTH CHECK
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['id_pegawai'])) {
    header("Location: ../auth/login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| SESSION TIMEOUT
|--------------------------------------------------------------------------
*/
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 1800) {
    session_unset();
    session_destroy();
    header("Location: ../auth/login.php");
    exit;
}

$_SESSION['last_activity'] = time();

/*
|--------------------------------------------------------------------------
| ADMIN ONLY
|--------------------------------------------------------------------------
*/
if (strtolower($_SESSION['jabatan'] ?? '') !== 'admin') {
    header("Location: ../admin/dashboard.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$alert_status = '';
$alert_message = '';

if (isset($_POST['simpan'])) {

    /*
    |--------------------------------------------------------------------------
    | CSRF VALIDATION
    |--------------------------------------------------------------------------
    */
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $alert_status = 'error';
        $alert_message = 'Permintaan tidak valid.';
    }

    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $harga = intval($_POST['harga'] ?? 0);
    $stok = intval($_POST['stok'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */
    if (empty($alert_status)) {

        if (
            empty($nama_produk) ||
            empty($deskripsi) ||
            empty($kategori)
        ) {
            $alert_status = 'error';
            $alert_message = 'Semua field wajib diisi.';
        } elseif (!preg_match('/^[a-zA-Z0-9\s\-\&]{3,100}$/u', $nama_produk)) {
            $alert_status = 'error';
            $alert_message = 'Nama menu tidak valid.';
        } elseif (strlen($deskripsi) < 5 || strlen($deskripsi) > 1000) {
            $alert_status = 'error';
            $alert_message = 'Deskripsi tidak valid.';
        } elseif (!in_array($kategori, ['Boba', 'Coffee', 'Snacks'])) {
            $alert_status = 'error';
            $alert_message = 'Kategori tidak valid.';
        } elseif ($harga <= 0 || $stok < 0) {
            $alert_status = 'error';
            $alert_message = 'Harga atau stok tidak valid.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | FILE UPLOAD
    |--------------------------------------------------------------------------
    */
    $foto_final = 'default.png';
    $uploaded_path = '';

    if (
        empty($alert_status) &&
        isset($_FILES['foto']) &&
        !empty($_FILES['foto']['name'])
    ) {
        if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            $alert_status = 'error';
            $alert_message = 'Upload foto gagal.';
        } else {

            $max_size = 10 * 1024 * 1024; // 10MB

            if ($_FILES['foto']['size'] > $max_size) {
                $alert_status = 'error';
                $alert_message = 'Ukuran foto terlalu besar (maks 10MB).';
            } else {

                $tmp_foto = $_FILES['foto']['tmp_name'];

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $tmp_foto);
                finfo_close($finfo);

                $allowed_mimes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png'
                ];

                if (!array_key_exists($mime, $allowed_mimes)) {
                    $alert_status = 'error';
                    $alert_message = 'Format foto harus JPG atau PNG.';
                } else {

                    $ext = $allowed_mimes[$mime];
                    $foto_final = bin2hex(random_bytes(16)) . '.' . $ext;
                    $uploaded_path = __DIR__ . '/img/' . $foto_final;

                    if (!move_uploaded_file($tmp_foto, $uploaded_path)) {
                        $alert_status = 'error';
                        $alert_message = 'Gagal menyimpan foto.';
                    }
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | INSERT DATABASE
    |--------------------------------------------------------------------------
    */
    if (empty($alert_status)) {

        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO produk
            (nama_produk, kategori, harga, stok, foto, deskripsi)
            VALUES (?, ?, ?, ?, ?, ?)"
        );

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                "ssiiss",
                $nama_produk,
                $kategori,
                $harga,
                $stok,
                $foto_final,
                $deskripsi
            );

            if (mysqli_stmt_execute($stmt)) {
                $alert_status = 'success';
                $alert_message = 'Menu baru berhasil ditambahkan ✨';

                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {

                if (
                    !empty($uploaded_path) &&
                    file_exists($uploaded_path)
                ) {
                    unlink($uploaded_path);
                }

                $alert_status = 'error';
                $alert_message = 'Gagal menyimpan menu.';
            }

            mysqli_stmt_close($stmt);

        } else {
            $alert_status = 'error';
            $alert_message = 'Kesalahan sistem database.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Menu | Jelly Potter ✨</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Quicksand:wght@400;600;700&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --milk-tea: #F5E6CA;
            --boba-brown: #5C3A21;
            --cream: rgba(255, 253, 249, 0.92);
            --accent: #8d735b;
        }

        body {
            font-family: 'Quicksand', sans-serif;
            margin: 0;
            padding: 140px 0 50px;
            color: var(--boba-brown);
        }

        .page-container {
            width: 95%;
            max-width: 1200px;
            margin: 0 auto;
        }

        .hero {
            background: linear-gradient(135deg,
                    rgba(255, 255, 255, 0.85),
                    rgba(245, 230, 202, 0.75));
            backdrop-filter: blur(18px);
            border-radius: 30px;
            padding: 35px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(92, 58, 33, 0.08);

            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .hero h1 {
            margin: 0;
            font-size: 2.2rem;
        }

        .hero p {
            margin-top: 10px;
            color: var(--accent);
            font-weight: 600;
        }

        .hero-badge {
            background: rgba(92, 58, 33, 0.08);
            padding: 14px 22px;
            border-radius: 18px;
            font-weight: 700;
        }

        .form-card {
            background: var(--cream);
            backdrop-filter: blur(18px);
            border-radius: 30px;
            padding: 35px;
            box-shadow: 0 15px 35px rgba(92, 58, 33, 0.08);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }

        .full-width {
            grid-column: span 2;
        }

        label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 15px 16px;
            border-radius: 18px;
            border: 2px solid #F1ECE4;
            font-family: 'Quicksand';
            box-sizing: border-box;
            background: white;
            transition: 0.25s;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: var(--boba-brown);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        input[type="file"] {
            padding: 12px;
        }

        input[type="file"]::file-selector-button {
            background: #f8f3eb;
            border: none;
            padding: 10px 14px;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            margin-right: 10px;
        }

        .action-row {
            display: flex;
            gap: 14px;
            margin-top: 30px;
        }

        .btn-primary {
            flex: 1;
            border: none;
            background: linear-gradient(135deg, #5C3A21, #3e2716);
            color: white;
            padding: 16px;
            border-radius: 18px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.25s;
        }

        .btn-primary.loading {
            pointer-events: none;
            opacity: 0.9;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
        }

        .btn-secondary {
            flex: 1;
            background: white;
            color: var(--accent);
            text-decoration: none;
            padding: 16px;
            border-radius: 18px;
            text-align: center;
            font-weight: 700;
            border: 2px solid #F1ECE4;
        }

        .upload-note {
            margin-top: 8px;
            color: var(--accent);
            font-size: 0.85rem;
        }

        .swal2-container {
            z-index: 999999 !important;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 1.6rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: span 1;
            }

            .action-row {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <?php include '../includes/navbar.php'; ?>

    <div class="page-container">

        <div class="hero">
            <div>
                <h1>Tambah Menu Baru 🍹</h1>
                <p>Tambahkan racikan baru ke command center Jelly Potter</p>
            </div>

            <div class="hero-badge">
                ✨ Menu Management
            </div>
        </div>

        <div class="form-card">
            <form method="POST" enctype="multipart/form-data" id="menuForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                <div class="form-grid">

                    <div>
                        <label>Nama Menu</label>
                        <input type="text" name="nama_produk" placeholder="Contoh: Taro Milk Tea" required>
                    </div>

                    <div>
                        <label>Kategori</label>
                        <select name="kategori" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Boba">Boba Vibes 🧋</option>
                            <option value="Coffee">Caffeine Potion ☕</option>
                            <option value="Snacks">Magic Bites 🍟</option>
                        </select>
                    </div>

                    <div>
                        <label>Harga (Rp)</label>
                        <input type="number" name="harga" required>
                    </div>

                    <div>
                        <label>Stok Awal</label>
                        <input type="number" name="stok" required>
                    </div>

                    <div class="full-width">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" placeholder="Deskripsi menu..." required></textarea>
                    </div>

                    <div class="full-width">
                        <label>Upload Foto</label>
                        <input type="file" name="foto" accept="image/png,image/jpeg,image/jpg">
                        <div class="upload-note">
                            Jika kosong akan memakai gambar default ✨
                        </div>
                    </div>

                </div>

                <div class="action-row">
                    <button type="submit" name="simpan" class="btn-primary" id="saveBtn">
                        💾 Simpan Menu
                    </button>
    
                    <a href="../admin/tampil.php" class="btn-secondary">
                        ← Kembali
                    </a>
                </div>

            </form>
        </div>

    </div>

    <?php if (!empty($alert_status)): ?>
        <script>
            Swal.fire({
                icon: <?= json_encode($alert_status); ?>,
                title: <?= json_encode($alert_message); ?>,
                confirmButtonColor: '#5C3A21',
                background: '#FFFDF9',
                color: '#5C3A21'
            }).then(() => {
                <?php if ($alert_status === 'success'): ?>
                    window.location.href = '../admin/tampil.php';
                <?php endif; ?>
            });
        </script>
    <?php endif; ?>
<script>
document.getElementById('menuForm').addEventListener('submit', function() {
    const btn = document.getElementById('saveBtn');
    btn.classList.add('loading');
    btn.innerHTML = 'Menyimpan menu... ⏳';
});
</script>
</body>

</html>