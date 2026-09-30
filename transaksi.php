<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id_pegawai'])) {
    header("location: login.php");
    exit;
}

$hari_ini = date('Y-m-d');

/*
========================================
RINGKASAN HARI INI
========================================
*/


$q_summary = mysqli_query($conn, "
    SELECT
        COUNT(id_jual) AS total_transaksi,
        COALESCE(SUM(total_bayar), 0) AS total_duit
    FROM penjualan
    WHERE DATE(tgl_transaksi) = '$hari_ini'
");

$summary = mysqli_fetch_assoc($q_summary);

$total_duit = (int)($summary['total_duit'] ?? 0);
$total_order = (int)($summary['total_transaksi'] ?? 0);

/*
========================================
BEST SELLER HARI INI
========================================
*/

$q_laku = mysqli_query($conn, "
    SELECT
        p.nama_produk,
        SUM(dp.jumlah) AS qty
    FROM detail_penjualan dp
    JOIN produk p ON dp.id_produk = p.id_produk
    JOIN penjualan pj ON dp.id_jual = pj.id_jual
    WHERE DATE(pj.tgl_transaksi) = '$hari_ini'
    GROUP BY dp.id_produk
    ORDER BY qty DESC
    LIMIT 1
");

$d_laku = mysqli_fetch_assoc($q_laku);

$menu_terlaris = htmlspecialchars($d_laku['nama_produk'] ?? 'Belum ada penjualan');
$qty_terlaris = (int)($d_laku['qty'] ?? 0);

include 'layout/navbar.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi | Jelly Potter ✨</title>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --milk-tea: #F5E6CA;
            --boba-brown: #5C3A21;
            --cream: rgba(255, 253, 249, 0.92);
            --accent: #8d735b;
            --success: #2ecc71;
            --info: #3498db;
            --warning: #e67e22;
        }

        body {
            font-family: 'Quicksand', sans-serif;
            margin: 0;
            padding: 140px 0 50px 0;
            color: var(--boba-brown);
        }

        .page-container {
            width: 95%;
            max-width: 1400px;
            margin: 0 auto;
        }

        /*
        ==========================
        HERO
        ==========================
        */

        .hero {
            background: linear-gradient(
                135deg,
                rgba(255,255,255,0.85),
                rgba(245,230,202,0.75)
            );
            backdrop-filter: blur(18px);
            border-radius: 30px;
            padding: 35px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(92,58,33,0.08);

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
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

        .btn-summary {
            background: linear-gradient(135deg, #5C3A21, #3e2716);
            color: white;
            border: none;
            padding: 15px 22px;
            border-radius: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.25s ease;
            box-shadow: 0 10px 20px rgba(92,58,33,0.2);
        }

        .btn-summary:hover {
            transform: translateY(-3px);
        }

        /*
        ==========================
        STATS
        ==========================
        */

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--cream);
            border-radius: 28px;
            padding: 28px;
            box-shadow: 0 12px 30px rgba(92,58,33,0.08);
            display: flex;
            align-items: center;
            gap: 18px;
            transition: 0.25s ease;
        }

        .stat-card:hover {
            transform: translateY(-6px);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            flex-shrink: 0;
        }

        .money .stat-icon {
            background: rgba(46, 204, 113, 0.12);
        }

        .trx .stat-icon {
            background: rgba(52, 152, 219, 0.12);
        }

        .best .stat-icon {
            background: rgba(230, 126, 34, 0.12);
        }

        .stat-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            color: var(--accent);
            font-weight: 700;
            letter-spacing: 1px;
        }

        .stat-value {
            margin-top: 8px;
            font-size: 1.7rem;
            font-weight: 700;
        }

        /*
        ==========================
        SECTION
        ==========================
        */

        .section-card {
            background: var(--cream);
            border-radius: 30px;
            padding: 30px;
            box-shadow: 0 12px 30px rgba(92,58,33,0.06);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .section-header h2 {
            margin: 0;
            font-size: 1.6rem;
        }

        .search-box {
            width: 300px;
            max-width: 100%;
        }

        .search-box input {
            width: 100%;
            padding: 14px 18px;
            border-radius: 18px;
            border: 2px solid #f1ece4;
            font-family: 'Quicksand', sans-serif;
            outline: none;
            box-sizing: border-box;
            transition: 0.2s ease;
        }

        .search-box input:focus {
            border-color: var(--boba-brown);
            box-shadow: 0 0 0 4px rgba(92,58,33,0.08);
        }

        /*
        ==========================
        TRANSACTION LIST
        ==========================
        */

        .trx-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .trx-card {
            background: white;
            border-radius: 22px;
            padding: 20px;
            box-shadow: 0 8px 20px rgba(92,58,33,0.05);

            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            flex-wrap: wrap;

            transition: 0.25s ease;
        }

        .trx-card:hover {
            transform: translateY(-4px);
        }

        .trx-left h3 {
            margin: 0;
            font-size: 1.1rem;
        }

        .trx-meta {
            margin-top: 8px;
            color: var(--accent);
            font-size: 0.92rem;
            line-height: 1.6;
        }

        .trx-right {
            text-align: right;
        }

        .trx-total {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .btn-nota {
            background: var(--boba-brown);
            color: white;
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 16px;
            font-weight: 700;
            display: inline-block;
        }

        .btn-nota:hover {
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 1.7rem;
            }

            .section-card {
                padding: 20px;
            }

            .trx-right {
                width: 100%;
                text-align: left;
            }

            .stat-value {
                font-size: 1.35rem;
            }
        }
    </style>
</head>

<body>

<div class="page-container">

    <div class="hero">
        <div>
            <h1>Transaction Command Center 📜✨</h1>
            <p>Monitor semua transaksi Jelly Potter dalam satu tempat</p>
        </div>

        <button class="btn-summary" onclick="showSummary()">
            📊 Ringkasan Hari Ini
        </button>
    </div>

    <div class="stats-grid">
        <div class="stat-card money">
            <div class="stat-icon">💰</div>
            <div>
                <div class="stat-label">Pendapatan Hari Ini</div>
                <div class="stat-value">
                    Rp <?= number_format($total_duit, 0, ',', '.'); ?>
                </div>
            </div>
        </div>

        <div class="stat-card trx">
            <div class="stat-icon">🧾</div>
            <div>
                <div class="stat-label">Total Transaksi</div>
                <div class="stat-value">
                    <?= $total_order; ?>
                </div>
            </div>
        </div>

        <div class="stat-card best">
            <div class="stat-icon">🏆</div>
            <div>
                <div class="stat-label">Best Seller Hari Ini</div>
                <div class="stat-value">
                    <?= $qty_terlaris; ?> Cup
                </div>
            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-header">
            <h2>Riwayat Transaksi</h2>

            <div class="search-box">
                <input
                    type="text"
                    id="searchInput"
                    placeholder="🔍 Cari transaksi..."
                >
            </div>
        </div>

        <div class="trx-list" id="trxList">
            <?php

$limit = 10;

$page = isset($_GET['page'])
    ? (int)$_GET['page']
    : 1;

if ($page < 1) {
    $page = 1;
}

$start = ($page - 1) * $limit;
$query = mysqli_query($conn, "
    SELECT
        penjualan.*,
        pelanggan.nama_pelanggan
    FROM penjualan
    LEFT JOIN pelanggan
        ON penjualan.id_pelanggan = pelanggan.id_pelanggan
    ORDER BY penjualan.id_jual DESC
    LIMIT $start, $limit
");
$total_query = mysqli_query($conn, "
    SELECT COUNT(id_jual) as total
    FROM penjualan
");

$total_data = mysqli_fetch_assoc($total_query)['total'];

$total_page = ceil($total_data / $limit);
$bulan_indo = [
    1 => 'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember'
];

if (mysqli_num_rows($query) > 0):
    while ($d = mysqli_fetch_assoc($query)):

        $time = strtotime($d['tgl_transaksi']);
        $bln = (int)date('m', $time);

        $tgl_tampil =
            date('d', $time) . ' ' .
            $bulan_indo[$bln] . ' ' .
            date('Y, H:i', $time);

        $nama_pelanggan = !empty($d['nama_pelanggan'])
            ? htmlspecialchars($d['nama_pelanggan'])
            : 'Pelanggan Umum';

        $id_jual = (int)$d['id_jual'];
?>

    <div
        class="trx-card"
        data-search="
            <?= strtolower(
                $id_jual . ' ' .
                $nama_pelanggan . ' ' .
                $tgl_tampil . ' ' .
                $d['total_bayar']
            ); ?>
        "
    >
        <div class="trx-left">
            <h3>
                Transaksi #<?= $id_jual; ?>
            </h3>

            <div class="trx-meta">
                👤 <?= $nama_pelanggan; ?><br>
                🕒 <?= $tgl_tampil; ?>
            </div>
        </div>

        <div class="trx-right">
            <div class="trx-total">
                Rp <?= number_format($d['total_bayar'], 0, ',', '.'); ?>
            </div>

            <a
                href="detail.php?id=<?= $id_jual; ?>"
                class="btn-nota"
            >
                Lihat Nota ✨
            </a>
        </div>
    </div>

<?php
    endwhile;
else:
?>

    <div class="trx-card">
        <div class="trx-left">
            <h3>Belum Ada Transaksi 😶</h3>
            <div class="trx-meta">
                Riwayat transaksi masih kosong.
            </div>
        </div>
    </div>

<?php endif; ?>

        </div>
    </div>
    <div style="
    display:flex;
    justify-content:center;
    gap:10px;
    margin-top:30px;
    flex-wrap:wrap;
">

<?php for ($i = 1; $i <= $total_page; $i++): ?>

    <a
        href="?page=<?= $i ?>"
        style="
            padding:12px 18px;
            border-radius:14px;
            text-decoration:none;
            font-weight:bold;

            <?= $page == $i
                ? 'background:#5C3A21;color:white;'
                : 'background:#F5E6CA;color:#5C3A21;'
            ?>
        "
    >
        <?= $i ?>
    </a>

<?php endfor; ?>

</div>
</div>

<script>
function showSummary() {
    Swal.fire({
        title: '✨ Tutup Buku Hari Ini ✨',
        html: `
            <div style="text-align:left; font-size:1rem; line-height:1.8;">
                <hr style="border:1px dashed #e6c8a3;">

                <div>
                    💰 <b>Pendapatan:</b><br>
                    <span style="
                        font-size:1.6rem;
                        color:#5C3A21;
                        font-weight:bold;
                    ">
                        Rp <?= number_format($total_duit, 0, ',', '.'); ?>
                    </span>
                </div>

                <div style="margin-top:12px;">
                    🧾 <b>Total Transaksi:</b>
                    <?= $total_order; ?>
                </div>

                <div style="margin-top:12px;">
                    🏆 <b>Menu Terlaris:</b><br>
                    <?= $menu_terlaris; ?>
                    (<?= $qty_terlaris; ?> Cup)
                </div>

                <hr style="border:1px dashed #e6c8a3;">

                <p style="
                    text-align:center;
                    color:#8d735b;
                    font-size:0.85rem;
                    margin-top:12px;
                ">
                    Data tanggal <?= date('d M Y'); ?>
                </p>
            </div>
        `,
        icon: 'info',
        confirmButtonColor: '#5C3A21',
        confirmButtonText: 'Mantap 🧋',
        background: '#FFFDF9',
        color: '#5C3A21'
    });
}

const searchInput = document.getElementById('searchInput');
const cards = document.querySelectorAll('.trx-card');

searchInput.addEventListener('input', function () {
    const keyword = this.value.toLowerCase();

    cards.forEach(card => {
        const text = card.dataset.search || '';

        if (text.includes(keyword)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
});
</script>

</body>
</html>