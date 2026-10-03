<?php
session_start();
include 'config/koneksi.php';

if (!isset($_GET['id'])) {
    header("Location: katalog.php");
    exit;
}

$id_pesanan = intval($_GET['id']);

$stmt = mysqli_prepare(
    $conn,
    "SELECT id_pesanan, nama_pelanggan, total_bayar, status_bayar
     FROM pesanan_pelanggan
     WHERE id_pesanan = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id_pesanan);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pesanan = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$pesanan) {
    header("Location: katalog.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $upload_dir = "img/bukti/";

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (
        isset($_FILES['bukti']) &&
        $_FILES['bukti']['error'] === 0
    ) {
        $ext = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            die("Format file tidak didukung.");
        }

        $nama_file = "bukti_" . time() . "_" . rand(1000,9999) . "." . $ext;
        $target = $upload_dir . $nama_file;

        if (move_uploaded_file($_FILES['bukti']['tmp_name'], $target)) {

            $status_bayar = "MENUNGGU VERIFIKASI";
            $status_pesanan = "Menunggu Konfirmasi";

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE pesanan_pelanggan
                 SET bukti_pembayaran = ?, status_bayar = ?, status_pesanan = ?
                 WHERE id_pesanan = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sssi",
                $nama_file,
                $status_bayar,
                $status_pesanan,
                $id_pesanan
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            echo "
<!DOCTYPE html>
<html lang='id'>
<head>
<meta charset='UTF-8'>
<title>Pembayaran Berhasil</title>
<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
<style>
body{
    margin:0;
    font-family:'Quicksand',sans-serif;
    background:
        radial-gradient(circle at top left, rgba(232,168,124,0.25), transparent 35%),
        linear-gradient(180deg,#FFFDF9,#FFF6EC);
    height:100vh;
}
</style>
</head>
<body>

<script>
Swal.fire({
    title: 'Pembayaran Terkirim! 🎉',
    html: `
        <div style='font-size:16px; line-height:1.7;'>
            Bukti pembayaran berhasil diupload ✨<br>
            Kasir akan memverifikasi pembayaranmu dulu ya 🧋
        </div>
    `,
    icon: 'success',
    background: '#FFFDF9',
    color: '#5C3A21',
    confirmButtonColor: '#5C3A21',
    confirmButtonText: 'Okeee ✨',
    showClass: {
        popup: 'animate__animated animate__zoomIn'
    },
    hideClass: {
        popup: 'animate__animated animate__fadeOut'
    }
}).then(() => {
    window.location.href = 'katalog.php';
});
</script>

</body>
</html>
";
exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>QRIS Payment | Jelly Potter ✨</title>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">

<style>
:root{
    --brown:#5C3A21;
    --accent:#E8A87C;
    --cream:#FFFDF9;
    --muted:#8b6e57;
}

body{
    margin:0;
    font-family:'Quicksand',sans-serif;
    background:
        radial-gradient(circle at top left, rgba(232,168,124,0.25), transparent 35%),
        linear-gradient(180deg,#FFFDF9,#FFF6EC);
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:30px;
}

.payment-card{
    width:100%;
    max-width:600px;
    background:rgba(255,255,255,0.85);
    backdrop-filter:blur(16px);
    border-radius:30px;
    padding:40px;
    box-shadow:0 20px 50px rgba(92,58,33,0.1);
    text-align:center;
}

h1{
    font-family:'Playfair Display',serif;
    color:var(--brown);
    margin-top:0;
    font-size:2.7rem;
}

.subtitle{
    color:var(--muted);
    font-weight:600;
    margin-bottom:30px;
}

.total-box{
    background:linear-gradient(135deg,#5C3A21,#3e2716);
    color:white;
    padding:22px;
    border-radius:22px;
    margin-bottom:30px;
}

.total-box strong{
    font-size:2rem;
    display:block;
    margin-top:10px;
}
.qr-box{
    background:white;
    padding:20px;
    border-radius:24px;
    box-shadow:0 10px 25px rgba(0,0,0,0.08);
    margin-bottom:25px;
}

.qr-box img{
    width:260px;
    max-width:100%;
    border-radius:18px;
}

.upload-box{
    border:2px dashed #e5d8c7;
    padding:24px;
    border-radius:22px;
    background:#fffaf5;
    margin-top:20px;
}

.upload-box input{
    width:100%;
    padding:14px;
    border-radius:14px;
    border:1px solid #ddd;
    background:white;
    font-family:inherit;
}

.btn-submit{
    margin-top:22px;
    width:100%;
    border:none;
    padding:18px;
    border-radius:20px;
    background:linear-gradient(135deg,var(--accent),#d98f5d);
    color:white;
    font-size:1rem;
    font-weight:700;
    cursor:pointer;
    transition:.3s;
}

.btn-submit:hover{
    transform:translateY(-4px);
    box-shadow:0 15px 30px rgba(232,168,124,0.35);
}

.note{
    margin-top:18px;
    font-size:.9rem;
    color:var(--muted);
}
</style>
</head>

<body>

<div class="payment-card">

    <h1>QRIS Payment ✨</h1>

    <p class="subtitle">
        Hai <b><?= htmlspecialchars($pesanan['nama_pelanggan']); ?></b>, scan QR di bawah untuk bayar 🧋
    </p>

    <div class="total-box">
        Total Pembayaran
        <strong>
            Rp <?= number_format($pesanan['total_bayar'], 0, ',', '.'); ?>
        </strong>
    </div>

    <div class="qr-box">
        <img src="img/qris-demo.png" alt="QRIS Dummy">
    </div>

    <form method="POST" enctype="multipart/form-data">

        <div class="upload-box">
            <p><b>Upload Bukti Pembayaran</b></p>

            <input
                type="file"
                name="bukti"
                accept=".jpg,.jpeg,.png,.webp"
                required>
        </div>

        <button type="submit" class="btn-submit">
            🪄 Saya Sudah Bayar
        </button>

    </form>

    <div class="note">
        Setelah upload bukti, kasir akan memverifikasi pembayaranmu 💳✨
    </div>

</div>

</body>
</html>