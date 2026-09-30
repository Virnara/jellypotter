<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['id_pegawai'])) {
    header("location: login.php");
    exit;
}

$notif_script = "";

if (isset($_POST['tambah'])) {

    $nama = trim(mysqli_real_escape_string($conn, $_POST['nama']));
    $hp = trim(mysqli_real_escape_string($conn, $_POST['hp']));

    if (empty($nama)) {
        $notif_script = "
        Swal.fire({
            title: 'Oops!',
            text: 'Nama pelanggan wajib diisi.',
            icon: 'error',
            confirmButtonColor: '#5C3A21'
        });";
    }

    elseif (!preg_match('/^[0-9]+$/', $hp)) {
        $notif_script = "
        Swal.fire({
            title: 'Nomor Tidak Valid 📱',
            text: 'Nomor HP hanya boleh angka.',
            icon: 'error',
            confirmButtonColor: '#5C3A21'
        });";
    }

    elseif (strlen($hp) < 10 || strlen($hp) > 15) {
        $notif_script = "
        Swal.fire({
            title: 'Nomor Tidak Valid 📱',
            text: 'Nomor HP harus 10–15 digit.',
            icon: 'warning',
            confirmButtonColor: '#5C3A21'
        });";
    }

    else {
        $cek_hp = mysqli_query($conn, "
            SELECT id_pelanggan
            FROM pelanggan
            WHERE bo_hp='$hp'
        ");

        if (mysqli_num_rows($cek_hp) > 0) {
            $notif_script = "
            Swal.fire({
                title: 'Sudah Terdaftar!',
                text: 'Nomor ini sudah menjadi member Jelly Potter.',
                icon: 'warning',
                confirmButtonColor: '#5C3A21'
            });";
        } else {
            $insert = mysqli_query($conn, "
                INSERT INTO pelanggan (nama_pelanggan, bo_hp, poin)
                VALUES ('$nama', '$hp', 0)
            ");

            if ($insert) {
                $notif_script = "
                Swal.fire({
                    title: 'Berhasil! 🎉',
                    text: 'Member baru berhasil ditambahkan.',
                    icon: 'success',
                    confirmButtonColor: '#5C3A21'
                }).then(() => {
                    window.location.href='pelanggan.php';
                });";
            }
        }
    }
}

include 'layout/navbar.php';

$q_total = mysqli_query($conn, "SELECT COUNT(*) total FROM pelanggan");
$total_member = mysqli_fetch_assoc($q_total)['total'];

$q_poin = mysqli_query($conn, "SELECT SUM(poin) total FROM pelanggan");
$total_poin = mysqli_fetch_assoc($q_poin)['total'] ?? 0;

$q_top = mysqli_query($conn, "
    SELECT nama_pelanggan, poin
    FROM pelanggan
    ORDER BY poin DESC
    LIMIT 1
");

$top_member = mysqli_fetch_assoc($q_top);
$top_member_name = $top_member['nama_pelanggan'] ?? 'Belum ada';
$top_member_poin = $top_member['poin'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Center | Jelly Potter ✨</title>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --milk-tea: #F5E6CA;
            --boba-brown: #5C3A21;
            --cream: rgba(255, 253, 249, 0.92);
            --accent: #8d735b;
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
        }

        .hero p {
            margin-top: 10px;
            color: var(--accent);
            font-weight: 600;
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
            padding: 24px;
            box-shadow: 0 12px 30px rgba(92,58,33,0.08);
        }

        .stat-title {
            font-size: 0.85rem;
            color: var(--accent);
            text-transform: uppercase;
            font-weight: 700;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-top: 8px;
        }

        .section-card {
            background: var(--cream);
            border-radius: 30px;
            padding: 30px;
            box-shadow: 0 12px 30px rgba(92,58,33,0.06);
            margin-bottom: 30px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr;
            gap: 14px;
        }

        input {
            padding: 16px;
            border-radius: 18px;
            border: 2px solid #f1ece4;
            font-family: 'Quicksand';
            outline: none;
            font-size: 0.95rem;
        }

        input:focus {
            border-color: var(--boba-brown);
        }

        .btn-add {
            border: none;
            border-radius: 18px;
            background: linear-gradient(135deg, #5C3A21, #3e2716);
            color: white;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .toolbar {
            margin-bottom: 20px;
        }

        .toolbar input {
            width: 100%;
            box-sizing: border-box;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 1.7rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
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
            <h1>Member Loyalty Center 👥✨</h1>
            <p>Kelola pelanggan setia Jelly Potter dalam satu command center</p>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-title">Total Member</div>
            <div class="stat-value"><?= $total_member ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-title">Total Poin Member</div>
            <div class="stat-value"><?= number_format($total_poin) ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-title">Top Member</div>
            <div class="stat-value" style="font-size:1.2rem;">
                <?= htmlspecialchars($top_member_name) ?>
                <br>
                <small><?= $top_member_poin ?> pts</small>
            </div>
        </div>
    </div>

    <div class="section-card">
        <h2>➕ Tambah Member Baru</h2>

        <form method="POST">
            <div class="form-grid">
                <input
                    type="text"
                    name="nama"
                    placeholder="Nama pelanggan"
                    required
                >

                <input
                    type="text"
                    name="hp"
                    placeholder="081234567890"
                    required
                >

                <button type="submit" name="tambah" class="btn-add">
                    Daftarkan
                </button>
            </div>
        </form>
    </div>

    <div class="section-card">
        <div class="toolbar">
            <input
                type="text"
                id="searchMember"
                placeholder="🔍 Cari member..."
            >
        </div>
        <table style="
            width:100%;
            border-collapse:collapse;
        ">
            <thead>
                <tr style="border-bottom:2px solid #f1ece4;">
                    <th style="padding:16px; text-align:left;">Nama</th>
                    <th style="padding:16px; text-align:left;">No HP</th>
                    <th style="padding:16px; text-align:left;">Poin</th>
                </tr>
            </thead>

            <tbody id="memberTable">

                <?php
                $res = mysqli_query($conn, "
                    SELECT *
                    FROM pelanggan
                    ORDER BY poin DESC, nama_pelanggan ASC
                ");

                if (mysqli_num_rows($res) > 0):
                    while ($p = mysqli_fetch_assoc($res)):
                ?>

                    <tr
                        class="member-row"
                        data-name="<?= strtolower(htmlspecialchars($p['nama_pelanggan'])) ?>"
                        style="
                            border-bottom:1px solid #f5f0e8;
                            transition:0.2s ease;
                        "
                    >
                        <td style="padding:18px; font-weight:700;">
                            <?= htmlspecialchars($p['nama_pelanggan']) ?>
                        </td>

                        <td style="padding:18px;">
                            <?= htmlspecialchars($p['bo_hp']) ?>
                        </td>

                        <td style="padding:18px;">
                            <span style="
                                background:#F5E6CA;
                                padding:8px 14px;
                                border-radius:14px;
                                font-weight:700;
                                display:inline-block;
                            ">
                                ✨ <?= intval($p['poin']) ?> pts
                            </span>
                        </td>
                    </tr>

                <?php
                    endwhile;
                else:
                ?>

                    <tr>
                        <td colspan="3" style="
                            padding:40px;
                            text-align:center;
                            color:#8d735b;
                        ">
                            Belum ada member 😢
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>
        </table>
    </div>

</div>

<?php if (!empty($notif_script)): ?>
<script>
    <?= $notif_script ?>
</script>
<?php endif; ?>

<script>
    const searchInput = document.getElementById('searchMember');

    searchInput.addEventListener('input', function() {
        const keyword = this.value.toLowerCase();
        const rows = document.querySelectorAll('.member-row');

        rows.forEach(row => {
            const name = row.dataset.name;

            if (name.includes(keyword)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    document.querySelectorAll('.member-row').forEach(row => {
        row.addEventListener('mouseenter', () => {
            row.style.background = 'rgba(255,255,255,0.55)';
        });

        row.addEventListener('mouseleave', () => {
            row.style.background = 'transparent';
        });
    });
</script>

<style>
    @media (max-width: 768px) {
        table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }

        th, td {
            min-width: 140px;
        }

        .stat-value {
            font-size: 1.4rem !important;
        }
    }
</style>

</body>
</html>