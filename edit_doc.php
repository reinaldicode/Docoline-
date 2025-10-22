<?php
include('header.php');
include 'koneksi.php';

// Pastikan $drf didefinisikan
$drf = isset($_GET['drf']) ? mysqli_real_escape_string($link, $_GET['drf']) : (isset($_POST['drf']) ? mysqli_real_escape_string($link, $_POST['drf']) : '');

if (empty($drf)) {
    die("Error: No DRF specified");
}

// ========== LOAD DOCUMENT TYPES FROM JSON ==========
$jsonFile = __DIR__ . '/data/document_types.json';
$docTypes = [];
if (file_exists($jsonFile)) {
    $tmp = json_decode(file_get_contents($jsonFile), true);
    if (is_array($tmp)) $docTypes = $tmp;
}

// ========== HANDLE UPDATE STATUS TO REVIEW ==========
if (isset($_POST['update_status']) && $_POST['update_status'] == '1' && isset($_POST['action']) && $_POST['action'] == 'finish') {
    $drf_update = mysqli_real_escape_string($link, $_POST['drf']);
    
    // Update status dokumen ke Review
    $update_status = "UPDATE docu SET status='Review' WHERE no_drf='$drf_update'";
    $result_update = mysqli_query($link, $update_status);
    
    // PERBAIKAN CRITICAL: Reset status semua approver kembali ke Review
    $reset_approvers = "UPDATE rev_doc SET status='Review', reason='', tgl_approve='-' WHERE id_doc='$drf_update'";
    $result_reset = mysqli_query($link, $reset_approvers);
    
    if ($result_update && $result_reset) {
        // Get document info for email notification
        $get_doc = mysqli_query($link, "SELECT no_doc, title, doc_type FROM docu WHERE no_drf='$drf_update'");
        $doc_data = mysqli_fetch_array($get_doc);
        $redirect_type = isset($doc_data['doc_type']) ? $doc_data['doc_type'] : '';
        
        // Get all approvers for notification
        $sql_approvers = "SELECT DISTINCT u.name, u.email 
                          FROM rev_doc rd
                          JOIN users u ON u.username = rd.nrp 
                          WHERE rd.id_doc = '$drf_update'";
        
        $res_approvers = mysqli_query($link, $sql_approvers);
        $approvers_to_notify = [];
        
        if ($res_approvers && mysqli_num_rows($res_approvers) > 0) {
            while ($data = mysqli_fetch_assoc($res_approvers)) {
                $approvers_to_notify[] = $data;
            }
        }
        
        // Send email notification
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
                $mail->Subject = "Document Updated - Re-Review Required: " . $doc_data['no_doc'];
                $mail->Body = "Attention Mr./Mrs. Reviewer,<br/><br/>" .
                              "The following document has been updated by the originator and requires your re-review:" .
                              "<br/><br/><b>No. Document:</b> " . htmlspecialchars($doc_data['no_doc']) .
                              "<br/><b>Title:</b> " . htmlspecialchars($doc_data['title']) .
                              "<br/><br/>Your previous decision has been reset. Please review the updated document." .
                              "<br/>Please login into <a href='http://192.168.132.15/document'>Document Online System</a>. Thank You.";

                $mail->send();
            } catch (Exception $e) {
                // Silent fail - don't block the process
            }
        }
        
        ?>
        <script language='javascript'>
            alert('Status berhasil diupdate ke Review dan approver telah diberitahu');
            document.location='my_doc.php?tipe=<?php echo urlencode($redirect_type); ?>&submit=Show';
        </script>
        <?php
        exit;
    } else {
        ?>
        <script language='javascript'>
            alert('Gagal mengupdate status: <?php echo mysqli_error($link); ?>');
        </script>
        <?php
    }
}

// ========== HANDLE FORM SUBMISSION ==========
if (isset($_POST['submit']) && $_POST['submit'] == 'save') {
    $drf_post = mysqli_real_escape_string($link, $_POST['drf']);

    // Escape all inputs
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

    $iso = 0;
    if (isset($_POST['iso']) && $_POST['iso'] !== '' && $_POST['iso'] !== null) {
        $iso = intval($_POST['iso']);
    }

    $hist = mysqli_real_escape_string($link, $_POST['hist']);

    // Update query without changing status
    $sql = "UPDATE docu SET 
            no_doc='$nodoc', 
            no_rev='$norev', 
            rev_to='$revto', 
            category='$cat', 
            doc_type='$type', 
            section='$section', 
            device='$device',
            process='$process',
            title='$title',
            descript='$desc', 
            iso=$iso, 
            history='$hist'
            WHERE no_drf='$drf_post'";

    $res = mysqli_query($link, $sql);

    if (!$res) {
        die("Update failed: " . mysqli_error($link));
    }

    // Delete old related documents
    mysqli_query($link, "DELETE FROM rel_doc WHERE no_drf='$drf_post'");

    // Insert new related documents
    if (isset($_POST["rel"]) && is_array($_POST["rel"])) {
        foreach($_POST["rel"] as $no_doc) {
            $no_doc = trim($no_doc);
            
            if (!empty($no_doc)) {
                $no_doc_escaped = mysqli_real_escape_string($link, $no_doc);
                
                $q = mysqli_query($link, "INSERT INTO rel_doc(no_drf, no_doc) VALUES ('$drf_post', '$no_doc_escaped')"); 
                
                if (!$q) {
                    echo "Error inserting related doc: " . mysqli_error($link) . "<br>";
                }
            }
        }
    }

    $showModal = true;
    $drf = $drf_post;
}

$sql="select * from docu where no_drf='$drf'";
$res=mysqli_query($link, $sql);

while($data = mysqli_fetch_array($res)) 
{
?>

<div class="row">
<div class="col-xs-1"></div>
<div class="col-xs-7 well well-lg">
 <h2>Edit Document</h2>

<form action="" method="POST" enctype="multipart/form-data" id="editForm">
    <table class="table">
        
        <input type="hidden" name="drf" value="<?php echo htmlspecialchars($drf); ?>">
        
        <tr cellpadding="50px">
            <td>No. Document &nbsp;&nbsp;</td>
            <td>:&nbsp; &nbsp; &nbsp;</td>
            <td><input type="text" class="form-control" name="nodoc" value="<?php echo htmlspecialchars($data['no_doc']); ?>"></td>
        </tr>
        <tr cellpadding="50px">
            <td>No. Revision &nbsp;&nbsp;</td>
            <td>:&nbsp; &nbsp; &nbsp;</td>
            <td><input type="text" class="form-control" name="norev" value="<?php echo htmlspecialchars($data['no_rev']); ?>"></td>
        </tr>

        <tr>
            <td>Review To</td>
            <td>:</td>
            <td>
                <select name="revto" class="form-control">
                    <option value="-"> --- Select --- </option>
                    <option value="Issue" <?php if ($data['rev_to']=="Issue") {echo 'selected';} ?> > Issue </option>
                    <option value="Revision" <?php if ($data['rev_to']=="Revision") {echo 'selected';} ?> > Revision </option>
                    <option value="Cancel" <?php if ($data['rev_to']=="Cancel") {echo 'selected';} ?> > Cancel </option>
                </select>
            </td>
        </tr>

        <tr>
            <td>Category</td>
            <td>:</td>
            <td>
                <select name="cat" class="form-control">
                    <option value="-"> --- Select --- </option>
                    <option value="Internal" <?php if ($data['category']=="Internal") {echo 'selected';} ?> > Internal </option>
                    <option value="External" <?php if ($data['category']=="External") {echo 'selected';} ?> > External </option>
                </select>
            </td>
        </tr>

        <tr>
            <td>Document Type</td>
            <td>:</td>
            <td>
                <?php
                $current_type = $data['doc_type'];
                
                if (!empty($docTypes)) {
                    echo '<select name="type" class="form-control" id="typeSelect">';
                    echo '<option value="-"> --- Select Type --- </option>';
                    
                    $flat = [];
                    $is_assoc = array_keys($docTypes) !== range(0, count($docTypes)-1);
                    
                    if ($is_assoc) {
                        foreach ($docTypes as $catk => $items) {
                            if (is_array($items)) {
                                foreach ($items as $it) {
                                    if (is_string($it)) $flat[] = $it;
                                    elseif (is_array($it) && isset($it['name'])) $flat[] = $it['name'];
                                }
                            }
                        }
                    } else {
                        foreach ($docTypes as $it) {
                            if (is_string($it)) $flat[] = $it;
                            elseif (is_array($it) && isset($it['name'])) $flat[] = $it['name'];
                        }
                    }
                    
                    $seen = [];
                    foreach ($flat as $dt) {
                        if ($dt === '' || in_array($dt, $seen)) continue;
                        $seen[] = $dt;
                        
                        $selected = '';
                        if (strcasecmp($current_type, $dt) === 0) {
                            $selected = 'selected';
                        }
                        
                        echo '<option value="'.htmlspecialchars($dt).'" '.$selected.'>'.htmlspecialchars($dt).'</option>';
                    }
                    
                    echo '</select>';
                } else {
                    ?>
                    <select name="type" class="form-control" id="typeSelect">
                        <option value="-"> --- Select Type --- </option>
                        <option value="Form" <?php if ($data['doc_type']=="Form") {echo 'selected';} ?> > Form </option>
                        <option value="Procedure" <?php if ($data['doc_type']=="Procedure") {echo 'selected';} ?> > Procedure </option>
                        <option value="WI" <?php if ($data['doc_type']=="WI") {echo 'selected';} ?> > WI </option>
                        <option value="Monitor Sample" <?php if ($data['doc_type']=="Monitor Sample") {echo 'selected';} ?> > Monitor Sample </option>
                        <option value="MSDS" <?php if ($data['doc_type']=="MSDS") {echo 'selected';} ?> > MSDS </option>
                        <option value="Material Spec" <?php if ($data['doc_type']=="Material Spec") {echo 'selected';} ?> > Material Spec </option>
                        <option value="ROHS" <?php if ($data['doc_type']=="ROHS") {echo 'selected';} ?> > ROHS </option>
                    </select>
                    <?php
                }
                ?>
            </td>
        </tr>

        <tr>
            <td>Section</td>
            <td>:</td>
            <td>
                <?php 
            $sect="select * from section order by id_section";
            $sql_sect=mysqli_query($link, $sect);
        ?>
             <select id="section" name="section" class="form-control" >
                            <option value="-"> --- Select Section --- </option>
                            <?php while($data_sec = mysqli_fetch_array( $sql_sect )) 
                            { ?>
                            <option value="<?php echo htmlspecialchars($data_sec['id_section']); ?>" <?php if ($data['section']==$data_sec['id_section']) {echo 'selected';} ?>> <?php echo htmlspecialchars($data_sec['sect_name']); ?> </option>
                            <?php } ?>
                            </option>
                        </select>
            </td>
        </tr>

        <tr>
            <td>Device</td>
            <td>:</td>
            <td>
                <?php 
            $dev="select * from device where status='Aktif' order by group_dev";
            $sql_dev=mysqli_query($link, $dev);
        ?>
             <select id="device" name="device" class="form-control" >
                            <option value="-"> --- Select Device --- </option>
                            <?php while($data_dev = mysqli_fetch_array( $sql_dev )) 
                            { ?>
                            <option value="<?php echo htmlspecialchars($data_dev['name']); ?>" <?php if ($data['device']==$data_dev['name']) {echo 'selected';} ?> > <?php echo htmlspecialchars($data_dev['name']); ?> </option>
                            <?php } ?>
                            </option>
                        </select>
            </td>
        </tr>

        <tr>
            <td>Process</td>
            <td>:</td>
            <td>
                <?php 
            $sect="select * from process order by  proc_name";
            $sql_sect=mysqli_query($link, $sect);
        ?>
             <select id="process" name="process" class="form-control">
                            <option value="-"> --- Select Process --- </option>
                            <?php while($data_sec = mysqli_fetch_array( $sql_sect )) 
                            { ?>
                            <option value="<?php echo htmlspecialchars($data_sec['proc_name']); ?>" <?php if ($data['process']==$data_sec['proc_name']) {echo 'selected';} ?> > <?php echo htmlspecialchars($data_sec['proc_name']); ?> </option>
                            <?php } ?>
                            </option>
                        </select>
            </td>
        </tr>

        <tr>
            <td>Doc. Title</td>
            <td>:</td>
            <td><input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($data['title']); ?>"></td>
        </tr>

        <tr>
            <td>Doc. Description</td>
            <td>:</td>
            <td >
            <textarea  class="form-control" name="desc" cols="40" rows="10" wrap="physical" ><?php echo htmlspecialchars($data['descript']); ?></textarea>
            </td>
        </tr>

        <tr>
            <td>Requirement Document</td>
            <td>:</td>
            <td >
              <table>
             <tr><input type="radio" aria-label="ISO 9001" name="iso" value="1" <?php if (isset($data['iso']) && $data['iso']==1){echo "checked";}?> >&nbsp; ISO 9001 <br /></tr>
                <tr><input type="radio" aria-label="ISO 14001" name="iso" value="2" <?php if (isset($data['iso']) && $data['iso']==2){echo "checked";}?> >&nbsp; ISO 14001 <br /></tr>
                <tr><input type="radio" aria-label="OHSAS" name="iso" value="3" <?php if (isset($data['iso']) && $data['iso']==3){echo "checked";}?>  >&nbsp; OHSAS <br /></tr>
                <tr><input type="radio" aria-label="None" name="iso" value="0" <?php if (!isset($data['iso']) || $data['iso']==0 || empty($data['iso'])){echo "checked";}?>  >&nbsp; None <br /></tr>
              </table>
            </td>
        </tr>
        <tr>
            <td>Related/Addopted Document</td>
            <td>:</td>
            <td>
            <input type="text" class="form-control" name="rel[]" placeholder="Related Doc 1">
            <input type="text" class="form-control" name="rel[]" placeholder="Related Doc 2">
            <input type="text" class="form-control" name="rel[]" placeholder="Related Doc 3">
            <input type="text" class="form-control" name="rel[]" placeholder="Related Doc 4">
            <input type="text" class="form-control" name="rel[]" placeholder="Related Doc 5">
            </td>
        </tr>

        <tr>
            <td>Revision reason/history</td>
            <td>:</td>
            <td >
            <textarea  class="form-control" name="hist" cols="40" rows="10" wrap="physical" ><?php echo htmlspecialchars($data['history']); ?></textarea>
            </td>
        </tr>

        <tr>
            <td></td>
            <td></td>
            <td>
                <button type="submit" name="submit" value="save" class="btn btn-success">Save Changes</button>
            </td>
        </tr>
    </table>
</form>

</div>
</div>

<!-- Modal Pilihan Aksi -->
<div class="modal fade" id="actionModal" tabindex="-1" role="dialog" aria-labelledby="actionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="actionModalLabel">Pilih Aksi Selanjutnya</h4>
            </div>
            <div class="modal-body">
                <p>Edit data berhasil disimpan. Apa yang ingin Anda lakukan selanjutnya?</p>
                <div class="alert alert-info">
                    <strong>Status saat ini:</strong> <?php echo htmlspecialchars($data['status']); ?>
                </div>
                <div class="alert alert-warning">
                    <strong>Catatan:</strong> Jika Anda memilih "Selesai", dokumen akan dikembalikan ke status Review dan semua approver akan diberitahu untuk melakukan review ulang.
                </div>
            </div>
            <div class="modal-footer">
                <form method="GET" action="ganti_doc.php" style="display:inline;">
                    <input type="hidden" name="drf" value="<?php echo htmlspecialchars($drf); ?>">
                    <input type="hidden" name="type" id="changeDocType" value="<?php echo htmlspecialchars($data['doc_type']); ?>">
                    <button type="submit" class="btn btn-warning">
                        <span class="glyphicon glyphicon-upload"></span> Change Document
                    </button>
                </form>
                
                <form method="POST" action="" style="display:inline;">
                    <input type="hidden" name="drf" value="<?php echo htmlspecialchars($drf); ?>">
                    <input type="hidden" name="action" value="finish">
                    <input type="hidden" name="update_status" value="1">
                    <button type="submit" class="btn btn-success">
                        <span class="glyphicon glyphicon-ok"></span> Selesai (Kembalikan ke Review)
                    </button>
                </form>
                
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    <?php if (isset($showModal) && $showModal): ?>
    $('#actionModal').modal('show');
    <?php endif; ?>
    
    $('#typeSelect').change(function() {
        $('#changeDocType').val($(this).val());
    });
});
</script>

<?php 
}
?>