<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

session_start();
include 'koneksi.php';

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
    echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        Swal.fire({
            title: 'Akses Ditolak 🚫',
            text: 'Hanya admin yang boleh mengedit menu.',
            icon: 'error',
            confirmButtonColor: '#5C3A21',
            background: '#FFFDF9',
            color: '#5C3A21'
        }).then(() => {
            window.location.href = '../admin/dashboard.php';
        });
    </script>";
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

include 'includes/navbar.php';

$status_notif = "";

/*
|--------------------------------------------------------------------------
| GET PRODUCT
|--------------------------------------------------------------------------
*/
$id_produk = intval($_GET['id'] ?? 0);

if ($id_produk <= 0) {
    header("Location: ../admin/tampil.php");
    exit;
}

$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM produk WHERE id_produk = ? LIMIT 1"
);

if (!$stmt) {
    header("Location: ../admin/tampil.php");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id_produk);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$d = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$d) {
    $status_notif = "kosong";
}

/*
|--------------------------------------------------------------------------
| UPDATE PROCESS
|--------------------------------------------------------------------------
*/
if (isset($_POST['update']) && $d) {

    /*
    |--------------------------------------------------------------------------
    | CSRF CHECK
    |--------------------------------------------------------------------------
    */
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $status_notif = "csrf";
    } else {

        $id_update = intval($_POST['id'] ?? 0);
        $nama_baru = trim($_POST['nama'] ?? '');
        $harga_baru = intval($_POST['harga'] ?? 0);
        $stok_baru = intval($_POST['stok'] ?? 0);
        $deskripsi_baru = trim($_POST['deskripsi'] ?? '');

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */
        if (
            $id_update <= 0 ||
            empty($nama_baru) ||
            empty($deskripsi_baru)
        ) {
            $status_notif = "gagal";
        } elseif (!preg_match('/^[a-zA-Z0-9\s\-\&]{3,100}$/u', $nama_baru)) {
            $status_notif = "gagal";
        } elseif (strlen($deskripsi_baru) < 5 || strlen($deskripsi_baru) > 1000) {
            $status_notif = "gagal";
        } elseif ($harga_baru <= 0 || $stok_baru < 0) {
            $status_notif = "gagal";
        } else {

            /*
            |--------------------------------------------------------------------------
            | GET CURRENT PHOTO
            |--------------------------------------------------------------------------
            */
            $stmt = mysqli_prepare(
                $conn,
                "SELECT foto FROM produk WHERE id_produk = ? LIMIT 1"
            );

            mysqli_stmt_bind_param($stmt, "i", $id_update);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $produk = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$produk) {
                $status_notif = "kosong";
            } else {

                $foto_lama = $produk['foto'];
                $foto_final = $foto_lama;
                $uploaded_path = '';
                $replace_photo = false;

                /*
                |--------------------------------------------------------------------------
                | FILE UPLOAD
                |--------------------------------------------------------------------------
                */
                if (
                    isset($_FILES['foto']) &&
                    !empty($_FILES['foto']['name'])
                ) {
                    if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                        $status_notif = "format_salah";
                    } else {

                        $max_size = 10 * 1024 * 1024; // 10MB

                        if ($_FILES['foto']['size'] > $max_size) {
                            $status_notif = "terlalu_besar";
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
                                $status_notif = "format_salah";
                            } else {
                                $ext = $allowed_mimes[$mime];
                                $foto_final = bin2hex(random_bytes(16)) . '.' . $ext;
                                $uploaded_path = __DIR__ . '/img/' . $foto_final;
                                $replace_photo = true;

                                if (!move_uploaded_file($tmp_foto, $uploaded_path)) {
                                    $status_notif = "gagal";
                                }
                            }
                        }
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | UPDATE DATABASE
                |--------------------------------------------------------------------------
                */
                if (empty($status_notif)) {

                    $stmt = mysqli_prepare(
                        $conn,
                        "UPDATE produk
                         SET nama_produk = ?, harga = ?, stok = ?, deskripsi = ?, foto = ?
                         WHERE id_produk = ?"
                    );

                    if ($stmt) {
                        mysqli_stmt_bind_param(
                            $stmt,
                            "siissi",
                            $nama_baru,
                            $harga_baru,
                            $stok_baru,
                            $deskripsi_baru,
                            $foto_final,
                            $id_update
                        );

                        if (mysqli_stmt_execute($stmt)) {
                            $status_notif = "sukses";

                            /*
                            |--------------------------------------------------------------------------
                            | DELETE OLD PHOTO AFTER SUCCESS
                            |--------------------------------------------------------------------------
                            */
                            if (
                                $replace_photo &&
                                !empty($foto_lama) &&
                                $foto_lama !== 'default.png'
                            ) {
                                $old_path = __DIR__ . '/img/' . basename($foto_lama);

                                if (file_exists($old_path)) {
                                    unlink($old_path);
                                }
                            }

                            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                        } else {

                            if (
                                !empty($uploaded_path) &&
                                file_exists($uploaded_path)
                            ) {
                                unlink($uploaded_path);
                            }

                            $status_notif = "gagal";
                        }

                        mysqli_stmt_close($stmt);
                    } else {
                        $status_notif = "gagal";
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Menu | Jelly Potter ✨</title>

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
            --danger: #e74c3c;
        }

        body {
            font-family: 'Quicksand', sans-serif;
            margin: 0;
            padding: 140px 0 50px;
            color: var(--boba-brown);
        }

        .page-container {
            width: 95%;
            max-width: 1100px;
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
            gap: 20px;
            flex-wrap: wrap;
        }

        .hero h1 {
            margin: 0;
            font-size: 2.2rem;
            font-family: 'Playfair Display', serif;
        }

        .hero p {
            margin-top: 10px;
            color: var(--accent);
            font-weight: 600;
        }

        .hero-badge {
            background: rgba(92, 58, 33, 0.08);
            padding: 14px 20px;
            border-radius: 18px;
            font-weight: 700;
        }

        .edit-card {
            background: var(--cream);
            padding: 35px;
            border-radius: 30px;
            box-shadow: 0 12px 30px rgba(92, 58, 33, 0.08);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .full-width {
            grid-column: 1 / -1;
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
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: var(--boba-brown);
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        .preview-box {
            display: flex;
            align-items: center;
            gap: 18px;
            background: white;
            padding: 18px;
            border-radius: 20px;
            border: 2px dashed #e6c8a3;
        }

        .preview-box img {
            width: 85px;
            height: 85px;
            object-fit: cover;
            border-radius: 20px;
            border: 4px solid var(--milk-tea);
        }

        .btn-update {
            width: 100%;
            border: none;
            background: linear-gradient(135deg, #5C3A21, #3e2716);
            color: white;
            padding: 18px;
            border-radius: 20px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: 0.25s ease;
        }

        .btn-update.loading {
            pointer-events: none;
            opacity: 0.9;
        }

        .btn-update:hover {
            transform: translateY(-4px);
        }

        .btn-back {
            display: inline-block;
            margin-top: 15px;
            text-decoration: none;
            color: var(--accent);
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 1.6rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .edit-card {
                padding: 20px;
            }

            .preview-box {
                flex-direction: column;
                text-align: center;
            }
        }

        .swal2-container {
            z-index: 99999 !important;
        }
    </style>
</head>

<body>

    <div class="page-container">

        <div class="hero">
            <div>
                <h1>Edit Menu Jelly Potter ✨</h1>
                <p>Perbarui racikan menu, stok, harga, dan tampilan visual produk</p>
            </div>

            <div class="hero-badge">
                🪄 Product Control Panel
            </div>
        </div>

        <?php if ($status_notif !== "kosong"): ?>
            <div class="edit-card">

                <form method="POST" enctype="multipart/form-data" id="editForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($d['id_produk']); ?>">

                    <div class="form-grid">

                        <div class="form-group">
                            <label>Nama Menu</label>
                            <input type="text" name="nama" value="<?= htmlspecialchars($d['nama_produk']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Harga (Rp)</label>
                            <input type="number" name="harga" value="<?= htmlspecialchars($d['harga']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Stok (Cup)</label>
                            <input type="number" name="stok" value="<?= htmlspecialchars($d['stok']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Upload Foto Baru</label>
                            <input type="file" name="foto" accept="image/png,image/jpeg,image/jpg">
                        </div>

                        <div class="form-group full-width">
                            <label>Deskripsi Menu</label>
                            <textarea name="deskripsi" required><?= htmlspecialchars($d['deskripsi']); ?></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label>Preview Foto Saat Ini</label>

                            <div class="preview-box">
                                <img src="img/<?= !empty($d['foto']) ? htmlspecialchars($d['foto']) : 'default.png'; ?>"
                                    alt="Preview Menu">

                                <div>
                                    <strong>
                                        <?= htmlspecialchars($d['nama_produk']); ?>
                                    </strong>
                                    <p style="margin:8px 0 0; color:#8d735b;">
                                        Upload foto baru jika ingin mengganti tampilan menu ✨
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <button type="submit" name="update" class="btn-update" id="updateBtn">
                                ✨ Simpan Perubahan
                            </button>

                            <a href="../admin/tampil.php" class="btn-back">
                                ← Kembali ke Kelola Menu
                            </a>
                        </div>

                    </div>
                </form>

            </div>
        <?php endif; ?>

    </div>

    <script>
        <?php
        $notif_map = [
            "sukses" => [
                "title" => "Berhasil! ✨",
                "text" => "Menu berhasil diperbarui.",
                "icon" => "success",
                "redirect" => "../admin/tampil.php"
            ],
            "gagal" => [
                "title" => "Oops!",
                "text" => "Gagal memperbarui data menu.",
                "icon" => "error"
            ],
            "format_salah" => [
                "title" => "Format Foto Salah 📷",
                "text" => "Upload hanya JPG / PNG ya.",
                "icon" => "warning"
            ],
            "terlalu_besar" => [
                "title" => "File Terlalu Besar 📦",
                "text" => "Ukuran foto maksimal 10MB.",
                "icon" => "warning"
            ],
            "csrf" => [
                "title" => "Request Tidak Valid ⚠️",
                "text" => "Token keamanan tidak cocok.",
                "icon" => "error"
            ],
            "kosong" => [
                "title" => "Data Tidak Ditemukan 😢",
                "text" => "Menu ini sudah tidak tersedia.",
                "icon" => "warning",
                "redirect" => "../admin/tampil.php"
            ]
        ];

        if (!empty($status_notif) && isset($notif_map[$status_notif])) {
            $notif = $notif_map[$status_notif];
            ?>
            Swal.fire({
                title: <?= json_encode($notif['title']); ?>,
                text: <?= json_encode($notif['text']); ?>,
                icon: <?= json_encode($notif['icon']); ?>,
                confirmButtonColor: '#5C3A21',
                background: '#FFFDF9',
                color: '#5C3A21'
            }).then(() => {
                <?php if (!empty($notif['redirect'])): ?>
                    window.location.href = <?= json_encode($notif['redirect']); ?>;
                <?php endif; ?>
            });
        <?php } ?>

        document.getElementById('editForm')?.addEventListener('submit', function () {
            const btn = document.getElementById('updateBtn');

            if (btn) {
                btn.classList.add('loading');
                btn.innerHTML = 'Menyimpan perubahan... ⏳';
            }
        });
    </script>

</body>

</html>