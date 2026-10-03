<?php
session_start();
include 'koneksi.php';

/*
|--------------------------------------------------------------------------
| CSRF
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
| HANDLE POST ACTIONS
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        header("Location: keranjang.php");
        exit;
    }

    // HAPUS ITEM
    if (isset($_POST['hapus'])) {
        $id = intval($_POST['hapus']);

        if ($id > 0 && isset($_SESSION['keranjang'][$id])) {
            unset($_SESSION['keranjang'][$id]);
        }

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        header("Location: keranjang.php?status=hapus");
        exit;
    }

    // TAMBAH QTY
    if (isset($_POST['plus'])) {
        $id = intval($_POST['plus']);

        if ($id > 0 && isset($_SESSION['keranjang'][$id])) {

            $stmt = mysqli_prepare(
                $conn,
                "SELECT stok FROM produk WHERE id_produk = ? LIMIT 1"
            );

            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($res);

            if ($data && $_SESSION['keranjang'][$id] < intval($data['stok'])) {
                $_SESSION['keranjang'][$id]++;
            }

            mysqli_stmt_close($stmt);
        }

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        header("Location: keranjang.php");
        exit;
    }

    // KURANG QTY
    if (isset($_POST['minus'])) {
        $id = intval($_POST['minus']);

        if ($id > 0 && isset($_SESSION['keranjang'][$id])) {
            $_SESSION['keranjang'][$id]--;

            if ($_SESSION['keranjang'][$id] <= 0) {
                unset($_SESSION['keranjang'][$id]);
            }
        }

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        header("Location: keranjang.php");
        exit;
    }

    // KOSONGKAN
    if (isset($_POST['kosongkan'])) {
        $_SESSION['keranjang'] = [];

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        header("Location: keranjang.php?status=clear");
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| FETCH CART
|--------------------------------------------------------------------------
*/
$cart_products = [];
$total_bayar = 0;

if (!empty($_SESSION['keranjang'])) {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT id_produk, nama_produk, harga, foto
         FROM produk
         WHERE id_produk = ?
         LIMIT 1"
    );

    foreach ($_SESSION['keranjang'] as $id_produk => $jumlah) {

        $id_produk = intval($id_produk);
        $jumlah = intval($jumlah);

        if ($id_produk <= 0 || $jumlah <= 0) {
            continue;
        }

        mysqli_stmt_bind_param($stmt, "i", $id_produk);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $prod = mysqli_fetch_assoc($res);

        if (!$prod)
            continue;

        $subtotal = intval($prod['harga']) * $jumlah;
        $total_bayar += $subtotal;

        $cart_products[] = [
            'id_produk' => $id_produk,
            'nama_produk' => $prod['nama_produk'],
            'harga' => intval($prod['harga']),
            'jumlah' => $jumlah,
            'subtotal' => $subtotal,
            'foto' => !empty($prod['foto']) ? basename($prod['foto']) : 'default.png'
        ];
    }

    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Magic Cart | Jelly Potter ✨</title>

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
                radial-gradient(circle at top left, rgba(232, 168, 124, 0.25), transparent 35%),
                radial-gradient(circle at bottom right, rgba(245, 230, 202, 0.8), transparent 40%),
                linear-gradient(180deg, #FFFDF9 0%, #FFF7EE 100%);
            min-height: 100vh;
            color: #333;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                radial-gradient(rgba(92, 58, 33, 0.03) 2px, transparent 2px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: -1;
        }

        .cart-page {
            max-width: 1300px;
            margin: 0 auto;
            padding: 130px 20px 80px;
        }

        .cart-hero {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeUp .8s ease;
        }

        .cart-hero h1 {
            margin: 0;
            font-size: 3.5rem;
            font-family: 'Playfair Display', serif;
            color: var(--brown);
        }

        .cart-hero p {
            margin-top: 14px;
            font-size: 1.05rem;
            color: var(--muted);
            font-weight: 600;
        }

        .cart-layout {
            display: grid;
            grid-template-columns: 1.8fr 1fr;
            gap: 30px;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.65);
            border-radius: 30px;
            padding: 30px;
            box-shadow: 0 18px 45px rgba(92, 58, 33, 0.08);
        }

        .cart-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .cart-toolbar a {
            text-decoration: none;
            color: var(--brown);
            font-weight: 700;
        }

        .toolbar-btn {
            border: none;
            background: none;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            color: #c62828;
        }

        .cart-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid rgba(92, 58, 33, 0.08);
            animation: fadeUp .5s ease;
        }

        .item-left {
            display: flex;
            align-items: center;
            gap: 18px;
            flex: 1;
        }

        .item-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 24px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .item-info h3 {
            margin: 0 0 8px;
            font-family: 'Playfair Display', serif;
            color: var(--brown);
            font-size: 1.35rem;
        }

        .item-price {
            color: var(--muted);
            font-weight: 700;
        }

        .qty-controls {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 14px;
        }

        .qty-btn {
            width: 42px;
            height: 42px;
            border: none;
            border-radius: 50%;
            background: var(--milk);
            color: var(--brown);
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: .25s;
        }

        .qty-btn:hover {
            transform: scale(1.08);
            background: var(--accent);
            color: white;
        }

        .qty-badge {
            min-width: 44px;
            text-align: center;
            font-weight: 700;
            font-size: 1rem;
        }

        .remove-btn {
            border: none;
            background: none;
            color: #c62828;
            font-weight: 700;
            cursor: pointer;
            margin-top: 14px;
            font-family: inherit;
        }

        .item-total {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--brown);
        }

        .checkout-card h2 {
            margin-top: 0;
            font-family: 'Playfair Display', serif;
            color: var(--brown);
            font-size: 2rem;
        }

        .total-box {
            background: linear-gradient(135deg, #5C3A21, #3e2716);
            color: white;
            padding: 24px;
            border-radius: 24px;
            text-align: center;
            margin-bottom: 24px;
            box-shadow: 0 15px 30px rgba(92, 58, 33, 0.22);
        }

        .total-box small {
            display: block;
            opacity: .8;
            margin-bottom: 10px;
        }

        .total-box strong {
            font-size: 2rem;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--brown);
        }

        .form-control {
            width: 100%;
            padding: 16px;
            border-radius: 18px;
            border: 2px solid #f1ece4;
            font-family: inherit;
            font-size: .95rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--brown);
        }

        .checkout-btn {
            width: 100%;
            border: none;
            padding: 18px;
            border-radius: 22px;
            background: linear-gradient(135deg, var(--accent), #d98f5d);
            color: white;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: .3s;
        }

        .checkout-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 30px rgba(232, 168, 124, 0.35);
        }

        .empty-cart {
            text-align: center;
            padding: 70px 20px;
        }

        .empty-cart h2 {
            font-family: 'Playfair Display', serif;
            color: var(--brown);
            font-size: 2.2rem;
        }

        .empty-cart a {
            display: inline-block;
            margin-top: 20px;
            background: var(--brown);
            color: white;
            text-decoration: none;
            padding: 16px 28px;
            border-radius: 22px;
            font-weight: 700;
        }

        .toast-fix {
            margin-top: 90px !important;
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(25px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media(max-width:900px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }

            .cart-hero h1 {
                font-size: 2.2rem;
            }

            .cart-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .item-left {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/navbar_pengunjung.php'; ?>

    <div class="cart-page">

        <div class="cart-hero">
            <h1>Your Magic Cart 🛒✨</h1>
            <p>Ramuan favoritmu hampir siap. Tinggal isi detail lalu kirim ke dapur Jelly Potter 🪄</p>
        </div>

        <?php if (empty($cart_products)): ?>

            <div class="glass-card empty-cart">
                <h2>Keranjangmu kosong 🥺</h2>
                <p>Belum ada ramuan yang berhasil kamu tangkap dari katalog.</p>
                <a href="katalog.php">🧋 Jelajahi Menu</a>
            </div>

        <?php else: ?>

            <div class="cart-layout">

                <!-- LEFT -->
                <div class="glass-card">

                    <div class="cart-toolbar">
                        <a href="katalog.php">← Tambah Menu Lagi</a>

                        <form method="POST" id="clearCartForm">
                            <input type="hidden" name="csrf_token"
                                value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                            <input type="hidden" name="kosongkan" value="1">

                            <button type="button" class="toolbar-btn" id="clearCartBtn">
                                🗑️ Kosongkan
                            </button>
                        </form>
                    </div>

                    <?php foreach ($cart_products as $item): ?>

                        <div class="cart-item">

                            <div class="item-left">

                                <img src="img/<?= htmlspecialchars($item['foto']); ?>" class="item-img"
                                    alt="<?= htmlspecialchars($item['nama_produk']); ?>">

                                <div class="item-info">

                                    <h3><?= htmlspecialchars($item['nama_produk']); ?></h3>

                                    <div class="item-price">
                                        Rp <?= number_format($item['harga'], 0, ',', '.'); ?>
                                    </div>

                                    <div class="qty-controls">

                                        <!-- MINUS -->
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                                            <button type="submit" name="minus" value="<?= $item['id_produk']; ?>"
                                                class="qty-btn">
                                                −
                                            </button>
                                        </form>

                                        <div class="qty-badge">
                                            <?= $item['jumlah']; ?>
                                        </div>

                                        <!-- PLUS -->
                                        <form method="POST">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                                            <button type="submit" name="plus" value="<?= $item['id_produk']; ?>"
                                                class="qty-btn">
                                                +
                                            </button>
                                        </form>

                                    </div>

                                    <!-- DELETE -->
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token"
                                            value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                                        <button type="submit" name="hapus" value="<?= $item['id_produk']; ?>"
                                            class="remove-btn">
                                            Hapus
                                        </button>
                                    </form>

                                </div>
                            </div>

                            <div class="item-total">
                                Rp <?= number_format($item['subtotal'], 0, ',', '.'); ?>
                            </div>

                        </div>

                    <?php endforeach; ?>

                </div>

                <!-- RIGHT -->
                <div class="glass-card checkout-card">

                    <h2>Checkout ✨</h2>

                    <div class="total-box">
                        <small>Total Pembayaran</small>
                        <strong>
                            Rp <?= number_format($total_bayar, 0, ',', '.'); ?>
                        </strong>
                    </div>

                    <form id="checkoutForm">

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

                        <div class="form-group">
                            <label>Nama Kamu</label>
                            <input type="text" name="nama_pelanggan" class="form-control" placeholder="Contoh: Radwell ✨"
                                required>
                        </div>

                        <div class="form-group">
                            <label>Nomor Meja</label>
                            <input type="text" name="nomor_meja" class="form-control" placeholder="Contoh: Meja 05"
                                required>
                        </div>
                        
                        <div class="form-group">
                            <label>Nomor WhatsApp</label>

                            <input type="text" name="nomor_wa" class="form-control" placeholder="Contoh: 08123456789"
                                required>
                        </div>

                        <!-- hidden metode -->
                        <input type="hidden" name="metode_bayar" id="metodeBayar">

                        <button type="button" class="checkout-btn" id="checkoutBtn">
                            🚀 Checkout Sekarang
                        </button>

                    </form>

                </div>

            </div>

        <?php endif; ?>

    </div>

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

        if (params.get('status') === 'hapus') {
            Toast.fire({
                icon: 'success',
                title: 'Item berhasil dihapus 🗑️'
            });

            window.history.replaceState(null, null, window.location.pathname);
        }

        if (params.get('status') === 'clear') {
            Toast.fire({
                icon: 'info',
                title: 'Keranjang dikosongkan ✨'
            });

            window.history.replaceState(null, null, window.location.pathname);
        }

        document.getElementById('checkoutBtn')?.addEventListener('click', function () {
            const form = document.getElementById('checkoutForm');
            const nama = form.querySelector('[name="nama_pelanggan"]').value.trim();
            const meja = form.querySelector('[name="nomor_meja"]').value.trim();

            if (!nama || !meja) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Data belum lengkap 😅',
                    text: 'Isi nama dan nomor meja dulu ya!',
                    confirmButtonColor: '#5C3A21'
                });
                return;
            }

            Swal.fire({
                title: 'Pilih Metode Pembayaran 💳',
                html: `
            <p style="margin-bottom:20px;color:#8b6e57;">
                Mau bayar sekarang atau nanti di kasir?
            </p>
        `,
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: '📱 Bayar QRIS',
                denyButtonText: '🏪 Bayar di Kasir',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#E8A87C',
                denyButtonColor: '#5C3A21',
                background: '#FFFDF9',
                color: '#5C3A21'
            }).then((result) => {

                if (result.isConfirmed) {
                    document.getElementById('metodeBayar').value = 'QRIS';
                }

                else if (result.isDenied) {
                    document.getElementById('metodeBayar').value = 'CASH';
                }

                else {
                    return;
                }

                form.action = '../api/proses_checkout.php';
                form.method = 'POST';

                document.getElementById('checkoutBtn').disabled = true;
                document.getElementById('checkoutBtn').innerHTML = '🪄 Memproses...';

                form.submit();
            });
        });


        document.getElementById('clearCartBtn')?.addEventListener('click', function () {
            const form = document.getElementById('clearCartForm');

            Swal.fire({
                title: 'Kosongkan keranjang?',
                text: 'Semua ramuan akan lenyap dari dimensi ini 🪄',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#c62828',
                cancelButtonColor: '#8b6e57',
                confirmButtonText: 'Ya, kosongkan',
                cancelButtonText: 'Batal',
                background: '#FFFDF9',
                color: '#5C3A21'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

    </script>

</body>

</html>