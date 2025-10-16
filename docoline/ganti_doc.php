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
    
    // --- Proses Upload File (Sama seperti kode Anda, tidak ada perubahan) ---
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
    // (Anda bisa menambahkan logika upload file master di sini jika perlu)

    // =====================================================================
    // MODIFIKASI INTI: Ambil email HANYA untuk approver yang perlu review ulang
    // =====================================================================
    $sql_approvers = "SELECT DISTINCT u.name, u.email 
                      FROM rev_doc rd
                      JOIN users u ON u.username = rd.nrp 
                      WHERE rd.id_doc = $drf 
                      AND rd.status <> 'Approved'"; // <-- KUNCI: Bukan 'Pending', tapi 'selain Approved'
    
    $res_approvers = mysqli_query($link, $sql_approvers);
    
    $approvers_to_notify = [];
    if ($res_approvers && mysqli_num_rows($res_approvers) > 0) {
        while ($data = mysqli_fetch_assoc($res_approvers)) {
            $approvers_to_notify[] = $data;
        }
    }

    // --- Update Database ---
    // MODIFIKASI: Pastikan status dokumen SELALU diupdate, bahkan jika tidak ada file baru
    $query_update_docu = "UPDATE docu SET status='Review'";
    if (!empty($nma_file)) {
        // Hanya update nama file jika ada file baru yang diupload
        $query_update_docu .= ", file='" . mysqli_real_escape_string($link, $nma_file) . "'";
    }
    $query_update_docu .= " WHERE no_drf=$drf";
    mysqli_query($link, $query_update_docu);

    // =======================================================================
    // MODIFIKASI INTI: Reset status HANYA untuk approver yang belum setuju
    // =======================================================================
    $query_reset_rev = "UPDATE rev_doc SET status='Review', reason='', tgl_approve='-' 
                        WHERE id_doc=$drf AND status <> 'Approved'"; // <-- KUNCI: Reset semua yang BUKAN 'Approved'
    mysqli_query($link, $query_reset_rev);

    // --- Kirim Email Notifikasi yang Tepat Sasaran ---
    if (count($approvers_to_notify) > 0) {
        require 'PHPMailer/PHPMailerAutoload.php';
        $mail = new PHPMailer();
        
        try {
            $mail->IsSMTP();
            include 'smtp.php';
            
            $mail->From = 'dc_admin@ssi.sharp-world.com';
            $mail->FromName = 'Admin Document Online System';

            // Kirim email HANYA ke approver yang relevan
            foreach ($approvers_to_notify as $recipient) {
                $mail->addAddress($recipient['email'], $recipient['name']);
            }
            
            $mail->IsHTML(true);
            $mail->Subject = "Re-Review Required for Revised Document: " . $nodoc;
            $mail->Body = "Attention Mr./Mrs. Reviewer,<br/><br/>" .
                          "The following document has been revised by the originator and requires your re-review:".
                          "<br/><br/><b>No. Document:</b> " . htmlspecialchars($nodoc) .
                          "<br/><b>Title:</b> " . htmlspecialchars($title) .
                          "<br/><br/>Your previous decision (if any) has been reset. Please review the new version of the document.".
                          "<br/>Please login into <a href='http://192.168.132.15/document'>Document Online System</a>. Thank You.";

            if ($mail->send()) {
                echo "<script>
                        alert('File changed successfully and notification sent to " . count($approvers_to_notify) . " relevant approver(s)!');
                        document.location='my_doc.php';
                      </script>";
            } else {
                echo "<script>
                        alert('File changed successfully, but email failed to send! Error: ".addslashes($mail->ErrorInfo)."');
                        document.location='my_doc.php';
                      </script>";
            }
        } catch (Exception $e) {
            echo "<script>
                    alert('File changed, but an error occurred during email sending. Error: ".$e->getMessage()."');
                    document.location='my_doc.php';
                  </script>";
        }
    } else {
        // Kondisi ini jarang terjadi, tapi bisa jika dokumen di-pending oleh Admin
        // dan semua approver sudah setuju.
        echo "<script>
                alert('File changed successfully. No pending approvers to notify.');
                document.location='my_doc.php';
              </script>";
    }
    
} else {
    // Jika halaman diakses tanpa POST
    echo "<div style='text-align:center; margin-top: 50px;'>Invalid request. Please upload a file through the designated form.</div>";
}
?>