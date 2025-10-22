<?php
include 'header.php';
include 'koneksi.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_POST['upload'])) {
    
    $drf = isset($_POST['drf']) ? (int)$_POST['drf'] : 0;
    $type = isset($_POST['type']) ? mysqli_real_escape_string($link, $_POST['type']) : '';
    $nodoc = isset($_POST['nodoc']) ? mysqli_real_escape_string($link, $_POST['nodoc']) : '';
    $title = isset($_POST['title']) ? mysqli_real_escape_string($link, $_POST['title']) : '';

    if ($drf == 0) {
        die("Error: DRF tidak valid.");
    }

    $nma_file = '';
    
    // --- Proses Upload File ---
    if (isset($_FILES['baru']) && $_FILES['baru']['error'] == UPLOAD_ERR_OK) {
        $tmp_file = $_FILES['baru']['tmp_name'];
        $nma_file = basename($_FILES['baru']['name']);
        $target_dir = rtrim($type, '/') . '/';
        
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (!move_uploaded_file($tmp_file, $target_dir . $nma_file)) {
             echo "ERROR - File Dokumen gagal di-upload.<br>";
        }
    }

    // Update status menjadi Review setelah upload file baru
    $new_status = 'Review';

    // --- Update Database ---
    $query_update_docu = "UPDATE docu SET status='$new_status'";
    if (!empty($nma_file)) {
        $query_update_docu .= ", file='" . mysqli_real_escape_string($link, $nma_file) . "'";
    }
    $query_update_docu .= " WHERE no_drf=$drf";
    mysqli_query($link, $query_update_docu);

    // Reset status semua approver
    $query_reset_rev = "UPDATE rev_doc SET status='Review', reason='', tgl_approve='-' WHERE id_doc=$drf";
    mysqli_query($link, $query_reset_rev);

    // Ambil semua approver untuk notifikasi
    $sql_approvers = "SELECT DISTINCT u.name, u.email 
                      FROM rev_doc rd
                      JOIN users u ON u.username = rd.nrp 
                      WHERE rd.id_doc = $drf";
    
    $res_approvers = mysqli_query($link, $sql_approvers);
    
    $approvers_to_notify = [];
    if ($res_approvers && mysqli_num_rows($res_approvers) > 0) {
        while ($data = mysqli_fetch_assoc($res_approvers)) {
            $approvers_to_notify[] = $data;
        }
    }

    // Kirim Email Notifikasi (jika diperlukan)
    if (count($approvers_to_notify) > 0) {
        require 'PHPMailer/PHPMailerAutoload.php';
        $mail = new PHPMailer();
        
        try {
            $mail->IsSMTP();
            include 'smtp.php';
            
            $mail->From = 'dc_admin@ssi.sharp-world.com';
            $mail->FromName = 'Admin Document Online System';

            foreach ($approvers_to_notify as $recipient) {
                $mail->addAddress($recipient['email'], $recipient['name']);
            }
            
            $mail->IsHTML(true);
            $mail->Subject = "Re-Review Required for Revised Document: " . $nodoc;
            $mail->Body = "Attention Mr./Mrs. Reviewer,<br/><br/>" .
                          "The following document has been revised by the originator and requires your re-review:".
                          "<br/><br/><b>No. Document:</b> " . htmlspecialchars($nodoc) .
                          "<br/><b>Title:</b> " . htmlspecialchars($title) .
                          "<br/><br/>Your previous decision has been reset. Please review the new version of the document.".
                          "<br/>Please login into <a href='http://192.168.132.15/document'>Document Online System</a>. Thank You.";

            if ($mail->send()) {
                echo "<script>
                        alert('File changed successfully and notification sent to " . count($approvers_to_notify) . " approver(s)! Status changed to Review.');
                        document.location='my_doc.php?tipe=" . urlencode($type) . "&submit=Show';
                      </script>";
            } else {
                echo "<script>
                        alert('File changed successfully, but email failed to send! Error: ".addslashes($mail->ErrorInfo)."');
                        document.location='my_doc.php?tipe=" . urlencode($type) . "&submit=Show';
                      </script>";
            }
        } catch (Exception $e) {
            echo "<script>
                    alert('File changed, but an error occurred during email sending. Error: ".$e->getMessage()."');
                    document.location='my_doc.php?tipe=" . urlencode($type) . "&submit=Show';
                  </script>";
        }
    } else {
        echo "<script>
                alert('File changed successfully. Status changed to Review.');
                document.location='my_doc.php?tipe=" . urlencode($type) . "&submit=Show';
              </script>";
    }
    
} else {
    // Jika halaman diakses tanpa POST, tampilkan form
    $drf = isset($_GET['drf']) ? (int)$_GET['drf'] : 0;
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    
    if ($drf == 0) {
        die("Error: DRF tidak valid.");
    }
    
    // Ambil data dokumen
    $sql_doc = "SELECT * FROM docu WHERE no_drf='$drf'";
    $res_doc = mysqli_query($link, $sql_doc);
    $doc_data = mysqli_fetch_assoc($res_doc);
    ?>

    <div class="row">
        <div class="col-xs-1"></div>
        <div class="col-xs-7 well well-lg">
            <h2>Change Document</h2>
            <div class="alert alert-info">
                <strong>Document:</strong> <?php echo htmlspecialchars($doc_data['no_doc']); ?> - <?php echo htmlspecialchars($doc_data['title']); ?>
            </div>
            
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="drf" value="<?php echo $drf; ?>">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                <input type="hidden" name="nodoc" value="<?php echo htmlspecialchars($doc_data['no_doc']); ?>">
                <input type="hidden" name="title" value="<?php echo htmlspecialchars($doc_data['title']); ?>">
                
                <div class="form-group">
                    <label>File Document (.pdf/.xlsx):</label>
                    <input type="file" name="baru" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>File Master (.docx/.xlsx):</label>
                    <input type="file" name="masterbaru" class="form-control">
                </div>
                
                <div class="form-group">
                    <input type="submit" name="upload" value="Update Document" class="btn btn-primary">
                    <a href="edit_doc.php?drf=<?php echo $drf; ?>" class="btn btn-default">Kembali ke Edit</a>
                </div>
            </form>
        </div>
    </div>
    <?php
}
?>