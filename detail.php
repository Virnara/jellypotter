<?php
session_start();
include 'config/koneksi.php';

if (!isset($_SESSION['id_pegawai'])) {
    header("location: ../auth/login.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("location: ../admin/transaksi.php");
    exit;
}

$id = intval($_GET['id']);

$id_session = (int) $_SESSION['id_pegawai'];
$jabatan = strtolower($_SESSION['jabatan'] ?? '');

$where_extra = "";

if ($jabatan !== 'admin') {
    $where_extra = " AND pj.id_pegawai = '$id_session' ";
}

$query_transaksi = mysqli_query($conn, "
    SELECT 
    pj.*,
    pg.nama_pegawai
    FROM penjualan pj
    LEFT JOIN pelanggan pl ON pj.id_pelanggan = pl.id_pelanggan
    LEFT JOIN pegawai pg ON pj.id_pegawai = pg.id_pegawai
    WHERE pj.id_jual = '$id'
    $where_extra
    LIMIT 1
");

if (!$query_transaksi || mysqli_num_rows($query_transaksi) === 0) {
    echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        Swal.fire({
            title: 'Transaksi Tidak Ditemukan!',
            text: 'Nota yang kamu cari tidak tersedia.',
            icon: 'error',
            confirmButtonColor: '#5C3A21',
            background: '#FFFDF9',
            color: '#5C3A21'
        }).then(() => {
            window.location.href = '../admin/transaksi.php';
        });
    </script>
    ";
    exit;
}

$data = mysqli_fetch_assoc($query_transaksi);

$query_detail = mysqli_query($conn, "
    SELECT 
        dp.jumlah,
        p.nama_produk,
        p.harga
    FROM detail_penjualan dp
    JOIN produk p ON dp.id_produk = p.id_produk
    WHERE dp.id_jual = '$id'
");

$detail_items = [];
$pesan_items = "";

while ($item = mysqli_fetch_assoc($query_detail)) {
    $detail_items[] = $item;
    $pesan_items .= "• " . $item['nama_produk'] . " (" . $item['jumlah'] . "x)\n";
}

$nama_display_pelanggan = !empty($data['nama_pelanggan'])
    ? $data['nama_pelanggan']
    : 'Pelanggan Umum';

$tgl_nota = date('d M Y, H:i', strtotime($data['tgl_transaksi']));

$no_wa = preg_replace('/[^0-9]/', '', $data['no_wa'] ?? '');

if (!empty($no_wa) && substr($no_wa, 0, 1) === '0') {
    $no_wa = '62' . substr($no_wa, 1);
}

$nama_toko = "*JELLY POTTER ✨*";

$pesan_wa = "$nama_toko\n";
$pesan_wa .= "Struk Digital #$id\n";
$pesan_wa .= "Tanggal: $tgl_nota\n";
$pesan_wa .= "Pelanggan: *$nama_display_pelanggan*\n";
$pesan_wa .= "-----------------------------\n";
$pesan_wa .= $pesan_items;
$pesan_wa .= "-----------------------------\n";
$pesan_wa .= "*TOTAL: Rp " . number_format($data['total_bayar'], 0, ',', '.') . "*\n\n";
$pesan_wa .= "Terima kasih sudah jajan di Jelly Potter 🧋✨";

$link_wa = "";

if (!empty($no_wa)) {
    $link_wa = "https://api.whatsapp.com/send?phone=$no_wa&text=" . urlencode($pesan_wa);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota #<?= $id; ?> | Jelly Potter ✨</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Quicksand:wght@400;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        :root {
            --milk-tea: #F8F1E4;
            --boba-brown: #5C3A21;
            --cream: #FFFDF9;
            --accent: #8d735b;
            --line: #e6d6bf;
            --wa-green: #25D366;
        }

        body {
            margin: 0;
            padding: 40px 20px;
            font-family: 'Quicksand', sans-serif;
            background: linear-gradient(180deg, #f8f1e4 0%, #fdf9f2 100%);
            color: var(--boba-brown);
            min-height: 100vh;
            box-sizing: border-box;
        }

        .page-wrapper {
            max-width: 1000px;
            margin: auto;
        }

        .hero-header {
            background: rgba(255, 253, 249, 0.92);
            backdrop-filter: blur(14px);
            border-radius: 30px;
            padding: 35px;
            text-align: center;
            box-shadow: 0 12px 30px rgba(92, 58, 33, 0.08);
            margin-bottom: 30px;
        }

        .hero-header h1 {
            margin: 0;
            font-family: 'Playfair Display', serif;
            font-size: 3rem;
            color: var(--boba-brown);
        }

        .hero-header p {
            margin-top: 10px;
            color: var(--accent);
            font-size: 1.05rem;
            font-weight: 600;
        }

        .invoice-card {
            background: white;
            border-radius: 35px;
            padding: 40px;
            box-shadow:
                0 18px 40px rgba(92, 58, 33, 0.12),
                inset 0 0 0 1px rgba(255, 255, 255, 0.5);

            position: relative;
            overflow: hidden;
        }

        .invoice-card::before,
        .invoice-card::after {
            content: '';
            position: absolute;
            left: 0;
            width: 100%;
            height: 14px;
            background:
                radial-gradient(circle, transparent 8px, #fff 9px);
            background-size: 22px 22px;
        }

        .invoice-card::before {
            top: -10px;
        }

        .invoice-card::after {
            bottom: -10px;
            transform: rotate(180deg);
        }

        .invoice-top {
            text-align: center;
            padding-bottom: 25px;
            border-bottom: 2px dashed var(--line);
        }

        .invoice-top img {
            width: 120px;
            margin-bottom: 20px;
        }

        .invoice-top p {
            margin: 6px 0;
            color: var(--accent);
            font-weight: 600;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 30px 0;
        }

        .info-box {
            background: #fcfbf8;
            border: 2px solid #f2e7d8;
            border-radius: 22px;
            padding: 22px;
        }

        .info-title {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--accent);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-content {
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--boba-brown);
            line-height: 1.7;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .items-table th {
            text-align: left;
            padding: 16px;
            background: #f8f3eb;
            color: var(--boba-brown);
            font-family: 'Playfair Display', serif;
            font-size: 1rem;
        }

        .items-table th:first-child {
            border-radius: 16px 0 0 16px;
        }

        .items-table th:last-child {
            border-radius: 0 16px 16px 0;
            text-align: right;
        }

        .items-table td {
            padding: 18px 16px;
            border-bottom: 1px solid #f1ece4;
        }

        .items-table td:last-child {
            text-align: right;
            font-weight: 700;
        }

        .menu-name {
            font-weight: 700;
            font-size: 1rem;
        }

        .menu-sub {
            font-size: 0.9rem;
            color: var(--accent);
            margin-top: 5px;
        }

        .total-box {
            margin-top: 30px;
            background: linear-gradient(135deg, #5C3A21, #3e2716);
            color: white;
            padding: 28px 35px;
            border-radius: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-box span:first-child {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .total-box span:last-child {
            font-size: 2rem;
            font-weight: 800;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            flex: 1;
            min-width: 220px;
            border: none;
            padding: 16px;
            border-radius: 18px;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
            transition: 0.25s;
            font-family: 'Quicksand';
        }

        .btn:hover {
            transform: translateY(-3px);
        }

        .btn-wa {
            background: #25D366;
            color: white;
        }

        .btn-print {
            background: var(--boba-brown);
            color: white;
        }

        .btn-back {
            background: white;
            color: var(--boba-brown);
            border: 2px solid var(--boba-brown);
        }

        @media (max-width: 768px) {
            .hero-header h1 {
                font-size: 2rem;
            }

            .invoice-card {
                padding: 22px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .total-box {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                min-width: auto;
            }
        }

        @media print {



            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .hero-header,
            .action-buttons {
                display: none !important;
            }

            .page-wrapper {
                max-width: 80mm !important;
                width: 80mm !important;
                margin: 0 auto !important;
            }

            .invoice-card {

                width: 100% !important;
                max-width: 100% !important;

                box-shadow: none !important;
                border-radius: 0 !important;

                padding: 10px !important;

                background: white !important;
            }

            .invoice-top h2 {
                font-size: 20px !important;
            }

            .invoice-top p,
            .info-content,
            .menu-sub,
            td,
            th {
                font-size: 11px !important;
            }

            .items-table th,
            .items-table td {
                padding: 8px 4px !important;
            }

            .total-box {

                background: white !important;
                color: black !important;

                border: 2px dashed black !important;

                box-shadow: none !important;

                padding: 12px !important;
                margin-top: 15px !important;
            }

            .total-box span:last-child {
                font-size: 18px !important;
            }

            .info-grid {
                grid-template-columns: 1fr !important;
                gap: 10px !important;
            }

            .info-box {
                padding: 10px !important;
            }

            img {
                max-width: 70px !important;
            }

            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>

<body>

    <div class="page-wrapper">

        <div class="hero-header">
            <h1>Digital Receipt ✨</h1>
            <p>Jelly Potter Premium Transaction Detail</p>
        </div>

        <div class="invoice-card">

            <div class="invoice-top">

                <img src="logo.png" alt="Logo Jelly Potter">

                <h2 style="
        margin:0;
        font-family:'Playfair Display',serif;
        font-size:2rem;
        color:#5C3A21;
    ">
                    Jelly Potter 🧋✨
                </h2>

                <p style="
        margin-top:8px;
        font-size:.95rem;
        color:#8d735b;
    ">
                    Magical Boba & Dessert
                </p>

                <div style="
        margin:20px auto;
        width:80%;
        border-top:2px dashed #e6d6bf;
    "></div>

                <p>🕒 <?= htmlspecialchars($tgl_nota); ?></p>
                <p>👨‍🍳 Kasir: <?= htmlspecialchars($data['nama_pegawai'] ?? 'Staff'); ?></p>
                <p>🧾 Invoice #<?= $id; ?></p>

            </div>

            <div class="info-grid">

                <div class="info-box">
                    <div class="info-title">Pelanggan</div>
                    <div class="info-content">
                        <?= htmlspecialchars($nama_display_pelanggan); ?>
                    </div>
                </div>

                <div class="info-box">
                    <div class="info-title">WhatsApp</div>
                    <div class="info-content">
                        <?= !empty($data['no_wa']) ? htmlspecialchars($data['no_wa']) : '-'; ?>
                    </div>
                </div>

            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Menu Item</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($detail_items as $item): ?>
                        <?php $sub = $item['harga'] * $item['jumlah']; ?>

                        <tr>
                            <td>
                                <div class="menu-name">
                                    <?= htmlspecialchars($item['nama_produk']); ?>
                                </div>

                                <div class="menu-sub">
                                    <?= intval($item['jumlah']); ?> x Rp <?= number_format($item['harga'], 0, ',', '.'); ?>
                                </div>
                            </td>

                            <td>
                                Rp <?= number_format($sub, 0, ',', '.'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-box">
                <span>TOTAL PAYMENT</span>
                <span>Rp <?= number_format($data['total_bayar'], 0, ',', '.'); ?></span>
            </div>

            <div class="action-buttons">

                <?php if (!empty($link_wa)): ?>
                    <a href="<?= $link_wa; ?>" target="_blank" class="btn btn-wa">
                        📲 Kirim WhatsApp
                    </a>
                <?php else: ?>
                    <button class="btn" style="background:#ccc; color:#666;" disabled>
                        WhatsApp Tidak Tersedia
                    </button>
                <?php endif; ?>

                <button onclick="window.print()" class="btn btn-print">
                    🖨️ Cetak Nota
                </button>

                <a href="../admin/pesanan.php" class="btn btn-back">
                    ← Order Baru
                </a>

            </div>
            <div style="
    text-align:center;
    margin-top:35px;
    color:#8d735b;
    font-size:.92rem;
    line-height:1.8;
">

                <div style="
        border-top:2px dashed #e6d6bf;
        margin-bottom:20px;
        padding-top:20px;
    ">
                    ✨ Thank you for visiting Jelly Potter ✨
                </div>

                <div>
                    “Every boba has its own magic.” 🪄
                </div>

            </div>

        </div>

    </div>

</body>

</html>