<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

session_start();
include 'koneksi.php';

/*
|--------------------------------------------------------------------------
| AUTH CHECK
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['id_pegawai'])) {
    header("Location: login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| SESSION TIMEOUT
|--------------------------------------------------------------------------
*/
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 1800) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}

$_SESSION['last_activity'] = time();

/*
|--------------------------------------------------------------------------
| ADMIN ONLY
|--------------------------------------------------------------------------
*/
if (strtolower($_SESSION['jabatan'] ?? '') !== 'admin') {
    echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        Swal.fire({
            title: 'Akses Ditolak 🚫',
            text: 'Hanya admin yang dapat menambah pegawai.',
            icon: 'error',
            confirmButtonColor: '#5C3A21',
            background: '#FFFDF9',
            color: '#5C3A21'
        }).then(() => {
            window.location.href = 'dashboard.php';
        });
    </script>";
    exit;
}

/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pesan = "";
$status = "";

/*
|--------------------------------------------------------------------------
| FORM PROCESS
|--------------------------------------------------------------------------
*/
if (isset($_POST['signup'])) {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $pesan = "Permintaan tidak valid.";
        $status = "error";
    } else {

        $nama = trim($_POST['nama_pegawai'] ?? '');
        $user = trim($_POST['username'] ?? '');
        $pass = $_POST['password'] ?? '';
        $jabatan = trim($_POST['jabatan'] ?? '');

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */
        if (empty($nama) || empty($user) || empty($pass) || empty($jabatan)) {
            $pesan = "Semua field wajib diisi!";
            $status = "error";
        }

        elseif (!preg_match('/^[a-zA-Z\s]{3,60}$/', $nama)) {
            $pesan = "Nama hanya boleh huruf dan minimal 3 karakter.";
            $status = "error";
        }

        elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $user)) {
            $pesan = "Username hanya boleh huruf, angka, underscore.";
            $status = "error";
        }

        elseif (!in_array($jabatan, ['Admin', 'Kasir'])) {
            $pesan = "Jabatan tidak valid.";
            $status = "error";
        }

        elseif (
            strlen($pass) < 8 ||
            !preg_match('/[A-Z]/', $pass) ||
            !preg_match('/[0-9]/', $pass)
        ) {
            $pesan = "Password minimal 8 karakter, ada huruf besar & angka.";
            $status = "error";
        }

        else {
            /*
            |--------------------------------------------------------------------------
            | CHECK DUPLICATE USERNAME
            |--------------------------------------------------------------------------
            */
            $stmt = mysqli_prepare(
                $conn,
                "SELECT id_pegawai FROM pegawai WHERE username = ? LIMIT 1"
            );

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $user);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if (mysqli_num_rows($result) > 0) {
                    $pesan = "Username sudah digunakan.";
                    $status = "error";
                }

                mysqli_stmt_close($stmt);
            }

            /*
            |--------------------------------------------------------------------------
            | INSERT NEW USER
            |--------------------------------------------------------------------------
            */
            if (empty($status)) {
                $pass_hash = password_hash($pass, PASSWORD_DEFAULT);

                $stmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO pegawai (nama_pegawai, username, password, jabatan)
                     VALUES (?, ?, ?, ?)"
                );

                if ($stmt) {
                    mysqli_stmt_bind_param(
                        $stmt,
                        "ssss",
                        $nama,
                        $user,
                        $pass_hash,
                        $jabatan
                    );

                    if (mysqli_stmt_execute($stmt)) {
                        $pesan = "Pegawai baru berhasil ditambahkan ✨";
                        $status = "success";

                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    } else {
                        $pesan = "Terjadi kesalahan sistem.";
                        $status = "error";
                    }

                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recruit New Crew | Jelly Potter 🪄</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --milk-tea: #F5E6CA;
            --boba-brown: #5C3A21;
            --cream: rgba(255, 253, 249, 0.90);
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

        .signup-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px;
            position: relative;
            z-index: 2;
        }

        .signup-container {
            width: 100%;
            max-width: 1180px;
            min-height: 700px;
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
            font-size: 3rem;
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

        .signup-side {
            background: var(--cream);
            padding: 60px 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .signup-card {
            width: 100%;
            max-width: 430px;
        }

        .signup-card h2 {
            font-family: 'Playfair Display', serif;
            color: var(--boba-brown);
            font-size: 2.3rem;
            margin-bottom: 10px;
        }

        .signup-subtitle {
            color: var(--text-soft);
            margin-bottom: 28px;
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

        .signup-input,
        .signup-select {
            width: 100%;
            padding: 16px 18px;
            border-radius: 18px;
            border: 2px solid #efe6dc;
            background: #fcfbf9;
            font-family: 'Quicksand', sans-serif;
            font-size: 0.95rem;
            transition: all 0.25s ease;
        }

        .signup-input:focus,
        .signup-select:focus {
            outline: none;
            border-color: var(--boba-brown);
            box-shadow: 0 0 0 4px rgba(92, 58, 33, 0.08);
        }

        .input-wrap {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            cursor: pointer;
            font-size: 1rem;
            color: var(--accent);
        }

        .signup-btn {
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

        .signup-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 22px rgba(92, 58, 33, 0.28);
        }

        .signup-btn.loading {
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
            .signup-container {
                grid-template-columns: 1fr;
                max-width: 580px;
            }

            .branding-side {
                padding: 45px;
            }

            .branding-side h1 {
                font-size: 2.4rem;
            }
        }

        @media (max-width: 640px) {
            .signup-wrapper {
                padding: 16px;
            }

            .branding-side {
                display: none;
            }

            .signup-side {
                padding: 35px 24px;
            }

            .signup-container {
                min-height: auto;
                border-radius: 28px;
            }

            .signup-card h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>

    <div class="bg-blob blob1"></div>
    <div class="bg-blob blob2"></div>
    <div class="bg-blob blob3"></div>

    <div class="signup-wrapper">
        <div class="signup-container">

            <div class="branding-side">
                <div class="brand-badge">
                    🪄 Jelly Potter Crew Management
                </div>

                <h1>Recruit new magic crew ✨</h1>

                <p>
                    Tambahkan pegawai baru untuk membantu operasional,
                    transaksi kasir, dan pengelolaan sistem Jelly Potter.
                </p>

                <div class="feature-list">
                    <div class="feature-item">
                        <div class="feature-icon">👥</div>
                        Secure employee account management
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">🔒</div>
                        Protected admin-only access
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon">⚡</div>
                        Fast onboarding for new crew
                    </div>
                </div>
            </div>

            <div class="signup-side">
                <div class="signup-card">

                    <h2>Tambah Pegawai</h2>
                    <p class="signup-subtitle">
                        Buat akun baru untuk admin atau kasir.
                    </p>

                    <form method="POST" id="signupForm">
                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>"
                        >

                        <div class="form-group">
                            <label class="form-label">Nama Lengkap</label>
                            <input
                                type="text"
                                name="nama_pegawai"
                                class="signup-input"
                                placeholder="Contoh: Radwell Putra"
                                required
                                maxlength="60"
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label">Username</label>
                            <input
                                type="text"
                                name="username"
                                class="signup-input"
                                placeholder="Contoh: radwell_admin"
                                required
                                maxlength="30"
                            >
                        </div>

                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <div class="input-wrap">
                                <input
                                    type="password"
                                    name="password"
                                    id="passwordField"
                                    class="signup-input"
                                    placeholder="Minimal 8 karakter"
                                    required
                                    maxlength="255"
                                >

                                <button type="button" class="password-toggle" onclick="togglePassword()">
                                    👁
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Jabatan</label>
                            <select name="jabatan" class="signup-select" required>
                                <option value="">-- Pilih Jabatan --</option>
                                <option value="Kasir">Kasir</option>
                                <option value="Admin">Admin</option>
                            </select>
                        </div>

                        <button type="submit" name="signup" class="signup-btn" id="signupBtn">
                            Tambah Pegawai ✨
                        </button>

                        <div class="mini-note">
                            Admin access required 🔐
                        </div>
                    </form>

                </div>
            </div>

        </div>
    </div>

    <?php if (!empty($status)): ?>
    <script>
        Swal.fire({
            title: <?= json_encode($status === 'success' ? 'Berhasil! 🎉' : 'Oops!'); ?>,
            text: <?= json_encode($pesan); ?>,
            icon: <?= json_encode($status); ?>,
            confirmButtonColor: '#5C3A21',
            background: '#FFFDF9',
            color: '#5C3A21'
        }).then(() => {
            <?php if ($status === 'success'): ?>
                window.location.href = 'dashboard.php';
            <?php endif; ?>
        });
    </script>
    <?php endif; ?>

    <script>
        function togglePassword() {
            const field = document.getElementById('passwordField');
            field.type = field.type === 'password' ? 'text' : 'password';
        }

        document.getElementById('signupForm').addEventListener('submit', function() {
            const btn = document.getElementById('signupBtn');
            btn.classList.add('loading');
            btn.innerHTML = 'Membuat akun... ⏳';
        });
    </script>

</body>
</html>