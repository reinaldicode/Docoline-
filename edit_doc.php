<?php
// Urutan include diperbaiki
include('header.php');
include('koneksi.php');
include_once('document_config.php'); // Tambahkan class loader

// Aktifkan error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Definisikan $drf
$drf = 0;
if (isset($_GET['drf']) && is_numeric($_GET['drf'])) {
    $drf = (int)$_GET['drf'];
} elseif (isset($_POST['drf']) && is_numeric($_POST['drf'])) {
    $drf = (int)$_POST['drf'];
}
if ($drf == 0) {
    die("Error: No valid DRF specified");
}

// Load document types dari class config
$docTypes = DocumentConfig::getFlattenedDocumentTypes();

// ============================================================================
// HANDLE UPDATE STATUS TO REVIEW (Tombol "Selesai")
// ============================================================================
if (isset($_POST['update_status']) && $_POST['update_status'] == '1' && isset($_POST['action']) && $_POST['action'] == 'finish') {
    if (!isset($_POST['drf']) || !is_numeric($_POST['drf'])) die("Error: Invalid DRF for update");
    $drf_update = (int)$_POST['drf'];
    if ($drf_update == 0) die("Error: DRF cannot be zero");

    // Cek data sebelum update
    $check_before = "SELECT id, nrp, status, tgl_approve, reason FROM rev_doc WHERE id_doc=$drf_update";
    $res_before = mysqli_query($link, $check_before);

    if ($res_before) {
        echo "\n<!-- DEBUG BEFORE UPDATE (DRF: $drf_update) -->\n";
        while ($row = mysqli_fetch_assoc($res_before)) {
            echo "<!-- ID: {$row['id']}, NRP: {$row['nrp']}, Status: {$row['status']}, Tgl: {$row['tgl_approve']} -->\n";
        }
    }

    // Update status dokumen ke Review
    $update_status = "UPDATE docu SET status='Review' WHERE no_drf=$drf_update";
    if (!mysqli_query($link, $update_status)) {
        die("Error updating docu: " . mysqli_error($link));
    }

    // Reset approver Pending menjadi Review
    $reset_approvers = "UPDATE rev_doc 
                        SET status='Review', reason='', tgl_approve='-' 
                        WHERE id_doc=$drf_update AND status='Pending'";
    $result_reset = mysqli_query($link, $reset_approvers);
    $affected_rows = 0;

    if (!$result_reset) {
        $get_pending = "SELECT id FROM rev_doc WHERE id_doc=$drf_update AND status='Pending'";
        $res_pending_ids = mysqli_query($link, $get_pending);

        $success_count = $error_count = 0;
        if ($res_pending_ids && mysqli_num_rows($res_pending_ids) > 0) {
            while ($row_id = mysqli_fetch_assoc($res_pending_ids)) {
                $id = $row_id['id'];
                $update_single = "UPDATE rev_doc 
                                  SET status='Review', reason='', tgl_approve='-' 
                                  WHERE id=$id";
                if (mysqli_query($link, $update_single)) $success_count++;
                else $error_count++;
            }
        }
        $affected_rows = $success_count;
        if ($error_count > 0) die("Error: Failed to update $error_count approver(s).");
    } else {
        $affected_rows = mysqli_affected_rows($link);
    }

    // Debug sesudah update
    $check_after = "SELECT id, nrp, status, tgl_approve, reason FROM rev_doc WHERE id_doc=$drf_update";
    $res_after = mysqli_query($link, $check_after);
    echo "\n<!-- DEBUG AFTER UPDATE -->\n";
    if ($res_after) {
        while ($row = mysqli_fetch_assoc($res_after)) {
            echo "<!-- ID: {$row['id']}, Status: {$row['status']} -->\n";
        }
    }

    // Get document info
    $get_doc = mysqli_query($link, "SELECT no_doc, title, doc_type FROM docu WHERE no_drf=$drf_update");
    if (!$get_doc) die("Error getting document: " . mysqli_error($link));
    $doc_data = mysqli_fetch_array($get_doc);
    if (!$doc_data) die("Error: Document not found for DRF $drf_update");
    $redirect_type = $doc_data['doc_type'];

    // Get approvers to notify
    $sql_pending_approvers = "SELECT DISTINCT u.name, u.email, u.username, rd.status
                              FROM rev_doc rd
                              JOIN users u ON u.username = rd.nrp 
                              WHERE rd.id_doc = $drf_update AND rd.status='Review'";
    $res_pending = mysqli_query($link, $sql_pending_approvers);
    $approvers_to_notify = [];
    if ($res_pending && mysqli_num_rows($res_pending) > 0) {
        while ($data = mysqli_fetch_assoc($res_pending)) $approvers_to_notify[] = $data;
    }

    // Cek yang sudah approve
    $sql_still_approved = "SELECT COUNT(*) as approved_count FROM rev_doc WHERE id_doc=$drf_update AND status='Approved'";
    $res_approved = mysqli_query($link, $sql_still_approved);
    $approved_count = mysqli_fetch_assoc($res_approved)['approved_count'] ?? 0;

    // Kirim email notifikasi
    if (count($approvers_to_notify) > 0 && file_exists('PHPMailer/PHPMailerAutoload.php')) {
        require 'PHPMailer/PHPMailerAutoload.php';
        $mail = new PHPMailer();
        try {
            $mail->IsSMTP();
            include 'smtp.php';
            $mail->From = 'dc_admin@ssi.sharp-world.com';
            $mail->FromName = 'Admin Document Online System';
            foreach ($approvers_to_notify as $recipient)
                $mail->addAddress($recipient['email'], $recipient['name']);
            $mail->IsHTML(true);
            $mail->Subject = "Document Updated - Re-Review Required: " . $doc_data['no_doc'];
            $mail->Body = "Attention Reviewer,<br/><br/>" .
                          "Document <b>{$doc_data['no_doc']}</b> titled <b>{$doc_data['title']}</b> requires re-review.<br/>" .
                          ($approved_count > 0 ? "<b>$approved_count approver(s)</b> already approved and do not need to reapprove." : "") .
                          "<br/><br/>Please login to <a href='http://192.168.132.15/document'>Document Online System</a>.";
            $mail->send();
        } catch (Exception $e) {
            error_log("Email send failed: " . $e->getMessage());
        }
    }

    $message = "✓ Status berhasil diupdate!\\nUpdated $affected_rows approver(s) to Review.";
    ?>
    <script>
        alert('<?php echo $message; ?>');
        document.location='my_doc.php?tipe=<?php echo urlencode($redirect_type); ?>&submit=Show';
    </script>
    <?php
    exit;
}

// ============================================================================
// HANDLE SAVE CHANGES
// ============================================================================
if (isset($_POST['submit']) && $_POST['submit'] == 'save') {
    if (!isset($_POST['drf']) || !is_numeric($_POST['drf'])) die("Error: Invalid DRF in form submission");
    $drf_post = (int)$_POST['drf'];

    $nodoc = mysqli_real_escape_string($link, $_POST['nodoc']);
    $norev = mysqli_real_escape_string($link, $_POST['norev']);
    $revto = mysqli_real_escape_string($link, $_POST['revto']);
    $cat = mysqli_real_escape_string($link, $_POST['cat']);
    $type = mysqli_real_escape_string($link, $_POST['type']);
    $section = mysqli_real_escape_string($link, $_POST['section']);
    $device = mysqli_real_escape_string($link, $_POST['device']);
    $process = mysqli_real_escape_string($link, $_POST['process']);
    $title = mysqli_real_escape_string($link, $_POST['title']);
    $desc = mysqli_real_escape_string($link, $_POST['desc']);
    $iso = isset($_POST['iso']) ? intval($_POST['iso']) : 0;
    $hist = mysqli_real_escape_string($link, $_POST['hist']);

    $sql = "UPDATE docu SET 
            no_doc='$nodoc', no_rev='$norev', rev_to='$revto', category='$cat', 
            doc_type='$type', section='$section', device='$device', process='$process',
            title='$title', descript='$desc', iso=$iso, history='$hist'
            WHERE no_drf=$drf_post";
    if (!mysqli_query($link, $sql)) die("Update failed: " . mysqli_error($link));

    mysqli_query($link, "DELETE FROM rel_doc WHERE no_drf=$drf_post");
    if (isset($_POST["rel"]) && is_array($_POST["rel"])) {
        foreach ($_POST["rel"] as $no_doc_rel) {
            $no_doc_rel = trim($no_doc_rel);
            if (!empty($no_doc_rel))
                mysqli_query($link, "INSERT INTO rel_doc(no_drf, no_doc) VALUES ($drf_post, '" . mysqli_real_escape_string($link, $no_doc_rel) . "')");
        }
    }

    $showModal = true;
    $drf = $drf_post;
}

// ============================================================================
// DISPLAY FORM
// ============================================================================
$sql = "SELECT * FROM docu WHERE no_drf=$drf";
$res = mysqli_query($link, $sql);
if (!$res) die("Error loading document: " . mysqli_error($link));

while ($data = mysqli_fetch_array($res)) {
    $sql_status = "SELECT 
                   SUM(CASE WHEN status='Approved' THEN 1 ELSE 0 END) as approved_count,
                   SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) as pending_count,
                   SUM(CASE WHEN status='Review' THEN 1 ELSE 0 END) as review_count,
                   COUNT(*) as total_count
                   FROM rev_doc WHERE id_doc=$drf";
    $res_status = mysqli_query($link, $sql_status);
    $status_data = mysqli_fetch_assoc($res_status);

    $sql_approvers = "SELECT rd.id, rd.nrp, u.name, rd.status, rd.tgl_approve, rd.reason
                      FROM rev_doc rd
                      LEFT JOIN users u ON u.username = rd.nrp
                      WHERE rd.id_doc=$drf ORDER BY rd.status DESC";
    $res_approvers = mysqli_query($link, $sql_approvers);
?>
