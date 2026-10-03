<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$is_admin = isset($_SESSION['jabatan']) && strtolower($_SESSION['jabatan']) === 'admin';
$nama_user = htmlspecialchars($_SESSION['nama_pegawai'] ?? 'Staff');

$menu_items = [];

if ($is_admin) {
    $menu_items = [
        ['file' => 'dashboard.php', 'label' => 'Dashboard', 'icon' => 'fa-chart-line'],
        ['file' => 'tampil.php', 'label' => 'Menu', 'icon' => 'fa-mug-hot'],
        ['file' => 'pesanan.php', 'label' => 'Pesanan', 'icon' => 'fa-cart-shopping'],
        ['file' => 'pelanggan.php', 'label' => 'Member', 'icon' => 'fa-users'],
        ['file' => 'transaksi.php', 'label' => 'Transaksi', 'icon' => 'fa-receipt'],
        ['file' => 'laporan.php', 'label' => 'Laporan', 'icon' => 'fa-file-lines'],
    ];
} else {
    $menu_items = [
        ['file' => 'pesanan.php', 'label' => 'Pesanan', 'icon' => 'fa-cart-shopping'],
        ['file' => 'pelanggan.php', 'label' => 'Member', 'icon' => 'fa-users'],
        ['file' => 'transaksi.php', 'label' => 'Transaksi', 'icon' => 'fa-receipt'],
    ];
}
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    :root {
        --milk-tea: #F5E6CA;
        --boba-brown: #5C3A21;
        --cream: rgba(255, 253, 249, 0.88);
        --accent: #8d735b;
        --danger: #e74c3c;
        --shadow: rgba(92, 58, 33, 0.12);
    }

    body {
        padding-top: 110px;
    }

    .jp-navbar {
        position: fixed;
        top: 18px;
        left: 50%;
        transform: translateX(-50%);
        width: 94%;
        max-width: 1450px;
        z-index: 99999;

        display: flex;
        justify-content: space-between;
        align-items: center;

        padding: 14px 22px;
        border-radius: 24px;

        background: var(--cream);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);

        border: 1px solid rgba(255, 255, 255, 0.5);
        box-shadow: 0 12px 30px var(--shadow);

        animation: jpFadeIn 0.45s ease;
        box-sizing: border-box;
    }

    @keyframes jpFadeIn {
        from {
            opacity: 0;
            transform: translateX(-50%) translateY(-18px);
        }
        to {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    }

    .jp-logo {
        text-decoration: none;
        font-family: 'Playfair Display', serif;
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--boba-brown);
        white-space: nowrap;
        transition: 0.25s ease;
    }

    .jp-logo:hover {
        transform: scale(1.03);
    }

    .jp-right {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .jp-user {
        background: rgba(245, 230, 202, 0.8);
        padding: 10px 14px;
        border-radius: 14px;
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--boba-brown);
        white-space: nowrap;
    }

    .jp-links {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .jp-links a {
        text-decoration: none;
        color: var(--accent);
        font-family: 'Quicksand', sans-serif;
        font-weight: 700;
        font-size: 0.9rem;

        padding: 10px 14px;
        border-radius: 14px;

        display: flex;
        align-items: center;
        gap: 8px;

        transition: all 0.25s ease;
    }

    .jp-links a:hover {
        background: rgba(245, 230, 202, 0.95);
        color: var(--boba-brown);
        transform: translateY(-2px);
    }

    .jp-links a.active {
        background: var(--boba-brown);
        color: white;
        box-shadow: 0 8px 18px rgba(92, 58, 33, 0.22);
    }

    .jp-logout {
        background: rgba(231, 76, 60, 0.12);
        color: var(--danger) !important;
    }

    .jp-logout:hover {
        background: rgba(231, 76, 60, 0.22) !important;
    }

    .jp-toggle {
        display: none;
        background: transparent;
        border: none;
        font-size: 1.4rem;
        color: var(--boba-brown);
        cursor: pointer;
    }

    .swal2-container {
        z-index: 999999 !important;
    }

    .swal2-popup {
        border-radius: 22px !important;
    }

    @media (max-width: 1150px) {
        .jp-user {
            display: none;
        }

        .jp-links a {
            font-size: 0.82rem;
            padding: 9px 11px;
        }
    }

    @media (max-width: 768px) {
        body {
            padding-top: 95px;
        }

        .jp-navbar {
            top: 12px;
            padding: 14px 16px;
            flex-wrap: wrap;
        }

        .jp-toggle {
            display: block;
        }

        .jp-right {
            width: 100%;
            flex-direction: column;
            align-items: stretch;
            display: none;
            margin-top: 14px;
        }

        .jp-right.show {
            display: flex;
        }

        .jp-links {
            flex-direction: column;
            width: 100%;
            gap: 8px;
        }

        .jp-links a {
            width: 100%;
            justify-content: center;
            box-sizing: border-box;
        }

        .jp-user {
            display: block;
            text-align: center;
        }
    }

    @media print {
        .jp-navbar {
            display: none !important;
        }

        body {
            padding-top: 0;
        }
    }
</style>

<nav class="jp-navbar">
    <a href="<?= $is_admin ? 'dashboard.php' : 'pesanan.php'; ?>" class="jp-logo">
        Jelly Potter 🪄
    </a>

    <button class="jp-toggle" onclick="toggleNavbar()" aria-label="Toggle navigation">
        <i class="fas fa-bars"></i>
    </button>

    <div class="jp-right" id="jpMenu">

        <div class="jp-user">
            👋 Halo, <?= $nama_user; ?>
        </div>

        <div class="jp-links">

            <?php foreach ($menu_items as $item): ?>
                <a href="<?= $item['file']; ?>" class="<?= $current_page === $item['file'] ? 'active' : ''; ?>">
                    <i class="fas <?= $item['icon']; ?>"></i>
                    <?= $item['label']; ?>
                </a>
            <?php endforeach; ?>

            <a href="#" class="jp-logout" onclick="confirmLogout(event)">
                <i class="fas fa-right-from-bracket"></i>
                Keluar
            </a>

        </div>
    </div>
</nav>

<script>
    function toggleNavbar() {
        document.getElementById('jpMenu').classList.toggle('show');
    }

    document.querySelectorAll('.jp-links a').forEach(link => {
        link.addEventListener('click', () => {
            document.getElementById('jpMenu').classList.remove('show');
        });
    });

    function confirmLogout(e) {
        e.preventDefault();

        Swal.fire({
            title: 'Keluar dari Jelly Potter?',
            text: 'Sesi kamu akan diakhiri.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#5C3A21',
            cancelButtonColor: '#b5b5b5',
            confirmButtonText: 'Ya, keluar',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'auth/logout.php';
            }
        });
    }
</script>