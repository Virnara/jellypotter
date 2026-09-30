<?php
session_start();
include 'koneksi.php';

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
| CART INIT
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['keranjang']) || !is_array($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

/*
|--------------------------------------------------------------------------
| VALID CATEGORIES
|--------------------------------------------------------------------------
*/
$allowed_categories = ['Semua', 'Boba', 'Coffee', 'Snacks'];

/*
|--------------------------------------------------------------------------
| ADD TO CART
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_cart'])) {

    $is_ajax =
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {

        if ($is_ajax) {
            header('Content-Type: application/json');
            ob_clean();
            echo json_encode([
                'success' => false,
                'message' => 'CSRF invalid'
            ]);
            exit;
        }

        header("Location: katalog.php");
        exit;
    }

    $id_produk = intval($_POST['id_produk'] ?? 0);
    $success = false;
    $cart_count = array_sum($_SESSION['keranjang']);

    if ($id_produk > 0) {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id_produk, stok
             FROM produk
             WHERE id_produk = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "i", $id_produk);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $produk = mysqli_fetch_assoc($res);

        if ($produk) {
            $stok = intval($produk['stok']);
            $qty = intval($_SESSION['keranjang'][$id_produk] ?? 0);

            if ($qty < $stok) {
                $_SESSION['keranjang'][$id_produk] = $qty + 1;
                $success = true;
            }
        }

        mysqli_stmt_close($stmt);
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $cart_count = array_sum($_SESSION['keranjang']);

    if ($is_ajax) {
        header('Content-Type: application/json');
        ob_clean();
        echo json_encode([
            'success' => $success,
            'cart_count' => $cart_count,
            'csrf_token' => $_SESSION['csrf_token']
        ]);
        exit;
    }

    header("Location: katalog.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| FILTER CATEGORY
|--------------------------------------------------------------------------
*/
$kategori_terpilih = trim($_GET['kategori'] ?? 'Semua');

if (!in_array($kategori_terpilih, $allowed_categories, true)) {
    $kategori_terpilih = 'Semua';
}

/*
|--------------------------------------------------------------------------
| FETCH PRODUCTS
|--------------------------------------------------------------------------
*/
if ($kategori_terpilih === 'Semua') {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id_produk, nama_produk, kategori, harga, stok, foto, deskripsi
         FROM produk
         ORDER BY nama_produk ASC"
    );

} else {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id_produk, nama_produk, kategori, harga, stok, foto, deskripsi
         FROM produk
         WHERE kategori = ?
         ORDER BY nama_produk ASC"
    );

    mysqli_stmt_bind_param($stmt, "s", $kategori_terpilih);
}

mysqli_stmt_execute($stmt);
$query = mysqli_stmt_get_result($stmt);
$total_menu = mysqli_num_rows($query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Magic Menu | Jelly Potter ✨</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Quicksand:wght@400;600;700&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --milk: #FFF8F0;
            --brown: #5C3A21;
            --accent: #E8A87C;
            --cream: #FFFDF9;
            --muted: #8b6e57;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Quicksand', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(232, 168, 124, 0.22), transparent 30%),
                radial-gradient(circle at bottom right, rgba(245, 230, 202, 0.8), transparent 40%),
                linear-gradient(180deg, #FFFDF9 0%, #FFF7EE 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                radial-gradient(rgba(92, 58, 33, 0.03) 2px, transparent 2px);
            background-size: 42px 42px;
            pointer-events: none;
            z-index: -1;
        }

        /* floating bubbles */
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 15% 20%, rgba(255, 255, 255, 0.55) 0 40px, transparent 45px),
                radial-gradient(circle at 85% 30%, rgba(232, 168, 124, 0.18) 0 60px, transparent 70px),
                radial-gradient(circle at 70% 80%, rgba(255, 255, 255, 0.45) 0 35px, transparent 40px);
            pointer-events: none;
            z-index: -1;
        }

        .katalog-page {
            max-width: 1300px;
            margin: 0 auto;
            padding: 130px 20px 100px;
        }

        .hero-menu {
            background: rgba(255, 255, 255, 0.72);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 36px;
            padding: 45px;
            box-shadow: 0 18px 45px rgba(92, 58, 33, 0.08);
            text-align: center;
            margin-bottom: 40px;
            animation: fadeUp .8s ease;
        }

        .hero-menu h1 {
            margin: 0;
            font-family: 'Playfair Display', serif;
            font-size: 3.8rem;
            color: var(--brown);
        }

        .hero-menu p {
            margin-top: 15px;
            color: var(--muted);
            font-size: 1.05rem;
            font-weight: 600;
        }

        .menu-count {
            display: inline-block;
            margin-top: 18px;
            background: var(--milk);
            color: var(--brown);
            padding: 10px 18px;
            border-radius: 30px;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(92, 58, 33, 0.08);
        }

        .filter-container {
            display: flex;
            justify-content: center;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 28px;
        }

        .btn-filter {
            text-decoration: none;
            padding: 12px 22px;
            border-radius: 30px;
            font-weight: 700;
            color: var(--brown);
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.8);
            transition: .3s;
            box-shadow: 0 8px 20px rgba(92, 58, 33, 0.05);
        }

        .btn-filter:hover {
            transform: translateY(-3px);
            background: var(--accent);
            color: white;
        }

        .btn-filter.active {
            background: linear-gradient(135deg, var(--brown), #3e2716);
            color: white;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 28px;
        }

        .menu-card {
            background: rgba(255, 255, 255, 0.78);
            backdrop-filter: blur(14px);
            border-radius: 30px;
            padding: 26px;
            border: 1px solid rgba(255, 255, 255, 0.75);
            box-shadow: 0 18px 35px rgba(92, 58, 33, 0.06);
            transition: .35s;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            animation: fadeUp .5s ease;
        }

        .menu-card:hover {
            transform: translateY(-10px) scale(1.01);
            box-shadow: 0 25px 45px rgba(92, 58, 33, 0.12);
        }

        .menu-img-wrap {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .menu-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: .4s;
        }

        .menu-card:hover .menu-img {
            transform: scale(1.08);
        }

        .badge-kategori {
            background: var(--milk);
            color: var(--brown);
            padding: 8px 14px;
            border-radius: 20px;
            font-size: .8rem;
            font-weight: 700;
            margin-bottom: 14px;
        }

        .menu-card h3 {
            margin: 0 0 10px;
            font-family: 'Playfair Display', serif;
            font-size: 1.45rem;
            color: var(--brown);
        }

        .menu-card p {
            color: var(--muted);
            font-size: .95rem;
            line-height: 1.6;
            min-height: 72px;
        }

        .price-stock {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 18px 0 20px;
        }

        .menu-price {
            font-weight: 800;
            color: var(--brown);
            font-size: 1.1rem;
        }

        .stock-badge {
            padding: 8px 12px;
            border-radius: 18px;
            font-size: .82rem;
            font-weight: 700;
        }

        .stock-ready {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .stock-low {
            background: #fff3e0;
            color: #e65100;
        }

        .stock-empty {
            background: #ffebee;
            color: #c62828;
        }

        .cart-form {
            width: 100%;
        }

        .add-cart-btn {
            width: 100%;
            border: none;
            padding: 16px;
            border-radius: 22px;
            background: linear-gradient(135deg, var(--accent), #d98f5d);
            color: white;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: .3s;
        }

        .add-cart-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 28px rgba(232, 168, 124, 0.35);
        }

        .add-cart-btn.loading {
            opacity: .9;
            pointer-events: none;
        }

        .sold-btn {
            background: #ddd;
            color: #777;
            cursor: not-allowed;
        }

        .floating-cart {
            position: fixed;
            right: 28px;
            bottom: 28px;
            z-index: 999;
            text-decoration: none;
            background: linear-gradient(135deg, var(--brown), #3e2716);
            color: white;
            padding: 16px 24px;
            border-radius: 40px;
            font-weight: 700;
            box-shadow: 0 18px 35px rgba(92, 58, 33, 0.28);
            transition: .3s;
        }

        .floating-cart:hover {
            transform: translateY(-5px);
        }

        .cart-count {
            background: white;
            color: var(--brown);
            border-radius: 50%;
            padding: 4px 9px;
            margin-left: 8px;
            font-size: .85rem;
        }

        .toast-fix {
            margin-top: 90px !important;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media(max-width:768px) {

            .hero-menu {
                padding: 30px 20px;
            }

            .hero-menu h1 {
                font-size: 2.4rem;
            }

            .floating-cart {
                right: 16px;
                bottom: 16px;
                padding: 14px 18px;
            }
        }
    </style>
</head>

<body>

    <?php include 'navbar_pengunjung.php'; ?>

    <div class="katalog-page">

        <div class="hero-menu">
            <h1>Magic Menu ✨</h1>
            <p>
                Pilih ramuan favoritmu, masukkan ke keranjang, dan biarkan Jelly Potter meracik keajaiban ☕🧋
            </p>

            <div class="menu-count">
                📦 <?= $total_menu; ?> Menu Tersedia
            </div>

            <div class="filter-container">
                <a href="katalog.php?kategori=Semua"
                    class="btn-filter <?= ($kategori_terpilih === 'Semua') ? 'active' : ''; ?>">
                    All Menu 🍽️
                </a>

                <a href="katalog.php?kategori=Boba"
                    class="btn-filter <?= ($kategori_terpilih === 'Boba') ? 'active' : ''; ?>">
                    Boba Vibes 🧋
                </a>

                <a href="katalog.php?kategori=Coffee"
                    class="btn-filter <?= ($kategori_terpilih === 'Coffee') ? 'active' : ''; ?>">
                    Caffeine Potion ☕
                </a>

                <a href="katalog.php?kategori=Snacks"
                    class="btn-filter <?= ($kategori_terpilih === 'Snacks') ? 'active' : ''; ?>">
                    Magic Bites 🍟
                </a>
            </div>
        </div>

        <div class="menu-grid">

            <?php if ($total_menu > 0): ?>

                <?php while ($row = mysqli_fetch_assoc($query)): ?>

                    <?php
                    $foto = !empty($row['foto']) ? basename($row['foto']) : 'default.png';
                    $stok = intval($row['stok']);
                    ?>

                    <div class="menu-card">

                        <div class="menu-img-wrap">
                            <img src="img/<?= htmlspecialchars($foto); ?>" class="menu-img"
                                alt="<?= htmlspecialchars($row['nama_produk']); ?>">
                        </div>

                        <div class="badge-kategori">
                            <?= htmlspecialchars($row['kategori']); ?>
                        </div>

                        <h3><?= htmlspecialchars($row['nama_produk']); ?></h3>

                        <p><?= htmlspecialchars($row['deskripsi']); ?></p>

                        <div class="price-stock">

                            <div class="menu-price">
                                Rp <?= number_format($row['harga'], 0, ',', '.'); ?>
                            </div>

                            <?php if ($stok <= 0): ?>
                                <div class="stock-badge stock-empty">
                                    Habis
                                </div>

                            <?php elseif ($stok <= 5): ?>
                                <div class="stock-badge stock-low">
                                    🔥 Sisa <?= $stok; ?>
                                </div>

                            <?php else: ?>
                                <div class="stock-badge stock-ready">
                                    Stok <?= $stok; ?>
                                </div>
                            <?php endif; ?>

                        </div>

                        <?php if ($stok > 0): ?>

                            <form method="POST" class="cart-form">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                                <input type="hidden" name="id_produk" value="<?= (int) $row['id_produk']; ?>">

                                <button type="submit" name="tambah_cart" class="add-cart-btn">
                                    🛒 Tambah ke Keranjang
                                </button>
                            </form>

                        <?php else: ?>

                            <button class="add-cart-btn sold-btn">
                                ❌ Sold Out
                            </button>

                        <?php endif; ?>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="glass-card" style="
                grid-column:1/-1;
                text-align:center;
                padding:60px;
            ">
                    <h2 style="
                    margin:0;
                    font-family:'Playfair Display',serif;
                    color:#5C3A21;
                ">
                        Menu belum tersedia 🥺
                    </h2>

                    <p style="color:#8b6e57; margin-top:14px;">
                        Kategori ini masih kosong. Coba jelajahi kategori lain ✨
                    </p>
                </div>

            <?php endif; ?>

        </div>

    </div>

    <a href="keranjang.php" class="floating-cart" id="floatingCart"
        style="<?= empty($_SESSION['keranjang']) ? 'display:none;' : '' ?>">
        🛒 Lihat Keranjang
        <span class="cart-count" id="cartCount">
            <?= array_sum(array_map('intval', $_SESSION['keranjang'])); ?>
        </span>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2200,
            timerProgressBar: true,
            customClass: {
                popup: 'toast-fix'
            }
        });

        const params = new URLSearchParams(window.location.search);

        if (params.get('status') === 'tambah') {
            Toast.fire({
                icon: 'success',
                title: 'Masuk ke keranjang 🧋✨'
            });

            window.history.replaceState(null, null, window.location.pathname);
        }

        document.querySelectorAll('.cart-form').forEach(form => {

            form.addEventListener('submit', async function (e) {
                e.preventDefault();

                const btn = form.querySelector('.add-cart-btn');
                const csrfInput = form.querySelector('input[name="csrf_token"]');

                btn.disabled = true;
                btn.innerHTML = '🪄 Menambahkan...';

                try {
                    const response = await fetch('ajax_tambah_keranjang.php', {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();

                    if (data.success) {

                        Toast.fire({
                            icon: 'success',
                            title: 'Masuk ke keranjang 🧋✨'
                        });

                        const floatingCart = document.getElementById('floatingCart');
                        const cartCount = document.getElementById('cartCount');

                        if (data.success) {
                            cartCount.textContent = data.cart_count;
                            floatingCart.style.display = 'flex';
                        }

                    } else {
                        Toast.fire({
                            icon: 'warning',
                            title: 'Stok habis atau gagal tambah 😢'
                        });
                    }

                } catch (err) {
                    console.log(err);
                    Toast.fire({
                        icon: 'error',
                        title: 'Koneksi bermasalah 😵'
                    });
                }

                btn.disabled = false;
                btn.innerHTML = '🛒 Tambah ke Keranjang';
            });

        });
    </script>

</body>

</html>