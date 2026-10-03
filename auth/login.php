<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

session_start();
include '../koneksi.php';

$error_pesan = "";

/*
|--------------------------------------------------------------------------
| BRUTE FORCE PROTECTION
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['login_attempt'])) {
    $_SESSION['login_attempt'] = 0;
}

if (!isset($_SESSION['login_lock_time'])) {
    $_SESSION['login_lock_time'] = 0;
}

$max_attempt = 5;
$lock_duration = 60;

if ($_SESSION['login_attempt'] >= $max_attempt) {
    if ((time() - $_SESSION['login_lock_time']) < $lock_duration) {
        $remaining = $lock_duration - (time() - $_SESSION['login_lock_time']);
        $error_pesan = "Terlalu banyak percobaan login. Tunggu {$remaining} detik.";
    } else {
        $_SESSION['login_attempt'] = 0;
        $_SESSION['login_lock_time'] = 0;
    }
}

/*
|--------------------------------------------------------------------------
| LOGIN PROCESS
|--------------------------------------------------------------------------
*/
if (isset($_POST['login']) && empty($error_pesan)) {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if (strlen($user) < 3 || strlen($user) > 50) {
        $error_pesan = "Username atau password salah!";
    } elseif (strlen($pass) < 1 || strlen($pass) > 255) {
        $error_pesan = "Username atau password salah!";
    } else {
        $stmt = mysqli_prepare($conn, "
            SELECT id_pegawai, nama_pegawai, username, password, jabatan
            FROM pegawai
            WHERE username = ?
            LIMIT 1
        ");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $user);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) === 1) {
                $data = mysqli_fetch_assoc($result);

                if (password_verify($pass, $data['password'])) {
                    $_SESSION['login_attempt'] = 0;
                    $_SESSION['login_lock_time'] = 0;

                    session_regenerate_id(true);

                    $_SESSION['id_pegawai'] = (int)$data['id_pegawai'];
                    $_SESSION['nama_pegawai'] = $data['nama_pegawai'];
                    $_SESSION['jabatan'] = $data['jabatan'];
                    $_SESSION['last_activity'] = time();

                    if (strtolower($data['jabatan']) === 'admin') {
                        header("Location: ../admin/dashboard.php");
                    } else {
                        header("Location: ../admin/pesanan.php");
                    }
                    exit;
                }
            }

            mysqli_stmt_close($stmt);
        }

        $_SESSION['login_attempt']++;
        $_SESSION['login_lock_time'] = time();
        $error_pesan = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Jelly Potter 🪄</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --milk-tea: #F5E6CA;
            --boba-brown: #5C3A21;
            --cream: rgba(255, 253, 249, 0.88);
            --accent: #8d735b;
            --text-soft: #9d8b7c;
            --shadow: rgba(92, 58, 33, 0.18);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: 'Quicksand', sans-serif;
            background: linear-gradient(135deg, #f5e6ca 0%, #ead3b3 45%, #f9efe2 100%);
            overflow-x: hidden;
            position: relative;
        }

        .bg-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.35;
            animation: floatBlob 10s infinite ease-in-out;
        }

        .blob1 {
            width: 280px;
            height: 280px;
            background: #d8b48a;
            top: -80px;
            left: -60px;
        }

        .blob2 {
            width: 320px;
            height: 320px;
            background: #b98a63;
            right: -80px;
            bottom: -90px;
            animation-delay: 2s;
        }

        .blob3 {
            width: 220px;
            height: 220px;
            background: #fff5e9;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation-delay: 4s;
        }

        @keyframes floatBlob {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-25px);
            }
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px;
            position: relative;
            z-index: 2;
        }

        .login-container {
            width: 100%;
            max-width: 1180px;
            min-height: 680px;
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            border-radius: 36px;
            overflow: hidden;
            background: rgba(255,255,255,0.35);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            box-shadow: 0 30px 80px var(--shadow);
            border: 1px solid rgba(255,255,255,0.4);
            animation: fadeUp 0.7s ease;
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

        .branding-side {
            padding: 60px;
            background:
                linear-gradient(
                    145deg,
                    rgba(92,58,33,0.95),
                    rgba(122,82,52,0.92)
                );
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .branding-side::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 35%);
        }

        .brand-badge {
            display: inline-flex;
            width: fit-content;
            padding: 10px 16px;
            border-radius: 999px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.15);
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 24px;
            backdrop-filter: blur(8px);
        }

        .branding-side h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.2rem;
            line-height: 1.15;
            margin-bottom: 18px;
        }

        .branding-side p {
            color: rgba(255,255,255,0.82);
            line-height: 1.8;
            font-size: 1rem;
            max-width: 460px;
        }

        .feature-list {
            margin-top: 35px;
            display: grid;
            gap: 16px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 14px;
            font-weight: 600;
            color: rgba(255,255,255,0.95);
        }

        .feature-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.14);
            font-size: 1.1rem;
        }
                .login-side {
            background: var(--cream);
            padding: 60px 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
        }

        .login-card h2 {
            font-family: 'Playfair Display', serif;
            color: var(--boba-brown);
            font-size: 2.4rem;
            margin-bottom: 10px;
        }

        .login-subtitle {
            color: var(--text-soft);
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--boba-brown);
            font-size: 0.92rem;
        }

        .input-wrap {
            position: relative;
        }

        .login-input {
            width: 100%;
            padding: 16px 18px;
            border-radius: 18px;
            border: 2px solid #efe6dc;
            background: #fcfbf9;
            font-family: 'Quicksand', sans-serif;
            font-size: 0.95rem;
            transition: all 0.25s ease;
        }

        .login-input:focus {
            outline: none;
            border-color: var(--boba-brown);
            box-shadow: 0 0 0 4px rgba(92, 58, 33, 0.08);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--accent);
            cursor: pointer;
            width: auto;
            padding: 0;
            font-size: 1rem;
            box-shadow: none;
        }

        .password-toggle:hover {
            transform: translateY(-50%);
            box-shadow: none;
        }

        .login-btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 18px;
            background: linear-gradient(135deg, #5C3A21, #7a5234);
            color: white;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.25s ease;
            margin-top: 8px;
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 22px rgba(92, 58, 33, 0.28);
        }

        .login-btn.loading {
            pointer-events: none;
            opacity: 0.9;
        }

        .mini-note {
            margin-top: 18px;
            text-align: center;
            font-size: 0.85rem;
            color: var(--text-soft);
        }

        @media (max-width: 980px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 560px;
            }

            .branding-side {
                padding: 45px;
            }

            .branding-side h1 {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 640px) {
            .login-wrapper {
                padding: 16px;
            }

            .branding-side {
                display: none;
            }

            .login-side {
                padding: 35px 24px;
            }

            .login-card h2 {
                font-size: 2rem;
            }

            .login-container {
                min-height: auto;
                border-radius: 28px;
            }
        }
    </style>
</head>
<body>

    <div class="bg-blob blob1"></div>
    <div class="bg-blob blob2"></div>
    <div class="bg-blob blob3"></div>

    <div class="login-wrapper">
        <div class="login-container">

            <div class="branding-side">
                <div class="brand-badge">
                    ✨ Jelly Potter Admin Portal
                </div>

                <h1>Welcome back, wizard 🪄</h1>

                <p>
                    Sistem manajemen Jelly Potter untuk transaksi, pelanggan,
                    laporan penjualan, dan operasional kasir modern.
                </p>

                <div class="feature-list">
                    <div class="feature-item">
                        <div class="feature-icon">📊</div>
                        Dashboard analytics premium
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">🧋</div>
                        POS transaksi cepat & modern
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">🔒</div>
                        Secure staff authentication
                    </div>
                </div>
            </div>

            <div class="login-side">
                <div class="login-card">

                    <h2>Masuk</h2>
                    <p class="login-subtitle">
                        Login untuk mengakses dashboard Jelly Potter.
                    </p>

                    <form method="POST" autocomplete="off" id="loginForm">

                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input
                                type="text"
                                name="username"
                                class="login-input"
                                placeholder="Masukkan username"
                                required
                                maxlength="50"
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <div class="input-wrap">
                                <input
                                    type="password"
                                    name="password"
                                    id="passwordField"
                                    class="login-input"
                                    placeholder="Masukkan password"
                                    required
                                    maxlength="255"
                                >

                                <button type="button" class="password-toggle" onclick="togglePassword()">
                                    👁
                                </button>
                            </div>
                        </div>

                        <button type="submit" name="login" class="login-btn" id="loginBtn">
                            Masuk ke Dashboard ✨
                        </button>

                        <div class="mini-note">
                            Authorized Jelly Potter Staff Only
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <?php if ($error_pesan != ""): ?>
    <script>
        Swal.fire({
            title: 'Oops!',
            text: <?= json_encode($error_pesan); ?>,
            icon: 'error',
            confirmButtonColor: '#5C3A21',
            background: '#FFFDF9',
            color: '#5C3A21'
        });
    </script>
    <?php endif; ?>

    <script>
        function togglePassword() {
            const field = document.getElementById('passwordField');

            if (field.type === 'password') {
                field.type = 'text';
            } else {
                field.type = 'password';
            }
        }

        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.innerHTML = 'Memverifikasi... ⏳';
        });
    </script>

</body>
</html>