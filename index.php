<?php
session_start();
include 'koneksi.php';

/*
|--------------------------------------------------------------------------
| WEEKLY RECOMMENDATION
|--------------------------------------------------------------------------
*/
$query_rec = mysqli_query($conn, "
    SELECT id_produk, nama_produk, harga, deskripsi, foto
    FROM produk
    WHERE stok > 0
    ORDER BY id_produk DESC
    LIMIT 3
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jelly Potter 🪄 | Magical Bubble Tea Experience</title>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --milk-tea: #F5E6CA;
            --boba-brown: #5C3A21;
            --cream: #FFFDF9;
            --accent: #D89C63;
            --accent-dark: #8d735b;
            --glass: rgba(255, 253, 249, 0.72);
            --shadow-soft: 0 15px 35px rgba(92, 58, 33, 0.08);
            --shadow-strong: 0 20px 45px rgba(92, 58, 33, 0.18);
            --text-muted: #7c624d;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            font-family: 'Quicksand', sans-serif;
            color: var(--boba-brown);
            overflow-x: hidden;
            background:
                radial-gradient(circle at top left, rgba(245,230,202,0.8), transparent 30%),
                linear-gradient(to bottom, #FFFDF9, #fdf8f1);
        }

        h1, h2, h3, h4 {
            font-family: 'Playfair Display', serif;
            margin: 0;
        }

        img {
            display: block;
            max-width: 100%;
        }

        a {
            text-decoration: none;
        }

        /*
        =========================================
        ANIMATION ENGINE
        =========================================
        */

        @keyframes floatSoft {
            0%, 100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-12px);
            }
        }

        @keyframes pulseGlow {
            0% {
                box-shadow: 0 0 0 0 rgba(216,156,99,0.45);
            }

            70% {
                box-shadow: 0 0 0 18px rgba(216,156,99,0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(216,156,99,0);
            }
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(45px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /*
        =========================================
        REVEAL SYSTEM
        =========================================
        */

        .reveal {
            opacity: 0;
            transform: translateY(45px);
            transition: 0.8s ease;
        }

        .reveal.show {
            opacity: 1;
            transform: translateY(0);
        }

        /*
        =========================================
        HERO
        =========================================
        */

        .hero {
            min-height: 100vh;
            padding: 150px 20px 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;

            background:
                radial-gradient(circle at 20% 20%, rgba(255,255,255,0.95), transparent 20%),
                radial-gradient(circle at 80% 70%, rgba(216,156,99,0.18), transparent 25%),
                linear-gradient(135deg, #F5E6CA 0%, #FFFDF9 100%);
        }

        .hero-particle {
            position: absolute;
            border-radius: 50%;
            opacity: 0.15;
            animation: floatSoft 6s ease-in-out infinite;
        }

        .particle-1 {
            width: 140px;
            height: 140px;
            background: var(--boba-brown);
            top: 15%;
            left: 8%;
        }

        .particle-2 {
            width: 220px;
            height: 220px;
            background: var(--accent);
            bottom: 8%;
            right: 8%;
            animation-delay: 2s;
        }

        .particle-3 {
            width: 80px;
            height: 80px;
            background: #fff;
            top: 28%;
            right: 20%;
            animation-delay: 1s;
        }

        .hero-content {
            max-width: 980px;
            text-align: center;
            z-index: 2;
            animation: fadeUp 1s ease;
        }

        .hero-badge {
            display: inline-block;
            padding: 12px 22px;
            border-radius: 999px;
            background: rgba(255,255,255,0.8);
            font-weight: 700;
            color: var(--accent-dark);
            box-shadow: var(--shadow-soft);
            margin-bottom: 24px;
        }

        .hero h1 {
            font-size: clamp(2.8rem, 7vw, 6rem);
            line-height: 1.08;
            margin-bottom: 24px;
            text-shadow: 2px 2px 0 rgba(255,255,255,0.7);
        }

        .hero h1 span {
            color: var(--accent);
        }

        .hero p {
            max-width: 760px;
            margin: 0 auto 38px;
            color: var(--text-muted);
            line-height: 1.9;
            font-weight: 600;
            font-size: clamp(1rem, 2vw, 1.2rem);
        }

        .hero-actions {
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, #5C3A21, #3e2716);
            color: white;
            padding: 18px 34px;
            border-radius: 20px;
            font-weight: 700;
            box-shadow: 0 14px 26px rgba(92,58,33,0.25);
            transition: 0.25s ease;
        }

        .btn-primary:hover {
            transform: translateY(-4px);
        }

        .btn-secondary {
            background: rgba(255,255,255,0.8);
            color: var(--boba-brown);
            padding: 18px 34px;
            border-radius: 20px;
            font-weight: 700;
            border: 2px solid rgba(92,58,33,0.08);
            transition: 0.25s ease;
        }

        .btn-secondary:hover {
            transform: translateY(-4px);
        }

        /*
        =========================================
        GLOBAL SECTION
        =========================================
        */

        section:not(.hero) {
            padding: 100px 20px;
        }

        .container {
            width: 95%;
            max-width: 1300px;
            margin: 0 auto;
        }

        .section-head {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-tag {
            display: inline-block;
            color: var(--accent);
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .section-head h2 {
            font-size: clamp(2rem, 5vw, 3.4rem);
            margin-bottom: 12px;
        }

        .section-head p {
            max-width: 700px;
            margin: 0 auto;
            color: var(--text-muted);
            font-weight: 600;
            line-height: 1.7;
        }

        /*
        =========================================
        GLASS CARD
        =========================================
        */

        .glass-card {
            background: var(--glass);
            backdrop-filter: blur(16px);
            border-radius: 30px;
            box-shadow: var(--shadow-soft);
            border: 1px solid rgba(255,255,255,0.45);
        }

        /*
        =========================================
        MENU SECTION
        =========================================
        */

        .recommendation-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 26px;
        }

        .menu-card {
            padding: 28px;
            text-align: center;
            transition: 0.3s ease;
        }

        .menu-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-strong);
        }

        .menu-img-wrap {
            width: 170px;
            height: 170px;
            border-radius: 50%;
            overflow: hidden;
            margin: 0 auto 20px;
            border: 8px solid white;
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
        }

        .menu-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .menu-card p {
            color: #666;
            line-height: 1.8;
            min-height: 70px;
        }

        .price-pill {
            display: inline-block;
            margin-top: 16px;
            padding: 10px 18px;
            border-radius: 14px;
            background: var(--milk-tea);
            font-weight: 700;
        }

        /*
        =========================================
        FEATURE
        =========================================
        */

        .feature-strip {
            background:
                linear-gradient(135deg, rgba(245,230,202,0.7), rgba(255,255,255,0.8));
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 22px;
        }

        .feature-card {
            padding: 28px;
            transition: 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-8px);
        }

        .feature-icon {
            font-size: 2rem;
            margin-bottom: 14px;
        }

        /*
        =========================================
        GALLERY
        =========================================
        */

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 18px;
        }

        .gallery-item {
            height: 320px;
            border-radius: 26px;
            overflow: hidden;
            position: relative;
            box-shadow: var(--shadow-soft);
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: 0.45s ease;
        }

        .gallery-item:hover img {
            transform: scale(1.08);
        }

        .gallery-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(92,58,33,0.88), transparent);
            display: flex;
            align-items: flex-end;
            padding: 22px;
            color: white;
            font-weight: 700;
        }

        /*
        =========================================
        TESTI
        =========================================
        */

        .testi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }

        .testi-card {
            padding: 30px;
            transition: 0.3s ease;
        }

        .testi-card:hover {
            transform: translateY(-8px);
        }

        .testi-text {
            line-height: 1.8;
            color: #666;
            font-style: italic;
            margin-bottom: 24px;
        }

        .testi-user {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .avatar {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            background: var(--accent);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        /*
        =========================================
        ABOUT PREMIUM
        =========================================
        */

        .about-premium {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 28px;
            align-items: stretch;
        }

        .story-card {
            padding: 38px;
        }

        .story-card p {
            line-height: 1.9;
            color: #666;
        }

        .stats-stack {
            display: grid;
            gap: 18px;
        }

        .magic-stat {
            padding: 24px;
        }

        .magic-stat h3 {
            font-size: 2rem;
            margin-bottom: 8px;
        }

        /*
        =========================================
        FOOTER
        =========================================
        */

        .footer {
            background: var(--boba-brown);
            color: white;
            padding: 70px 20px 25px;
        }

        .footer-grid {
            max-width: 1300px;
            margin: 0 auto 35px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 30px;
        }

        .footer h4 {
            color: var(--accent);
        }

        .footer p {
            color: rgba(255,255,255,0.8);
            line-height: 1.7;
        }

        .footer-bottom {
            text-align: center;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
            color: rgba(255,255,255,0.7);
        }

        /*
        =========================================
        FLOAT BUTTON
        =========================================
        */

        .floating-order {
            position: fixed;
            right: 24px;
            bottom: 24px;
            z-index: 999;
        }

        .floating-order a {
            background: var(--accent);
            color: white;
            padding: 16px 24px;
            border-radius: 999px;
            font-weight: 700;
            display: inline-flex;
            gap: 10px;
            align-items: center;
            animation: pulseGlow 2.3s infinite;
        }

        /*
        =========================================
        MOBILE
        =========================================
        */

        @media (max-width: 900px) {
            .about-premium {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .hero {
                padding-top: 130px;
            }

            .hero-actions {
                flex-direction: column;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
            }

            .gallery-item {
                height: 240px;
            }

            .floating-order {
                right: 14px;
                bottom: 14px;
            }

            .floating-order a {
                font-size: 0.9rem;
                padding: 14px 18px;
            }
        }
    </style>
</head>
<body>

<?php include 'includes/navbar_pengunjung.php'; ?>

<!-- HERO -->
<section class="hero" id="home">

    <div class="hero-particle particle-1"></div>
    <div class="hero-particle particle-2"></div>
    <div class="hero-particle particle-3"></div>

    <div class="hero-content">
        <div class="hero-badge">
            ✨ Magical Bubble Tea Experience
        </div>

        <h1>
            Sip The <span>Magic</span><br>
            Feel The Jelly Vibes 🧋
        </h1>

        <p>
            Bukan sekadar minuman. Jelly Potter adalah ritual kecil untuk upgrade mood,
            recharge energi, dan menemani chaos kehidupan kampus ☕✨
        </p>

        <div class="hero-actions">
            <a href="katalog.php" class="btn-primary">
                🧋 Pesan Sekarang
            </a>

            <a href="#about" class="btn-secondary">
                📖 Cerita Kami
            </a>
        </div>
    </div>
</section>

<!-- RECOMMENDATION -->
<section id="menu" class="reveal">
    <div class="container">

        <div class="section-head">
            <span class="section-tag">Weekly Recommendation</span>
            <h2>Ramuan Favorit Minggu Ini ✨</h2>
            <p>
                Pilihan spesial dari Jelly Potter yang paling banyak bikin pelanggan gagal move on.
            </p>
        </div>

        <div class="recommendation-grid">

            <?php
            if (mysqli_num_rows($query_rec) > 0):
                while ($row = mysqli_fetch_assoc($query_rec)):
                    $foto = !empty($row['foto']) ? $row['foto'] : 'default.png';
                    $desc = substr(strip_tags($row['deskripsi']), 0, 95) . "...";
            ?>

            <div class="glass-card menu-card reveal">
                <div class="menu-img-wrap">
                    <img src="img/<?= htmlspecialchars($foto); ?>">
                </div>

                <h3><?= htmlspecialchars($row['nama_produk']); ?></h3>

                <p><?= htmlspecialchars($desc); ?></p>

                <div class="price-pill">
                    Rp <?= number_format($row['harga'], 0, ',', '.'); ?>
                </div>
            </div>

            <?php
                endwhile;
            else:
            ?>

            <p style="text-align:center; color:#888;">
                Menu belum tersedia 😢
            </p>

            <?php endif; ?>

        </div>
    </div>
</section>

<!-- FEATURES -->
<section class="feature-strip reveal">
    <div class="container">

        <div class="section-head">
            <span class="section-tag">Why Jelly Potter</span>
            <h2>Kenapa Banyak yang Balik Lagi? 👀</h2>
        </div>

        <div class="feature-grid">

            <div class="glass-card feature-card reveal">
                <div class="feature-icon">🧋</div>
                <h3>Boba Fresh Daily</h3>
                <p>
                    Dibuat fresh tiap hari biar teksturnya tetap kenyal dan satisfying.
                </p>
            </div>

            <div class="glass-card feature-card reveal">
                <div class="feature-icon">☕</div>
                <h3>Premium Ingredients</h3>
                <p>
                    Racikan teh, susu, kopi, dan topping dengan kualitas premium.
                </p>
            </div>

            <div class="glass-card feature-card reveal">
                <div class="feature-icon">⚡</div>
                <h3>Fast Ordering</h3>
                <p>
                    Tinggal scan, pilih menu, masuk keranjang, selesai.
                </p>
            </div>

            <div class="glass-card feature-card reveal">
                <div class="feature-icon">🎁</div>
                <h3>Member Rewards</h3>
                <p>
                    Ngumpulin poin sambil ngopi? Why not 😈
                </p>
            </div>

        </div>
    </div>
</section>

<!-- GALLERY -->
<section id="gallery" class="reveal">
    <div class="container">

        <div class="section-head">
            <span class="section-tag">Gallery</span>
            <h2>Visual Mood Booster 📸</h2>
        </div>

        <div class="gallery-grid">

            <div class="gallery-item reveal">
                <img src="img/Brown_Sugar_Classic.png">
                <div class="gallery-overlay">Brown Sugar Classic</div>
            </div>

            <div class="gallery-item reveal">
                <img src="img/Matcha_Heaven.png">
                <div class="gallery-overlay">Matcha Heaven</div>
            </div>

            <div class="gallery-item reveal">
                <img src="img/Berry_Splash.png">
                <div class="gallery-overlay">Berry Splash</div>
            </div>

            <div class="gallery-item reveal">
                <img src="img/Taro_Dream.png">
                <div class="gallery-overlay">Taro Dream</div>
            </div>

        </div>
    </div>
</section>

<!-- TESTIMONI -->
<section class="reveal">
    <div class="container">

        <div class="section-head">
            <span class="section-tag">Testimonials</span>
            <h2>Kata Mereka 🗣️</h2>
        </div>

        <div class="testi-grid">

            <div class="glass-card testi-card reveal">
                <div class="testi-text">
                    "Brown sugar-nya absurd enaknya. Bobanya legit banget 😭"
                </div>

                <div class="testi-user">
                    <div class="avatar">R</div>
                    <div>
                        <strong>Rina</strong><br>
                        <small>Mahasiswa</small>
                    </div>
                </div>
            </div>

            <div class="glass-card testi-card reveal">
                <div class="testi-text">
                    "Matcha-nya premium vibes banget, bukan matcha ecek-ecek."
                </div>

                <div class="testi-user">
                    <div class="avatar">B</div>
                    <div>
                        <strong>Budi</strong><br>
                        <small>Karyawan</small>
                    </div>
                </div>
            </div>

            <div class="glass-card testi-card reveal">
                <div class="testi-text">
                    "UI website-nya cakep, ordering cepet, hidup terasa ringan."
                </div>

                <div class="testi-user">
                    <div class="avatar">A</div>
                    <div>
                        <strong>Andini</strong><br>
                        <small>Customer</small>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ABOUT -->
<section id="about" class="reveal">
    <div class="container">

        <div class="section-head">
            <span class="section-tag">Our Story</span>
            <h2>The Magic Behind Jelly Potter 🪄</h2>
        </div>

        <div class="about-premium">

            <div class="glass-card story-card reveal">
                <p>
                    Jelly Potter lahir dari ide sederhana:
                    <strong>minuman enak harus bikin mood naik, bukan cuma haus hilang.</strong>
                </p>

                <p>
                    Dari eksperimen kecil sampai sistem ordering digital,
                    semua dibuat supaya pengalaman pelanggan terasa effortless.
                </p>

                <p>
                    Jadi ini bukan cuma boba shop.
                    Ini command center kebahagiaan kecil 😈✨
                </p>
            </div>

            <div class="stats-stack">

                <div class="glass-card magic-stat reveal">
                    <h3>100%</h3>
                    <p>Fresh Daily Ingredients</p>
                </div>

                <div class="glass-card magic-stat reveal">
                    <h3>Fast</h3>
                    <p>Digital Ordering Experience</p>
                </div>

                <div class="glass-card magic-stat reveal">
                    <h3>∞</h3>
                    <p>Mood Booster Potential</p>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">

    <div class="footer-grid">

        <div>
            <h4>Jelly Potter 🪄</h4>
            <p>
                Magical drinks for magical people.
            </p>
        </div>

        <div>
            <h4>Jam Operasional</h4>
            <p>
                Senin - Minggu<br>
                09:00 - 22:00
            </p>
        </div>

        <div>
            <h4>Visit Us</h4>
            <p>
                Kampus Area, Kota Pendidikan
            </p>
        </div>

    </div>

    <div class="footer-bottom">
        © <?= date('Y'); ?> Jelly Potter — crafted with ☕
    </div>

</footer>

<!-- FLOAT BTN -->
<div class="floating-order">
    <a href="katalog.php">
        🛒 Order Now
    </a>
</div>

<!-- REVEAL JS -->
<script>
const reveals = document.querySelectorAll('.reveal');

function revealOnScroll() {
    reveals.forEach(item => {
        const top = item.getBoundingClientRect().top;
        const trigger = window.innerHeight - 100;

        if (top < trigger) {
            item.classList.add('show');
        }
    });
}

window.addEventListener('scroll', revealOnScroll);
revealOnScroll();
</script>

</body>
</html>