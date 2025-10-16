<?php
// upload_sosialisasi.php
session_start();
error_reporting(E_ALL ^ (E_NOTICE | E_WARNING | E_DEPRECATED));

// require koneksi
if (!file_exists('koneksi.php')) {
    die("koneksi.php tidak ditemukan.");
}
include 'koneksi.php';

// cek method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: wi_prod.php');
    exit;
}

// CSRF
$csrf_post = $_POST['csrf_token'] ?? '';
if ($csrf_post === '' || $csrf_post !== ($_SESSION['csrf_token'] ?? '')) {
    die("Invalid CSRF token");
}

// ambil input dan sanitasi awal
$drf_raw = $_POST['drf'] ?? '';
$drf = mysqli_real_escape_string($link, trim($drf_raw));
$notes = mysqli_real_escape_string($link, trim($_POST['notes'] ?? ''));

// Tentukan uploader secara robust (session prioritas)
$uploader = '';
if (!empty($_SESSION['nrp'])) {
    $uploader = $_SESSION['nrp'];
} elseif (!empty($_SESSION['username'])) {
    $uploader = $_SESSION['username'];
} elseif (!empty($_SESSION['user'])) {
    $uploader = $_SESSION['user'];
} elseif (!empty($_SESSION['name'])) {
    $uploader = $_SESSION['name'];
} elseif (!empty($_SESSION['email'])) {
    $uploader = $_SESSION['email'];
} elseif (!empty($nrp)) { // variabel global dari include lain
    $uploader = $nrp;
} elseif (!empty($email)) {
    $uploader = $email;
} else {
    // fallback akhir
    $uploader = 'system';
}
// sanitasi uploader untuk DB
$uploader_db = mysqli_real_escape_string($link, $uploader);

// validasi drf
if ($drf === '') {
    die("DRF tidak diberikan.");
}

// file check
if (!isset($_FILES['sos_file'])) {
    die("File tidak ditemukan.");
}

$file = $_FILES['sos_file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errCode = intval($file['error']);
    die("Upload error code: " . $errCode);
}

// konfigurasi validasi
$allowed_ext = ['pdf','jpg','jpeg','png'];
$max_size = 10 * 1024 * 1024; // 10MB

$originalName = basename($file['name']);
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if ($ext === '') {
    die("Tidak dapat menentukan ekstensi file.");
}
if (!in_array($ext, $allowed_ext)) {
    die("Ekstensi file tidak diizinkan. Hanya: " . implode(', ', $allowed_ext));
}
if ($file['size'] > $max_size) {
    die("File terlalu besar. Max 10MB.");
}

// cek MIME type menggunakan finfo (tambahan keamanan)
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$allowed_mimes = [
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
];
// beberapa server bisa memberikan variasi mime; periksa contains
$validMime = false;
if (isset($allowed_mimes[$ext])) {
    if (strpos($mime, explode('/', $allowed_mimes[$ext])[0]) !== false || $mime === $allowed_mimes[$ext]) {
        $validMime = true;
    }
}
if (!$validMime) {
    // jangan langsung tolak hanya berdasarkan finfo karena beberapa server/ jenis file bisa beda,
    // tapi beri peringatan / tolak untuk safety — di sini saya tolak.
    die("Tipe file tidak diizinkan (mime: $mime).");
}

// pastikan folder sosialisasi ada
$uploadDir = __DIR__ . '/sosialisasi/';
$webDir = 'sosialisasi/'; // path relatif web untuk link
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        die("Gagal membuat folder upload.");
    }
}

// buat nama file aman
$time = time();
$safeBase = preg_replace("/[^A-Za-z0-9_\-]/", '_', pathinfo($originalName, PATHINFO_FILENAME));
$finalName = 'sos_' . preg_replace('/_+/', '_', $safeBase) . '_' . $time . '.' . $ext;
$target = $uploadDir . $finalName;

// pindahkan file
if (!move_uploaded_file($file['tmp_name'], $target)) {
    die("Gagal memindahkan file ke folder upload.");
}

// OPTIONAL: jika sebelumnya sudah ada file pada kolom sosialisasi (sos_file) -> hapus file lama
$rowQ = mysqli_query($link, "SELECT sos_file FROM docu WHERE no_drf = '" . mysqli_real_escape_string($link, $drf) . "' LIMIT 1");
if ($rowQ && mysqli_num_rows($rowQ) > 0) {
    $r = mysqli_fetch_assoc($rowQ);
    $old = $r['sos_file'] ?? '';
    if (!empty($old)) {
        $oldPath = $uploadDir . $old;
        if (file_exists($oldPath)) {
            // hapus, tapi jangan menghentikan proses jika gagal menghapus
            @unlink($oldPath);
        }
    }
}

// update docu: kolom yang digunakan:
// sos_file (varchar), sos_uploaded_by (varchar), sos_upload_date (datetime), sos_notes (text)
$finalName_db = mysqli_real_escape_string($link, $finalName);
$notes_db = mysqli_real_escape_string($link, $notes);

$update = "UPDATE docu SET 
    sos_file = '$finalName_db',
    sos_uploaded_by = '$uploader_db',
    sos_upload_date = NOW(),
    sos_notes = '$notes_db'
    WHERE no_drf = '" . mysqli_real_escape_string($link, $drf) . "' LIMIT 1";

$r = mysqli_query($link, $update);
if (!$r) {
    // rollback file
    @unlink($target);
    die("Gagal menyimpan ke database: " . mysqli_error($link));
}

// sukses -> redirect kembali ke lihat_sosialisasi atau wi_prod
header("Location: lihat_sosialisasi.php?drf=" . urlencode($drf));
exit;
