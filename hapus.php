<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

session_start();
include 'config/koneksi.php';

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
            text: 'Hanya admin yang boleh menghapus menu.',
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
| METHOD CHECK
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin/tampil.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| CSRF CHECK
|--------------------------------------------------------------------------
*/
if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        Swal.fire({
            title: 'Request Tidak Valid ⚠️',
            text: 'Token keamanan tidak cocok.',
            icon: 'error',
            confirmButtonColor: '#5C3A21',
            background: '#FFFDF9',
            color: '#5C3A21'
        }).then(() => {
            window.location.href = '../admin/tampil.php';
        });
    </script>";
    exit;
}

/*
|--------------------------------------------------------------------------
| INPUT VALIDATION
|--------------------------------------------------------------------------
*/
$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    header("Location: ../admin/tampil.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| GET PRODUCT DATA
|--------------------------------------------------------------------------
*/
$stmt = mysqli_prepare(
    $conn,
    "SELECT nama_produk, foto
     FROM produk
     WHERE id_produk = ?
     LIMIT 1"
);

if (!$stmt) {
    header("Location: ../admin/tampil.php");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    mysqli_stmt_close($stmt);
    header("Location: ../admin/tampil.php");
    exit;
}

$data = mysqli_fetch_assoc($result);
$nama = $data['nama_produk'];
$foto_lama = $data['foto'];

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| DELETE PRODUCT
|--------------------------------------------------------------------------
*/
$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM produk WHERE id_produk = ?"
);

if (!$stmt) {
    header("Location: ../admin/tampil.php");
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {

    /*
    |--------------------------------------------------------------------------
    | DELETE IMAGE AFTER DB SUCCESS
    |--------------------------------------------------------------------------
    */
    if (
        !empty($foto_lama) &&
        $foto_lama !== 'default.png'
    ) {
        $path_foto = __DIR__ . "/img/" . basename($foto_lama);

        if (file_exists($path_foto)) {
            unlink($path_foto);
        }
    }

    mysqli_stmt_close($stmt);

    header(
        "Location: ../admin/tampil.php?status=terhapus&menu=" .
        urlencode($nama)
    );
    exit;
}

mysqli_stmt_close($stmt);

header("Location: ../admin/tampil.php");
exit;
?>