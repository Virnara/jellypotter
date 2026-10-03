<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id_pegawai'])) {
    header("location: login.php");
    exit;
}

if (strtolower($_SESSION['jabatan']) !== 'admin') {
    echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        Swal.fire({
            title: 'Akses Ditolak! 🚫',
            text: 'Hanya Admin yang boleh membuka laporan.',
            icon: 'error',
            confirmButtonColor: '#5C3A21',
            background: '#FFFDF9',
            color: '#5C3A21'
        }).then(() => {
            window.location.href='dashboard.php';
        });
    </script>";
    exit;
}

include 'includes/navbar.php';

$bulan_pilih = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun_pilih = isset($_GET['tahun']) ? intval($_GET['tahun']) : date('Y');

$bulan_pilih = str_pad(intval($bulan_pilih), 2, '0', STR_PAD_LEFT);

if (!preg_match('/^(0[1-9]|1[0-2])$/', $bulan_pilih)) {
    $bulan_pilih = date('m');
}

$tahun_sekarang = date('Y');

if ($tahun_pilih < ($tahun_sekarang - 3) || $tahun_pilih > $tahun_sekarang) {
    $tahun_pilih = $tahun_sekarang;
}

$nama_bulan = [
    '01' => 'Januari',
    '02' => 'Februari',
    '03' => 'Maret',
    '04' => 'April',
    '05' => 'Mei',
    '06' => 'Juni',
    '07' => 'Juli',
    '08' => 'Agustus',
    '09' => 'September',
    '10' => 'Oktober',
    '11' => 'November',
    '12' => 'Desember'
];

$query_laporan = mysqli_query($conn, "
    SELECT
        p.*,
        pl.nama_pelanggan
    FROM penjualan p
    LEFT JOIN pelanggan pl
        ON p.id_pelanggan = pl.id_pelanggan
    WHERE MONTH(p.tgl_transaksi) = '$bulan_pilih'
    AND YEAR(p.tgl_transaksi) = '$tahun_pilih'
    ORDER BY p.tgl_transaksi DESC
");

$total_pendapatan = 0;
$total_transaksi = 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan | Jelly Potter ✨</title>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --milk-tea: #F5E6CA;
            --boba-brown: #5C3A21;
            --cream: rgba(255, 253, 249, 0.92);
            --accent: #8d735b;
            --success: #2ecc71;
        }

        body {
            font-family: 'Quicksand', sans-serif;
            margin: 0;
            padding: 140px 0 50px 0;
            color: var(--boba-brown);
        }

        .report-container {
            width: 95%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .hero {
            background: linear-gradient(
                135deg,
                rgba(255,255,255,0.88),
                rgba(245,230,202,0.78)
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

        .btn-print {
            background: linear-gradient(135deg, #5C3A21, #3e2716);
            color: white;
            text-decoration: none;
            padding: 15px 24px;
            border-radius: 18px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: 0.25s ease;
            box-shadow: 0 10px 20px rgba(92,58,33,0.2);
        }

        .btn-print:hover {
            transform: translateY(-3px);
        }

        .section-card {
            background: var(--cream);
            border-radius: 30px;
            padding: 30px;
            box-shadow: 0 12px 30px rgba(92,58,33,0.06);
            margin-bottom: 30px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--accent);
        }

        select,
        input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 16px;
            border: 2px solid #f1ece4;
            font-family: 'Quicksand', sans-serif;
            font-size: 0.95rem;
            box-sizing: border-box;
            outline: none;
        }

        select:focus,
        input:focus {
            border-color: var(--boba-brown);
        }

        .btn-filter {
            background: var(--boba-brown);
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 16px;
            font-weight: 700;
            cursor: pointer;
            height: fit-content;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--cream);
            border-radius: 28px;
            padding: 28px;
            box-shadow: 0 12px 30px rgba(92,58,33,0.08);
        }

        .stat-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            color: var(--accent);
            font-weight: 700;
            letter-spacing: 1px;
        }

        .stat-value {
            margin-top: 10px;
            font-size: 1.8rem;
            font-weight: 800;
        }

        .report-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .report-card {
            background: white;
            border-radius: 24px;
            padding: 22px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            box-shadow: 0 8px 20px rgba(92,58,33,0.05);
            transition: 0.25s ease;
        }

        .report-card:hover {
            transform: translateY(-4px);
        }

        .report-left h3 {
            margin: 0 0 8px 0;
            font-size: 1.1rem;
        }

        .report-meta {
            color: var(--accent);
            font-size: 0.92rem;
            line-height: 1.7;
        }

        .report-total {
            font-size: 1.4rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--accent);
        }
                @media (max-width: 768px) {
            .hero h1 {
                font-size: 1.7rem;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }

            .report-card {
                flex-direction: column;
                align-items: flex-start;
            }

            .report-total {
                font-size: 1.2rem;
            }

            .section-card {
                padding: 20px;
            }
        }

        @media print {
    .hero,
    .filter-section,
    #searchInput,
    .btn-filter,
    .btn-print,
    .navbar,
    .jp-navbar {
        display: none !important;
    }

    body {
        background: white !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .report-container {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .section-card {
        background: white !important;
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .stats-grid {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 10px !important;
        margin-bottom: 20px !important;
    }

    .stat-card {
        box-shadow: none !important;
        border: 1px solid #ccc !important;
        background: white !important;
        padding: 12px !important;
        border-radius: 10px !important;
    }

    .report-list {
        gap: 8px !important;
    }

    .report-card {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        box-shadow: none !important;
        border: 1px solid #ddd !important;
        background: white !important;
        padding: 10px 14px !important;
        border-radius: 8px !important;
        page-break-inside: avoid;
        break-inside: avoid;
    }

    .report-left h3 {
        font-size: 0.95rem !important;
        margin: 0 0 4px 0 !important;
    }

    .report-meta {
        font-size: 0.75rem !important;
        line-height: 1.4 !important;
    }

    .report-total {
        font-size: 0.95rem !important;
    }

    @page {
        size: A4 portrait;
        margin: 10mm;
    }
}
    </style>
</head>

<body>

<div class="report-container">

    <div class="hero">
        <div>
            <h1>Laporan Pendapatan 📊</h1>
            <p>
                Rekap periode
                <strong><?= $nama_bulan[$bulan_pilih] . ' ' . $tahun_pilih; ?></strong>
            </p>
        </div>

        <button onclick="window.print()" class="btn-print">
            🖨️ Cetak Laporan
        </button>
    </div>

    <div class="section-card filter-section">
        <form method="GET" class="filter-grid">

            <div class="field">
                <label>Bulan</label>
                <select name="bulan">
                    <?php foreach ($nama_bulan as $key => $value): ?>
                        <option
                            value="<?= $key; ?>"
                            <?= ($key == $bulan_pilih) ? 'selected' : ''; ?>
                        >
                            <?= $value; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field">
                <label>Tahun</label>
                <select name="tahun">
                    <?php for ($i = $tahun_sekarang - 3; $i <= $tahun_sekarang; $i++): ?>
                        <option
                            value="<?= $i; ?>"
                            <?= ($i == $tahun_pilih) ? 'selected' : ''; ?>
                        >
                            <?= $i; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <button type="submit" class="btn-filter">
                🔍 Terapkan Filter
            </button>
        </form>
    </div>

    <div class="section-card filter-section">
        <div class="field">
            <label>Cari Transaksi Cepat</label>
            <input
                type="text"
                id="searchInput"
                placeholder="Cari nama pelanggan / ID transaksi..."
            >
        </div>
    </div>

    <div class="stats-grid">
        <?php
        mysqli_data_seek($query_laporan, 0);

        while ($calc = mysqli_fetch_assoc($query_laporan)) {
            $total_pendapatan += $calc['total_bayar'];
            $total_transaksi++;
        }

        mysqli_data_seek($query_laporan, 0);
        ?>

        <div class="stat-card">
            <div class="stat-label">Total Omzet</div>
            <div class="stat-value">
                Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Jumlah Transaksi</div>
            <div class="stat-value">
                <?= $total_transaksi; ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Rata-rata per Transaksi</div>
            <div class="stat-value">
                Rp <?= number_format(
                    $total_transaksi > 0 ? ($total_pendapatan / $total_transaksi) : 0,
                    0,
                    ',',
                    '.'
                ); ?>
            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="report-list" id="reportList">

            <?php if (mysqli_num_rows($query_laporan) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($query_laporan)): ?>

                    <?php
                    $tgl = date('d M Y, H:i', strtotime($row['tgl_transaksi']));

                    $pelanggan = !empty($row['nama_pelanggan'])
                        ? htmlspecialchars($row['nama_pelanggan'])
                        : 'Pelanggan Umum';
                    ?>

                    <div
                        class="report-card"
                        data-search="<?= strtolower(
                            $row['id_jual'] . ' ' .
                            $pelanggan . ' ' .
                            $tgl
                        ); ?>"
                    >
                        <div class="report-left">
                            <h3>
                                Transaksi #<?= (int)$row['id_jual']; ?>
                            </h3>

                            <div class="report-meta">
                                👤 <?= $pelanggan; ?><br>
                                🕒 <?= $tgl; ?>
                            </div>
                        </div>

                        <div class="report-total">
                            Rp <?= number_format($row['total_bayar'], 0, ',', '.'); ?>
                        </div>
                    </div>

                <?php endwhile; ?>
            <?php else: ?>

                <div class="empty-state">
                    <h3>Belum Ada Data 😶</h3>
                    <p>Tidak ada transaksi pada periode ini.</p>
                </div>

            <?php endif; ?>

        </div>
    </div>

</div>

<script>
const searchInput = document.getElementById('searchInput');
const reportCards = document.querySelectorAll('.report-card');

searchInput.addEventListener('input', function () {
    const keyword = this.value.toLowerCase();

    reportCards.forEach(card => {
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