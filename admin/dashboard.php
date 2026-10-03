<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_pegawai'])) {
    header("location: ../auth/login.php");
    exit;
}

if (strtolower($_SESSION['jabatan'] ?? '') !== 'admin') {
    header("location: ../admin/pesanan.php");
    exit;
}

include '../includes/navbar.php';

$hari_ini = date('Y-m-d');

/*
========================================
STATISTIK HARI INI
========================================
*/

$q_stat = mysqli_query($conn, "
    SELECT
        COUNT(id_jual) AS total_transaksi,
        COALESCE(SUM(total_bayar), 0) AS total_omzet
    FROM penjualan
    WHERE DATE(tgl_transaksi) = '$hari_ini'
");

$stat = mysqli_fetch_assoc($q_stat);

$transaksi = (int) ($stat['total_transaksi'] ?? 0);
$pendapatan = (int) ($stat['total_omzet'] ?? 0);

/*
========================================
STOK KRITIS
========================================
*/

$q_stok = mysqli_query($conn, "
    SELECT COUNT(id_produk) AS stok_kritis
    FROM produk
    WHERE stok < 10
");

$stok_data = mysqli_fetch_assoc($q_stok);
$stok_kritis = (int) ($stok_data['stok_kritis'] ?? 0);

/*
========================================
TOP 5 MENU TERLARIS
========================================
*/

$query_best = mysqli_query($conn, "
    SELECT
        p.nama_produk,
        p.foto,
        SUM(dp.jumlah) AS total_terjual
    FROM detail_penjualan dp
    INNER JOIN produk p ON dp.id_produk = p.id_produk
    GROUP BY p.id_produk
    ORDER BY total_terjual DESC
    LIMIT 5
");

/*
========================================
AKTIVITAS TERAKHIR
========================================
*/

$query_recent = mysqli_query($conn, "
    SELECT
        p.id_jual,
        p.tgl_transaksi,
        p.total_bayar,
        pl.nama_pelanggan
    FROM penjualan p
    LEFT JOIN pelanggan pl ON p.id_pelanggan = pl.id_pelanggan
    ORDER BY p.id_jual DESC
    LIMIT 5
");

/*
========================================
DATA GRAFIK 30 HARI (1 QUERY ONLY 😤)
========================================
*/

$chart_map = [];
$label_7hari = [];
$data_7hari = [];
$label_30hari = [];
$data_30hari = [];

$q_chart = mysqli_query($conn, "
    SELECT
        DATE(tgl_transaksi) AS tanggal,
        SUM(total_bayar) AS total
    FROM penjualan
    WHERE tgl_transaksi >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY DATE(tgl_transaksi)
");

while ($row = mysqli_fetch_assoc($q_chart)) {
    $chart_map[$row['tanggal']] = (int) $row['total'];
}

for ($i = 29; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $label = date('d M', strtotime($tgl));
    $total = $chart_map[$tgl] ?? 0;

    $label_30hari[] = $label;
    $data_30hari[] = $total;

    if ($i <= 6) {
        $label_7hari[] = $label;
        $data_7hari[] = $total;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Jelly Potter ✨</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --milk-tea: #F5E6CA;
            --boba-brown: #5C3A21;
            --cream: rgba(255, 253, 249, 0.92);
            --accent: #8d735b;
            --danger: #e74c3c;
            --success: #2ecc71;
            --info: #3498db;
        }

        body {
            font-family: 'Quicksand', sans-serif;
            margin: 0;
            padding: 140px 0 50px 0;
            color: var(--boba-brown);
        }

        .dashboard-container {
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
            gap: 20px;
            flex-wrap: wrap;
        }

        .hero h1 {
            margin: 0;
            font-size: 2.3rem;
            color: var(--boba-brown);
        }

        .hero p {
            margin-top: 10px;
            color: var(--accent);
            font-weight: 600;
        }

        .btn-recruit {
            background: linear-gradient(135deg, #5C3A21, #3e2716);
            color: white;
            text-decoration: none;
            padding: 15px 24px;
            border-radius: 18px;
            font-weight: 700;
            transition: 0.25s ease;
            box-shadow: 0 10px 20px rgba(92, 58, 33, 0.2);
        }

        .btn-recruit:hover {
            transform: translateY(-3px);
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 18px;
            margin-bottom: 30px;
        }

        .quick-card {
            background: var(--cream);
            border-radius: 24px;
            padding: 22px;
            text-decoration: none;
            color: var(--boba-brown);
            box-shadow: 0 12px 25px rgba(92, 58, 33, 0.06);
            transition: 0.25s ease;
        }

        .quick-card:hover {
            transform: translateY(-5px);
        }

        .quick-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .quick-title {
            font-weight: 700;
            font-size: 1rem;
        }

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
            box-shadow: 0 12px 30px rgba(92, 58, 33, 0.08);
            display: flex;
            align-items: center;
            gap: 18px;
            transition: 0.25s ease;
            border: 2px solid transparent;
        }

        .stat-card:hover {
            transform: translateY(-6px);
            border-color: rgba(92, 58, 33, 0.15);
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

        .omzet .stat-icon {
            background: rgba(46, 204, 113, 0.12);
        }

        .trx .stat-icon {
            background: rgba(52, 152, 219, 0.12);
        }

        .stok .stat-icon {
            background: rgba(231, 76, 60, 0.12);
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
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--boba-brown);
        }

        .section-card {
            background: var(--cream);
            border-radius: 30px;
            padding: 30px;
            box-shadow: 0 12px 30px rgba(92, 58, 33, 0.06);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .section-header h2 {
            margin: 0;
            font-size: 1.6rem;
        }

        .chart-tabs {
            display: flex;
            background: #f1ece4;
            padding: 5px;
            border-radius: 16px;
            gap: 5px;
        }

        .chart-btn {
            border: none;
            background: transparent;
            padding: 10px 16px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            color: var(--accent);
            transition: 0.2s;
        }

        .chart-btn.active {
            background: var(--boba-brown);
            color: white;
        }

        .bestseller-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 20px;
        }

        .best-item {
            background: white;
            border-radius: 24px;
            padding: 20px;
            text-align: center;
            position: relative;
            border: 1px solid #f1ece4;
            transition: 0.25s ease;
        }

        .best-item:hover {
            transform: translateY(-5px);
        }

        .best-rank {
            position: absolute;
            top: 14px;
            left: 14px;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--boba-brown);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .best-img-wrap {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 15px;
            border: 5px solid var(--milk-tea);
        }

        .best-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .best-name {
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 10px;
        }

        .best-badge {
            display: inline-block;
            background: var(--milk-tea);
            padding: 6px 14px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.8rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 16px;
            border-bottom: 2px solid #f1ece4;
            font-size: 0.85rem;
            text-transform: uppercase;
            color: var(--accent);
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #f5f0e8;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.4);
        }

        .btn-view {
            text-decoration: none;
            color: var(--accent);
            font-weight: 700;
            background: #f8f3eb;
            padding: 10px 16px;
            border-radius: 14px;
        }

        .alert-stock {
            background: rgba(231, 76, 60, 0.08);
            border: 2px dashed rgba(231, 76, 60, 0.25);
            padding: 18px;
            border-radius: 20px;
            margin-bottom: 25px;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 1.7rem;
            }

            .section-card {
                padding: 20px;
            }

            .stat-value {
                font-size: 1.4rem;
            }
        }
    </style>
</head>

<body>

    <div class="dashboard-container">

        <div class="hero">
            <div>
                <h1>Welcome back, Admin ✨</h1>
                <p>Jelly Potter Command Center & Daily Performance</p>
            </div>

            <a href="../auth/signup.php" class="btn-recruit">
                🪄 Recruit New Crew
            </a>
        </div>

        <div class="quick-actions">
            <a href="../admin/pesanan.php" class="quick-card">
                <div class="quick-icon">🧋</div>
                <div class="quick-title">Buka Kasir</div>
            </a>

            <a href="../admin/tampil.php" class="quick-card">
                <div class="quick-icon">📦</div>
                <div class="quick-title">Kelola Menu</div>
            </a>

            <a href="../admin/pelanggan.php" class="quick-card">
                <div class="quick-icon">👥</div>
                <div class="quick-title">Data Member</div>
            </a>

            <a href="../admin/laporan.php" class="quick-card">
                <div class="quick-icon">📊</div>
                <div class="quick-title">Laporan</div>
            </a>
        </div>

        <?php if ($stok_kritis > 0): ?>
            <div class="alert-stock">
                ⚠️ Ada <?= $stok_kritis ?> menu dengan stok kritis. Segera lakukan restock.
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card omzet">
                <div class="stat-icon">💰</div>
                <div>
                    <div class="stat-label">Omzet Hari Ini</div>
                    <div class="stat-value">Rp <?= number_format($pendapatan, 0, ',', '.'); ?></div>
                </div>
            </div>

            <div class="stat-card trx">
                <div class="stat-icon">🧾</div>
                <div>
                    <div class="stat-label">Transaksi Hari Ini</div>
                    <div class="stat-value"><?= $transaksi; ?></div>
                </div>
            </div>

            <div class="stat-card stok">
                <div class="stat-icon">⚠️</div>
                <div>
                    <div class="stat-label">Stok Kritis</div>
                    <div class="stat-value"><?= $stok_kritis; ?> Menu</div>
                </div>
            </div>
        </div>
        <div class="section-card">
            <div class="section-header">
                <h2>🏆 Top 5 Menu Terlaris</h2>
            </div>

            <div class="bestseller-grid">
                <?php
                $rank = 1;
                while ($b = mysqli_fetch_assoc($query_best)):
                    $foto = !empty($b['foto']) ? $b['foto'] : 'default.png';
                    ?>
                    <div class="best-item">
                        <div class="best-rank"><?= $rank++; ?></div>

                        <div class="best-img-wrap">
                            <img src="img/<?= htmlspecialchars($foto); ?>"
                                alt="<?= htmlspecialchars($b['nama_produk']); ?>">
                        </div>

                        <div class="best-name">
                            <?= htmlspecialchars($b['nama_produk']); ?>
                        </div>

                        <div class="best-badge">
                            <?= (int) $b['total_terjual']; ?> Cup
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">
                <h2>📈 Tren Penjualan</h2>

                <div class="chart-tabs">
                    <button class="chart-btn active" onclick="switchChart(this, 7)">
                        7 Hari
                    </button>

                    <button class="chart-btn" onclick="switchChart(this, 30)">
                        30 Hari
                    </button>
                </div>
            </div>

            <canvas id="salesChart" height="110"></canvas>
        </div>

        <div class="section-card">
            <div class="section-header">
                <h2>✨ Aktivitas Terakhir</h2>

                <a href="../admin/transaksi.php" class="btn-view">
                    Lihat Semua →
                </a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID Transaksi</th>
                        <th>Waktu</th>
                        <th>Pelanggan</th>
                        <th>Total</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (mysqli_num_rows($query_recent) > 0): ?>
                        <?php while ($r = mysqli_fetch_assoc($query_recent)): ?>
                            <?php
                            $nama_pelanggan = !empty($r['nama_pelanggan'])
                                ? $r['nama_pelanggan']
                                : 'Pelanggan Umum';

                            $tgl = date('d M Y H:i', strtotime($r['tgl_transaksi']));
                            ?>
                            <tr>
                                <td>
                                    <strong>#<?= (int) $r['id_jual']; ?></strong>
                                </td>

                                <td>
                                    <?= htmlspecialchars($tgl); ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($nama_pelanggan); ?>
                                </td>

                                <td>
                                    <strong>
                                        Rp <?= number_format($r['total_bayar'], 0, ',', '.'); ?>
                                    </strong>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center; padding:30px; color:#888;">
                                Belum ada transaksi.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <script>
        const dataSet7 = {
            labels: <?= json_encode($label_7hari); ?>,
            data: <?= json_encode($data_7hari); ?>
        };

        const dataSet30 = {
            labels: <?= json_encode($label_30hari); ?>,
            data: <?= json_encode($data_30hari); ?>
        };

        const ctx = document.getElementById('salesChart').getContext('2d');

        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(92,58,33,0.20)');
        gradient.addColorStop(1, 'rgba(255,255,255,0)');

        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: dataSet7.labels,
                datasets: [{
                    data: dataSet7.data,
                    borderColor: '#5C3A21',
                    backgroundColor: gradient,
                    fill: true,
                    borderWidth: 4,
                    tension: 0.4,
                    pointRadius: 5,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#5C3A21',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(92,58,33,0.06)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        function switchChart(button, range) {
            document.querySelectorAll('.chart-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            button.classList.add('active');

            if (range === 7) {
                salesChart.data.labels = dataSet7.labels;
                salesChart.data.datasets[0].data = dataSet7.data;
            } else {
                salesChart.data.labels = dataSet30.labels;
                salesChart.data.datasets[0].data = dataSet30.data;
            }

            salesChart.update();
        }
    </script>

</body>

</html>