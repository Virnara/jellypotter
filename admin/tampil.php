<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_pegawai'])) {
    header("location: ../auth/login.php");
    exit;
}

if (!isset($_SESSION['jabatan']) || strtolower($_SESSION['jabatan']) !== 'admin') {
    header("location: ../admin/pesanan.php");
    exit;
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
include '../includes/navbar.php';

$query = mysqli_query($conn, "
    SELECT id_produk, nama_produk, harga, stok, foto
    FROM produk
    ORDER BY id_produk DESC
");

$total_menu = mysqli_num_rows($query);

$stok_aman = 0;
$stok_warning = 0;
$stok_kritis = 0;

$menu_data = [];

while ($row = mysqli_fetch_assoc($query)) {
    $stok = (int)$row['stok'];

    if ($stok <= 5) {
        $stok_kritis++;
        $row['stok_status'] = 'kritis';
    } elseif ($stok <= 15) {
        $stok_warning++;
        $row['stok_status'] = 'warning';
    } else {
        $stok_aman++;
        $row['stok_status'] = 'aman';
    }

    $menu_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Control | Jelly Potter ✨</title>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --milk-tea: #F5E6CA;
            --boba-brown: #5C3A21;
            --cream: rgba(255, 253, 249, 0.92);
            --accent: #8d735b;
            --danger: #e74c3c;
            --warning: #ef6c00;
            --success: #2ecc71;
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
            color: var(--boba-brown);
        }

        .hero p {
            margin-top: 10px;
            color: var(--accent);
            font-weight: 600;
        }

        .btn-add {
            background: linear-gradient(135deg, #5C3A21, #3e2716);
            color: white;
            text-decoration: none;
            padding: 15px 24px;
            border-radius: 18px;
            font-weight: 700;
            transition: 0.25s ease;
            box-shadow: 0 10px 20px rgba(92,58,33,0.2);
        }

        .btn-add:hover {
            transform: translateY(-3px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--cream);
            border-radius: 28px;
            padding: 24px;
            box-shadow: 0 12px 30px rgba(92,58,33,0.08);
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .stat-icon {
            width: 65px;
            height: 65px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .success .stat-icon {
            background: rgba(46, 204, 113, 0.12);
        }

        .warning .stat-icon {
            background: rgba(239, 108, 0, 0.12);
        }

        .danger .stat-icon {
            background: rgba(231, 76, 60, 0.12);
        }

        .stat-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            color: var(--accent);
            font-weight: 700;
        }

        .stat-value {
            font-size: 1.7rem;
            font-weight: 700;
            margin-top: 6px;
        }

        .section-card {
            background: var(--cream);
            border-radius: 30px;
            padding: 30px;
            box-shadow: 0 12px 30px rgba(92,58,33,0.06);
            margin-bottom: 30px;
        }

        .toolbar {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid #f1ece4;
            border-radius: 18px;
            font-size: 0.95rem;
            box-sizing: border-box;
            outline: none;
        }

        .search-box input:focus {
            border-color: var(--boba-brown);
        }

        .filter-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-btn {
            border: none;
            background: #f1ece4;
            color: var(--accent);
            padding: 12px 16px;
            border-radius: 14px;
            cursor: pointer;
            font-weight: 700;
            transition: 0.2s;
        }

        .filter-btn.active {
            background: var(--boba-brown);
            color: white;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 1.7rem;
            }

            .section-card {
                padding: 20px;
            }
        }
    </style>
</head>

<body>

<div class="page-container">

    <div class="hero">
        <div>
            <h1>Menu Command Center 🧋✨</h1>
            <p>Kelola semua racikan Jelly Potter dalam satu tempat premium</p>
        </div>

        <a href="../admin/tambah_menu.php" class="btn-add">
            🍹 Tambah Menu Baru
        </a>
    </div>

    <div class="stats-grid">
        <div class="stat-card success">
            <div class="stat-icon">📦</div>
            <div>
                <div class="stat-label">Total Menu</div>
                <div class="stat-value"><?= $total_menu ?></div>
            </div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon">🟠</div>
            <div>
                <div class="stat-label">Stok Menipis</div>
                <div class="stat-value"><?= $stok_warning ?></div>
            </div>
        </div>

        <div class="stat-card danger">
            <div class="stat-icon">⚠️</div>
            <div>
                <div class="stat-label">Stok Kritis</div>
                <div class="stat-value"><?= $stok_kritis ?></div>
            </div>
        </div>
    </div>

    <div class="section-card">

        <div class="toolbar">
            <div class="search-box">
                <input
                    type="text"
                    id="searchMenu"
                    placeholder="🔍 Cari menu..."
                >
            </div>

            <div class="filter-group">
                <button class="filter-btn active" onclick="filterMenu('all', this)">Semua</button>
                <button class="filter-btn" onclick="filterMenu('aman', this)">Aman</button>
                <button class="filter-btn" onclick="filterMenu('warning', this)">Menipis</button>
                <button class="filter-btn" onclick="filterMenu('kritis', this)">Kritis</button>
            </div>
        </div>
                <div
            id="menuGrid"
            style="
                display:grid;
                grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));
                gap:20px;
            "
        >

            <?php if (!empty($menu_data)): ?>
                <?php foreach ($menu_data as $row): ?>

                    <?php
                    $id_produk = (int)$row['id_produk'];
                    $nama_produk = htmlspecialchars($row['nama_produk']);
                    $harga = (int)$row['harga'];
                    $stok = (int)$row['stok'];
                    $status = $row['stok_status'];

                    $foto = !empty($row['foto'])
                        ? "img/" . htmlspecialchars($row['foto'])
                        : "img/default.png";

                    if ($status === 'kritis') {
                        $badge_bg = "#ffebee";
                        $badge_color = "#c62828";
                        $badge_text = "⚠️ Stok Kritis ($stok)";
                    } elseif ($status === 'warning') {
                        $badge_bg = "#fff3e0";
                        $badge_color = "#ef6c00";
                        $badge_text = "🟠 Menipis ($stok)";
                    } else {
                        $badge_bg = "#e8f5e9";
                        $badge_color = "#2e7d32";
                        $badge_text = "🟢 Aman ($stok)";
                    }
                    ?>

                    <div
                        class="menu-card"
                        data-name="<?= strtolower($nama_produk); ?>"
                        data-status="<?= $status; ?>"
                        style="
                            background:white;
                            border-radius:26px;
                            padding:20px;
                            box-shadow:0 10px 24px rgba(92,58,33,0.06);
                            border:1px solid #f3ece2;
                            transition:0.25s ease;
                        "
                    >

                        <div style="
                            display:flex;
                            gap:16px;
                            align-items:center;
                            margin-bottom:18px;
                        ">
                            <img
                                src="<?= $foto ?>"
                                alt="<?= $nama_produk ?>"
                                onerror="this.src='img/default.png'"
                                style="
                                    width:90px;
                                    height:90px;
                                    border-radius:20px;
                                    object-fit:cover;
                                    border:4px solid #F5E6CA;
                                    flex-shrink:0;
                                "
                            >

                            <div style="flex:1;">
                                <h3 style="
                                    margin:0;
                                    font-size:1.1rem;
                                    line-height:1.3;
                                    color:#5C3A21;
                                ">
                                    <?= $nama_produk ?>
                                </h3>

                                <div style="
                                    margin-top:8px;
                                    font-weight:700;
                                    color:#8d735b;
                                    font-size:1rem;
                                ">
                                    Rp <?= number_format($harga, 0, ',', '.'); ?>
                                </div>

                                <span style="
                                    display:inline-block;
                                    margin-top:10px;
                                    padding:8px 14px;
                                    border-radius:14px;
                                    background:<?= $badge_bg ?>;
                                    color:<?= $badge_color ?>;
                                    font-weight:700;
                                    font-size:0.8rem;
                                ">
                                    <?= $badge_text ?>
                                </span>
                            </div>
                        </div>

                        <div style="
                            display:flex;
                            gap:10px;
                            flex-wrap:wrap;
                        ">
                            <a
                                href="../edit.php?id=<?= $id_produk ?>"
                                style="
                                    flex:1;
                                    text-align:center;
                                    text-decoration:none;
                                    padding:12px;
                                    border-radius:16px;
                                    background:#fff3e0;
                                    color:#ef6c00;
                                    font-weight:700;
                                "
                            >
                                ✏️ Edit
                            </a>

                            <button
                                onclick="hapusMenu(<?= $id_produk ?>, '<?= addslashes($nama_produk) ?>')"
                                style="
                                    flex:1;
                                    border:none;
                                    padding:12px;
                                    border-radius:16px;
                                    background:#ffebee;
                                    color:#c62828;
                                    font-weight:700;
                                    cursor:pointer;
                                "
                            >
                                🗑️ Hapus
                            </button>
                        </div>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div style="
                    text-align:center;
                    padding:60px 20px;
                    grid-column:1/-1;
                    color:#8d735b;
                ">
                    <h2 style="margin-bottom:10px;">Belum Ada Menu 😢</h2>
                    <p>Tambahkan menu pertama Jelly Potter dulu ✨</p>
                </div>

            <?php endif; ?>

        </div>

    </div>
</div>
<script>
   function hapusMenu(id, nama) {
    Swal.fire({
        title: 'Hapus Menu?',
        html: 'Yakin mau hapus <b>' + nama + '</b>?<br>Data tidak bisa dikembalikan 😶',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#c62828',
        cancelButtonColor: '#8d735b',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        background: '#FFFDF9',
        color: '#5C3A21'
    }).then((result) => {
        if (result.isConfirmed) {

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../hapus.php';

            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = 'csrf_token';
            csrfInput.value = '<?= $_SESSION['csrf_token']; ?>';

            form.appendChild(idInput);
            form.appendChild(csrfInput);

            document.body.appendChild(form);
            form.submit();
        }
    });
}
    function filterMenu(status, btn) {
        document.querySelectorAll('.filter-btn').forEach(button => {
            button.classList.remove('active');
        });

        btn.classList.add('active');

        const cards = document.querySelectorAll('.menu-card');

        cards.forEach(card => {
            const cardStatus = card.dataset.status;

            if (status === 'all' || cardStatus === status) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    document.getElementById('searchMenu').addEventListener('input', function() {
        const keyword = this.value.toLowerCase();
        const cards = document.querySelectorAll('.menu-card');

        cards.forEach(card => {
            const name = card.dataset.name;

            if (name.includes(keyword)) {
                if (card.style.display !== 'none' || keyword.length > 0) {
                    card.style.display = 'block';
                }
            } else {
                card.style.display = 'none';
            }
        });
    });

    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.get('status') === 'terhapus') {
        Swal.fire({
            title: 'Berhasil! 🎉',
            text: 'Menu berhasil dihapus dari Jelly Potter.',
            icon: 'success',
            confirmButtonColor: '#5C3A21',
            background: '#FFFDF9',
            color: '#5C3A21'
        });

        window.history.replaceState({}, document.title, window.location.pathname);
    }

    document.querySelectorAll('.menu-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-6px)';
            card.style.boxShadow = '0 18px 35px rgba(92,58,33,0.12)';
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
            card.style.boxShadow = '0 10px 24px rgba(92,58,33,0.06)';
        });
    });
</script>

<style>
    @media (max-width: 768px) {
        .page-container {
            width: 92% !important;
        }

        .hero {
            padding: 22px !important;
            border-radius: 24px !important;
        }

        .hero h1 {
            font-size: 1.6rem !important;
        }

        .hero p {
            font-size: 0.85rem !important;
        }

        .btn-add {
            width: 100%;
            text-align: center;
            box-sizing: border-box;
        }

        .stats-grid {
            grid-template-columns: 1fr !important;
        }

        #menuGrid {
            grid-template-columns: 1fr !important;
        }

        .toolbar {
            flex-direction: column !important;
        }

        .filter-group {
            width: 100%;
            justify-content: center;
        }

        .filter-btn {
            flex: 1;
        }

        .menu-card img {
            width: 75px !important;
            height: 75px !important;
        }

        .menu-card h3 {
            font-size: 1rem !important;
        }
    }

    @media (max-width: 480px) {
        .menu-card > div:first-child {
            flex-direction: column !important;
            align-items: flex-start !important;
        }

        .menu-card img {
            width: 100% !important;
            height: 180px !important;
            border-radius: 18px !important;
        }
    }
</style>

</body>
</html>