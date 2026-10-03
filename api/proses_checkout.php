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
| SESSION TIMEOUT
|--------------------------------------------------------------------------
*/
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 1800) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

$_SESSION['last_activity'] = time();

/*
|--------------------------------------------------------------------------
| CART CHECK
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['keranjang']) || empty($_SESSION['keranjang'])) {
    header("Location: katalog.php");
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

/*
|--------------------------------------------------------------------------
| SAFE ALERT
|--------------------------------------------------------------------------
*/
function showAlert($title, $text, $icon, $redirect = 'katalog.php')
{
    $title = json_encode($title);
    $text = json_encode($text);
    $icon = json_encode($icon);
    $redirect = json_encode($redirect);

    echo "
    <!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <title>Processing...</title>
        <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <style>
            body {
                background-color: #F5E6CA;
                font-family: 'Quicksand', sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
        </style>
    </head>
    <body>
    <script>
        Swal.fire({
            title: $title,
            html: $text,
            icon: $icon,
            background: '#FFFDF9',
            color: '#5C3A21',
            confirmButtonColor: '#5C3A21'
        }).then(() => {
            window.location.href = $redirect;
        });
    </script>
    </body>
    </html>";
    exit;
}

/*
|--------------------------------------------------------------------------
| METHOD CHECK
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: katalog.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| CSRF VALIDATION
|--------------------------------------------------------------------------
*/
if (
    !isset($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    showAlert(
        'Request Tidak Valid ⚠️',
        'Token keamanan tidak cocok.',
        'error',
        'checkout.php'
    );
}

/*
|--------------------------------------------------------------------------
| INPUT
|--------------------------------------------------------------------------
*/
$nama_pelanggan = trim($_POST['nama_pelanggan'] ?? '');
$nomor_meja = trim($_POST['nomor_meja'] ?? '');
$nomor_wa = trim($_POST['nomor_wa'] ?? '');
$metode_bayar = strtoupper(trim($_POST['metode_bayar'] ?? 'CASH'));


/*
|--------------------------------------------------------------------------
| VALIDATION
|--------------------------------------------------------------------------
*/
if (empty($nama_pelanggan) || empty($nomor_meja)) {
    showAlert(
        'Data Tidak Lengkap!',
        'Nama pelanggan dan nomor meja wajib diisi.',
        'warning',
        'checkout.php'
    );
}

if (!preg_match('/^[0-9]{10,15}$/', $nomor_wa)) {

    showAlert(
        'Nomor WhatsApp Tidak Valid!',
        'Masukkan nomor WhatsApp yang benar.',
        'warning',
        'keranjang.php'
    );
}

if (!preg_match('/^[a-zA-Z0-9\s\.\-]{3,100}$/u', $nama_pelanggan)) {
    showAlert(
        'Nama Tidak Valid!',
        'Nama pelanggan mengandung karakter yang tidak diperbolehkan.',
        'warning',
        'checkout.php'
    );
}

if (!preg_match('/^[a-zA-Z0-9\-]{1,20}$/', $nomor_meja)) {
    showAlert(
        'Nomor Meja Invalid!',
        'Nomor meja hanya boleh huruf, angka, atau strip.',
        'warning',
        'checkout.php'
    );
}
if ($metode_bayar === 'QRIS') {
    $status_pesanan = 'Menunggu Pembayaran';
    $status_bayar = 'MENUNGGU PEMBAYARAN';
} else {
    $status_pesanan = 'Pending';
    $status_bayar = 'BELUM BAYAR';
}
/*
|--------------------------------------------------------------------------
| START TRANSACTION
|--------------------------------------------------------------------------
*/
mysqli_begin_transaction($conn);

try {
    $total_bayar = 0;
    $detail_items = [];

    /*
    |--------------------------------------------------------------------------
    | VALIDATE CART + LOCK PRODUCTS
    |--------------------------------------------------------------------------
    */
    foreach ($_SESSION['keranjang'] as $id_produk => $jumlah) {

        $id_produk = intval($id_produk);
        $jumlah = intval($jumlah);

        if ($id_produk <= 0 || $jumlah <= 0) {
            throw new Exception("Data keranjang tidak valid.");
        }

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_produk, nama_produk, harga, stok
             FROM produk
             WHERE id_produk = ?
             LIMIT 1
             FOR UPDATE"
        );

        if (!$stmt) {
            throw new Exception("Kesalahan sistem.");
        }

        mysqli_stmt_bind_param($stmt, "i", $id_produk);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $produk = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$produk) {
            throw new Exception("Produk tidak ditemukan.");
        }

        if ($produk['stok'] < $jumlah) {
            throw new Exception(
                "Maaf, menu <b>" .
                htmlspecialchars($produk['nama_produk']) .
                "</b> hanya tersisa <b>" .
                intval($produk['stok']) .
                " cup</b>."
            );
        }

        $subtotal = intval($produk['harga']) * $jumlah;
        $total_bayar += $subtotal;

        $detail_items[] = [
            'id_produk' => $id_produk,
            'jumlah' => $jumlah,
            'subtotal' => $subtotal
        ];
    }
    /*
|--------------------------------------------------------------------------
| INSERT ORDER
|--------------------------------------------------------------------------
*/
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO pesanan_pelanggan
(
    nama_pelanggan,
    nomor_wa,
    nomor_meja,
    total_bayar,
    status_pesanan,
    metode_bayar,
    status_bayar
)
VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        throw new Exception("Gagal membuat pesanan.");
    }

    mysqli_stmt_bind_param(
        $stmt,
        "sssisss",
        $nama_pelanggan,
        $nomor_wa,
        $nomor_meja,
        $total_bayar,
        $status_pesanan,
        $metode_bayar,
        $status_bayar
    );

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        throw new Exception("Gagal membuat pesanan.");
    }

    $id_pesanan_baru = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    /*
    |--------------------------------------------------------------------------
    | INSERT DETAIL + UPDATE STOCK
    |--------------------------------------------------------------------------
    */
    foreach ($detail_items as $item) {

        $id_produk = $item['id_produk'];
        $jumlah = $item['jumlah'];
        $subtotal = $item['subtotal'];

        /*
        |--------------------------------------------------------------------------
        | INSERT DETAIL
        |--------------------------------------------------------------------------
        */
        $stmt = mysqli_prepare(
            $conn,
            "INSERT INTO detail_pesanan_pelanggan
            (id_pesanan, id_produk, jumlah, subtotal)
            VALUES (?, ?, ?, ?)"
        );

        if (!$stmt) {
            throw new Exception("Gagal menyimpan detail pesanan.");
        }

        mysqli_stmt_bind_param(
            $stmt,
            "iiii",
            $id_pesanan_baru,
            $id_produk,
            $jumlah,
            $subtotal
        );

        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            throw new Exception("Gagal menyimpan detail pesanan.");
        }

        mysqli_stmt_close($stmt);

        /*
        |--------------------------------------------------------------------------
        | UPDATE STOCK
        |--------------------------------------------------------------------------
        */
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE produk
             SET stok = stok - ?
             WHERE id_produk = ?"
        );

        if (!$stmt) {
            throw new Exception("Gagal update stok.");
        }

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $jumlah,
            $id_produk
        );

        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            throw new Exception("Gagal update stok.");
        }

        mysqli_stmt_close($stmt);
    }

    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */
    mysqli_commit($conn);

    unset($_SESSION['keranjang']);

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    if ($metode_bayar === 'QRIS') {
        header("Location: qris.php?id=" . $id_pesanan_baru);
    } else {
        header("Location: katalog.php?status=checkout_sukses");
    }
    exit;

} catch (Exception $e) {

    mysqli_rollback($conn);

    showAlert(
        'Checkout Gagal! 😢',
        htmlspecialchars($e->getMessage()),
        'error',
        'keranjang.php'
    );
}
?>