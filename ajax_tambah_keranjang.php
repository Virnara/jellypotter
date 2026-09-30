<?php
session_start();
include 'koneksi.php';

/*
|--------------------------------------------------------------------------
| FORCE CLEAN JSON RESPONSE
|--------------------------------------------------------------------------
*/
if (ob_get_length()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');

/*
|--------------------------------------------------------------------------
| SESSION INIT
|--------------------------------------------------------------------------
*/
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['keranjang']) || !is_array($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

/*
|--------------------------------------------------------------------------
| ONLY POST
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Request tidak valid'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| CSRF VALIDATION
|--------------------------------------------------------------------------
*/
if (
    !isset($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    echo json_encode([
        'success' => false,
        'message' => 'Token expired'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| PRODUCT ID
|--------------------------------------------------------------------------
*/
$id_produk = intval($_POST['id_produk'] ?? 0);

if ($id_produk <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Produk invalid'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| GET PRODUCT
|--------------------------------------------------------------------------
*/
$stmt = mysqli_prepare(
    $conn,
    "SELECT id_produk, stok
     FROM produk
     WHERE id_produk = ?
     LIMIT 1"
);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Query error'
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "i", $id_produk);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$produk = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$produk) {
    echo json_encode([
        'success' => false,
        'message' => 'Produk tidak ditemukan'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| STOCK CHECK
|--------------------------------------------------------------------------
*/
$stok = intval($produk['stok']);
$qty_sekarang = intval($_SESSION['keranjang'][$id_produk] ?? 0);

if ($qty_sekarang >= $stok) {
    echo json_encode([
        'success' => false,
        'message' => 'Stok habis'
    ]);
    exit;
}

/*
|--------------------------------------------------------------------------
| ADD TO CART
|--------------------------------------------------------------------------
*/
$_SESSION['keranjang'][$id_produk] = $qty_sekarang + 1;

/*
|--------------------------------------------------------------------------
| RESPONSE
|--------------------------------------------------------------------------
*/
echo json_encode([
    'success' => true,
    'cart_count' => array_sum($_SESSION['keranjang'])
]);

exit;