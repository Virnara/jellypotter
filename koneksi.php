<?php
// Sesuaikan menjadi jelly_poter (satu 't' sesuai gambar)
$conn = mysqli_connect("localhost", "root", "", "jelly_potter");

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>