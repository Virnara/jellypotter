<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_pegawai'])) {
    exit;
}

$q_pesanan_online = mysqli_query($conn, "
    SELECT *
    FROM pesanan_pelanggan
    WHERE status_pesanan IN ('Pending', 'Menunggu Konfirmasi')
    ORDER BY waktu_pesan DESC
");

if (mysqli_num_rows($q_pesanan_online) > 0):

    while ($row = mysqli_fetch_assoc($q_pesanan_online)):
        ?>

        <div class="queue-card">

            <div class="queue-top">

                <div class="queue-id">
                    #<?= (int) $row['id_pesanan']; ?>
                </div>

                <div class="queue-time">
                    🕒 <?= date('H:i', strtotime($row['waktu_pesan'])); ?> WIB
                </div>

            </div>

            <div class="queue-name">
                👤 <?= htmlspecialchars($row['nama_pelanggan']); ?>
            </div>

            <div class="queue-meta">

                <div class="queue-badge">
                    🪑 Meja <?= htmlspecialchars($row['nomor_meja']); ?>
                </div>

                <div class="queue-badge">

                    <?php if ($row['metode_bayar'] === 'QRIS'): ?>
                        💳 QRIS
                    <?php else: ?>
                        💵 CASH
                    <?php endif; ?>

                </div>

                <div class="queue-badge">

                    <?php if ($row['status_pesanan'] === 'Menunggu Konfirmasi'): ?>
                        🔔 Menunggu Verifikasi
                    <?php else: ?>
                        📦 Pending
                    <?php endif; ?>

                </div>

            </div>

            <div class="queue-total">
                💰 Rp <?= number_format($row['total_bayar'], 0, ',', '.'); ?>
            </div>

            <div class="aksi-group">

                <form method="POST" style="flex:1; margin:0;">
                    <input type="hidden" name="aksi" value="selesai">
                    <input type="hidden" name="id_pesanan" value="<?= (int) $row['id_pesanan']; ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                    <button type="button" class="btn-action btn-selesai btn-selesai-order">

                        ✅ Selesaikan

                    </button>
                </form>

                <form method="POST" style="flex:1; margin:0;">
                    <input type="hidden" name="aksi" value="tolak">
                    <input type="hidden" name="id_pesanan" value="<?= (int) $row['id_pesanan']; ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

                    <button type="button" class="btn-action btn-tolak btn-tolak-order">

                        ❌ Tolak

                    </button>
                </form>

            </div>

        </div>

        <?php
    endwhile;

else:
    ?>

    <div class="empty-queue">
        <h3>Belum Ada Pesanan ☕</h3>
        <p>Antrean masih kosong 😌🧋</p>
    </div>

<?php endif; ?>