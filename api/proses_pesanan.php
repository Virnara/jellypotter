<?php
session_start();
include '../koneksi.php';

/*
|--------------------------------------------------------------------------
| SESSION TIMEOUT
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['id_pegawai'])) {
    header("location: login.php");
    exit;
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 1800) {
    session_unset();
    session_destroy();
    header("location: login.php");
    exit;
}
$_SESSION['last_activity'] = time();

/*
|--------------------------------------------------------------------------
| ALERT FUNCTION (SAFE)
|--------------------------------------------------------------------------
*/
function showAlert($title, $text, $icon, $redirect = 'pesanan.php')
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
        <title>Memproses...</title>
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
    header("location: pesanan.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| INPUT
|--------------------------------------------------------------------------
*/
$id_pegawai = intval($_SESSION['id_pegawai']);
$id_pelanggan = intval($_POST['id_pelanggan_final'] ?? 1);
$data_item = $_POST['data_item'] ?? '';
$pakai_poin = isset($_POST['pakai_poin']) && $_POST['pakai_poin'] === 'ya';

/*
|--------------------------------------------------------------------------
| VALIDATE CART
|--------------------------------------------------------------------------
*/
$keranjang = json_decode($data_item, true);

if (
    json_last_error() !== JSON_ERROR_NONE ||
    empty($keranjang) ||
    !is_array($keranjang)
) {
    showAlert(
        'Keranjang Invalid!',
        'Data pesanan rusak atau kosong.',
        'error'
    );
}

/*
|--------------------------------------------------------------------------
| VALIDATE CUSTOMER
|--------------------------------------------------------------------------
*/
$cek_pelanggan = mysqli_query(
    $conn,
    "SELECT id_pelanggan, poin 
     FROM pelanggan 
     WHERE id_pelanggan = '$id_pelanggan'
     LIMIT 1"
);

if (!$cek_pelanggan || mysqli_num_rows($cek_pelanggan) === 0) {
    $id_pelanggan = 1;
}

/*
|--------------------------------------------------------------------------
| START TRANSACTION
|--------------------------------------------------------------------------
*/
mysqli_begin_transaction($conn);

try {
    $total_bayar = 0;
    $total_cup = 0;
    $detail_items = [];

    /*
    |--------------------------------------------------------------------------
    | VALIDATE PRODUCTS + LOCK
    |--------------------------------------------------------------------------
    */
    foreach ($keranjang as $item) {
        $id_produk = intval($item['id'] ?? 0);
        $qty = intval($item['qty'] ?? 0);

        if ($id_produk <= 0 || $qty <= 0) {
            throw new Exception("Data item tidak valid.");
        }

        $q_produk = mysqli_query(
            $conn,
            "SELECT id_produk, nama_produk, harga, stok
             FROM produk
             WHERE id_produk = '$id_produk'
             FOR UPDATE"
        );

        if (!$q_produk || mysqli_num_rows($q_produk) === 0) {
            throw new Exception("Produk tidak ditemukan.");
        }

        $produk = mysqli_fetch_assoc($q_produk);

        if ($produk['stok'] < $qty) {
            throw new Exception("Stok {$produk['nama_produk']} tidak cukup.");
        }

        $subtotal = $produk['harga'] * $qty;
        $total_bayar += $subtotal;
        $total_cup += $qty;

        $detail_items[] = [
            'id_produk' => $id_produk,
            'qty' => $qty
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | LOCK MEMBER POINTS
    |--------------------------------------------------------------------------
    */
    $poin_saat_ini = 0;

    if ($id_pelanggan != 1) {
        $q_member = mysqli_query(
            $conn,
            "SELECT poin
             FROM pelanggan
             WHERE id_pelanggan = '$id_pelanggan'
             FOR UPDATE"
        );

        if ($q_member && mysqli_num_rows($q_member) > 0) {
            $member = mysqli_fetch_assoc($q_member);
            $poin_saat_ini = intval($member['poin']);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POINT DISCOUNT
    |--------------------------------------------------------------------------
    */
    $diskon_terpakai = 0;
    $poin_sisa = $poin_saat_ini;

    if ($pakai_poin && $poin_saat_ini > 0) {
        $nilai_diskon = $poin_saat_ini * 100;

        if ($nilai_diskon >= $total_bayar) {
            $diskon_terpakai = $total_bayar;
            $poin_sisa = $poin_saat_ini - ceil($total_bayar / 100);
        } else {
            $diskon_terpakai = $nilai_diskon;
            $poin_sisa = 0;
        }

        $total_bayar -= $diskon_terpakai;
    }

    /*
    |--------------------------------------------------------------------------
    | INSERT PENJUALAN
    |--------------------------------------------------------------------------
    */
    $insert_penjualan = mysqli_query(
        $conn,
        "INSERT INTO penjualan (id_pelanggan, id_pegawai, total_bayar)
         VALUES ('$id_pelanggan', '$id_pegawai', '$total_bayar')"
    );

    if (!$insert_penjualan) {
        throw new Exception("Gagal menyimpan transaksi.");
    }

    $id_jual = mysqli_insert_id($conn);

    /*
    |--------------------------------------------------------------------------
    | INSERT DETAIL + UPDATE STOCK
    |--------------------------------------------------------------------------
    */
    foreach ($detail_items as $detail) {
        $id_produk = $detail['id_produk'];
        $qty = $detail['qty'];

        $insert_detail = mysqli_query(
            $conn,
            "INSERT INTO detail_penjualan (id_jual, id_produk, jumlah)
             VALUES ('$id_jual', '$id_produk', '$qty')"
        );

        if (!$insert_detail) {
            throw new Exception("Gagal menyimpan detail transaksi.");
        }

        $update_stok = mysqli_query(
            $conn,
            "UPDATE produk
             SET stok = stok - $qty
             WHERE id_produk = '$id_produk'"
        );

        if (!$update_stok) {
            throw new Exception("Gagal update stok.");
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE MEMBER POINTS
    |--------------------------------------------------------------------------
    */
    $info_poin = "";

    if ($id_pelanggan != 1) {
        if ($pakai_poin) {
            $update_poin = mysqli_query(
                $conn,
                "UPDATE pelanggan
                 SET poin = '$poin_sisa'
                 WHERE id_pelanggan = '$id_pelanggan'"
            );

            if (!$update_poin) {
                throw new Exception("Gagal update poin member.");
            }

            if ($diskon_terpakai > 0) {
                $info_poin .= "<br>Diskon Poin: <b>- Rp " .
                    number_format($diskon_terpakai, 0, ',', '.') .
                    " 🎉</b>";
            }
        }

        $tambah_poin = $total_cup * 10;

        $update_bonus = mysqli_query(
            $conn,
            "UPDATE pelanggan
             SET poin = poin + $tambah_poin
             WHERE id_pelanggan = '$id_pelanggan'"
        );

        if (!$update_bonus) {
            throw new Exception("Gagal menambahkan poin member.");
        }

        $info_poin .= "<br>Poin Baru Bertambah: <b>+$tambah_poin Pts ✨</b>";
    }

    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */
    mysqli_commit($conn);

    $pesan_sukses =
        "Total Bayar: <b>Rp " .
        number_format($total_bayar, 0, ',', '.') .
        "</b>" .
        $info_poin;

    showAlert(
        'Transaksi Berhasil! 🎉',
        $pesan_sukses,
        'success',
        "detail.php?id=$id_jual"
    );

} catch (Exception $e) {
    mysqli_rollback($conn);

    showAlert(
        'Transaksi Gagal!',
        htmlspecialchars($e->getMessage()),
        'error'
    );
}
?>