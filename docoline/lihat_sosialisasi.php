<?php
// lihat_sosialisasi.php
// Perbaikan: Simplified session check & login protection

// START: Session check
if (session_status() === PHP_SESSION_NONE) session_start();

// Debug session (hapus setelah testing)
// var_dump($_SESSION); // uncomment untuk debug

// Flexible session check - sesuaikan dengan sistem Anda
$isLoggedIn = false;
$user_state = '';
$user_nrp = '';

// Cek berbagai kemungkinan nama session variable
if ((isset($_SESSION['state']) && !empty($_SESSION['state'])) || 
    (isset($_SESSION['login']) && $_SESSION['login'] === true) ||
    (isset($_SESSION['user']) && !empty($_SESSION['user'])) ||
    (isset($_SESSION['username']) && !empty($_SESSION['username'])) ||
    (isset($_SESSION['nrp']) && !empty($_SESSION['nrp']))) {
    
    $isLoggedIn = true;
    $user_state = $_SESSION['state'] ?? ($_SESSION['role'] ?? 'User');
    $user_nrp = $_SESSION['nrp'] ?? ($_SESSION['user'] ?? ($_SESSION['username'] ?? 'Unknown'));
}

// Jika masih belum login, coba include header.php dulu (mungkin ada auto-login)
if (!$isLoggedIn) {
    if (file_exists('header.php')) include 'header.php';
    
    // Cek lagi setelah include header
    if ((isset($_SESSION['state']) && !empty($_SESSION['state'])) || 
        (isset($_SESSION['nrp']) && !empty($_SESSION['nrp']))) {
        $isLoggedIn = true;
        $user_state = $_SESSION['state'] ?? 'User';
        $user_nrp = $_SESSION['nrp'] ?? 'Unknown';
    }
} else {
    // Jika sudah login, tetap include header.php untuk background
    if (file_exists('header.php')) include 'header.php';
}

// Jika tetap belum login, redirect
if (!$isLoggedIn) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: index_login.php'); // atau login.php
    exit;
}

// Include koneksi setelah memastikan user sudah login
if (!$isLoggedIn && file_exists('header.php')) include 'header.php';
if (!file_exists('koneksi.php')) { die("koneksi.php tidak ditemukan."); }
include 'koneksi.php';

error_reporting(E_ALL ^ (E_NOTICE | E_WARNING | E_DEPRECATED));

$uploadDir = __DIR__ . '/sosialisasi/';
$webUploadDir = 'sosialisasi/';

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    if (function_exists('random_bytes')) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    else $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

if (empty($_GET['drf'])) {
    echo "<div class='alert alert-danger'>Parameter drf tidak ditemukan.</div>";
    exit;
}
$drf = mysqli_real_escape_string($link, $_GET['drf']);

// handle delete (POST)
$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $post_token = $_POST['csrf_token'] ?? '';
    if ($post_token !== $_SESSION['csrf_token']) {
        $alert = "<div class='alert alert-danger'>Token invalid. Aksi dibatalkan.</div>";
    } else {
        // ambil record
        $q = mysqli_query($link, "SELECT sos_file, sos_uploaded_by FROM docu WHERE no_drf = '" . mysqli_real_escape_string($link, $drf) . "' LIMIT 1");
        if ($q && mysqli_num_rows($q) > 0) {
            $row = mysqli_fetch_assoc($q);
            $file = $row['sos_file'];
            $uploaded_by = $row['sos_uploaded_by'];

            $allowed_to_delete = false;
            if ($user_state === 'Admin') $allowed_to_delete = true;
            if (!empty($user_nrp) && $user_nrp === $uploaded_by) $allowed_to_delete = true;

            if (!$allowed_to_delete) {
                $alert = "<div class='alert alert-danger'>Anda tidak mempunyai izin untuk menghapus file ini.</div>";
            } else {
                $fullpath = $uploadDir . $file;
                $deleted = false;
                if (file_exists($fullpath)) {
                    $deleted = @unlink($fullpath);
                } else {
                    $deleted = true;
                }

                if ($deleted) {
                    // nullify fields in docu
                    $delQ = mysqli_query($link, "UPDATE docu SET sos_file = NULL, sos_uploaded_by = NULL, sos_upload_date = NULL, sos_notes = NULL WHERE no_drf = '" . mysqli_real_escape_string($link, $drf) . "' LIMIT 1");
                    if ($delQ) {
                        $alert = "<div class='alert alert-success'>File dan record sosialisasi berhasil dihapus (fields di docu di-reset).</div>";
                    } else {
                        $alert = "<div class='alert alert-warning'>File dihapus, tapi gagal update database.</div>";
                    }
                } else {
                    $alert = "<div class='alert alert-danger'>Gagal menghapus file dari server.</div>";
                }
            }
        } else {
            $alert = "<div class='alert alert-danger'>Record tidak ditemukan.</div>";
        }
    }
}

// ambil data docu
$q = mysqli_query($link, "SELECT no_doc, sos_file, sos_uploaded_by, sos_upload_date, sos_notes FROM docu WHERE no_drf = '" . mysqli_real_escape_string($link, $drf) . "' LIMIT 1");
if (!$q || mysqli_num_rows($q) === 0) {
    echo "<div class='alert alert-danger'>Dokumen tidak ditemukan.</div>";
    exit;
}
$row = mysqli_fetch_assoc($q);
$has_sos = !empty($row['sos_file']);
?>

<br><br><br>
<div class="container" style="padding-top:20px;">
    <h3>Bukti Sosialisasi untuk DRF: <strong><?php echo htmlspecialchars($drf); ?></strong></h3>

    <?php if ($alert) echo $alert; ?>

    <p>
        <button class="btn btn-primary" onclick="goBack()">
            <span class="glyphicon glyphicon-arrow-left"></span>&nbsp;Back
        </button>
    </p>

    <script>
    function goBack() {
        if (document.referrer !== "") {
            window.history.back();
        } else {
            // fallback kalau user buka langsung
            window.location.href = "detail.php?drf=<?php echo urlencode($drf); ?>";
        }
    }
    </script>

    <?php if ($has_sos): 
        $fileUrl = $webUploadDir . rawurlencode($row['sos_file']);
    ?>
        <table class="table table-hover table-bordered">
            <tr><th>No. Document</th><td><?php echo htmlspecialchars($row['no_doc']); ?></td></tr>
            <tr><th>Uploaded by</th><td><?php echo htmlspecialchars($row['sos_uploaded_by']); ?></td></tr>
            <tr><th>Upload date</th><td><?php echo htmlspecialchars($row['sos_upload_date']); ?></td></tr>
            <tr><th>File</th>
                <td>
                    <a target="_blank" href="<?php echo $fileUrl;?>">
                        <span class="glyphicon glyphicon-file"></span> <?php echo htmlspecialchars($row['sos_file']); ?>
                    </a>
                </td>
            </tr>
            <tr><th>Notes</th><td><?php echo nl2br(htmlspecialchars($row['sos_notes'])); ?></td></tr>
            <tr><th>Actions</th>
                <td>
                    <a class="btn btn-xs btn-info" target="_blank" href="<?php echo $fileUrl;?>" title="View"><span class="glyphicon glyphicon-eye-open"></span></a>
                    <a class="btn btn-xs btn-success" href="<?php echo $fileUrl;?>" download="<?php echo htmlspecialchars($row['sos_file']); ?>" title="Download"><span class="glyphicon glyphicon-download"></span></a>

                    <?php
                    // Cek permission untuk delete (Admin atau user yang upload)
                    if ($user_state === 'Admin' || (!empty($user_nrp) && $user_nrp === $row['sos_uploaded_by'])):
                    ?>
                        <form method="post" style="display:inline-block;" onsubmit="return confirm('Hapus bukti ini?');">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                            <input type="hidden" name="action" value="delete">
                            <button class="btn btn-xs btn-danger" type="submit" title="Hapus">
                                <span class="glyphicon glyphicon-trash"></span>
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    <?php else: ?>
        <div class="alert alert-info">Belum ada bukti sosialisasi untuk DRF ini.</div>
    <?php endif; ?>

    <hr />
    <h4>Replace bukti sosialisasi</h4>
    
    <?php if ($isLoggedIn): ?>
        <form action="upload_sosialisasi.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="drf" value="<?php echo htmlspecialchars($drf); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <div class="form-group">
                <label>Pilih file (pdf/jpg/png). Max 10MB.</label>
                <input type="file" name="sos_file" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Catatan / Keterangan (optional)</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>
            <button class="btn btn-primary" type="submit">Upload Sosialisasi</button>
        </form>
    <?php else: ?>
        <div class="alert alert-warning">
            <strong>Info:</strong> Anda harus login terlebih dahulu untuk dapat mengupload bukti sosialisasi.
            <br><br>
            <a href="index_login.php" class="btn btn-primary">Login Sekarang</a>
        </div>
    <?php endif; ?>

</div>

<script src="bootstrap/js/jquery.min.js"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
</body>
</html>