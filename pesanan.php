<?php
session_start();
include 'koneksi.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['id_pegawai'])) {
    header("location: login.php");
    exit;
}

$id_pegawai = (int) $_SESSION['id_pegawai'];

/*
========================================
QRIS NOTIFICATION COUNT
========================================
*/
$cek_qris = mysqli_query($conn, "
    SELECT COUNT(*) as total
    FROM pesanan_pelanggan
    WHERE status_bayar = 'MENUNGGU VERIFIKASI'
");

$data_qris = mysqli_fetch_assoc($cek_qris);
$total_qris = (int) $data_qris['total'];

/*
========================================
SELESAIKAN PESANAN
========================================
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'selesai') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die("Akses tidak valid.");
    }

    $id = (int) $_POST['id_pesanan'];

    $get_pesanan = mysqli_query($conn, "
        SELECT *
        FROM pesanan_pelanggan
        WHERE id_pesanan = '$id'
        LIMIT 1
    ");

    if ($get_pesanan && mysqli_num_rows($get_pesanan) > 0) {

        $data_pesanan = mysqli_fetch_assoc($get_pesanan);
        $total_bayar = (int) $data_pesanan['total_bayar'];
        $nama_pelanggan = urlencode($data_pesanan['nama_pelanggan']);

        $id_pelanggan_final = 1;

        $insert_jual = mysqli_query($conn, "
            INSERT INTO penjualan
(
    tgl_transaksi,
    total_bayar,
    id_pelanggan,
    id_pegawai,
    no_wa,
    nama_pelanggan
)
VALUES
(
    NOW(),
    '$total_bayar',
    '$id_pelanggan_final',
    '$id_pegawai',
    '{$data_pesanan['nomor_wa']}',
    '{$data_pesanan['nama_pelanggan']}'
)
        ");

        if ($insert_jual) {

            $id_jual_baru = mysqli_insert_id($conn);

            $get_detail = mysqli_query($conn, "
                SELECT *
                FROM detail_pesanan_pelanggan
                WHERE id_pesanan = '$id'
            ");

            while ($dt = mysqli_fetch_assoc($get_detail)) {

                $id_produk = (int) $dt['id_produk'];
                $jumlah = (int) $dt['jumlah'];

                mysqli_query($conn, "
                    INSERT INTO detail_penjualan (
                        id_jual,
                        id_produk,
                        jumlah
                    )
                    VALUES (
                        '$id_jual_baru',
                        '$id_produk',
                        '$jumlah'
                    )
                ");
            }

            mysqli_query($conn, "
                UPDATE pesanan_pelanggan
                SET
                    status_pesanan = 'Selesai',
                    status_bayar = 'LUNAS'
                WHERE id_pesanan = '$id'
            ");

            header("Location: detail.php?id=" . $id_jual_baru);
            exit;
        }
    }

    header("Location: pesanan.php?notif=gagal");
    exit;
}

/*
========================================
TOLAK PESANAN
========================================
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi'] === 'tolak') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die("Akses tidak valid.");
    }

    $id = (int) $_POST['id_pesanan'];

    $cek_pesanan = mysqli_query($conn, "
        SELECT *
        FROM pesanan_pelanggan
        WHERE id_pesanan = '$id'
        LIMIT 1
    ");

    if ($cek_pesanan && mysqli_num_rows($cek_pesanan) > 0) {

        $data_pesanan = mysqli_fetch_assoc($cek_pesanan);
        $nama_pelanggan = urlencode($data_pesanan['nama_pelanggan']);

        $query_detail = mysqli_query($conn, "
            SELECT id_produk, jumlah
            FROM detail_pesanan_pelanggan
            WHERE id_pesanan = '$id'
        ");

        while ($item = mysqli_fetch_assoc($query_detail)) {

            $id_prod = (int) $item['id_produk'];
            $qty_batal = (int) $item['jumlah'];

            mysqli_query($conn, "
                UPDATE produk
                SET stok = stok + $qty_batal
                WHERE id_produk = '$id_prod'
            ");
        }

        mysqli_query($conn, "
            DELETE FROM detail_pesanan_pelanggan
            WHERE id_pesanan = '$id'
        ");

        mysqli_query($conn, "
            DELETE FROM pesanan_pelanggan
            WHERE id_pesanan = '$id'
        ");

        header("Location: pesanan.php?notif=tolak&nama={$nama_pelanggan}");
        exit;
    }

    header("Location: pesanan.php?notif=gagal");
    exit;
}

/*
========================================
AMBIL ANTREAN
========================================
*/
include 'layout/navbar.php';

$q_pesanan_online = mysqli_query($conn, "
    SELECT *
    FROM pesanan_pelanggan
    WHERE status_pesanan IN ('Pending', 'Menunggu Konfirmasi')
    ORDER BY waktu_pesan DESC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir | Jelly Potter ✨</title>

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
            --success: #2ecc71;
        }

        body {
            font-family: 'Quicksand', sans-serif;
            margin: 0;
            padding: 140px 0 50px;
            color: var(--boba-brown);
        }

        .page-container {
            width: 95%;
            max-width: 1400px;
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

        .order-wrapper {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
            align-items: start;
        }

        .card {
            background: var(--cream);
            padding: 28px;
            border-radius: 30px;
            box-shadow: 0 12px 30px rgba(92, 58, 33, 0.08);
        }

        h2 {
            font-family: 'Playfair Display', serif;
            margin-top: 0;
        }

        label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
        }

        select,
        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 14px 16px;
            border-radius: 18px;
            border: 2px solid #F1ECE4;
            font-family: 'Quicksand';
            box-sizing: border-box;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 18px;
            margin-top: 20px;
            max-height: 65vh;
            overflow-y: auto;
        }

        .menu-item {
            background: white;
            border-radius: 24px;
            padding: 18px;
            text-align: center;
            cursor: pointer;
            transition: 0.25s ease;
            box-shadow: 0 8px 20px rgba(92, 58, 33, 0.05);
        }

        .menu-item:hover {
            transform: translateY(-6px);
        }

        .menu-item img {
            width: 95px;
            height: 95px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--milk-tea);
            margin-bottom: 12px;
        }

        .menu-item h4 {
            margin: 0 0 6px;
        }

        .menu-item p {
            margin: 0;
            font-weight: 700;
            color: var(--accent);
        }

        .cart-card {
            position: sticky;
            top: 110px;
        }

        .btn-checkout {
            width: 100%;
            border: none;
            background: linear-gradient(135deg, #5C3A21, #3e2716);
            color: white;
            padding: 18px;
            border-radius: 20px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
        }

        .btn-hapus {
            background: #ffebee;
            color: var(--danger);
            border: none;
            padding: 8px 12px;
            border-radius: 12px;
            cursor: pointer;
        }

        .queue-wrapper {
            background: rgba(255, 253, 249, 0.92);
            backdrop-filter: blur(18px);
            border-radius: 30px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(92, 58, 33, 0.08);
        }

        .queue-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .queue-header h2 {
            margin: 0;
        }

        .btn-refresh {
            background: linear-gradient(135deg, #5C3A21, #3e2716);
            color: white;
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 16px;
            font-weight: 700;
            transition: 0.25s ease;
        }

        .btn-refresh:hover {
            transform: translateY(-3px);
        }

        .queue-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .queue-card {
            background: white;
            border-radius: 24px;
            padding: 22px;
            box-shadow: 0 10px 25px rgba(92, 58, 33, 0.06);
            transition: 0.25s ease;
        }

        .queue-card:hover {
            transform: translateY(-5px);
        }

        .queue-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }

        .queue-id {
            font-size: 1.2rem;
            font-weight: 800;
        }

        .queue-time {
            background: #f8f3eb;
            padding: 8px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--accent);
        }

        .queue-name {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 14px;
        }

        .queue-meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .queue-badge {
            background: #eef2f7;
            padding: 10px 14px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .queue-total {
            font-size: 1.3rem;
            font-weight: 800;
            margin-bottom: 18px;
        }

        .aksi-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-action {
            flex: 1;
            border: none;
            padding: 14px;
            border-radius: 16px;
            font-weight: 700;
            cursor: pointer;
            color: white;
        }

        .btn-selesai {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }

        .btn-tolak {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .empty-queue {
            text-align: center;
            padding: 40px;
            color: var(--accent);
        }

        @media (max-width: 992px) {
            .order-wrapper {
                grid-template-columns: 1fr;
            }

            .cart-card {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 1.6rem;
            }

            .menu-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .menu-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="page-container">

        <div class="hero">
            <div>
                <h1>Kasir Jelly Potter 🧋✨</h1>
                <p>Fast checkout + online order command center</p>
            </div>

            <div class="hero-badge">
                ⚡ Fast Checkout Mode
            </div>
        </div>

        <div class="order-wrapper">

            <!-- LEFT -->
            <div class="card">
                <div
                    style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; flex-wrap:wrap; gap:15px;">
                    <h2 style="margin:0;">Pilih Menu ✨</h2>

                    <a href="tambah_menu.php" style="
                    background:linear-gradient(135deg,#5C3A21,#3e2716);
                    color:white;
                    text-decoration:none;
                    padding:12px 18px;
                    border-radius:16px;
                    font-weight:bold;
                ">
                        ➕ Tambah Menu
                    </a>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <div>
                        <label>Pelanggan / Member</label>
                        <select id="id_pelanggan">
                            <?php
                            $pel = mysqli_query($conn, "SELECT * FROM pelanggan ORDER BY nama_pelanggan ASC");
                            while ($p = mysqli_fetch_array($pel)) {
                                echo "<option value='" . (int) $p['id_pelanggan'] . "'>" . htmlspecialchars($p['nama_pelanggan']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label>Cari Menu</label>
                        <input type="text" id="cariMenu" placeholder="🔍 Cari menu favorit...">
                    </div>
                </div>

                <div class="menu-grid">
                    <?php
                    $prod = mysqli_query($conn, "SELECT * FROM produk WHERE stok > 0 ORDER BY nama_produk ASC");

                    while ($pr = mysqli_fetch_array($prod)) {
                        $foto = !empty($pr['foto']) ? $pr['foto'] : 'default.png';
                        ?>
                        <div class="menu-item" onclick="tambahKeKeranjangGrid(
                            '<?= (int) $pr['id_produk']; ?>',
                            '<?= addslashes(htmlspecialchars($pr['nama_produk'])); ?>',
                            <?= (int) $pr['harga']; ?>
                        )">
                            <img src="img/<?= htmlspecialchars($foto); ?>">
                            <h4><?= htmlspecialchars($pr['nama_produk']); ?></h4>
                            <p>Rp <?= number_format($pr['harga'], 0, ',', '.'); ?></p>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <!-- RIGHT -->
            <div class="card cart-card">
                <h2>Keranjang 🛒</h2>

                <form action="proses_pesanan.php" method="POST" id="formCheckout">
                    <input type="hidden" name="id_pelanggan_final" id="id_pelanggan_final">
                    <input type="hidden" name="data_item" id="data_item">

                    <div style="max-height:25vh; overflow-y:auto;">
                        <table id="tabelKeranjang">
                            <thead>
                                <tr>
                                    <th>Menu</th>
                                    <th>Harga</th>
                                    <th>Qty</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <div style="
                    border-top:2px dashed #F1ECE4;
                    padding-top:15px;
                    margin-top:15px;
                    font-size:1.3rem;
                    font-weight:bold;
                    text-align:right;
                ">
                        Total: Rp <span id="totalHarga">0</span>
                    </div>

                    <div style="
                    background:#FCFBF9;
                    border:2px solid #F1ECE4;
                    padding:15px;
                    border-radius:20px;
                    margin-top:15px;
                ">
                        <label>Uang Dibayar</label>
                        <input type="number" id="uangBayar" name="uang_bayar" required>

                        <div style="
                        font-size:1.1rem;
                        font-weight:bold;
                        text-align:right;
                        margin-top:10px;
                    ">
                            Kembalian:
                            <span id="warnaKembalian">
                                <span id="kembalian">0</span>
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="btn-checkout">
                        Proses Transaksi ✨
                    </button>
                </form>
            </div>

        </div>

        <!-- QUEUE -->
        <div class="queue-wrapper">

            <div class="queue-header">
                <h2>📱 Antrean Pesanan Mandiri</h2>

                <a href="pesanan.php" class="btn-refresh">
                    🔄 Refresh
                </a>
            </div>

            <!-- IMPORTANT -->
            <div class="queue-grid" id="queueGrid">

                <?php if (mysqli_num_rows($q_pesanan_online) > 0): ?>

                    <?php while ($row = mysqli_fetch_assoc($q_pesanan_online)): ?>

                        <div class="queue-card">

                            <div class="queue-top">

                                <div class="queue-id">
                                    #<?= (int) $row['id_pesanan']; ?>
                                </div>

                                <div class="queue-time">
                                    🕒 <?= date('H:i', strtotime($row['waktu_pesan'])); ?> WIB
                                </div>

                            </div>

                            <div class="queue-name">
                                👤 <?= htmlspecialchars($row['nama_pelanggan']); ?>
                            </div>

                            <div class="queue-meta">

                                <div class="queue-badge">
                                    🪑 Meja <?= htmlspecialchars($row['nomor_meja']); ?>
                                </div>

                                <div class="queue-badge">

                                    <?php if ($row['metode_bayar'] === 'QRIS'): ?>
                                        💳 QRIS
                                    <?php else: ?>
                                        💵 CASH
                                    <?php endif; ?>

                                </div>

                                <div class="queue-badge">

                                    <?php if ($row['status_pesanan'] === 'Menunggu Konfirmasi'): ?>
                                        🔔 Menunggu Verifikasi
                                    <?php else: ?>
                                        📦 Pending
                                    <?php endif; ?>

                                </div>

                            </div>

                            <div class="queue-total">
                                💰 Rp <?= number_format($row['total_bayar'], 0, ',', '.'); ?>
                            </div>

                            <div class="aksi-group">

                                <form method="POST" style="flex:1;">
                                    <input type="hidden" name="aksi" value="selesai">

                                    <input type="hidden" name="id_pesanan" value="<?= (int) $row['id_pesanan']; ?>">

                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                                    <button type="button" class="btn-action btn-selesai btn-selesai-order">

                                        ✅ Selesaikan

                                    </button>
                                </form>

                                <form method="POST" style="flex:1;">
                                    <input type="hidden" name="aksi" value="tolak">

                                    <input type="hidden" name="id_pesanan" value="<?= (int) $row['id_pesanan']; ?>">

                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                                    <button type="button" class="btn-action btn-tolak btn-tolak-order">

                                        ❌ Tolak

                                    </button>
                                </form>

                            </div>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <div class="empty-queue">
                        <h3>Belum Ada Pesanan ☕</h3>
                        <p>Antrean masih kosong 😌🧋</p>
                    </div>

                <?php endif; ?>

            </div>

        </div>

        <script>
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                background: '#FFFDF9',
                color: '#5C3A21'
            });

            let keranjang = [];
            let totalBelanjaan = 0;

            const inputUangBayar = document.getElementById('uangBayar');
            inputUangBayar.addEventListener(
                'input',
                hitungKembalian
            );
            const tbodyKeranjang = document.querySelector('#tabelKeranjang tbody');

            function tambahKeKeranjangGrid(id, nama, harga) {
                harga = parseInt(harga);

                const existing = keranjang.findIndex(item => item.id === id);

                if (existing !== -1) {
                    keranjang[existing].qty++;
                } else {
                    keranjang.push({ id, nama, harga, qty: 1 });
                }

                renderKeranjang();

                Toast.fire({
                    icon: 'success',
                    title: nama + ' masuk keranjang 🛒'
                });
            }

            function hapusItem(index) {
                keranjang.splice(index, 1);
                renderKeranjang();
            }

            function renderKeranjang() {
                let html = '';
                totalBelanjaan = 0;

                keranjang.forEach((item, index) => {
                    const subtotal = item.harga * item.qty;
                    totalBelanjaan += subtotal;

                    html += `
        <tr>
            <td>${item.nama}</td>
            <td>Rp ${item.harga.toLocaleString('id-ID')}</td>
            <td>x${item.qty}</td>
            <td>
                <button type="button" class="btn-hapus" onclick="hapusItem(${index})">
                    Hapus
                </button>
            </td>
        </tr>`;
                });

                tbodyKeranjang.innerHTML = html;
                document.getElementById('totalHarga').innerText = totalBelanjaan.toLocaleString('id-ID');
                document.getElementById('data_item').value = JSON.stringify(keranjang);
                document.getElementById('id_pelanggan_final').value = document.getElementById('id_pelanggan').value;

                hitungKembalian();
            }

            function hitungKembalian() {

                const bayar =
                    parseInt(inputUangBayar.value) || 0;

                const kembali =
                    bayar - totalBelanjaan;

                const warna =
                    document.getElementById('warnaKembalian');

                const text =
                    document.getElementById('kembalian');

                /*
                ========================================
                BELUM INPUT
                ========================================
                */
                if (bayar <= 0) {

                    text.innerText = '0';

                    warna.style.color = '#5C3A21';

                    return;
                }

                /*
                ========================================
                UANG KURANG
                ========================================
                */
                if (kembali < 0) {

                    text.innerText =
                        'Kurang Rp ' +
                        Math.abs(kembali).toLocaleString('id-ID');

                    warna.style.color = '#e74c3c';

                    return;
                }

                /*
                ========================================
                UANG CUKUP
                ========================================
                */
                text.innerText =
                    'Rp ' +
                    kembali.toLocaleString('id-ID');

                warna.style.color = '#2ecc71';
            }


            function konfirmasiSelesai(formElement) {
                Swal.fire({
                    title: 'Selesaikan pesanan?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#2ecc71'
                }).then((result) => {
                    if (result.isConfirmed) formElement.submit();
                });
            }

            function konfirmasiTolak(formElement) {
                Swal.fire({
                    title: 'Tolak pesanan?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e74c3c'
                }).then((result) => {
                    if (result.isConfirmed) formElement.submit();
                });
            }
        </script>

        <?php if (isset($_GET['notif']) && $_GET['notif'] === 'selesai'): ?>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Pesanan selesai 🎉',
                    text: 'Pesanan <?= htmlspecialchars($_GET['nama'] ?? '') ?> berhasil diproses'
                });
            </script>
        <?php endif; ?>

        <?php if (isset($_GET['notif']) && $_GET['notif'] === 'tolak'): ?>
            <script>
                Swal.fire({
                    icon: 'warning',
                    title: 'Pesanan ditolak ❌'
                });
            </script>
        <?php endif; ?>

        <?php if ($total_qris > 0): ?>
            <script>
                Toast.fire({
                    icon: 'info',
                    title: '🔔 Ada <?= $total_qris ?> pembayaran QRIS'
                });
            </script>
        <?php endif; ?>
        <script>
            function refreshQueue() {

                fetch('fetch_pesanan.php')
                    .then(response => response.text())
                    .then(data => {

                        const queueGrid =
                            document.getElementById('queueGrid');

                        if (queueGrid) {
                            queueGrid.innerHTML = data;
                        }

                    })
                    .catch(error => {
                        console.log('Auto refresh error:', error);
                    });
            }

            /*
            ========================================
            AUTO REFRESH TIAP 5 DETIK
            ========================================
            */
            document.addEventListener('click', function (e) {

                /*
                ========================================
                SELESAIKAN
                ========================================
                */
                if (e.target.classList.contains('btn-selesai-order')) {

                    const form =
                        e.target.closest('form');

                    Swal.fire({
                        title: 'Selesaikan pesanan?',
                        text: 'Pastikan pembayaran diterima.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#2ecc71'
                    }).then((result) => {

                        if (result.isConfirmed) {
                            form.submit();
                        }

                    });
                }

                /*
                ========================================
                TOLAK
                ========================================
                */
                if (e.target.classList.contains('btn-tolak-order')) {

                    const form =
                        e.target.closest('form');

                    Swal.fire({
                        title: 'Tolak pesanan?',
                        text: 'Stok akan dikembalikan.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e74c3c'
                    }).then((result) => {

                        if (result.isConfirmed) {
                            form.submit();
                        }

                    });
                }

            });
            setInterval(refreshQueue, 5000);
        </script>
</body>

</html>